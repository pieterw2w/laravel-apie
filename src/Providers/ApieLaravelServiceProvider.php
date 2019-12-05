<?php
namespace W2w\Laravel\Apie\Providers;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Message\ServerRequestInterface;
use W2w\Laravel\Apie\Controllers\SwaggerUiController;
use W2w\Laravel\Apie\Services\RequestToFacadeResponseConverter;
use W2w\Lib\Apie\Models\ApiResourceFacadeResponse;

/**
 * Service provider for Apie to link to Laravel (and that do not work in Lumen)
 */
class ApieLaravelServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $config = $this->app->get('apie.config');

        if ($config['bind-api-resource-facade-response']) {
            foreach ($config['resources'] as $resourceClass) {
                $this->registerResourceClass($resourceClass);
            }
        }

        if ($config['disable-routes']) {
            return;
        }
        if ($config['swagger-ui-test-page']) {
            $this->loadRoutesFrom(__DIR__ . '/../../config/routes-openapi.php');
        }
        $this->loadRoutesFrom(__DIR__ . '/../../config/routes.php');
    }

    public function register()
    {
        // fix for https://github.com/laravel/framework/issues/30415
        $this->app->extend(
            ServerRequestInterface::class,
            function (ServerRequestInterface $psrRequest) {
                $route = $this->app->make('request')->route();
                if ($route) {
                    $parameters = $route->parameters();
                    foreach ($parameters as $key => $value) {
                        $psrRequest = $psrRequest->withAttribute($key, $value);
                    }
                }
                return $psrRequest;
            }
        );

        $this->app->bind(RequestToFacadeResponseConverter::class);

        $this->app->bind(
            SwaggerUiController::class,
            function () {
                $urlGenerator = $this->app->get(UrlGenerator::class);
                return new SwaggerUiController($urlGenerator, __DIR__ . '/../../resources/open-api.html');
            }
        );

        $this->app->bind(ApiResourceFacadeResponse::class, function () {
            /** @var ServerRequestInterface $request */
            $request = $this->app->get(ServerRequestInterface::class);

            /** @var RequestToFacadeResponseConverter $converter */
            $converter = $this->app->get(RequestToFacadeResponseConverter::class);

            return $converter->convertUnknownResourceClassToResponse($request);
        });
    }

    public function registerResourceClass(string $resourceClass)
    {
        $this->app->bind($resourceClass, function () use ($resourceClass) {
            /** @var ServerRequestInterface $request */
            $request = $this->app->get(ServerRequestInterface::class);

            /** @var RequestToFacadeResponseConverter $converter */
            $converter = $this->app->get(RequestToFacadeResponseConverter::class);

            return $converter->convertRequestToResponse($resourceClass, $request)->getResource();
        });
    }
}
