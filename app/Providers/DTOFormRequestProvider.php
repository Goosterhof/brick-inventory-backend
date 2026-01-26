<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Requests\DTOFormRequest;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Override;

final class DTOFormRequestProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->beforeResolving(DTOFormRequest::class, function (string $class, mixed $parameters, Application $application): void {
            if ($application->has($class)) {
                return;
            }

            $application->bind(
                $class,
                /** @phpstan-ignore return.type */
                fn (Application $application): DTOFormRequest => $class::fromRequest(
                    $application->make('request'),
                    $application->make(Factory::class),
                ),
            );
        });
    }
}
