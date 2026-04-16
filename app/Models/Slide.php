<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['document_id', 'sort_order', 'title', 'content'])]
class Slide extends Model
{
    /** @use HasFactory<\Database\Factories\SlideFactory> */
    use HasFactory;

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
