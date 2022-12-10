<?php
declare(strict_types=1);

namespace Arrayiterator\AggregatorCpSdk\Helper\Ffmpeg;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

class FrameAncestor
{
    const GENERATE_COMMANDS = '%ffmpeg% -i %input% -movflags +faststart -ss %second% -vframes %count% %out%';

    const MAX_READ_BYTES = 4096 * 1024 * 1024;
    private array $required_binaries = [
        'ffprobe',
        'ffmpeg'
    ];

    /**
     * @var string
     */
    private string $which = '/usr/bin/which';

    private ?array $binary = null;
    public string $video_cache_directory;
    public string $image_cache_directory;

    public function __construct()
    {
        $videoCacheDir = sys_get_temp_dir() . '/ffmpeg-video';
        $imageCacheDir = sys_get_temp_dir() . '/ffmpeg-image';
        if (!is_dir($imageCacheDir)) {
            mkdir($imageCacheDir, 0755, true);
        }
        if (!is_dir($imageCacheDir)) {
            mkdir($imageCacheDir, 0755, true);
        }

        $this->video_cache_directory = realpath($videoCacheDir)?:$videoCacheDir;
        $this->image_cache_directory = realpath($imageCacheDir)?:$imageCacheDir;
    }


    /**
     * @param string $command command could not contain rm|del|ln|remove
     * @param bool $asString
     *
     * @return array|false|string
     */
    public function shell(string $command, bool $asString = true): bool|array|string
    {
        if (preg_match('~^(.+[\\\/]?)?(del[^\s]*|rm[^\s]*|rem[^\s]*|ln)(\s+|\s*$)~i', $command)) {
            throw new RuntimeException(
                'Can not execute command: '. $command
            );
        }
        $res = shell_exec($command);
        if (is_string($res)) {
            $res = trim($res);
        }
        if (!is_string($res)) {
            return false;
        }
        if ($asString) {
            return $res;
        }
        return array_map('trim', explode("\n", $res));
    }

    /**
     * @param string $command
     *
     * @return array|false
     */
    public function shellArray(string $command): bool|array
    {
        return $this->shell($command, false);
    }

    /**
     * @param string $command
     *
     * @return false|string
     */
    public function shellString(string $command): bool|string
    {
        return $this->shell($command);
    }

    /**
     * @return array{"ffprobe":string|false,"ffmpeg":string|false}
     */
    private function getBinaries(): array
    {
        if (is_array($this->binary)) {
            return $this->binary;
        }
        $this->binary = [];
        foreach ($this->required_binaries as $binary) {
            $this->binary[$binary] = false;
            $binaryShell = null;
            if (is_executable($this->which)) {
                $command = sprintf("%s $binary", $this->which);
                $binaryShell = $this->shellString($command);
            }
            $binaryShell = $binaryShell ?: null;
            if (!$binaryShell) {
                foreach ([
                    "/usr/local/bin/$binary",
                    "/opt/local/bin/$binary",
                    "/usr/bin/$binary",
                    "/bin/$binary",
                ] as $p_search) {
                    if (file_exists($p_search) && is_executable($p_search)) {
                        $binaryShell = $p_search;
                        break;
                    }
                }
            }
            if ($binaryShell) {
                $this->binary[$binary] = $binaryShell;
            }
        }

        return $this->binary;
    }

    public function getFfprobe()
    {
        return $this->getBinaries()['ffprobe'];
    }

    public function getFfmpeg()
    {
        return $this->getBinaries()['ffmpeg'];
    }

