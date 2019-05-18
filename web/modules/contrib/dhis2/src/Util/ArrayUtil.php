<?php

namespace Drupal\dhis\Util;

/**
 * A set of utility functions for manipulating arrays and data
 *
 * Class ArrayUtil
 * @package Drupal\dhis\Util
 */
class ArrayUtil
{
    public function implodeArray($arrayToImplode = array(), $separator = ";")
    {

        return implode($separator, $arrayToImplode);
    }

    public function reformatDhisAnalyticData($analytics_data, $exclude_empty_value = TRUE, $api_version = TRUE)
    {

        $rows_final = [];
        $periods = $analytics_data['metaData']['dimensions']['pe'];
        if (!$api_version) {
            $periods = $analytics_data['metaData']['pe'];
        }

        $rows = $analytics_data['rows'];
        foreach ($rows as $k => $row) {
            $split = array_splice($row, ($analytics_data['width'] - count($periods)), count($periods));
            foreach ($split as $key => $value) {
                if (empty($value) && $exclude_empty_value == TRUE) {
                    continue;
                }

                array_push($rows_final, [$row[0], $row[1], $row[2], $row[4], $row[5], $row[6], $periods[$key], $value]);
            }
        }

        return $rows_final;
    }
}