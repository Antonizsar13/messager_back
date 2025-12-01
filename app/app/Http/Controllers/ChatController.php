<?php

namespace App\Http\Controllers;

use App\Enums\ChatType;
use App\Enums\ChatUserRole;
use App\Http\Resources\ChatResource;
use App\Http\Resources\UserResource;
use App\Models\Chat;
use App\Models\ChatUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\File;
use App\Enums\BucketName;
use App\Enums\TypeFile;


class ChatController extends Controller
{

    private function authorize(Chat $chat, ?string $method = null)
    {
        $userId = Auth::id();

        $exists = $chat->users()
            ->where('users.id', $userId)
            ->exists();

        if (! $exists) {
            abort(403, 'Доступ запрещён');
        }

        return true;
    }

    /**
     * @lrd:start
     * # Список всех чатов пользователя
     * Возвращает все чаты, в которых участвует текущий пользователь.
     * @lrd:end
     * @LRDresponses 200 Возвращает список чатов с последним сообщением
     */
    public function index()
    {
        $userId = Auth::id();

        $chats = Chat::whereHas('users', fn($q) => $q->where('user_id', $userId))
            ->with([
                'users',
                'avatar',
                'messages' => fn($q) => $q->latest()->take(1)
            ])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return ChatResource::collection($chats);
    }

    /**
     * @lrd:start
     * # Просмотр одного чата
     * Возвращает чат с последними 50 сообщениями.
     * @lrd:end
     * @LRDresponses 200 Возвращает чат с участниками и сообщениями
     * @LRDresponses 404 Чат не найден
     */
    public function show(Chat $chat)
    {
        $this->authorize($chat);

        $chat->load([
            'users',
            'avatar',
            'messages' => fn($q) => $q->latest()->take(50)
        ]);

        return new ChatResource($chat);
    }

    /**
     * @lrd:start
     * # Создание нового чата
     * Создаёт чат и добавляет участников.
     * @lrd:end
     * @LRDparam type string required Тип чата: private|group
     * @LRDparam title string nullable Название чата
     * @LRDparam users array required Массив ID пользователей
     * @LRDresponses 201 Чат создан
     * @LRDresponses 422 Ошибка валидации
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:private,group,',
            'title' => 'nullable|string|max:255',
            'users' => 'required|array|min:1',
            'users.*' => 'exists:users,id',
        ]);

        $chat = Chat::create([
            'type' => $request->type,
            'title' => count($request->users) > 1 ? $request->title : null,
            'created_by' => Auth::id(),
        ]);

        $chat->users()->attach(
            collect($request->users)->mapWithKeys(fn($id) => [
                $id => ['role' => ChatUserRole::MEMBER->value]
            ])
        );

        $createsRole = ChatUserRole::MEMBER;
        if (count($request->users) > 1) {
            $createsRole = ChatUserRole::ADMIN;
        }
        $chat->users()->attach(Auth::id(), ['role' => $createsRole]);
        $chat->load([
            'users'
        ]);
        return new ChatResource($chat);
    }

    /**
     * @lrd:start
     * # Обновление чата
     * Обновляет название группы.
     * @lrd:end
     * @LRDparam title string nullable Новое название
     * @LRDresponses 200 Чат обновлён
     * @LRDresponses 403 Недостаточно прав
     * @LRDresponses 404 Чат не найден
     */
    public function update(Request $request, Chat $chat)
    {
        $this->authorize($chat, 'update');

        $request->validate([
            'title' => 'nullable|string|max:255',
        ]);

        $chat->update($request->only('title'));

        return new ChatResource($chat);
    }

    /**
     * @lrd:start
     * # Удаление чата
     * Удаляет чат (soft delete)
     * @lrd:end
     * @LRDresponses 200 Чат удалён
     * @LRDresponses 403 Недостаточно прав
     * @LRDresponses 404 Чат не найден
     */
    public function destroy(Chat $chat)
    {
        $this->authorize($chat, 'destroy');
        $chat->delete();

        return response()->json(['message' => 'Chat deleted']);
    }

    /**
     * @lrd:start
     * # Добавление пользователя в чат
     * Добавляет пользователя в чат с указанной ролью
     * @lrd:end
     * @LRDparam users array required Массив ID пользователей
     * @LRDresponses 200 Пользователь добавлен
     * @LRDresponses 403 Недостаточно прав
     * @LRDresponses 404 Чат или пользователь не найден
     */
    public function addUser(Request $request, Chat $chat)
    {
        $this->authorize($chat,'addUser');

        $request->validate([
            'users' => 'required|array|min:1',
            'users.*' => 'exists:users,id',
        ]);
        $chat->users()->syncWithoutDetaching(
            collect($request->users)->mapWithKeys(fn($id) => [
                $id => ['role' => ChatUserRole::MEMBER->value]
            ])
        );

        return response()->json(['message' => 'User added']);
    }

    /**
     * @lrd:start
     * # Удаление пользователя из чата
     * @lrd:end
     * @LRDresponses 200 Пользователь удалён
     * @LRDresponses 403 Недостаточно прав
     * @LRDresponses 404 Чат или пользователь не найден
     */
    public function removeUser(Chat $chat, User $user)
    {;
        $this->authorize($chat,'removeUser');
        $chat->users()->detach($user);

        return response()->json(['message' => 'User removed']);
    }

    /**
     * @lrd:start
     * # Получение списка участников чата
     * @lrd:end
     * @LRDresponses 200 Список пользователей
     * @LRDresponses 404 Чат не найден
     */
    public function users(Chat $chat)
    {
        $chat->load('users');

        return UserResource::collection($chat->users);
    }

    /**
     * @lrd:start
     * # Загрузка аватара чата
     * Загружает или заменяет аватар у чата. Старый аватар удаляется автоматически.
     * @lrd:end
     * @LRDparam avatar file required Изображение (макс. 5MB)
     * @LRDresponses 201 Аватар успешно загружен и сохранён
     * @LRDresponses 403 Недостаточно прав для изменения чата
     * @LRDresponses 404 Чат не найден
     * @LRDresponses 422 Ошибка валидации данных
     */
    public function uploadChatAvatar(Request $request, Chat $chat)
    {
        $this->authorize($chat);

        $request->validate([
            'avatar' => 'required|file|image|max:5120', // max 5MB
        ]);

        $file = $request->file('avatar');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = 'chat-avatars/' . $chat->id;

        if ($chat->avatar) {
//            Storage::disk($chat->avatar->disk)->delete($chat->avatar->full_path);
            $chat->avatar->delete();
        }

        Storage::disk(BucketName::PROFILE->value)->putFileAs($path, $file, $filename);

        $fileRecord = new File([
            'disk' => BucketName::PROFILE->value,
            'path' => $path,
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'type' => TypeFile::avatar,
        ]);

        $chat->avatar()->save($fileRecord);

        return response()->json([
            'message' => 'Chat avatar uploaded successfully',
            'file' => [
                'id' => $fileRecord->id,
                'url' => $fileRecord->url,
                'created_at' => $fileRecord->created_at,
            ],
        ], 201);
    }

}
