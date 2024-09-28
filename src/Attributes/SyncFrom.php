<?php

namespace CTanner\LaravelDeepSync\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class SyncFrom
{
    /**
     * This attribute is used by DeepSyncObserver to determine
     * parent/child relationships. Model methods marked with this
     * attribute will be used to compile a list of DIRECT PARENTS
     * that should be checked before changing state or being deleted
     * when triggered by another parent.
     */
}
