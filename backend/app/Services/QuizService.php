<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Cache;

class QuizService
{
    /**
     * Generate 30 unique quiz questions using AI
     */
    public function generateQuestions(string $subject, ?string $fileContent = null): array
    {
        $apiKey = config('services.openai.api_key');
        
        if (!$apiKey) {
            throw new \Exception('OpenAI API key is required for quiz generation. Please configure OPENAI_API_KEY in your environment.');
        }

        try {
            $prompt = $this->buildPrompt($subject, $fileContent);
            
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert educational content creator specializing in advanced assessment design. Generate challenging, situational multiple-choice questions that test deep understanding and critical thinking. Your questions should present real-world scenarios requiring analysis and application of concepts, not simple factual recall. Draw from established academic sources, textbooks, and peer-reviewed materials. Each question must be unique, factually correct, and include convincing distractors.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.8,
                'max_tokens' => 4000,
            ]);

            $content = $response->choices[0]->message->content;
            return $this->parseAIResponse($content);
            
        } catch (\Exception $e) {
            \Log::error('OpenAI API error: ' . $e->getMessage());
            throw new \Exception('Failed to generate quiz questions: ' . $e->getMessage());
        }
    }

    /**
     * Build the prompt for AI question generation
     */
    private function buildPrompt(string $subject, ?string $fileContent): string
    {
        $basePrompt = "Generate exactly 30 unique, challenging multiple-choice quiz questions about '{$subject}'. ";
        
        if ($fileContent) {
            $basePrompt .= "Base the questions on the following content:\n\n{$fileContent}\n\n";
        } else {
            $basePrompt .= "Draw from established academic sources, textbooks, and scholarly literature in this field. ";
        }

        $basePrompt .= "
Requirements:
- Each question must be unique and different from the others
- Create DIFFICULT, CHALLENGING questions that test deep understanding
- Focus on SITUATIONAL questions that require critical thinking and application
- Present real-world scenarios, case studies, or complex problem-solving situations
- Avoid simple factual recall - instead ask students to apply concepts to novel situations
- Questions should require analysis, synthesis, and evaluation (Bloom's Taxonomy levels 4-6)
- Include scenarios where students must identify the best approach among plausible options
- Use trusted academic sources as reference for accuracy
- Format each question as a JSON object with these exact keys:
  - 'prompt': the question text (describe a scenario/situation)
  - 'answers': array of 4 plausible answers (make distractors convincing)
  - 'correct': index (0-3) of the correct answer
  - 'why': detailed explanation of why this answer is correct and why others are wrong

Return ONLY a valid JSON array of 30 question objects. No additional text.";

        return $basePrompt;
    }

    /**
     * Parse AI response into question array
     */
    private function parseAIResponse(string $content): array
    {
        $content = trim($content);
        
        // Remove markdown code blocks if present
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*$/', '', $content);
        
        $questions = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($questions)) {
            throw new \Exception('Failed to parse AI response as JSON');
        }

        // Validate and sanitize questions
        $validQuestions = [];
        foreach ($questions as $question) {
            if ($this->validateQuestion($question)) {
                $validQuestions[] = $question;
            }
        }

        // Ensure we have at least some questions
        if (count($validQuestions) < 5) {
            throw new \Exception('Insufficient valid questions generated');
        }

        return $validQuestions;
    }

    /**
     * Validate a question structure
     */
    private function validateQuestion($question): bool
    {
        return isset($question['prompt']) 
            && isset($question['answers']) 
            && is_array($question['answers']) 
            && count($question['answers']) === 4
            && isset($question['correct']) 
            && is_int($question['correct']) 
            && $question['correct'] >= 0 
            && $question['correct'] <= 3
            && isset($question['why']);
    }
}
