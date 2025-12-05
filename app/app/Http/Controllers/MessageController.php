<?php

namespace App\Http\Controllers;

use App\Enums\BucketName;
use App\Enums\TypeFile;
use App\Events\Chat\NewMessage;
use App\Http\Resources\MessageResource;
use App\Models\Chat;
use App\Models\Message;
use App\Models\MessageStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    /**
     * @lrd:start
     * # Список сообщений чата
     * Возвращает последние 50 сообщений с пагинацией
     * @lrd:end
     * @LRDresponses 200 Список сообщений
     * @LRDresponses 404 Чат не найден
     */
    public function index(Chat $chat)
    {
        $messages = $chat->messages()
            ->with(['user', 'files', 'replyToMessage'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return MessageResource::collection($messages);
    }

    /**
     * @lrd:start
     * # Отправка сообщения
     * Создаёт сообщение в чате, привязывает файлы и reply_to
     * @lrd:end
     * @LRDparam message string nullable Текст сообщения
     * @LRDparam type string required Тип сообщения: text|image|file|system
     * @LRDparam reply_to int nullable ID сообщения, на которое отвечают
     * @LRDparam files array nullable Массив id файлов
     * @LRDresponses 201 Сообщение создано
     * @LRDresponses 422 Ошибка валидации
     * @LRDresponses 404 Чат не найден
     */
    public function store(Request $request, Chat $chat)
    {

        $request->validate([
            'message' => 'nullable|string',
            'type' => 'required|in:text,image,file,system',
            'reply_to' => 'nullable|exists:messages,id',
            'files' => 'nullable|array',
            'files.*' => 'exists:files,id',
        ]);

        $message = Message::create([
            'chat_id' => $chat->id,
            'user_id' => Auth::id(),
            'type' => $request->type,
            'message' => $request->message,
            'reply_to' => $request->reply_to,
        ]);

        $fileIds = $request->input('files', []);
        if (!empty($fileIds)) {
            $files = Auth::user()->files()->whereIn('id', $fileIds)->get();

            foreach ($files as $file) {
                $file->fileable()->associate($message);
                $file->save();
            }
        }
        event(new \App\Events\Message\MessageSent($message));
        event(new NewMessage($message, $chat->users()->pluck('users.id')->toArray()));

        return new MessageResource($message->load(['user', 'files', 'replyToMessage']));
    }

    /**
     * @lrd:start
     * # Просмотр конкретного сообщения
     * Возвращает сообщение с пользователем, файлами и reply
     * @lrd:end
     * @LRDresponses 200 Сообщение
     * @LRDresponses 404 Сообщение не найдено
     */
    public function show(Message $message)
    {
        return new MessageResource($message->load(['user', 'files', 'replyToMessage']));
    }

    /**
     * @lrd:start
     * # Редактирование сообщения
     * Пользователь может редактировать только свой текст
     * @lrd:end
     * @LRDparam message string required Новый текст
     * @LRDresponses 200 Сообщение обновлено
     * @LRDresponses 403 Доступ запрещён
     * @LRDresponses 404 Сообщение не найдено
     */
    public function update(Request $request, Message $message)
    {
        if ($message->user_id !== Auth::id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'message' => 'required|string',
        ]);

        $message->update([
            'message' => $request->message,
        ]);

        return new MessageResource($message->load(['user', 'files', 'replyToMessage']));
    }

    /**
     * @lrd:start
     * # Удаление сообщения
     * Сообщение удаляется мягко (soft delete)
     * @lrd:end
     * @LRDresponses 200 Сообщение удалено
     * @LRDresponses 403 Доступ запрещён
     * @LRDresponses 404 Сообщение не найдено
     */
    public function destroy(Message $message)
    {

        if ($message->user_id !== Auth::id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $message->delete();

        return response()->json(['message' => 'Message deleted']);
    }

    /**
     * @lrd:start
     * # Пометить сообщение прочитанным
     * Обновляет last_read_message_id и создаёт запись в message_statuses
     * @lrd:end
     * @LRDresponses 200 Помечено как прочитанное
     * @LRDresponses 404 Сообщение не найдено
     */
    public function markAsRead(Message $message)
    {
        $userId = Auth::id();

        DB::transaction(function () use ($message, $userId) {
            $chatUser = $message->chat->users()->where('user_id', $userId)->first();
            if ($chatUser) {
                $chatUser->pivot->last_read_message_id = $message->id;
                $chatUser->pivot->save();
            }

            MessageStatus::updateOrCreate(
                [
                    'message_id' => $message->id,
                    'user_id' => $userId,
                ],
                [
                    'status' => 'read',
                    'read_at' => now(),
                ]
            );
        });

        event(new \App\Events\Message\MessageRead(
            $message->chat_id,
            $message->id,
            Auth::id()
        ));

        return response()->json(['message' => 'Marked as read']);
    }

    /**
     * @lrd:start
     * # Получение количества непрочитанных сообщений
     * Возвращает количество сообщений, которые пользователь ещё не прочитал
     * @lrd:end
     * @LRDresponses 200 Количество непрочитанных сообщений
     * @LRDresponses 404 Чат не найден
     */
    public function getUnreadCount(Chat $chat)
    {
        $userId = Auth::id();

        $lastReadId = $chat->users()->where('user_id', $userId)->first()?->pivot->last_read_message_id ?? 0;

        $count = $chat->messages()
            ->where('id', '>', $lastReadId)
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    /**
     * @lrd:start
     * # Загрузка файла для сообщения
     * Загружает файл, который позже можно будет привязать к сообщению.
     * Файл пока не прикрепляется к сообщению.
     * @lrd:end
     * @LRDparam file file required Файл для загрузки
     * @LRDresponses 201 Файл успешно загружен, возвращает ID и URL
     * @LRDresponses 422 Ошибка валидации (неверный файл или тип)
     */

    public function uploadMessageFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // например, до 10MB
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $file = $request->file('file');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = 'message-files/' . $user->id;

        Storage::disk(BucketName::PROFILE->value)->putFileAs($path, $file, $filename);

        $fileRecord = $user->files()->create([
            'disk' => BucketName::PROFILE->value,
            'path' => $path,
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'type' => TypeFile::message,
        ]);

        return response()->json([
            'message' => 'File uploaded successfully',
            'file' => [
                'id' => $fileRecord->id,
                'url' => $fileRecord->url,
                'created_at' => $fileRecord->created_at,
            ],
        ], 201);
    }

}
