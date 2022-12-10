<?php
declare(strict_types=1);

namespace Arrayiterator\AggregatorCpSdk\Endpoints;

use Arrayiterator\AggregatorCpSdk\Endpoint;
use Arrayiterator\AggregatorCpSdk\Generator\Json;
use GuzzleHttp\Psr7\UploadedFile;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Throwable;

class Content extends Endpoint
{
    public function create(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) : ResponseInterface {
        /**
         * @var UploadedFile[] $files
         */
        $files = $request->getUploadedFiles();
        $upload = [
            'file' => null,
            'cover_image' => null,
        ];
        foreach ($upload as $name => $item) {
            $item = $files[$name] ?? null;
            if ($item) {
                preg_match('~^(.+)\.([a-z]+)$~i', $item->getClientFilename(), $match);
                if (!empty($match[2])) {
                    $item = md5($match[1].microtime()) . ($name === 'file' ? '.mp4' : '.jpg');
                } else {
                    $item = md5($item->getClientFilename().microtime()) . ($name === 'file' ? '.mp4' : '.jpg');
                }
                $upload[$name] = $item;
            }
        }
        foreach ($files as $file) {
            try {
                $path = sys_get_temp_dir() . '/' . md5($file->getClientFilename());
                $file->moveTo($path);
                if (is_file($path)) {
                    @unlink($path);
                }
            } catch (Throwable $e) {
                // pass
            }
        }

        $body = $request->getParsedBody();
        $channel_id = $body['channel_id']??null;
        $provider_id = $body['cp_channel_id']??null;
        if (empty($channel_id)) {
            return Json::encode(
                [
                    'message' => 'Channel ID could not empty',
                    'error' => 'EMPTY_CHANNEL_ID'
                ],
                428
            );
        }
        if (empty($provider_id)) {
            return Json::encode(
                [
                    'message' => 'Content Provider Channel ID could not empty',
                    'error' => 'EMPTY_CP_CHANNEL_ID'
                ],
                428
            );
        }

        if (!is_numeric($channel_id) || str_contains($channel_id, '.')) {
            return Json::encode(
                [
                    'message' => 'Channel ID must be integer',
                    'error' => 'INVALID_CHANNEL_ID'
                ],
                412
            );
        }

        if (!is_numeric($provider_id) || str_contains($provider_id, '.')) {
            return Json::encode(
                [
                    'message' => 'Content Provider Channel ID must be integer',
                    'error' => 'INVALID_CP_CHANNEL_ID'
                ],
                412
            );
        }

        $channel_id = (int) $channel_id;
        $provider_id = (int) $provider_id;
        if ($channel_id === 404) {
            return Json::encode(
                [
                    'message' => 'Channel not found',
                    'error' => 'NOTFOUND_CHANNEL_ID'
                ],
                412
            );
        }
        if ($channel_id !== (int) strrev((string) $provider_id)) {
            return Json::encode(
                [
                    'message' => 'Channel Mismatch',
                    'error' => 'MISMATCH_CHANNEL_ID'
                ],
                412
            );
        }

        $args = [
            'channel_id' => $channel_id,
            'cp_channel_id' => $provider_id,
            'file'   => (isset($files['file']) ? $files['file']->getClientFilename() : null),
            'cover_image' => (isset($files['cover_image']) ? $files['cover_image']->getClientFilename() : null),
        ];
        if (empty($args['file'])) {
            return Json::encode(
                [
                    'message' => 'Uploaded file is empty',
                    'error' => 'EMPTY_FILE'
                ],
                428
            );
        }
        $res = $args;
        foreach ($args as $key => $item) {
            if ($item && ($key === 'file' || $key === 'cover_image')) {
                continue;
            }
            if (!isset($body[$key])) {
                unset($args[$key]);
            }
        }
        unset($res['banner'], $res['file']);
        $res['file_url'] = !$upload['file'] ? null : $request->getUri()->withQuery('')->withPath("/images/{$upload['file']}");
        $res['cover_image'] = !$upload['cover_image'] ? null : $request->getUri()->withQuery('')->withPath("/images/{$upload['cover_image']}");
        $res = ['content_id' => 1]+ $res;
        $data = [
            'request' => $args,
            'result' => $res,
        ];
        return Json::encode($data, 200, $response);
    }

