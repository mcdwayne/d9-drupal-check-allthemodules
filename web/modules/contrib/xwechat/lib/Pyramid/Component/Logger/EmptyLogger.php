<?php

/**
 * @file
 *
 * EmptyLogger
 */

namespace Pyramid\Component\Logger;

class EmptyLogger {
    function fatal($string) {}
    function error($string) {}
    function warn($string) {}
    function info($string) {}
    function debug($string) {}
    function trace($string) {}
    function log($string) {}
}
