<?php
/**
 * @file
 * Generates an E_ERROR level error.
 */

// Accessing an object as an array.
$object = new stdClass();
// This produces a fatal error.
$object[5];
