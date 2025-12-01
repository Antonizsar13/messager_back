<?php

namespace App\Http\Controllers;

use App\Enums\BucketName;
use App\Enums\TypeFile;
use App\Http\Resources\UserResource;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|file|image|max:5120', // максимум 5MB
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $file = $request->file('avatar');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = 'avatars/' . $user->id;

        Storage::disk(BucketName::PROFILE->value)->putFileAs($path, $file, $filename);

        $fileRecord = $user->files()->create([
            'disk' => BucketName::PROFILE->value,
            'path' => $path,
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'type' => TypeFile::avatar,
        ]);

        return response()->json([
            'message' => 'Avatar uploaded successfully',
            'file' => [
                'id' => $fileRecord->id,
                'url' => $fileRecord->url,
                'created_at' => $fileRecord->created_at,
            ],
        ]);
    }

    public function profile()
    {
        $user = Auth::user();

        return new UserResource($user);
    }
}
