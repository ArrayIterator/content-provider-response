<?php
declare(strict_types=1);

namespace Arrayiterator\AggregatorCpSdk\Helper\Image;

use Psr\Http\Message\StreamInterface;
use Arrayiterator\AggregatorCpSdk\Helper\Image\Exceptions\UnsupportedAdapter;
use Arrayiterator\AggregatorCpSdk\Helper\Image\Adapter\Gd;
use Arrayiterator\AggregatorCpSdk\Helper\Image\Adapter\ImageAdapterInterface;
use Arrayiterator\AggregatorCpSdk\Helper\Image\Adapter\Imagick;

class ResizerFactory implements ResizerFactoryInterface
{
    const USE_GD = 1;
    const USE_IMAGICK = 2;

    /**
     * @var int|false|null USE_GD|USE_IMAGICK
     */
    private static int|null|false $imageGenerationMode = null;
    private static bool $GdExists = false;
    private static bool $ImagickExists = false;
    public function __construct()
    {
        if (self::$imageGenerationMode === null) {
            self::$GdExists            = extension_loaded('gd');
            self::$ImagickExists       = extension_loaded('imagick');
            self::$imageGenerationMode = self::$ImagickExists
                ? self::USE_IMAGICK
                : (self::$GdExists ? self::USE_GD : false);
        }

        if (self::$imageGenerationMode === false) {
            throw new UnsupportedAdapter(
                'Extension gd or imagick has not been installed on the system.'
            );
        }
    }

    /**
     * @param resource|string|StreamInterface $source
     *
     * @return ImageAdapterInterface
     */
    public function create(mixed $source) : ImageAdapterInterface
    {
        return self::$imageGenerationMode === self::USE_IMAGICK
            ? new Imagick($this, $source)
            : new Gd($this, $source);
    }

    /**
     * @param int $used
     * @param mixed $source
     *
     * @return ImageAdapterInterface
     */
    public function possibleUse(int $used, mixed $source): ImageAdapterInterface
    {
        if ($used === self::USE_GD && self::$GdExists) {
            return new Gd($this, $source);
        }
        if ($used === self::USE_IMAGICK && self::$ImagickExists) {
            return new Imagick($this, $source);
        }
        return $this->create($source);
    }
}
