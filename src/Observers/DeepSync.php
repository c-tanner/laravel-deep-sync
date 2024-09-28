<?php

declare(strict_types=1);

namespace CTanner\LaravelDeepSync\Observers;

use CTanner\LaravelDeepSync\Attributes\SyncFrom;
use CTanner\LaravelDeepSync\Attributes\SyncTo;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;

class DeepSync implements ShouldHandleEventsAfterCommit
{
    private $triggerObj;
    private $logEnabled;
    private $logLevel;
    private $syncProperty;

    public function __construct()
    {
        $this->logEnabled = config('deepsync.logging.enabled');
        $this->logLevel = config('deepsync.logging.log_level');

        if ($this->logEnabled) {
            Context::add('meta', [
                'source'    => get_class($this),
                'traceId'   => Str::uuid()->toString()
            ]);
        }
    }

    /**
     * Log a message if enabled
     * 
     * @param string $msg The log message
     * 
     * @return void
     */
    private function logger(string $msg): void
    {
        if (!$this->logEnabled) {
            return;
        }

        Log::{$this->logLevel}($msg);
    }

    /**
     * Handle state change events
     *
     * @param Model $triggerObj
     * 
     * @return void
     */
    public function saved(Model $triggerObj): void
    {
        if (!$triggerObj->syncable) {
            return;
        }

        $this->triggerObj = $triggerObj;

        foreach ($this->triggerObj->syncable as $property) {

            $this->syncProperty = $property;

            // Only trigger if the object is moving to the desired state
            if ($this->triggerObj->$property !== $this->triggerObj->getOriginal($property)) {

                $this->logger(get_class($this->triggerObj) ." (ID: {$this->triggerObj->id}) triggered a sync event ($property)");

                $this->reflectionIterator([$this, 'handleStateChange']);
            }

        }
    }

    /**
     * Handle deletion events
     *
     * @param Model $triggerObj
     * 
     * @return void
     */
    public function deleted(Model $triggerObj): void
    {
        $this->triggerObj = $triggerObj;

        $this->logger(get_class($this->triggerObj) . " (ID: $triggerObj->id) triggered a deletion event");

        $this->reflectionIterator([$this, 'handleDelete']);
        
    }

    /**
     * Handles iterating over related parent/child records for reflection-based relationships
     *
     * @param callable $callback The callback function that executes the modification of the child
     *
     * @return void
     */
    private function reflectionIterator(callable $callback): void
    {
        $this->logger("Beginning reflectionIterator for ". get_class($this->triggerObj) ." ...");

        if ($children = $this->getChildrenByReflection($this->triggerObj)) {

            $children->each(function($child) use ($callback) {

                $this->logger('Checking child: ' . json_encode($child));

                if ($parents = $this->getParentsByReflection($child)) {

                    $this->logger('Found parents for child:');

                    $parents->each(function ($item) {
                        $this->logger(json_encode($item));
                    });

                    $callback($parents, $child);

                } else {

                    $this->logger('Did not find parents for: ' . json_encode($child));

                }
            });
        }
    }

    /**
     * Handles deletion children if all parents are deleted
     *
     * @param Collection $parents A collection of parent models to check
     * @param Model $childRecord The related child record to delete
     *
     * @return void
     */
    private function handleDelete(Collection $parents, Model $childRecord): void
    {
        $parents->filter(function($parent) {
            return $parent->deleted_at === null;
        });

        if (!count($parents)) {
            // If all parents are deleted, delete child
            $this->logger(get_class($childRecord) . " (ID: $childRecord->id) has no more live parents, deleting..");
            $childRecord->delete();
        }
    }

    /**
     * Handle state changes on child record if all parents share the same status
     *
     * @param Collection $parents A collection of parent models to check
     * @param Model $childRecord The related child record to modify
     *
     * @return void
     */
    private function handleStateChange(Collection $parents, Model $childRecord): void
    {
        // Normalize 0/false/null
        $triggerValue = !!$this->triggerObj->{$this->syncProperty} ? 1 : 0;

        $parents->each(function($parent) use ($triggerValue) {

            $this->logger('Checking parent status for: ' . json_encode($parent));

            // Normalize 0/false/null
            $parentValue = !!$parent->{$this->syncProperty} ? 1 : 0;

            // Short-circuit on the first non-homogenous match
            if ($parentValue !== $triggerValue) {
                $this->logger(
                    "short circuit: parent $this->syncProperty value ($parentValue) ".
                    " does not equal trigger object value ($triggerValue)"
                );
                return;
            }

        });

        // If all parents share the same status, sync child
        $this->logger(
            "All parents share same $this->syncProperty value ($triggerValue), syncing " .
            get_class($this->triggerObj) . " (ID: $this->triggerObj->id).."
        );

        $childRecord->update([$this->syncProperty => $triggerValue]);
    }

    /**
     * Reflect the model by attribute
     *
     * @param Model $model The Model object to retrieve children for
     */
    private function getChildrenByReflection(Model $model): Collection
    {
        return $this->reflector($model, SyncTo::class);
    }

    /**
     * Reflect the model by attribute
     *
     * @param Model $model The Model object to retrieve parents for
     */
    private function getParentsByReflection(Model $model): Collection
    {
        return $this->reflector($model, SyncFrom::class);
    }

    /**
     * Collect related models by method attribute
     *
     * @param Model $model The Model object to reflect
     * @param string $attributeClass The method attribute to filter by
     *
     * @return Collection A Collection of all related items
     */
    private function reflector(Model $model, string $attributeClass): Collection
    {
        $relatedItems = [];

        collect(
            // Get the model's public methods
            (new \ReflectionClass($model))
                ->getMethods(\ReflectionMethod::IS_PUBLIC)
        )
            ->filter(function (\ReflectionMethod $method) use ($attributeClass) {
                // Filter the method list by the requested attribute
                return $method->getAttributes($attributeClass);
            })
            ->each(function ($method) use ($model, &$relatedItems) {
                // Compile the list of items using the model method
                $methodName = $method->name;
                $relatedItems = [
                    ...$relatedItems,
                    ...$model->$methodName()->get()
                ];
            });
            
        return collect($relatedItems);
    }
}
