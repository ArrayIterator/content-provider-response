<?php
declare(strict_types=1);

use Arrayiterator\AggregatorCpSdk\Endpoint;
use Arrayiterator\AggregatorCpSdk\Generator\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;

if (!isset($app) || !$app instanceof App) {
    return;
}
$app->add(function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($app) {
    try {
        $namespace = "Arrayiterator\\AggregatorCpSdk\\Endpoints";
        foreach (new DirectoryIterator(__DIR__.'/src/Endpoints') as $endpoint) {
            if (!$endpoint->isFile() || $endpoint->isDot() || $endpoint->getExtension() !== 'php') {
                continue;
            }
            $baseName = substr($endpoint->getBasename(), 0, -4);
            if (!preg_match('~^[A-Z]+([A-Z0-9_])$~i', $baseName)) {
                continue;
            }
            if (!class_exists("$namespace\\$baseName") || !is_subclass_of(
                    "$namespace\\$baseName",
                    Endpoint::class
                )) {
                continue;
            }
            $className = "$namespace\\$baseName";
            /**
             * @var Endpoint $className
             */
            $className = new $className($app);
            $className->register();
        }
        $app->any(
            '{param: .*}',
            function (ServerRequestInterface  $request, ResponseInterface $response) {
                return Json::encode('Endpoint Not Found', 404, $response);
            });
        return $handler->handle($request);
    } catch (Throwable $e) {
        return Json::encode($e, 500);
    }
});
