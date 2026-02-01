<?php

declare(strict_types=1);

namespace App\Contracts\BrickIdentification;

use Illuminate\Http\UploadedFile;

interface IdentifyBrickInterface
{
    public UploadedFile $image { get; }
}
