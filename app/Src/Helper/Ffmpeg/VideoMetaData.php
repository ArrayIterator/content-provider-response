<?php
declare(strict_types=1);

namespace Arrayiterator\AggregatorCpSdk\Helper\Ffmpeg;

use InvalidArgumentException;

class VideoMetaData
{
    private ?int $frameCount = null;
    private array $fileNames = [];
    private ?int $duration = null;

    /**
     * @param FrameAncestor $frameAncestor
     * @param string $sourceOriginalFileName
     * @param string $sourceVideoFile
     * @internal
     */
    public function __construct(
        public FrameAncestor $frameAncestor,
        public string $sourceOriginalFileName,
        public string $sourceVideoFile
    ) {
        if ((debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class']??null) !== FrameAncestor::class) {
            throw new InvalidArgumentException(
                'Video meta data only allowed via'
            );
        }
    }

    /**
     * @return int
     */
    public function getFrameCount(): int
    {
        if (is_int($this->frameCount)) {
            return $this->frameCount;
        }
        $this->frameCount = 0;
        $command = $this->frameAncestor->generateFrameCountCommand($this->sourceVideoFile);
        $count = $this->frameAncestor->shellString($command);
        $count = trim((string) $count);
        if (is_numeric($count)) {
            $this->frameCount = (int) $count;
        }
        return $this->frameCount;
    }

    public function getDuration(): int
    {
        if (is_int($this->duration)) {
            return $this->duration;
        }

        $this->duration = 0;
        $command = $this->frameAncestor->generateDurationCommand($this->sourceVideoFile);
        $count = $this->frameAncestor->shellString($command);
        $count = trim((string) $count);
        if (is_numeric($count)) {
            $this->duration = (int) $count;
        }
        return $this->duration;
    }

    public function getFrameInSecond(int $second) : ?string
    {
        if (isset($this->fileNames[$second])) {
            return $this->fileNames[$second]?:null;
        }

        $duration = $this->getDuration();
        $second = $duration >= $second ? $second : $duration;
        $fileName = $this->frameAncestor->generateImageFileName();
        $command = $this->frameAncestor->generateFrameCommand(
            $this->sourceVideoFile,
            $second,
            1,
            $fileName
        );
        $command = "$command &> /dev/null";
        $this->frameAncestor->shellString($command);
        $this->fileNames[$second] = false;
        if (is_file($fileName)) {
            $this->fileNames[$second] = $fileName;
        }
        return $this->fileNames[$second]?:null;
    }

    /**
     * @return array<int, string>
     */
    public function getGeneratedFileNames(): array
    {
        return $this->fileNames;
    }

    public function __destruct()
    {
        if (str_starts_with($this->sourceVideoFile, $this->frameAncestor->video_cache_directory)) {
            unlink($this->sourceVideoFile);
        }

        foreach ($this->getGeneratedFileNames() as $file) {
            if (is_file($file) && is_writable($file)) {
                 unlink($file);
            }
        }
    }
}
