<?php

namespace Company\DocumentBundle\Service\DocumentTransformer;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Symfony\Component\HttpFoundation\File\File;

class InnerFrameImageTransformer implements DocumentTransformerInterface
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

        $newWidth = (int) (round($width * $parameters['height'] / $height));

        if ($newWidth < $parameters['width']) {
            $newHeight = (int) (round($height * $parameters['width'] / $width));
            $newWidth = $parameters['width'];
        } else {
            $newHeight = $parameters['height'];
        }

        $sizeBox = new Box($newWidth, $newHeight);
        $thumbnail->resize($sizeBox);

        $x = (int)round(($newWidth - $parameters['width']) / 2);
        $y = (int)round(($newHeight - $parameters['height']) / 2);
        $thumbnail->crop(new Point($x, $y), new Box($parameters['width'], $parameters['height']));

        $thumbnail->save($filePath);
    }
}
