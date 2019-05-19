<?php

/**
 * @file
 *
 * Pyramid by M
 */

use Pyramid\Component\ClassLoader\ClassLoader;
use Pyramid\Component\Logger\Logger;

/**
 * 类自动加载
 */
function class_loader() {
    static $loader;    
    if (!isset($loader)) {
        $dir = dirname(__DIR__);
        require_once $dir . '/Pyramid/Component/ClassLoader/ClassLoader.php';
        $loader = new ClassLoader();
        $loader->registerNamespace('Pyramid', $dir);
        $loader->register();
    }
    return $loader;
}

/**
 * Logger
 */
function logger($target = 'default') {
    return Logger::getLogger($target);
}

/**
 * like array_column (php5.5+)
 */
function pyramid_array_column($input, $columnKey, $indexKey = null) {
    $return = array();
    if (!is_array($input)) {
        return $return; 
    }
    if ($indexKey === null) {
        foreach ($input as $v) {
            if (is_object($v) && isset($v->$columnKey)) {
                $return[] = $v->$columnKey;
            } elseif (isset($v[$columnKey])) {
                $return[] = $v[$columnKey];
            }
        }
    } else {
        foreach ($input as $v) {
            if (isset($v[$columnKey])) {
                $return[$v[$indexKey]] = $v[$columnKey];
            }
        }
    }
    
    return $return;
}

class_loader();