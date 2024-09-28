<?php

namespace CTanner\LaravelDeepSync\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class SyncTo
{
    /**
     * This attribute is used by DeepSyncObserver to determine
     * parent/child relationships. Model methods marked with this
     * attribute will be used to compile a list of DIRECT CHILDREN
     * that should also be affected by state change or deletion
     * events when the parent model changes state or is deleted.
     */
}
