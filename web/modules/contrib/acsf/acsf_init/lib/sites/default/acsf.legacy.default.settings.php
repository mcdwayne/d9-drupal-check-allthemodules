<?php

/**
 * @file
 * Destination for sites that are not registered with Site Factory or Hosting.
 */

// Don't run any of this code if we are drush or a CLI script.
if (function_exists('drush_main') || !\Drupal::hasContainer() || PHP_SAPI === 'cli') {
  if (!function_exists('drush_main')) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
  }
  return;
}

// Print a 404 response and a small HTML page.
header("HTTP/1.0 404 Not Found");
header('Content-type: text/html; charset=utf-8');

print <<<HTML
<!DOCTYPE html>
<html>
 <head>
  <meta charset="UTF-8" />
  <title>404 Page Not Found</title>
  <meta name="robots" content="noindex, nofollow, noarchive" />
 </head>
 <body>
HTML;

print('<p>' . t('The site you are looking for could not be found.') . '</p>');

print <<<HTML
 </body>
</html>
HTML;

exit();
