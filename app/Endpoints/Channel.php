<?php
declare(strict_types=1);

namespace Arrayiterator\AggregatorCpSdk\Endpoints;

use Arrayiterator\AggregatorCpSdk\Endpoint;
use Arrayiterator\AggregatorCpSdk\Generator\Json;
use DateTimeImmutable;
use GuzzleHttp\Psr7\UploadedFile;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Throwable;

class Channel extends Endpoint
{
    /**
     * path : /channel/create
    request param :
    - name : string
    - description : string
    - logo : file
    - banner : file
    - subscription_period : integer
    - subscription_frequency : integer
    - subscription_price : integer
    - status : string
    - creator_name : string
    - slug : string
    - created_at : date
    - channel_id : integer
    response param :
    - success : boolean
    - channel_id : integer
     */
    public function create(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) : ResponseInterface {

        /**
         * @var UploadedFile[] $files
         */
        $files = $request->getUploadedFiles();
        $upload = [
            'logo' => null,
            'banner' => null,
        ];
        foreach ($upload as $name => $item) {
            $item = $files[$name] ?? null;
            if ($item) {
                preg_match('~^(.+)\.([a-z]+)$~i', $item->getClientFilename(), $match);
                if (!empty($match[2])) {
                    $item = md5($match[1].microtime()) . '.jpg';
                } else {
                    $item = md5($item->getClientFilename().microtime()) . '.jpg';
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
        $description = $body['description']??'';

        $subscription_period = $body['subscription_period']??'';
        $subscription_period = !is_string($subscription_period) ? '' : trim($subscription_period);
        $subscription_frequency = $body['subscription_frequency']??'';
        $subscription_frequency = !is_string($subscription_frequency) ? '' : trim($subscription_frequency);
        $subscription_price = $body['subscription_price']??'';
        $subscription_price = !is_string($subscription_price) ? '' : trim($subscription_price);

        $status = strtolower(trim((string)($body['status']??'')));
        $creator_name = trim((string)($body['creator_name']??''));
        $slug = trim((string)$body['slug']??'');
        $created_at = $body['created_at']??(new DateTimeImmutable())->format('Y-m-d H:i:s');
        $created_at = @strtotime($created_at)?:(new DateTimeImmutable())->getTimestamp();
        $created_at = date('c', $created_at);
        if (empty($channel_id)) {
            return Json::encode(
                [
                    'message' => 'Channel ID could not empty',
                    'error' => 'EMPTY_CHANNEL_ID'
                ],
                428
            );
        }

        if ($subscription_price === '') {
            return Json::encode(
                [
                    'message' => 'Subscription price could not empty',
                    'error' => 'EMPTY_SUBSCRIPTION_PRICE'
                ],
                428
            );
        }
        if ($subscription_period === '') {
            return Json::encode(
                [
                    'message' => 'Subscription period could not empty',
                    'error' => 'EMPTY_SUBSCRIPTION_PERIOD'
                ],
                428
            );
        }
        if ($subscription_frequency === '') {
            return Json::encode(
                [
                    'message' => 'Subscription frequency could not empty',
                    'error' => 'EMPTY_SUBSCRIPTION_FREQUENCY'
                ],
                428
            );
        }
        if (empty($creator_name)) {
            return Json::encode(
                [
                    'message' => 'Creator name could not empty',
                    'error' => 'EMPTY_CREATOR_NAME'
                ],
                412
            );
        }

        if (empty($status)) {
            return Json::encode(
                [
                    'message' => 'Status could not empty',
                    'error' => 'EMPTY_STATUS'
                ],
                412
            );
        }

        if (empty($slug)) {
            return Json::encode(
                [
                    'message' => 'Slug could not empty',
                    'error' => 'EMPTY_SLUG'
                ],
                412
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

        if (!is_numeric($subscription_period) || str_contains($subscription_period, '.')) {
            return Json::encode(
                [
                    'message' => 'Subscription period must be integer',
                    'error' => 'INVALID_SUBSCRIPTION_PERIOD'
                ],
                412
            );
        }
        if (!is_numeric($subscription_frequency) || str_contains($subscription_frequency, '.')) {
            return Json::encode(
                [
                    'message' => 'Subscription frequency must be integer',
                    'error' => 'INVALID_SUBSCRIPTION_FREQUENCY'
                ],
                412
            );
        }
        if (!is_numeric($subscription_price) || str_contains($subscription_price, '.')) {
            return Json::encode(
                [
                    'message' => 'Subscription price must be integer',
                    'error' => 'INVALID_SUBSCRIPTION_PRICE'
                ],
                412
            );
        }

        $channel_id = (int) $channel_id;
        $args = [
            'channel_id' => $channel_id,
            'description' => trim($description),
            'logo'   => (isset($files['logo']) ? $files['logo']->getClientFilename() : null),
            'banner' => (isset($files['banner']) ? $files['banner']->getClientFilename() : null),
            'subscription_period' => (int) $subscription_period,
            'subscription_frequency' => (int) $subscription_frequency,
            'subscription_price' => (int) $subscription_price,
            'status' => $status,
            'creator_name' => $creator_name,
            'slug' => $slug,
            'created_at' => $created_at,
        ];
        $res = ['channel_id' => $channel_id, 'cp_channel_id' => crc32((string) $channel_id)] + $args;
        foreach ($args as $key => $item) {
            if ($item && ($key === 'banner' || $key === 'logo')) {
                continue;
            }
            if (!isset($body[$key])) {
                unset($args[$key]);
            }
        }
        unset($res['banner'], $res['logo']);
        $res['logo_url'] = !$upload['logo'] ? null : $request->getUri()->withQuery('')->withPath("/images/{$upload['logo']}");
        $res['banner_url'] = !$upload['banner'] ? null : $request->getUri()->withQuery('')->withPath("/images/{$upload['banner']}");
        $data = [
            'request' => $args,
            'result' => $res,
        ];
        return Json::encode($data, 200, $response);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function update(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) : ResponseInterface {
        /**
         * @var UploadedFile[] $files
         */
        $files = $request->getUploadedFiles();
        $upload = [
            'logo' => null,
            'banner' => null,
        ];
        foreach ($upload as $name => $item) {
            $item = $files[$name] ?? null;
            if ($item) {
                preg_match('~^(.+)\.([a-z]+)$~i', $item->getClientFilename(), $match);
                if (!empty($match[2])) {
                    $item = md5($match[1].microtime()) . '.jpg';
                } else {
                    $item = md5($item->getClientFilename().microtime()) . '.jpg';
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
        $description = $body['description']??'';

        $subscription_period = $body['subscription_period']??'10';
        $subscription_period = !is_string($subscription_period) ? '10' : trim($subscription_period);
        $subscription_frequency = $body['subscription_frequency']??'10';
        $subscription_frequency = !is_string($subscription_frequency) ? '10' : trim($subscription_frequency);
        $subscription_price = $body['subscription_price']??'20000';
        $subscription_price = !is_string($subscription_price) ? '20000' : trim($subscription_price);

        $status = strtolower((string)($body['status']??'active'));
        $creator_name = trim((string)($body['creator_name']??'Test Creator'));
        $slug = trim((string)($body['slug']??md5((string) $channel_id)));
        $created_at = $body['created_at']??(new DateTimeImmutable())->format('Y-m-d H:i:s');
        $created_at = @strtotime($created_at)?:(new DateTimeImmutable())->getTimestamp();
        $created_at = date('c', $created_at);
        if (empty($channel_id)) {
            return Json::encode(
                [
                    'message' => 'Channel ID could not empty',
                    'error' => 'EMPTY_CHANNEL_ID'
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

        if (!is_numeric($subscription_period) || str_contains($subscription_period, '.')) {
            return Json::encode(
                [
                    'message' => 'Subscription period must be integer',
                    'error' => 'INVALID_SUBSCRIPTION_PERIOD'
                ],
                412
            );
        }
        if (!is_numeric($subscription_frequency) || str_contains($subscription_frequency, '.')) {
            return Json::encode(
                [
                    'message' => 'Subscription frequency must be integer',
                    'error' => 'INVALID_SUBSCRIPTION_FREQUENCY'
                ],
                412
            );
        }
        if (!is_numeric($subscription_price) || str_contains($subscription_price, '.')) {
            return Json::encode(
                [
                    'message' => 'Subscription price must be integer',
                    'error' => 'INVALID_SUBSCRIPTION_PRICE'
                ],
                412
            );
        }
        if (empty($creator_name)) {
            return Json::encode(
                [
                    'message' => 'Creator name could not empty',
                    'error' => 'EMPTY_CREATOR_NAME'
                ],
                412
            );
        }

        if (empty($status)) {
            return Json::encode(
                [
                    'message' => 'Status could not empty',
                    'error' => 'EMPTY_STATUS'
                ],
                412
            );
        }

        $channel_id = (int) $channel_id;
        if ($channel_id === 404) {
            return Json::encode(
                [
                    'message' => 'Channel not found',
                    'error' => 'NOTFOUND_CHANNEL_ID'
                ],
                412
            );
        }
        $upload['logo'] = $upload['logo']??md5((string)$channel_id).'.jpg';
        $upload['banner'] = $upload['banner']??md5((string)$channel_id).'.jpg';
        $args = [
            'channel_id' => $channel_id,
            'description' => trim($description),
            'logo'   => (isset($files['logo']) ? $files['logo']->getClientFilename() : null),
            'banner' => (isset($files['banner']) ? $files['banner']->getClientFilename() : null),
            'subscription_period' => (int) $subscription_period,
            'subscription_frequency' => (int) $subscription_frequency,
            'subscription_price' => (int) $subscription_price,
            'status' => $status,
            'creator_name' => $creator_name,
            'slug' => $slug,
            'created_at' => $created_at,
        ];
        $res = ['channel_id' => $channel_id, 'cp_channel_id' => crc32((string) $channel_id)] + $args;
        foreach ($args as $key => $item) {
            if ($item && ($key === 'banner' || $key === 'logo')) {
                continue;
            }
            if (!isset($body[$key])) {
                unset($args[$key]);
            }
        }

        unset($res['banner'], $res['logo']);
        $res['logo_url'] = $request->getUri()->withQuery('')->withPath("/images/{$upload['logo']}");
        $res['banner_url'] = $request->getUri()->withQuery('')->withPath("/images/{$upload['banner']}");
        $data = [
            'request' => $args,
            'result' => $res,
        ];
        return Json::encode($data, 200, $response);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function publish(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) : ResponseInterface {
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

        return Json::encode([
            'data' => [
                'request' => [
                    'channel_id' => $channel_id,
                ],
                'result' => [
                    'channel_id' => $channel_id,
                    'cp_channel_id' => $provider_id,
                ]
            ]
        ]);
    }

    public function registerRoute(App $app)
    {
        $app->post('{p: [/]+}channel/create[/]', [$this, 'create']);
        $app->post('{p: [/]+}channel/update[/]', [$this, 'update']);
        $app->post('{p: [/]+}channel/publish[/]', [$this, 'publish']);
    }
}
