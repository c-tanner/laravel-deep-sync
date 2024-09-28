<?php

namespace CTanner\DeepSync\tests\Models;

use CTanner\LaravelDeepSync\Attributes\SyncFrom;
use CTanner\LaravelDeepSync\Observers\DeepSync;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy([new DeepSync(Tag::class)])]
class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    #[SyncFrom]
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }
}