<?php

namespace ConvertApi;

class Task
{
    final public const DEFAULT_URL_FORMAT = 'url';

    function __construct($fromFormat, $toFormat, $params, $conversionTimeout = null)
    {
        $this->fromFormat = $fromFormat;
        $this->toFormat = $toFormat;
        $this->params = $params;
        $this->conversionTimeout = $conversionTimeout ?: ConvertApi::$conversionTimeout;
    }

    function run()
    {
        $params = array_merge(
            $this->normalizedParams(),
            [
                'StoreFile' => true,
            ]
        );

        if ($this->conversionTimeout) {
            $params['Timeout'] = $this->conversionTimeout;
            $readTimeout = $this->conversionTimeout + ConvertApi::$conversionTimeoutDelta;
        } else {
            $readTimeout = ConvertApi::$readTimeout;
        }

        $fromFormat = $this->fromFormat ?: $this->detectFormat($params);
        $converter = $this->detectConverter($params);
        $converterPath = $converter ? "/converter/{$converter}" : '';
        $path = 'convert/' . $fromFormat . '/to/' . $this->toFormat . $converterPath;

        $response = ConvertApi::client()->post($path, $params, $readTimeout);

        return new Result($response);
    }

    private function normalizedParams()
    {
        $result = [];

        foreach ($this->params as $key => $val)
        {
            $result[$key] = match (true) {
                $key != 'StoreFile' && preg_match('/File$/', (string) $key) => FileParam::build($val),
                $key == 'Files' => $this->filesBatch($val),
                default => $val,
            };
        }

        return $result;
    }

    private function filesBatch($values)
    {
        $files = [];

        foreach ((array)$values as $val)
            $files[] = FileParam::build($val);

        return $files;
    }

    private function detectFormat($params)
    {
        if (!empty($params['Url']))
            return self::DEFAULT_URL_FORMAT;

        if (!empty($params['File']))
        {
            $resource = $params['File'];
        }
        elseif (!empty($params['Files']))
        {
            $files = (array)$params['Files'];
            $resource = $files[0];
        }

        $detector = new FormatDetector($resource);

        return $detector->run();
    }

    private function detectConverter($params)
    {
        $keys = array_keys($params);

        foreach ($keys as $key)
            if (strtolower($key) == 'converter')
                return $params[$key];

        return;
    }
}
