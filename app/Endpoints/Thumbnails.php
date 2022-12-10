<?php
declare(strict_types=1);

namespace Arrayiterator\AggregatorCpSdk\Endpoints;

use Arrayiterator\AggregatorCpSdk\Endpoint;
use Arrayiterator\AggregatorCpSdk\Generator\Json;
use Arrayiterator\AggregatorCpSdk\Helper\Ffmpeg\FrameAncestor;
use Arrayiterator\AggregatorCpSdk\Helper\Image\Adapter\ImageAdapterInterface;
use Arrayiterator\AggregatorCpSdk\Helper\Image\ResizerFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\App;
use Throwable;

class Thumbnails extends Endpoint
{
    private ?string $videoFile = null;
    private ?string $uploadDir = null;
    private ?string $tempDir = null;

    public function generate(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $params = []
    ): ResponseInterface {
        /**
         * @global App $app
         */
        global $app;
        $basePath = $app?->getBasePath()?:'';
        $basePath = trim($basePath, '/');
        $queryParams = $request->getParsedBody();
        $auth = $request->getServerParams()['UPLOAD_AUTH']??null;
        if ($auth && ($queryParams['auth']??null) !== $auth) {
            return Json::encode(
                [
                    'message' => 'Unauthorized',
                    'error' => 'UNAUTHORIZED'
                ],
                401
            );
        }

        $targetPath = '/uploads';
        $publicDir = dirname($request->getServerParams()['SCRIPT_FILENAME']);
        $targetDir = $publicDir . $targetPath;
        $this->uploadDir = $targetDir;
        $video = $request->getUploadedFiles()['video']??null;
        if (!$video instanceof UploadedFileInterface) {
            return Json::encode(
                [
                    'message' => 'Video can not be empty',
                    'error' => 'EMPTY_VIDEO'
                ],
                428
            );
        }
        $mediaType = $video->getClientMediaType();
        if (!$mediaType || ! str_starts_with($mediaType, 'video/')) {
            return Json::encode(
                [
                    'message' => 'Uploaded file is not a video',
                    'error' => 'INVALID_MIMETYPE'
                ],
                428
            );
        }

        $screenshotSizeWidth  = $queryParams['width']??640;
        if (!is_numeric($screenshotSizeWidth)) {
            $screenshotSizeWidth = 640;
        }
        $screenshotSizeWidth = (int) $screenshotSizeWidth;
        if ($screenshotSizeWidth < 300) {
            $screenshotSizeWidth = 300;
        }
        if ($screenshotSizeWidth > 1280) {
            $screenshotSizeWidth = 1280;
        }
        $screenshotSizeHeight = $queryParams['width']??$screenshotSizeWidth;
        if (!is_numeric($screenshotSizeHeight)) {
            $screenshotSizeHeight = 640;
        }
        $screenshotSizeHeight = (int) $screenshotSizeHeight;
        if ($screenshotSizeHeight < 300) {
            $screenshotSizeHeight = 300;
        }
        if ($screenshotSizeHeight > 1280) {
            $screenshotSizeHeight = 1280;
        }
        $second = $queryParams['second']??1;
        if (!is_numeric($second)) {
            $second = 1;
        }
        $second = (int) $second;
        if ($second < 1) {
            $second = 1;
        }
        if ($second > 10) {
            $second = 10;
        }
        $frameAncestor = new FrameAncestor();
        $cacheDirectory = $frameAncestor->video_cache_directory;
        if (!is_dir($cacheDirectory)) {
            mkdir($cacheDirectory, 0755, true);
        }
        $name = sha1(microtime());
        $ext = explode('/', $mediaType)[1]??'mp4';
        $this->videoFile = "$targetDir/$name.$ext";
        $video->moveTo($this->videoFile);
        try {
            $metadata = $frameAncestor->createVideoMeta($this->videoFile);
            $frame = $metadata->getFrameInSecond($second);
            if (!$frame) {
                return Json::encode(
                    [
                        'message' => 'Can not generated thumbnail',
                        'error' => 'FAILED_GENERATION_VIDEO'
                    ],
                    417
                );
            }
            $imageName = basename($frame);
            $imageTarget = "$targetDir/$imageName";
            $factory = new ResizerFactory();
            $resizer = $factory
                ->create($frame)
                ->resize(
                    $screenshotSizeWidth,
                    $screenshotSizeHeight,
                    ImageAdapterInterface::MODE_CROP
                );
            $this->tempDir = $resizer?->tempDir;
            $result = $resizer->saveTo($imageTarget, 90, true);
            if (!$result) {
                return Json::encode(
                    [
                        'message' => 'Can not generated thumbnail',
                        'error' => 'FAILED_GENERATION_RESIZE'
                    ],
                    417
                );
            }

            $basePath = $basePath ? "$basePath/" : $basePath;
            // suddenly call destruct
            unset($resizer, $factory, $frameAncestor, $metadata);
            $path = "$basePath$targetPath/$imageName";
            $path = '/'.ltrim(preg_replace('~[\\\/]+~', '/', $path), '/');
            $path = str_replace('//', '/', $path);
            $urlPath = $request->getUri()
                ->withQuery('')
                ->withFragment('')
                ->withPath($path);
            return Json::encode([
                'width' => $result['width'],
                'height' => $result['height'],
                'type' => $result['type'],
                'path' => $urlPath->getPath(),
                'url' => $urlPath
            ]);
        } catch (Throwable $e) {
            return Json::encode([
                'message' => $e,
                'error' => 'SYSTEM_ERROR'
            ], 500);
        }
    }

    private function cleanDirectory(string $directory)
    {
        if (!is_dir($directory) || !is_writable($directory)) {
            return;
        }
        $uid = getmyuid();
        $directory = realpath($directory)?:$directory;
        set_error_handler(static function () {
        });
        $dir = @opendir($directory);
        restore_error_handler();
        if (!$dir) {
            return;
        }
        // 7 hours clean
        $seven_hours = strtotime('+7 hours');
        while (($file = readdir($dir))) {
            if ($file === '.' || $file === '..' || $file === '.gitkeep') {
                continue;
            }

            $file = $directory . DIRECTORY_SEPARATOR . $file;
            if (is_file($file)) {
                $time = filemtime($file);
                $owner = fileowner($file);
                if ($owner === $uid && ($time - $seven_hours) > 0) {
                    unlink($file);
                }
            }
        }
        closedir($dir);
    }

    public function __destruct()
    {
        if ($this->videoFile && file_exists($this->videoFile)) {
            unlink($this->videoFile);
            $this->videoFile = null;
        }
        if ($this->uploadDir && is_dir($this->uploadDir) && is_writable($this->uploadDir)) {
            $this->cleanDirectory($this->uploadDir);
        }
        if ($this->tempDir && is_dir($this->tempDir) && is_writable($this->tempDir)) {
            $this->cleanDirectory($this->tempDir);
        }
    }

    /**
     * @param App $app
     */
    public function registerRoute(App $app)
    {
        $app->post('{p: [/]+}video/thumbnail[/]', [$this, 'generate']);
    }
}
