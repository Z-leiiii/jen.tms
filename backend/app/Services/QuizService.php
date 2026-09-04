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
            // Fallback to template-based generation if no API key
            return $this->generateTemplateQuestions($subject);
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
            // Fallback to template-based generation on error
            \Log::error('OpenAI API error: ' . $e->getMessage());
            return $this->generateTemplateQuestions($subject);
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

    /**
     * Fallback template-based question generation
     */
    private function generateTemplateQuestions(string $subject): array
    {
        $questionTemplates = $this->getQuestionTemplates($subject);
        $questions = [];

        // Generate 30 unique questions from templates
        for ($i = 0; $i < 30; $i++) {
            $template = $questionTemplates[$i % count($questionTemplates)];
            $question = $this->generateQuestionFromTemplate($template, $subject, $i);
            $questions[] = $question;
        }

        shuffle($questions);
        return $questions;
    }

    /**
     * Get question templates based on subject category
     */
    private function getQuestionTemplates(string $subject): array
    {
        $lowerSubject = strtolower($subject);

        if (str_contains($lowerSubject, 'math') || str_contains($lowerSubject, 'calculus') || str_contains($lowerSubject, 'algebra') || str_contains($lowerSubject, 'statistic')) {
            return $this->getMathTemplates();
        } elseif (str_contains($lowerSubject, 'physic') || str_contains($lowerSubject, 'mechanic') || str_contains($lowerSubject, 'thermo') || str_contains($lowerSubject, 'static')) {
            return $this->getPhysicsTemplates();
        } elseif (str_contains($lowerSubject, 'chem') || str_contains($lowerSubject, 'molecule') || str_contains($lowerSubject, 'atom')) {
            return $this->getChemistryTemplates();
        } elseif (str_contains($lowerSubject, 'comput') || str_contains($lowerSubject, 'program') || str_contains($lowerSubject, 'data') || str_contains($lowerSubject, 'algorithm')) {
            return $this->getComputerScienceTemplates();
        } elseif (str_contains($lowerSubject, 'biolog') || str_contains($lowerSubject, 'cell') || str_contains($lowerSubject, 'genetic')) {
            return $this->getBiologyTemplates();
        } else {
            return $this->getGeneralTemplates();
        }
    }

    /**
     * Generate a specific question from template
     */
    private function generateQuestionFromTemplate(array $template, string $subject, int $index): array
    {
        return [
            'prompt' => $this->replacePlaceholders($template['prompt'], $subject, $index),
            'answers' => $template['answers'],
            'correct' => $template['correct'],
            'why' => $this->replacePlaceholders($template['why'], $subject, $index),
        ];
    }

    /**
     * Replace placeholders in template with dynamic content
     */
    private function replacePlaceholders(string $text, string $subject, int $index): string
    {
        $text = str_replace('{subject}', $subject, $text);
        $text = str_replace('{index}', (string)($index + 1), $text);
        return $text;
    }

    /**
     * Mathematics question templates
     */
    private function getMathTemplates(): array
    {
        return [
            [
                'prompt' => 'What is the derivative of x² with respect to x?',
                'answers' => ['2x', 'x²', '2x²', 'x'],
                'correct' => 0,
                'why' => 'The power rule states that d/dx(x^n) = nx^(n-1), so d/dx(x²) = 2x.'
            ],
            [
                'prompt' => 'What is the integral of 2x dx?',
                'answers' => ['x² + C', '2x² + C', 'x + C', '2 + C'],
                'correct' => 0,
                'why' => 'The integral of 2x is x² plus the constant of integration C.'
            ],
            [
                'prompt' => 'What is the limit of (x²-1)/(x-1) as x approaches 1?',
                'answers' => ['0', '1', '2', 'Undefined'],
                'correct' => 2,
                'why' => 'Factor the numerator: (x-1)(x+1)/(x-1) = x+1, which approaches 2 as x→1.'
            ],
            [
                'prompt' => 'What is the slope of the tangent line to y=x³ at x=2?',
                'answers' => ['6', '8', '12', '4'],
                'correct' => 2,
                'why' => 'The derivative is 3x², so at x=2, the slope is 3(4) = 12.'
            ],
            [
                'prompt' => 'What is the area under the curve y=x from x=0 to x=3?',
                'answers' => ['3', '4.5', '6', '9'],
                'correct' => 1,
                'why' => '∫₀³ x dx = [x²/2]₀³ = 9/2 = 4.5.'
            ],
            [
                'prompt' => 'What is the second derivative of x³?',
                'answers' => ['3x²', '6x', '6', 'x'],
                'correct' => 1,
                'why' => 'First derivative: 3x², second derivative: 6x.'
            ],
            [
                'prompt' => 'What is the value of e^(ln(5))?',
                'answers' => ['1', '5', 'e', 'ln(5)'],
                'correct' => 1,
                'why' => 'e^(ln(x)) = x for any positive x, so e^(ln(5)) = 5.'
            ],
            [
                'prompt' => 'What is the sum of the first 10 natural numbers?',
                'answers' => ['45', '50', '55', '100'],
                'correct' => 2,
                'why' => 'The formula is n(n+1)/2, so 10(11)/2 = 55.'
            ],
            [
                'prompt' => 'What is the probability of rolling a 6 on a fair die?',
                'answers' => ['1/2', '1/3', '1/6', '5/6'],
                'correct' => 2,
                'why' => 'A fair die has 6 equally likely outcomes, so P(6) = 1/6.'
            ],
            [
                'prompt' => 'What is the median of the set {1, 3, 5, 7, 9}?',
                'answers' => ['3', '5', '7', '4'],
                'correct' => 1,
                'why' => 'The median is the middle value when arranged in order, which is 5.'
            ]
        ];
    }

    /**
     * Physics question templates
     */
    private function getPhysicsTemplates(): array
    {
        return [
            [
                'prompt' => 'What is Newton\'s First Law of Motion?',
                'answers' => ['F=ma', 'Objects in motion stay in motion', 'Action equals reaction', 'Energy is conserved'],
                'correct' => 1,
                'why' => 'Newton\'s First Law states that an object at rest stays at rest, and an object in motion stays in motion unless acted upon by an external force.'
            ],
            [
                'prompt' => 'What is the unit of force?',
                'answers' => ['Joule', 'Watt', 'Newton', 'Pascal'],
                'correct' => 2,
                'why' => 'The Newton (N) is the SI unit of force, named after Isaac Newton.'
            ],
            [
                'prompt' => 'What is the speed of light in vacuum?',
                'answers' => ['3×10⁸ m/s', '3×10⁶ m/s', '3×10¹⁰ m/s', '3×10⁴ m/s'],
                'correct' => 0,
                'why' => 'The speed of light in vacuum is approximately 3×10⁸ meters per second.'
            ],
            [
                'prompt' => 'What is kinetic energy?',
                'answers' => ['½mv²', 'mgh', 'F×d', 'ma'],
                'correct' => 0,
                'why' => 'Kinetic energy = ½mv², where m is mass and v is velocity.'
            ],
            [
                'prompt' => 'What is the unit of electric current?',
                'answers' => ['Volt', 'Ohm', 'Ampere', 'Coulomb'],
                'correct' => 2,
                'why' => 'The Ampere (A) is the SI unit of electric current.'
            ],
            [
                'prompt' => 'What is the law of conservation of energy?',
                'answers' => ['Energy can be created', 'Energy is constant', 'Energy transforms but is conserved', 'Energy disappears'],
                'correct' => 2,
                'why' => 'Energy cannot be created or destroyed, only transformed from one form to another.'
            ],
            [
                'prompt' => 'What is the acceleration due to gravity on Earth?',
                'answers' => ['9.8 m/s²', '8.9 m/s²', '10.8 m/s²', '9.0 m/s²'],
                'correct' => 0,
                'why' => 'The acceleration due to gravity on Earth\'s surface is approximately 9.8 m/s².'
            ],
            [
                'prompt' => 'What is Ohm\'s Law?',
                'answers' => ['V=IR', 'I=VR', 'R=VI', 'P=VI'],
                'correct' => 0,
                'why' => 'Ohm\'s Law states that voltage equals current times resistance: V = IR.'
            ],
            [
                'prompt' => 'What is the unit of power?',
                'answers' => ['Joule', 'Newton', 'Watt', 'Pascal'],
                'correct' => 2,
                'why' => 'The Watt (W) is the SI unit of power, equal to one joule per second.'
            ],
            [
                'prompt' => 'What is the Doppler effect?',
                'answers' => ['Change in wave frequency due to motion', 'Wave reflection', 'Wave interference', 'Wave diffraction'],
                'correct' => 0,
                'why' => 'The Doppler effect is the change in frequency of a wave due to the relative motion of source and observer.'
            ]
        ];
    }

    /**
     * Chemistry question templates
     */
    private function getChemistryTemplates(): array
    {
        return [
            [
                'prompt' => 'What is the atomic number of Carbon?',
                'answers' => ['4', '6', '8', '12'],
                'correct' => 1,
                'why' => 'Carbon has 6 protons, giving it an atomic number of 6.'
            ],
            [
                'prompt' => 'What is the chemical formula for water?',
                'answers' => ['CO₂', 'H₂O', 'NaCl', 'O₂'],
                'correct' => 1,
                'why' => 'Water consists of two hydrogen atoms and one oxygen atom: H₂O.'
            ],
            [
                'prompt' => 'What is the pH of a neutral solution?',
                'answers' => ['0', '7', '14', '1'],
                'correct' => 1,
                'why' => 'A neutral solution has a pH of 7, with equal concentrations of H⁺ and OH⁻ ions.'
            ],
            [
                'prompt' => 'What type of bond forms in NaCl?',
                'answers' => ['Covalent', 'Ionic', 'Metallic', 'Hydrogen'],
                'correct' => 1,
                'why' => 'NaCl forms an ionic bond through the transfer of electrons from sodium to chlorine.'
            ],
            [
                'prompt' => 'What is the molar mass of oxygen (O₂)?',
                'answers' => ['16 g/mol', '32 g/mol', '8 g/mol', '64 g/mol'],
                'correct' => 1,
                'why' => 'Oxygen has atomic mass 16, so O₂ has molar mass 32 g/mol.'
            ],
            [
                'prompt' => 'What is Avogadro\'s number?',
                'answers' => ['6.02×10²³', '3.14×10²³', '1.0×10²⁴', '6.02×10²²'],
                'correct' => 0,
                'why' => 'Avogadro\'s number is 6.02×10²³, representing particles in one mole.'
            ],
            [
                'prompt' => 'What is the electron configuration of Neon?',
                'answers' => ['1s²2s²', '1s²2s²2p⁶', '1s²2s²2p⁶3s²', '1s²2s²2p⁴'],
                'correct' => 1,
                'why' => 'Neon (atomic number 10) has the configuration 1s²2s²2p⁶, a full octet.'
            ],
            [
                'prompt' => 'What is an exothermic reaction?',
                'answers' => ['Absorbs heat', 'Releases heat', 'No heat change', 'Requires catalyst'],
                'correct' => 1,
                'why' => 'An exothermic reaction releases heat to the surroundings.'
            ],
            [
                'prompt' => 'What is the unit of molarity?',
                'answers' => ['g/L', 'mol/L', 'mol/g', 'L/mol'],
                'correct' => 1,
                'why' => 'Molarity is measured in moles per liter (mol/L or M).'
            ],
            [
                'prompt' => 'What is the periodic trend for atomic radius?',
                'answers' => ['Increases down and left', 'Increases up and right', 'Decreases down and left', 'Constant'],
                'correct' => 0,
                'why' => 'Atomic radius increases down a group (more shells) and decreases across a period (more nuclear charge).'
            ]
        ];
    }

    /**
     * Computer Science question templates
     */
    private function getComputerScienceTemplates(): array
    {
        return [
            [
                'prompt' => 'What data structure uses LIFO?',
                'answers' => ['Queue', 'Stack', 'Array', 'Linked List'],
                'correct' => 1,
                'why' => 'A stack follows Last-In-First-Out (LIFO) principle.'
            ],
            [
                'prompt' => 'What is the time complexity of binary search?',
                'answers' => ['O(n)', 'O(log n)', 'O(n²)', 'O(1)'],
                'correct' => 1,
                'why' => 'Binary search has O(log n) time complexity as it halves the search space each iteration.'
            ],
            [
                'prompt' => 'What is the purpose of a hash table?',
                'answers' => ['Sort data', 'Fast key-value lookup', 'Store sequential data', 'Compress data'],
                'correct' => 1,
                'why' => 'Hash tables provide average O(1) time complexity for key-value operations.'
            ],
            [
                'prompt' => 'What is recursion?',
                'answers' => ['Looping', 'Function calling itself', 'Memory allocation', 'Error handling'],
                'correct' => 1,
                'why' => 'Recursion is when a function calls itself to solve smaller instances of the problem.'
            ],
            [
                'prompt' => 'What is Big O notation used for?',
                'answers' => ['Memory size', 'Algorithm efficiency', 'Code length', 'Variable names'],
                'correct' => 1,
                'why' => 'Big O notation describes the upper bound of an algorithm\'s time or space complexity.'
            ],
            [
                'prompt' => 'What is a binary tree?',
                'answers' => ['Tree with 2 children max per node', 'Tree with only leaves', 'Tree with no root', 'Linear structure'],
                'correct' => 0,
                'why' => 'A binary tree is a tree data structure where each node has at most two children.'
            ],
            [
                'prompt' => 'What is the difference between BFS and DFS?',
                'answers' => ['Same algorithm', 'BFS uses queue, DFS uses stack', 'BFS is recursive', 'DFS is level-order'],
                'correct' => 1,
                'why' => 'BFS uses a queue for level-order traversal, while DFS uses a stack (or recursion) for depth-first exploration.'
            ],
            [
                'prompt' => 'What is polymorphism?',
                'answers' => ['Multiple inheritance', 'Same interface, different implementations', 'Data hiding', 'Code reuse'],
                'correct' => 1,
                'why' => 'Polymorphism allows objects of different types to be treated as objects of a common type.'
            ],
            [
                'prompt' => 'What is a pointer?',
                'answers' => ['A variable storing data', 'A variable storing memory address', 'A function', 'A loop'],
                'correct' => 1,
                'why' => 'A pointer is a variable that stores the memory address of another variable.'
            ],
            [
                'prompt' => 'What is the purpose of an index in a database?',
                'answers' => ['Store data', 'Speed up queries', 'Encrypt data', 'Backup data'],
                'correct' => 1,
                'why' => 'Database indexes improve query performance by creating fast lookup paths.'
            ]
        ];
    }

    /**
     * Biology question templates
     */
    private function getBiologyTemplates(): array
    {
        return [
            [
                'prompt' => 'What is the basic unit of life?',
                'answers' => ['Atom', 'Cell', 'Tissue', 'Organ'],
                'correct' => 1,
                'why' => 'The cell is the fundamental unit of life, capable of performing all life processes.'
            ],
            [
                'prompt' => 'What is DNA?',
                'answers' => ['A protein', 'Genetic material', 'A carbohydrate', 'A lipid'],
                'correct' => 1,
                'why' => 'DNA (deoxyribonucleic acid) carries genetic information in living organisms.'
            ],
            [
                'prompt' => 'What is the powerhouse of the cell?',
                'answers' => ['Nucleus', 'Mitochondria', 'Ribosome', 'Golgi body'],
                'correct' => 1,
                'why' => 'Mitochondria produce ATP through cellular respiration, earning them the nickname "powerhouse of the cell."'
            ],
            [
                'prompt' => 'What is photosynthesis?',
                'answers' => ['Breaking down glucose', 'Converting light to chemical energy', 'Cell division', 'Protein synthesis'],
                'correct' => 1,
                'why' => 'Photosynthesis converts light energy into chemical energy stored in glucose.'
            ],
            [
                'prompt' => 'What are the four bases of DNA?',
                'answers' => ['A, T, C, G', 'A, U, C, G', 'A, T, U, G', 'A, T, C, U'],
                'correct' => 0,
                'why' => 'DNA contains four nitrogenous bases: Adenine (A), Thymine (T), Cytosine (C), and Guanine (G).'
            ],
            [
                'prompt' => 'What is mitosis?',
                'answers' => ['Cell division producing gametes', 'Cell division producing identical cells', 'DNA replication', 'Protein synthesis'],
                'correct' => 1,
                'why' => 'Mitosis produces two genetically identical daughter cells for growth and repair.'
            ],
            [
                'prompt' => 'What is the function of red blood cells?',
                'answers' => ['Fight infection', 'Carry oxygen', 'Clot blood', 'Produce hormones'],
                'correct' => 1,
                'why' => 'Red blood cells contain hemoglobin and transport oxygen throughout the body.'
            ],
            [
                'prompt' => 'What is an ecosystem?',
                'answers' => ['A single organism', 'A community of organisms and environment', 'A habitat', 'A food chain'],
                'correct' => 1,
                'why' => 'An ecosystem includes all living organisms and their physical environment interacting as a system.'
            ],
            [
                'prompt' => 'What is the role of enzymes?',
                'answers' => ['Provide energy', 'Catalyze reactions', 'Store genetic info', 'Transport materials'],
                'correct' => 1,
                'why' => 'Enzymes are biological catalysts that speed up chemical reactions without being consumed.'
            ],
            [
                'prompt' => 'What is natural selection?',
                'answers' => ['Random mutation', 'Survival of the fittest', 'Genetic drift', 'Artificial breeding'],
                'correct' => 1,
                'why' => 'Natural selection is the process where organisms better adapted to their environment tend to survive and reproduce.'
            ]
        ];
    }

    /**
     * General question templates for other subjects
     */
    private function getGeneralTemplates(): array
    {
        return [
            [
                'prompt' => 'What is the main focus of this subject?',
                'answers' => ['Historical analysis', 'Understanding core concepts', 'Mathematical proofs', 'Laboratory experiments'],
                'correct' => 1,
                'why' => 'This subject focuses on understanding fundamental concepts and their applications.'
            ],
            [
                'prompt' => 'Why is practice important in learning?',
                'answers' => ['It\'s not important', 'Reinforces learning', 'Only for exams', 'Wastes time'],
                'correct' => 1,
                'why' => 'Practice reinforces neural pathways and improves retention.'
            ],
            [
                'prompt' => 'What is a key principle in effective learning?',
                'answers' => ['Memorization only', 'Understanding relationships', 'Ignoring context', 'Rote learning'],
                'correct' => 1,
                'why' => 'Understanding relationships between concepts is crucial.'
            ],
            [
                'prompt' => 'How should you approach complex problems?',
                'answers' => ['Randomly', 'Systematically', 'Skip them', 'Guess only'],
                'correct' => 1,
                'why' => 'A systematic approach helps break down complex problems.'
            ],
            [
                'prompt' => 'What is the value of studying this subject?',
                'answers' => ['No value', 'Critical thinking skills', 'Only for grades', 'Useless knowledge'],
                'correct' => 1,
                'why' => 'This subject develops critical thinking and problem-solving skills applicable to many areas.'
            ],
            [
                'prompt' => 'What is the role of examples in learning?',
                'answers' => ['They\'re distractions', 'Illustrate concepts', 'Should be ignored', 'Only for beginners'],
                'correct' => 1,
                'why' => 'Examples help illustrate abstract concepts.'
            ],
            [
                'prompt' => 'What is effective learning?',
                'answers' => ['Passive reading', 'Active engagement', 'Cramming', 'Memorization'],
                'correct' => 1,
                'why' => 'Active engagement through practice and application is most effective.'
            ],
            [
                'prompt' => 'How do you master a subject?',
                'answers' => ['Never practice', 'Consistent practice', 'Read once', 'Watch videos only'],
                'correct' => 1,
                'why' => 'Consistent practice and application lead to mastery.'
            ],
            [
                'prompt' => 'What is the relationship between theory and practice?',
                'answers' => ['Unrelated', 'Complementary', 'Theory only', 'Practice only'],
                'correct' => 1,
                'why' => 'Theory and practice are complementary - theory guides, practice reinforces.'
            ],
            [
                'prompt' => 'What makes a good explanation?',
                'answers' => ['Complex language', 'Clarity and simplicity', 'Lengthy text', 'Technical jargon'],
                'correct' => 1,
                'why' => 'Good explanations are clear, simple, and build understanding progressively.'
            ]
        ];
    }
}
