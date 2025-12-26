<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'title',
        'content',
        'slug',
        'original_url',
        'excerpt',
        'author',
        'published_at',
        'is_updated',
        'reference_articles',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_updated' => 'boolean',
        'reference_articles' => 'array',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }
}


