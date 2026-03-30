<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatbotFaq;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatbotController extends Controller
{
    /**
     * Public API: Get active FAQs for chatbot (website).
     */
    public function index()
    {
        try {
            $faqs = ChatbotFaq::active()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'question', 'answer']);

            return response()->json([
                'status' => true,
                'data' => $faqs,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch chatbot FAQs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: List all FAQs.
     */
    public function adminIndex(Request $request)
    {
        try {
            $query = ChatbotFaq::query()->orderBy('sort_order')->orderBy('id');

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('question', 'LIKE', "%{$search}%")
                        ->orWhere('answer', 'LIKE', "%{$search}%");
                });
            }

            $faqs = $query->get();

            return response()->json([
                'status' => true,
                'message' => 'Chatbot FAQs fetched successfully',
                'data' => $faqs,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch chatbot FAQs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Store new FAQ.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'question' => 'required|string|max:500',
                'answer' => 'required|string|max:2000',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();
            $data['is_active'] = $data['is_active'] ?? true;
            $data['sort_order'] = $data['sort_order'] ?? 0;

            $faq = ChatbotFaq::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Chatbot FAQ created successfully',
                'data' => $faq,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create chatbot FAQ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Update FAQ.
     */
    public function update(Request $request, $id)
    {
        try {
            $faq = ChatbotFaq::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'question' => 'sometimes|required|string|max:500',
                'answer' => 'sometimes|required|string|max:2000',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $faq->update($validator->validated());

            return response()->json([
                'status' => true,
                'message' => 'Chatbot FAQ updated successfully',
                'data' => $faq->fresh(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update chatbot FAQ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Delete FAQ.
     */
    public function destroy($id)
    {
        try {
            $faq = ChatbotFaq::findOrFail($id);
            $faq->delete();

            return response()->json([
                'status' => true,
                'message' => 'Chatbot FAQ deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete chatbot FAQ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
