<?php
declare(strict_types=1);

namespace Arrayiterator\AggregatorCpSdk\Helper\Image\Exceptions;

use InvalidArgumentException;
use JetBrains\PhpStorm\Pure;
use Throwable;

class ImageFileNotFoundException extends InvalidArgumentException
{
    #[Pure] public function __construct(
        ?string $file = null,
        $code = 0,
        Throwable $previous = null
    ) {
        $message = $file
            ? sprintf('File %s has not found or is not readable.', $file)
            : 'File has not found or is not readable';
        parent::__construct($message, $code, $previous);
    }
}
