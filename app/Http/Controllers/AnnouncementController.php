<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $ascCode = $request->user()?->asc_code ?? $request->query('asc_code');
        $query = Announcement::query();
        if ($ascCode) {
            $query->where('asc_code', $ascCode);
        }
        $news = $query->orderBy('created_at', 'desc')->get();
            
        return response()->json($news);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'message' => 'required|string',
            'type' => 'required|string',
        ]);

        $announcement = Announcement::create([
            'asc_code' => $request->user()->asc_code,
            'title' => $request->title,
            'message' => $request->message,
            'type' => $request->type,
        ]);

        return response()->json($announcement, 201);
    }
}
