<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'disk',
        'path',
        'filename',
        'original_name',
        'mime',
        'size',
        'type',
        'fileable_id',
        'fileable_type',
    ];

    public function fileable()
    {
        return $this->morphTo();
    }

    public function getFullPathAttribute(): string
    {
        return $this->path . '/' . $this->filename;
    }

    public function getUrlAttribute(): ?string
    {
        try {
            return Storage::disk($this->disk)->url($this->full_path);
        } catch (\Exception $e) {
            return 123;
        }
    }

    public function isType(string $type): bool
    {
        return $this->type === $type;
    }
}
