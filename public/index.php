<?php
declare(strict_types=1);

use Slim\Factory\AppFactory;

require dirname(__DIR__) .'/vendor/autoload.php';
ob_start();
$app = AppFactory::create();
try {
    require_once dirname(__DIR__) . '/app/Routes.php';
    $app->run();
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

