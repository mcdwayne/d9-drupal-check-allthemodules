<?php

/**
 * @file
 *
 * ¼æÈÝD7/D8µÄ·ÖÒ³
 */

namespace Pyramid\Drupal;

class Pager {
    
    public static function compat() {
        if (class_exists('Drupal\Core\Database\Query\PagerSelectExtender')) {
            return 'Drupal\Core\Database\Query\PagerSelectExtender';
        } else {
            return 'PagerDefault';
        }
    }

}
