<?php

namespace Company\ExportBundle\Presentation;

class HttpHeaders
{
    public const NO_CACHE_HEADERS_FOR_FILES = [
        'Last-Modified' => 'Wed, 11 Jan 1984 05:00:00 GMT',
        'Cache-Control' => 'max-age=0, no-store, no-cache, must-revalidate, proxy-revalidate, private',
        'Pragma' => 'max-age=0, no-store, no-cache, must-revalidate, proxy-revalidate, private',
        'Expires' => '-1',
    ];
}
