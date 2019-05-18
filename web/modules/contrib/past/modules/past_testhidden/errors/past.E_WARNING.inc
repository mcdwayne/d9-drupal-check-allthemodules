<?php
/**
 * @file
 * Generates an E_WARNING level error.
 */

// Opening a file that does not exists.
fopen('does.not.exists.txt', 'r');
