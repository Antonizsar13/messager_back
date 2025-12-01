<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('user_id');

            $table->enum('type', ['text', 'image', 'file', 'system'])->default('text');

            $table->text('message')->nullable();
            $table->string('file_path')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('reply_to')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('chat_id')->references('id')->on('chats')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reply_to')->references('id')->on('messages')->nullOnDelete();

            $table->index(['chat_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
