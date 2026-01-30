<?php
/**
 * iDBase - Import Database SQL
 * © 2026 Fredric Lesomar
 *
 * This file is part of iDBase.
 *
 * iDBase is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE file for details.
 */

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
