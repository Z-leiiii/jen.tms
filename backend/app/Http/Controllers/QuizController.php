<?php

namespace App\Http\Controllers;

use App\Services\QuizService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuizController extends Controller
{
    public function __construct(
        private QuizService $quizService
    ) {}

    /**
     * Generate AI-powered quiz questions based on subject
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'file_content' => 'nullable|string',
        ]);

        $subject = $request->input('subject');
        $fileContent = $request->input('file_content');

        $questions = $this->quizService->generateQuestions($subject, $fileContent);

        return response()->json([
            'questions' => $questions,
            'subject' => $subject,
            'total' => count($questions),
        ]);
    }
}
