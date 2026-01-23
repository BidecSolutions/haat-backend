<?php

namespace App\Http\Controllers;

use App\Models\DeepLinking;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeepLinkingController extends Controller
{
    //
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'link' => 'required|string|url|unique:deep_linking,link',
                'link2' => 'required|string|url',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first(),
                ]);
            }

            $deeplinking = DeepLinking::create([
                'link' => $request->link,
                'link2' => $request->link2,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Deep link created successfully.',
                'data' => $deeplinking,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function get(Request $request)
    {
        $link = $request->input('link');
        if($link == ''){
            return response()->json([
                'status' => false,
                'message' => 'Link is required',
            ], 404);
        }
        $deeplink = DeepLinking::where('link', $link)->first();
        if (! $deeplink) {
            return response()->json([
                'status' => false,
                'message' => 'this link is not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Deeplink fetching successfully',
            'data' => [
                'deeplink' => $deeplink,
            ],
        ]);
    }
}
