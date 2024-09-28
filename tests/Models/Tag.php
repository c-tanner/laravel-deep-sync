<?php

namespace CTanner\LaravelDeepSync\Tests\Models;

use CTanner\LaravelDeepSync\Attributes\SyncFrom;
use CTanner\LaravelDeepSync\Observers\DeepSync;
use CTanner\LaravelDeepSync\Tests\Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([DeepSync::class])]
class Tag extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['name'];

    #[SyncFrom]
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_tags');
    }

    protected static function newFactory()
    {
        return TagFactory::new();
    }
}