    public function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ??
               'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36';
    }

    public function generateFrameCountCommand(string $videoFile): string
    {
        $videoFile = trim($videoFile);
        if (str_contains($videoFile, "\n")) {
            throw new InvalidArgumentException(
                'Input could not contain new line'
            );
        }

        $ffProbe = $this->getFfprobe();
        if (!$ffProbe) {
            throw new RuntimeException(
                'FFPROBE does not exists'
            );
        }

        return sprintf(
            "'%s' -v error -select_streams v:0 -count_packets -show_entries stream=nb_read_packets -of csv=p=0 '%s'",
            addcslashes($ffProbe, "\\'"),
            addcslashes($videoFile, "\\'")
        );
    }

    public function generateDurationCommand(string $videoFile): string
    {
        $videoFile = trim($videoFile);
        if (str_contains($videoFile, "\n")) {
            throw new InvalidArgumentException(
                'Input could not contain new line'
            );
        }
        $ffProbe = $this->getFfprobe();
        if (!$ffProbe) {
            throw new RuntimeException(
                'FFPROBE does not exists'
            );
        }
        return sprintf(
            "'%s' -v error -select_streams v:0 -count_packets -show_entries stream=duration -of csv=p=0 '%s'",
            addcslashes($ffProbe, "\\'"),
            addcslashes($videoFile, "\\'")
        );
    }

    /**
     * @param string $inputVideo
     * @param int $seconds
     * @param int $frame
     * @param string $output
     *
     * @return string
     */
    public function generateFrameCommand(
        string $inputVideo,
        int $seconds,
        int $frame,
        string $output
    ): string {
        $inputVideo = trim($inputVideo);
        if (str_contains($inputVideo, "\n")) {
            throw new InvalidArgumentException(
                'Input could not contain new line'
            );
        }
        $ffmpeg = $this->getFfmpeg();
        if (!$ffmpeg) {
            throw new RuntimeException(
                'FFPROBE does not exists'
            );
        }

        return str_replace(
            [
                '%ffmpeg%',
                '%input%',
                '%second%',
                '%count%',
                '%out%',
            ],
            [
                sprintf("'%s'", addcslashes($ffmpeg, "\\'")),
                sprintf("'%s'", addcslashes($inputVideo, "\\'")),
                $seconds,
                $frame,
                sprintf("'%s'", addcslashes($output, "\\'")),
            ],
            self::GENERATE_COMMANDS
        );
    }

    public function generateImageFileName(): string
    {
        $random = sha1(microtime());
        $imageDir = $this->image_cache_directory . DIRECTORY_SEPARATOR;
        if (!is_dir($imageDir)) {
            mkdir($imageDir, 0755, true);
        }
        $cacheFile = $imageDir . $random . '.jpg';
        while (file_exists($cacheFile)) {
            $random = sha1(microtime() . mt_rand(1000, 10000));
            $cacheFile = $imageDir . $random . '.jpg';
        }
        return $cacheFile;
    }

    public function generateVideoFileName(string $name): string
    {
        $name = basename($name);
        $random = sha1(microtime());
        $ext = pathinfo($name, PATHINFO_EXTENSION)??'video';
        $videoCacheDirectory = $this->video_cache_directory . DIRECTORY_SEPARATOR;
        $cacheFile = $videoCacheDirectory . $random . '.' . $ext;
        while (file_exists($cacheFile)) {
            $random = sha1(microtime() . mt_rand(1000, 10000));
            $cacheFile = $videoCacheDirectory . $random . '.' . $ext;
        }
        return $cacheFile;
    }

    /**
     * @param string $inputVideo
     *
     * @return VideoMetaData
     */
    public function createVideoMeta(
        string $inputVideo
    ): VideoMetaData {
        $originalInput = $inputVideo;
        if (preg_match('~^(https?)://~i', $inputVideo, $match)) {
            $header = "Accept-language: en-US,en;q=0.9,id;q=0.8,es;q=0.7\r\n"
                  . "Cache-Control: no-cache\r\n"
                  . "Pragma: no-cache\r\n"
                  . "Upgrade-Insecure-Requests: 1\r\n"
                  . "user-agent: ".trim($this->getUserAgent())."\r\n";
            if (strtolower($match[1]) === 'https') {
                $args = [
                    'https' => [
                        'method' => 'GET',
                        'header'=> $header,
                        'max_redirects' => 10,
                        'user_agent' => $this->getUserAgent(),
                        "verify_peer" => false,
                        "verify_peer_name" => false,
                    ]
                ];
            } else {
                $args = [
                    'http' => [
                        'method' => 'GET',
                        'header' => $header,
                        'max_redirects' => 10,
                        'user_agent' => $this->getUserAgent()
                    ]
                ];
            }
            $context = stream_context_create($args);
            try {
                set_error_handler(function ($errno, $errstr) use ($inputVideo) {
                    throw new RuntimeException(
                        sprintf(
                            'Can not get requested url %s with error: %s',
                            $inputVideo,
                            $errstr
                        ),
                        $errno
                    );
                });
                $socketURL = fopen($inputVideo, 'rb', false, $context);
            } catch (Throwable $e) {
                restore_error_handler();
                throw $e;
            }
            restore_error_handler();
            $tempFileName = $this->generateVideoFileName($inputVideo);
            try {
                set_error_handler(function ($errno, $errstr) use ($tempFileName) {
                    throw new RuntimeException(
                        sprintf(
                            'Can not create file %s with error: %s',
                            $tempFileName,
                            $errstr
                        ),
                        $errno
                    );
                });
                $videoSocket = fopen($tempFileName, 'wb');
            } catch (Throwable $e) {
                restore_error_handler();
                throw $e;
            }
            $size = 0;
            while (!feof($socketURL) && $size < self::MAX_READ_BYTES) {
                $content = fread($socketURL, 4096);
                $written = fwrite($videoSocket, $content);
                if ($written === false) {
                    break;
                }
                $size += $written;
            }
            fclose($socketURL);
            fclose($videoSocket);
            $inputVideo = realpath($tempFileName)?:$tempFileName;
        }
        if (!file_exists($inputVideo)) {
            throw new InvalidArgumentException(
                sprintf('File %s has not found', $inputVideo)
            );
        }
        if (!is_file($inputVideo)) {
            throw new InvalidArgumentException(
                sprintf('File %s is not a file', $inputVideo)
            );
        }

        return new VideoMetaData(
            $this,
            $originalInput,
            $inputVideo
        );
    }
}
