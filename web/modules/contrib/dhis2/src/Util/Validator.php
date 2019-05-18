<?php
/**
 * @file
 */

namespace Drupal\dhis\Util;


final class Validator
{
    public static function verifyFormat($format)
    {
        if (strtolower($format) != "xml")
            $format = "json";

        return strtolower($format);
    }

    public static function verifyPagination($isPaginated)
    {
        $paging = "true";
        if ($isPaginated !== TRUE)
            $paging = "false";

        return $paging;
    }
}