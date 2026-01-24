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
        $this->app->beforeResolving(DTOFormRequest::class, function (string $class, mixed $parameters, Application $app): void {
            if ($app->has($class)) {
                return;
            }

            $app->bind(
                $class,
                /** @phpstan-ignore return.type */
                fn (Application $container): DTOFormRequest => $class::fromRequest(
                    $container->make('request'),
                    $container->make(Factory::class),
                ),
            );
        });
    }
}
