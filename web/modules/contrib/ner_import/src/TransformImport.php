<?php
/**
 * DRUPAL 8 NER importer.
 * Copyright (C) 2017. Tarik Curto <centro.tarik@live.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace Drupal\ner_import;


/**
 * Utils for transform NER data
 * to Drupal data.
 *
 * @package Drupal\ner_import
 */
class TransformImport {

    /**
     *
     * @param string $string
     * @return string
     */
    public static function idByString(string $string): string {

        $id = str_replace([' ', '-', '.'], ['_', '_', '_'], $string);
        $id = strtolower($id);

        return $id;
    }

    /**
     *
     * @param string $string
     * @return string
     */
    public static function nameByString(string $string): string {

        $name = str_replace(['_', '.'], [' ', ' '], $string);
        $name = strtolower($name);
        $name = ucfirst($name);

        return $name;
    }
}