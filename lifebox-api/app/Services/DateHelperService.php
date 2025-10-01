<?php
declare(strict_types=1);

namespace App\Services;


use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

class DateHelperService
{
    /**
     * @param string $dateString
     *
     * @return string|null
     */
    public static function convertToDatabaseDate(string $dateString)
    {
        try {
            return Carbon::createFromFormat('d/m/Y', $dateString)->format('Y-m-d');
        } catch (InvalidFormatException $exception) {
            return null;
        }
    }
}
