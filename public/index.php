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
    $serverParams = $request->getServerParams();
    $request_uri = explode('?', $serverParams['REQUEST_URI'])[0];
    $script_name = dirname($serverParams['SCRIPT_NAME']);
    // use nginx fastcgi_param base_path /content-provider
    // if the location is on /content-provider
    // example
    /*
        server {
            # block server ....

            # .....
            # start here
            location ~ /content-provider(/|/?$) {
                root /path/to/app/public;
                index index.php;
                # this for basepath
                fastcgi_param base_path /content-provider;

                # php listener
                fastcgi_split_path_info ^(.+\.php)(/.+)$;
		        include fastcgi_params;
		        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
		        fastcgi_param SCRIPT_NAME $fastcgi_script_name;
		        fastcgi_index index.php;

		        fastcgi_intercept_errors off;
		        fastcgi_buffers 16 16k;
		        fastcgi_buffer_size 32k;
        		fastcgi_pass unix:/var/run/php/socket.sock;
                try_files /index.php$is_args$args =404;
            }
        }
     */
    $basePath = getenv('base_path')?:($serverParams['base_path']??null);
    if ($basePath) {
        $basePath = preg_replace('~[\\\/]+~', '/', $basePath);
        $basePath = ltrim($basePath, '/');
        $basePath = $basePath ? "/$basePath" : $basePath;
        $app->setBasePath($basePath);
    } elseif ($request_uri !== $script_name && str_starts_with($request_uri, $script_name)) {
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

