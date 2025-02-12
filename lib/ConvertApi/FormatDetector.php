<?php

namespace ConvertApi;

class FormatDetector
{
    function __construct($resource)
    {
        $this->resource = $resource;
    }

    function run()
    {
        if (is_a($this->resource, '\ConvertApi\FileUpload'))
            return $this->resource->getFileExt();

        return pathinfo((string) $this->path(), PATHINFO_EXTENSION);
    }

    private function path()
    {
        return parse_url((string) $this->resource, PHP_URL_PATH);
    }
}
