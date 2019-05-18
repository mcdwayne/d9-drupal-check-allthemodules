<?php
include dirname(__DIR__) . '/vendor/autoload.php';

// PHUnit 6+ compat
if (   !class_exists('\PHPUnit_Framework_TestCase')
    && class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');
}