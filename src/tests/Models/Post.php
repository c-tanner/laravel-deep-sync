<?php

namespace CTanner\DeepSync\tests\Models;

use CTanner\LaravelDeepSync\Attributes\SyncFrom;
use CTanner\LaravelDeepSync\Attributes\SyncTo;
use CTanner\LaravelDeepSync\Observers\DeepSync;
use CTanner\LaravelDeepSync\Tests\Models\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy([new DeepSync(Post::class)])]
class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'author_id'
    ];

    /**
     * DeepSync properties and trigger values
     */
    protected $syncable = [
        'is_active' => 0
    ];

    #[SyncFrom]
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[SyncTo]
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
}
