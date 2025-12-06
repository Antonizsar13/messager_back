<?php

namespace App\Http\Controllers;

use App\Enums\BucketName;
use App\Enums\TypeFile;
use App\Http\Resources\UserResource;
use App\Models\File;
use App\Models\User;
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

    /**
     * @lrd:start
     * # Получение списка пользователей
     * Возвращает список всех пользователей с возможностью фильтрации по имени или phone и пагинацией через offset и limit(default 50)
     * @lrd:end
     * @LRDparam offset int nullable Смещение, с которого начинать выборку, по умолчанию 0
     * @LRDparam limit int nullable Количество пользователей для выборки, по умолчанию 20
     * @LRDparam search string nullable Поиск по имени или phone
     * @LRDresponses 200 Список пользователей
     * @LRDresponses 401 Неавторизованный доступ
     */
    public function index(Request $request)
    {
        $offset = (int) $request->get('offset', 0);
        $limit = (int) $request->get('limit', 50);

        $query = User::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%$search%")
                ->orWhere('phone', 'like', "%$search%");
        }

        $users = $query->offset($offset)
            ->limit($limit)
            ->get();

        $total = $query->count();

        return response()->json([
            'data' => UserResource::collection($users),
            'meta' => [
                'offset' => $offset,
                'limit' => $limit,
                'total' => $total,
            ]
        ]);
    }

    /**
     * @lrd:start
     * # Получение пользователя по ID
     * Возвращает информацию о пользователе по его идентификатору
     * @lrd:end
     * @LRDparam id int required ID пользователя
     * @LRDresponses 200 Информация о пользователе
     * @LRDresponses 401 Неавторизованный доступ
     * @LRDresponses 404 Пользователь не найден
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }
}
