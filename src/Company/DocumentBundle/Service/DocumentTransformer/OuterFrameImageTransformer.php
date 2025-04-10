<?php

namespace Company\DocumentBundle\Service\DocumentTransformer;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Symfony\Component\HttpFoundation\File\File;

class OuterFrameImageTransformer implements DocumentTransformerInterface
{
    private const MIME_TYPE_PREFIX = 'image/';

    public function isSupported(File $file): bool
    {
        return self::MIME_TYPE_PREFIX === substr($file->getMimeType(), 0, strlen(self::MIME_TYPE_PREFIX));
    }

    public function transform(File $file, array $parameters): void
    {
        $filePath = $file->getRealPath();
        $sizeInfo = getimagesize($filePath);
        $width = $sizeInfo[0];
        $height = $sizeInfo[1];

        $imagine = new Imagine();
        $thumbnail = $imagine->open($filePath);
        if ($width <= $parameters['width'] && $height <= $parameters['height']) {
            return;
        }

        $newWidth = (int) (round($width * $parameters['height'] / $height));

        $sizeBox = ($newWidth <= $parameters['width'])
            ? new Box($newWidth, $parameters['height'])
            : new Box($parameters['width'], (int) (round($height * $parameters['width'] / $width)));

        $thumbnail->resize($sizeBox);
        $thumbnail->save($filePath);
    }
}
