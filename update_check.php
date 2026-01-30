<?php

function checkForUpdate()
{
    $current = IDBASE_VERSION;
    $url = 'https://raw.githubusercontent.com/fredriclesomar/iDBase/refs/heads/master/version.json';

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 3
        ]
    ]);

    $json = @file_get_contents($url, false, $ctx);
    if (!$json) return null;

    $data = json_decode($json, true);
    if (!isset($data['latest'])) return null;

    if (version_compare($data['latest'], $current, '>')) {
        return $data;
    }

    return null;
}
