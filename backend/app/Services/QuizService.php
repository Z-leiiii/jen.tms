<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class QuizService
{
    /**
     * Generate 30 unique quiz questions using AI.
     */
    public function generateQuestions(string $subject, ?string $fileContent = null): array
    {
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            Log::error('Quiz generation failed: OPENAI_API_KEY is missing.');

            throw new Exception(
                'OpenAI API key is not configured on the server.'
            );
        }

        try {
            $prompt = $this->buildPrompt($subject, $fileContent);

            Log::info('Starting quiz generation.', [
                'subject' => $subject,
                'has_file_content' => !empty($fileContent),
            ]);

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => <<<TEXT
You are an expert educational content creator specializing in advanced assessment design.

Generate challenging, situational multiple-choice questions that test deep understanding, analysis, application, and critical thinking.

Questions must:
- Be factually accurate
- Be educationally appropriate
- Be unique
- Use realistic situations or scenarios
- Avoid simple memorization whenever possible
- Have exactly four plausible answer choices
- Have exactly one correct answer
- Include a clear explanation
- Follow the requested JSON format exactly

Return ONLY valid JSON.
Do not use Markdown.
Do not wrap the JSON in ```json or ``` blocks.
TEXT
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ],
                ],
                'temperature' => 0.7,
                'max_tokens' => 12000,
            ]);

            $content = $response->choices[0]->message->content ?? null;

            if (empty($content)) {
                Log::error('OpenAI returned an empty response.', [
                    'response' => $response,
                ]);

                throw new Exception(
                    'OpenAI returned an empty response.'
                );
            }

            Log::info('OpenAI quiz response received.', [
                'response_length' => strlen($content),
            ]);

            return $this->parseAIResponse($content);

        } catch (Exception $e) {
            Log::error('Quiz generation failed.', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'subject' => $subject,
            ]);

            throw new Exception(
                'Failed to generate quiz questions: ' . $e->getMessage()
            );
        }
    }

    /**
     * Build the AI prompt.
     */
    private function buildPrompt(string $subject, ?string $fileContent): string
    {
        $basePrompt = <<<TEXT
Generate exactly 30 unique, challenging multiple-choice quiz questions about:

{$subject}

TEXT;

        if (!empty($fileContent)) {
            $basePrompt .= <<<TEXT
Use the following study material as the primary source for the questions:

{$fileContent}

TEXT;
        } else {
            $basePrompt .= <<<TEXT
Use established academic knowledge and reliable educational sources
relevant to this subject.

TEXT;
        }

        $basePrompt .= <<<TEXT
Requirements:

1. Generate exactly 30 questions.

2. Every question must be unique.

3. Questions should be difficult and challenging.

4. Prefer situational questions, case studies, real-world scenarios,
   problem-solving, analysis, application, synthesis, and evaluation.

5. Avoid simple factual recall whenever a deeper application question
   can reasonably be created.

6. Each question must have exactly four answer choices.

7. Each answer choice must be plausible.

8. There must be exactly one correct answer.

9. The "correct" field must contain the zero-based index of the
   correct answer:
   0 = first answer
   1 = second answer
   2 = third answer
   3 = fourth answer

10. The "why" field must explain why the correct answer is correct
    and why the other choices are not the best answer.

11. Make sure every question is factually accurate.

12. Return ONLY a valid JSON array.

13. Do NOT include Markdown.

14. Do NOT include ```json.

15. Do NOT include any introductory or concluding text.

Each question must have exactly this structure:

{
    "prompt": "Question text",
    "answers": [
        "Answer choice 1",
        "Answer choice 2",
        "Answer choice 3",
        "Answer choice 4"
    ],
    "correct": 0,
    "why": "Explanation"
}

Return exactly 30 question objects inside one JSON array.

TEXT;

        return $basePrompt;
    }

    /**
     * Parse the AI response.
     */
    private function parseAIResponse(string $content): array
    {
        $content = trim($content);

        /*
         * Remove Markdown code fences if the AI accidentally
         * returns them.
         */
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);

        $content = trim($content);

        /*
         * Sometimes an AI response contains extra text before
         * or after the JSON. Try to isolate the JSON array.
         */
        $firstBracket = strpos($content, '[');
        $lastBracket = strrpos($content, ']');

        if ($firstBracket !== false && $lastBracket !== false) {
            $content = substr(
                $content,
                $firstBracket,
                $lastBracket - $firstBracket + 1
            );
        }

        $questions = json_decode($content, true);

        if (
            json_last_error() !== JSON_ERROR_NONE ||
            !is_array($questions)
        ) {
            Log::error('Failed to parse OpenAI response as JSON.', [
                'json_error' => json_last_error_msg(),
                'response_preview' => substr($content, 0, 2000),
            ]);

            throw new Exception(
                'OpenAI returned invalid JSON: ' . json_last_error_msg()
            );
        }

        $validQuestions = [];

        foreach ($questions as $question) {
            if ($this->validateQuestion($question)) {
                $validQuestions[] = $question;
            }
        }

        if (count($validQuestions) < 5) {
            Log::error('Insufficient valid questions generated.', [
                'total_received' => count($questions),
                'valid_questions' => count($validQuestions),
            ]);

            throw new Exception(
                'Insufficient valid questions were generated by OpenAI.'
            );
        }

        return $validQuestions;
    }

    /**
     * Validate one question.
     */
    private function validateQuestion(mixed $question): bool
    {
        if (!is_array($question)) {
            return false;
        }

        if (
            !isset($question['prompt']) ||
            !is_string($question['prompt']) ||
            trim($question['prompt']) === ''
        ) {
            return false;
        }

        if (
            !isset($question['answers']) ||
            !is_array($question['answers']) ||
            count($question['answers']) !== 4
        ) {
            return false;
        }

        foreach ($question['answers'] as $answer) {
            if (
                !is_string($answer) ||
                trim($answer) === ''
            ) {
                return false;
            }
        }

        if (
            !isset($question['correct']) ||
            !is_int($question['correct']) ||
            $question['correct'] < 0 ||
            $question['correct'] > 3
        ) {
            return false;
        }

        if (
            !isset($question['why']) ||
            !is_string($question['why']) ||
            trim($question['why']) === ''
        ) {
            return false;
        }

        return true;
    }
}