    public function update(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) : ResponseInterface {
        /**
         * @var UploadedFile[] $files
         */
        $files = $request->getUploadedFiles();
        $upload = [
            'file' => null,
            'cover_image' => null,
        ];
        foreach ($upload as $name => $item) {
            $item = $files[$name] ?? null;
            if ($item) {
                preg_match('~^(.+)\.([a-z]+)$~i', $item->getClientFilename(), $match);
                if (!empty($match[2])) {
                    $item = md5($match[1].microtime()) . ($name === 'file' ? '.mp4' : '.jpg');
                } else {
                    $item = md5($item->getClientFilename().microtime()) . ($name === 'file' ? '.mp4' : '.jpg');
                }
                $upload[$name] = $item;
            }
        }
        foreach ($files as $file) {
            try {
                $path = sys_get_temp_dir() . '/' . md5($file->getClientFilename());
                $file->moveTo($path);
                if (is_file($path)) {
                    @unlink($path);
                }
            } catch (Throwable $e) {
                // pass
            }
        }

        $body = $request->getParsedBody();
        $channel_id = $body['channel_id']??null;
        $provider_id = $body['cp_channel_id']??null;
        $content_id = $body['content_id']??null;
        if (empty($channel_id)) {
            return Json::encode(
                [
                    'message' => 'Channel ID could not empty',
                    'error' => 'EMPTY_CHANNEL_ID'
                ],
                428
            );
        }
        if (empty($provider_id)) {
            return Json::encode(
                [
                    'message' => 'Content Provider Channel ID could not empty',
                    'error' => 'EMPTY_CP_CHANNEL_ID'
                ],
                428
            );
        }
        if (empty($content_id)) {
            return Json::encode(
                [
                    'message' => 'Content ID could not empty',
                    'error' => 'EMPTY_CONTENT_ID'
                ],
                428
            );
        }

        if (!is_numeric($channel_id) || str_contains($channel_id, '.')) {
            return Json::encode(
                [
                    'message' => 'Channel ID must be integer',
                    'error' => 'INVALID_CHANNEL_ID'
                ],
                412
            );
        }

        if (!is_numeric($provider_id) || str_contains($provider_id, '.')) {
            return Json::encode(
                [
                    'message' => 'Content Provider Channel ID must be integer',
                    'error' => 'INVALID_CP_CHANNEL_ID'
                ],
                412
            );
        }

        if (!is_numeric($content_id) || str_contains($content_id, '.')) {
            return Json::encode(
                [
                    'message' => 'Content ID must be integer',
                    'error' => 'INVALID_CONTENT_ID'
                ],
                412
            );
        }

        $channel_id = (int) $channel_id;
        $provider_id = (int) $provider_id;
        $content_id = (int) $content_id;
        if ($channel_id === 404) {
            return Json::encode(
                [
                    'message' => 'Channel not found',
                    'error' => 'NOTFOUND_CHANNEL_ID'
                ],
                412
            );
        }
        if ($content_id === 404) {
            return Json::encode(
                [
                    'message' => 'Content ID not found',
                    'error' => 'NOTFOUND_CONTENT_ID'
                ],
                412
            );
        }

        if ($channel_id !== (int) strrev((string) $provider_id)) {
            return Json::encode(
                [
                    'message' => 'Channel Mismatch',
                    'error' => 'MISMATCH_CHANNEL_ID'
                ],
                412
            );
        }

        $args = [
            'content_id' => $content_id,
            'channel_id' => $channel_id,
            'cp_channel_id' => $provider_id,
            'file'   => (isset($files['file']) ? $files['file']->getClientFilename() : null),
            'cover_image' => (isset($files['cover_image']) ? $files['cover_image']->getClientFilename() : null),
        ];
        if (empty($args['file']) && empty($args['cover_image'])) {
            return Json::encode(
                [
                    'message' => 'Uploaded file or cover image is empty',
                    'error' => 'EMPTY_ATTACHMENT'
                ],
                428
            );
        }
        $res = $args;
        foreach ($args as $key => $item) {
            if ($item && ($key === 'file' || $key === 'cover_image')) {
                continue;
            }
            if (!isset($body[$key])) {
                unset($args[$key]);
            }
        }
        unset($res['banner'], $res['file']);
        $res['file_url']    = !$upload['file'] ? null : $request->getUri()->withQuery('')->withPath("/images/{$upload['file']}");
        $res['cover_image'] = !$upload['cover_image'] ? null : $request->getUri()->withQuery('')->withPath("/images/{$upload['cover_image']}");
        $res = ['content_id' => 1]+ $res;
        $data = [
            'request' => $args,
            'result' => $res,
        ];
        return Json::encode($data, 200, $response);
    }

    public function registerRoute(App $app)
    {
        $app->post('{p: [/]+}content/create[/]', [$this, 'create']);
        $app->post('{p: [/]+}content/update[/]', [$this, 'update']);
    }
}