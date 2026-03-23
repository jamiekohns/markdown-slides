<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

#[Fillable(['user_id', 'theme_id', 'title', 'description', 'content'])]
class Document extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentFactory> */
    use HasFactory, SoftDeletes;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->latest();
    }

    public function presentationToken(): string
    {
        $friendlyName = preg_replace('/[^A-Za-z0-9]+/', '_', trim($this->title));
        $friendlyName = is_string($friendlyName) ? trim($friendlyName, '_') : '';

        if ($friendlyName === '') {
            $friendlyName = 'document';
        }

        return "{$this->getKey()}-{$friendlyName}";
    }

    public function presentationUrl(): string
    {
        if (! $this->exists) {
            throw new RuntimeException('Cannot generate presentation URL for an unsaved document.');
        }

        return route('slidewire.database.presentations', ['document' => $this->presentationToken()]);
    }
}
