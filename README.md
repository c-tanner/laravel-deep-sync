[![build](https://github.com/c-tanner/laravel-deep-sync/actions/workflows/php.yml/badge.svg)](https://github.com/c-tanner/laravel-deep-sync/actions/workflows/php.yml)

# Laravel Deep Sync

Elegantly sync properties across any relationship.

## Beyond cascading soft deletes

Cascading soft-deletes within Laravel has been covered by a number of great packages in the past. At it's core, though, `deleted_at` is just another class property. 

With DeepSync, you can assign any model property as `syncable` - and choose which models should follow suit:

```php
#[ObservedBy([DeepSync::class])]
class User extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'is_active'
    ];

    // Properties that trigger DeepSync
    public $syncable = ['is_active'];

    #[SyncTo]
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }
}
```

Here, our `User` model defines it's `is_active` property as `syncable`, and that the `Post` model should `SyncTo` changes.

Then, in our `Post` model:

```php
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
}
```

> Note that the `Post` model must contain the `#[SyncFrom]` attribute, the `is_active` class property, and the `$syncable` array.

## Observer events

DeepSync currently supports `saved()` and `deleted()` model events. Note that in Laravel, `update()` also calls `save()` under the hood, and will also trigger the DeepSync observer.

## Polymorphic support

Cascading attributes in one-to-one or one-to-many relationships is straightforward: when the "parent" model changes state, DeepSync finds the "child" records using Eloquent relationship methods tagged with the `#[SyncTo]` attribute and updates the attribute to the same value. Child models are also inspected for their relationship methods, and the process continues down the tree.

For many-to-many or many-to-one relationships, DeepSync only updates child records _if all parents share the same state_.

![example relationship diagram](https://github.com/c-tanner/laravel-deep-sync/blob/main/doc/relationship-example-1.png)

In the example above, we can see that when User A is deleted, Post A is also deleted, as User A is it's only parent. Since Post B, even though it also related to User A, is also related to User B. Since User B is a "live" parent, Post B remains unchanged.

DeepSync relationships cascade, and will traverse to as many levels as are defined:

![example multi-level relationship diagram](https://github.com/c-tanner/laravel-deep-sync/blob/main/doc/relationship-example-2.png)

Though this example uses delete actions as an example, this concept applies to all DeepSync attributes.

## Omnidirectional syncs

Because we can define the direction of `SyncFrom` and `SyncTo` independent of our actual class hierarchy, a pretty neat feature becomes available.

Let's say we have two models, `Task` and `Subtask`. The class hierarchy is as you would expect:

```php
class Task {
    return subtasks(): HasMany
        return $this->hasMany(Subtask::class);
    }
}
```

However, let's say that both classes have a property, `is_complete`, which defaults to `false`, and we want to automatically mark a `Task` complete only when all related `Subtasks` are also complete:

```php
#[ObservedBy([DeepSync::class])]
class Task extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'is_complete'
    ];

    public $syncable = ['is_complete'];

    #[SyncFrom]
    public function subtasks(): HasMany
    {
        return $this->hasMany(Subtask::class);
    }
}
```

Note that we are using the `#[SyncFrom]` attribute on the "parent" class here instead of `#[SyncTo]`.

And in our `Subtask` class:

```php
#[ObservedBy([DeepSync::class])]
class Subtask extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'is_complete',
        'task_id'
    ];

    public $syncable = ['is_complete'];

    #[SyncTo]
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
```

Now let's test it:

```php
public function test_reverse_sync()
{
    $task = Task::factory()->has(
        Subtask::factory(3)->state(
            function(array $attributes, Task $task) {
                return [
                    'task_id' => $task->id
                ];
            }
        )
    )->create();

    $this->assertEquals(1, Task::count());
    $this->assertEquals(3, Subtask::count());
    $this->assertEquals(3, Task::find($task->id)->subtasks()->count());

    // Task only becomes complete when all subtasks are complete
    
    $subtask1 = Subtask::find(1);
    $subtask1->update(['is_complete' => 1]);

    $this->assertEquals(0, Task::find($task->id)->is_complete);

    $subtask2 = Subtask::find(2);
    $subtask2->update(['is_complete' => 1]);

    $this->assertEquals(0, Task::find($task->id)->is_complete);

    $subtask3 = Subtask::find(3);
    $subtask3->update(['is_complete' => 1]);

    $this->assertEquals(1, Task::find($task->id)->is_complete);
    
}
```

```
$ ~/laravel-deep-sync: vendor/bin/phpunit --testsuite=Feature --colors=always         
PHPUnit 11.3.6 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.3.11
Configuration: /Users/christanner/Code/laravel-deep-sync/phpunit.xml

.                                                                   1 / 1 (100%)

Time: 00:00.238, Memory: 38.50 MB

OK (1 test, 6 assertions)
```

## Configuration

Ironically, Observers in Laravel aren't very observable (I think that's what irony is, right?). This can make debugging quite difficult, so DeepSync comes with verbose logging configured by default, output to your application's default log channel. You can turn logging off, or change the log severity by publishing the configuration file:

`php artisan vendor:publish --tag=deepsync`
