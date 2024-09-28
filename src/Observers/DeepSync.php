<?php

declare(strict_types=1);

namespace CTanner\LaravelDeepSync\Observers;

use App\Attributes\CascadeFrom;
use App\Attributes\CascadeTo;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;

class DeepSync implements ShouldHandleEventsAfterCommit
{
    private $triggerObj;

    public function __construct($triggerObj)
    {
        $this->triggerObj = $triggerObj;

        // Add context to log entries for traceability
        Context::add('metadata', [
            'source'    => 'DeepSync',
            'trigger'   => get_class($this->triggerObj),
            'objId'     => $this->triggerObj->id,
            'traceId'   => Str::uuid()->toString()
        ]);
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
        // $this->triggerObj = $triggerObj;

        if (!$this->triggerObj->syncable) {
            return;
        }

        foreach ($this->triggerObj->syncable as $property => $targetValue) {

            // Only trigger if the object is moving to the desired state

            if (
                $this->triggerObj->$property == $targetValue 
                && $this->triggerObj->getOriginal($property) !== $targetValue
            ) {

                $this->logger(get_class($this->triggerObj) ." (ID: {$this->triggerObj->id}) triggered a sync event");

                $this->handleAction('handleStateChange');
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

        $this->handleAction('handleDelete');
    }

    /**
     * Log a message if enabled
     * 
     * @param string $msg The log message
     * 
     * @return void
     */
    public function logger(string $msg): void
    {
        if (!config('deepsync.logging.enabled')) {
            return;
        }

        $logLevel = config('deepsync.logging.log_level', 'debug');

        Log::$logLevel($msg);
    }

    /**
     * Determines how to handle the event based on the Model type
     *
     * @param string $actionType The action type
     * @param Model $triggerObj The Model object triggering the event
     *
     * @return void
     */
    private function handleAction(string $actionType): void
    {
        $this->logger("Beginning reflectionIterator for ". get_class($this->triggerObj) ." ...");

        $this->reflectionIterator([$this, $actionType]);
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
        if ($children = $this->getChildrenByReflection($this->triggerObj)) {

            $children->each(function($child) use ($callback) {

                $this->logger('Checking child: ' . json_encode($child));

                if ($parents = $this->getParentsByReflection($child)) {

                    $this->logger('Found parents for child:');

                    $parents->each(function ($item) {
                        $this->logger(json_encode($item));
                    });

                    $callback($parents, $child, false);

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
     * @param bool $hydrateParent Whether or not we need to fetch the parent record from CurriculumMapListType metadata
     *
     * @return void
     */
    private function handleDelete(Collection $parents, Model $childRecord, bool $hydrateParent): void
    {
        $parents->each(function($parent) use ($hydrateParent) {

            $parentRecord = ($hydrateParent)
                ? app($parent->model_type)->find($parent->parent_id)
                : $parent;

            if ($parentRecord) {
                // Short-circuit on the first live parent
                if ($parentRecord->deleted_at === null) {
                    return;
                }
            }

        });

        // If all parents are deleted, delete child

        $this->logger(get_class($childRecord) . " (ID: $childRecord->id) has no more live parents, deleting..");
        
        $childRecord->delete();
    }

    /**
     * Handles active status changes on children if all parents share the same status
     *
     * @param Collection $parents A collection of parent models to check
     * @param Model $childRecord The related child record to modify
     * @param bool $hydrateParent Controls whether the parent record will be hydrated from object metadata
     * @param Model $triggerObj The model triggering the event
     *
     * @return void
     */
    private function handleStateChange(Collection $parents, Model $childRecord, bool $hydrateParent): void
    {
        // Handle 0/false/null
        $triggerIsActiveValue = !!$this->triggerObj->is_active ? 1 : 0;

        $parents->each(function($parent) use ($hydrateParent, $triggerIsActiveValue) {

            $parentRecord = ($hydrateParent)
                ? app($parent->model_type)->find($parent->parent_id)
                : $parent;

            $this->logger('Checking parent status for: ' . json_encode($parent));

            if ($parentRecord) {

                // Handle 0/false/null
                $parentIsActiveValue = !!$parentRecord->is_active ? 1 : 0;

                // Short-circuit on the first non-homogenous match
                if ($parentIsActiveValue !== $triggerIsActiveValue) {
                    $this->logger(
                        'short circuit: parent is_active value ' . $parentIsActiveValue .
                        ' does not equal trigger object is_active value ' . $triggerIsActiveValue
                    );
                    return;
                }
            }
        });

        // If all parents share the same status, sync child
        $this->logger(
            'All parents share same is_active value (' . $triggerIsActiveValue . '), syncing "' .
            get_class($childRecord) . " (ID: $childRecord->id).."
        );

        $childRecord->update(['is_active' => 0]);
    }

    /**
     * Reflect the model by attribute
     *
     * @param Model $model The Model object to retrieve children for
     */
    private function getChildrenByReflection(Model $model): Collection
    {
        return $this->reflector($model, CascadeTo::class);
    }

    /**
     * Reflect the model by attribute
     *
     * @param Model $model The Model object to retrieve parents for
     */
    private function getParentsByReflection(Model $model): Collection
    {
        return $this->reflector($model, CascadeFrom::class);
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
