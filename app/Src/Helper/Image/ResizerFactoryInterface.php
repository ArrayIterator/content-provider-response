<?php
declare(strict_types=1);

namespace Arrayiterator\AggregatorCpSdk\Helper\Image;

use Arrayiterator\AggregatorCpSdk\Helper\Image\Adapter\ImageAdapterInterface;

interface ResizerFactoryInterface
{
    /**
     * @param mixed $source
     *
     * @return ImageAdapterInterface
     */
    public function create(mixed $source) : ImageAdapterInterface;
}
