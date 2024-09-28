<?php

namespace CTanner\LaravelDeepSync\Tests\Models;

use CTanner\LaravelDeepSync\Attributes\SyncFrom;
use CTanner\LaravelDeepSync\Attributes\SyncTo;
use CTanner\LaravelDeepSync\Observers\DeepSync;
use CTanner\LaravelDeepSync\Tests\Database\Factories\PostFactory;
use CTanner\LaravelDeepSync\Tests\Models\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([DeepSync::class])]
class Post extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'body',
        'author_id',
        'is_active'
    ];

    /**
     * DeepSync properties and trigger values
     */
    public $syncable = ['is_active'];

    #[SyncFrom]
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    #[SyncTo]
    public function tags(): belongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tags');
    }

    protected static function newFactory()
    {
        return PostFactory::new();
    }
}
