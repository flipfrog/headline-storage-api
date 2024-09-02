<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use \Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Headline extends Model
{
    use SoftDeletes, HasFactory;

    const CATEGORIES = [
        'book-digital',
        'book-paper',
        'sound-file',
        'sound-cd',
        'sound-vinyl',
        'bookmark-network',
    ];

    public string $title;
    public string $category;
    public string $description;

    protected $fillable = [
        'title',
        'category',
        'description',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [];
    }

    /**
     * 自身で登録した関連する目次の一覧
     * @return BelongsToMany
     */
    public function forwardRefs(): BelongsToMany
    {
        return $this
            ->belongsToMany(self::class, 'headline_headline', 'origin_id', 'end_id')
            ->withTimestamps();
    }

    /**
     * 他の目次で登録された関連する目次の一覧
     * @return BelongsToMany
     */
    public function backwardRefs(): BelongsToMany
    {
        return $this
            ->belongsToMany(self::class, 'headline_headline', 'end_id', 'origin_id')
            ->withTimestamps();
    }
}
