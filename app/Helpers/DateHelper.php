<?php

namespace App\Helpers;

use Carbon\CarbonInterface;

class DateHelper
{
    public static function response(CarbonInterface $date, $format = 'Y-m-d'): array
    {
        return [
            'raw' => $date->timestamp,
            'formatted' => $date->format($format),
            'diff' => $date->diffForHumans(),
        ];
    }
}
