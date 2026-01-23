<?php

declare(strict_types=1);

namespace App\Http\Resources;

/**
 * Sentinel class representing a missing/unloaded value.
 *
 * When used in a ResourceData class, properties with this value
 * will be excluded from the array output.
 */
final readonly class MissingValue {}
