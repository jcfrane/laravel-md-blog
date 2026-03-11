<?php

namespace JCFrane\MdBlog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use JCFrane\MdBlog\Services\PostMailService;

class SendPostMailController
{
    public function __invoke(Request $request, PostMailService $service): JsonResponse
    {
        $request->validate([
            'path' => ['required', 'string'],
        ]);

        try {
            $count = $service->send($request->input('path'));

            return response()->json([
                'message' => "Successfully sent/queued {$count} email(s).",
                'count' => $count,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
