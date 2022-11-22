<?php
declare(strict_types=1);

namespace Arrayiterator\AggregatorCpSdk\Generator;

use Arrayiterator\AggregatorCpSdk\Http\Code;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class Json
{
    public static function encode(
        $data = null,
        int $code = 200,
        ResponseInterface $response = null,
        int $opt = JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT
    ) : ResponseInterface {
        if (!$data) {
            $data = Code::statusMessage($code);
        }
        $response = (new HttpFactory)->createResponse($code)->withHeader(
            'Content-Type',
            'application/json'
        );
        if ($code < 300) {
            if (!is_array($data) || !isset($data['data']) || count($data) !== 1) {
                $data = ['data' => $data];
            }
        } else {
            if ($data instanceof Throwable) {
                $data = [
                    'message' => $data->getMessage(),
                    'file' => $data->getFile(),
                    'line' => $data->getLine(),
                    'trace' => $data->getTrace()
                ];
            } elseif (is_string($data)) {
                $data = ['message' => $data];
            } elseif (is_array($data)) {
                $found = false;
                if (!isset($data['message']) || !is_string($data['message'])) {
                    foreach ($data as $datum) {
                        if (is_string($datum)) {
                            $data = ['message' => $datum] + $data;
                            $found = true;
                            break;
                        }
                    }
                } else {
                    $found = true;
                }
                if (!$found) {
                    $data = ['message' => Code::statusMessage($code) ] + $data;
                }
            } else {
                $data = ['message' => (string) $data];
            }
        }
        $response
            ->getBody()
            ->write(json_encode(
                $data,
                $opt
            ));
        return $response;
    }
}
