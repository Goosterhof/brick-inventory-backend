<?php

declare(strict_types = 1);

namespace App\Contracts;

/**
 * Marker interface for Data DTOs eligible to feed ComputedResourceData.
 * Constrains the generic to prevent arbitrary objects from being passed.
 */
interface ResourceDataSourceInterface {}
