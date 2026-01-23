<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function index(Listing $listing)
    {
        $comments = Comment::with(['user', 'replies.user'])
            ->where('listing_id', $listing->id)
            ->whereNull('parent_id')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'message' => "Comments for this listings fetched successfully",
            'data' => $comments,
        ]);
    }

    public function store(Request $request, Listing $listing)
    {
        $request->validate([
            'comment_text' => 'required|string|max:2000',
        ]);

        $comment = $listing->comments()->create([
            'user_id' => Auth::id(),
            'comment_text' => $request->comment_text,
        ]);

        return response()->json($comment->load('user'), 201);
    }

    // public function reply(Request $request, Comment $comment)
    // {
        //nested replies
    //     $request->validate([
    //         'comment_text' => 'required|string|max:2000',
    //     ]);

    //     $reply = $comment->replies()->create([
    //         'user_id' => Auth::id(),
    //         'listing_id' => $comment->listing_id,
    //         'comment_text' => $request->comment_text,
    //     ]);

    //     return response()->json($reply->load('user'), 201);
    // }

    public function reply(Request $request, Comment $comment)
{
    // we're only replying to parent comments (not replies)
    if ($comment->parent_id !== null) {
        return response()->json([
            'message' => 'You can only reply to top-level comments'
        ], 422);
    }

    $request->validate([
        'comment_text' => 'required|string|max:2000',
    ]);

    $reply = $comment->replies()->create([
        'user_id' => Auth::id(),
        'listing_id' => $comment->listing_id,
        'comment_text' => $request->comment_text,
    ]);

    return response()->json($reply->load('user'), 201);
}
    public function update(Request $request, Comment $comment)
    {
        // Authorization check without policy
        if (Auth::id() !== $comment->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'comment_text' => 'required|string|max:2000',
        ]);

        $comment->update([
            'comment_text' => $request->comment_text,
        ]);

        return response()->json($comment->load('user'));
    }

    public function destroy(Comment $comment)
    {
        // Authorization check without policy
        if (Auth::id() !== $comment->user_id && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(null, 204);
    }
}