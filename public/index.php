<?php
declare(strict_types=1);

use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;

require dirname(__DIR__) .'/vendor/autoload.php';
ob_start();
$app = AppFactory::create();
try {
    require_once dirname(__DIR__) . '/app/Routes.php';
    $serverRequestCreator = ServerRequestCreatorFactory::create();
    $request = $serverRequestCreator->createServerRequestFromGlobals();
    $serverPrams = $request->getServerParams();
    $request_uri = explode('?', $serverPrams['REQUEST_URI'])[0];
    $script_name = dirname($serverPrams['SCRIPT_NAME']);
    if ($request_uri !== $script_name && str_starts_with($request_uri, $script_name)) {
        $basePath = substr($request_uri, 0, strlen($script_name));
        if ($basePath !== '/' && ($basePath[0]??'') === '/') {
            $app->setBasePath($basePath);
        }
    }
    $app->run($request);
} catch (Throwable $e) {
    if (ob_get_level() > 0 && ob_get_length() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json', true, 500);
    echo json_encode([
        'message' => "Internal Server Error"
    ]);
}
__halt_compiler();

