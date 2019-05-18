<?php
$libraries['mustache.js'] = array(
  // Only used in administrative UI of Libraries API.
  'name' => 'mustache.js',
  'vendor url' => 'https://github.com/janl/mustache.js',
  'download url' => 'https://github.com/janl/mustache.js',
  'version arguments' => array(
    'file' => 'package.json',
    'pattern' => '/"version": "([0-9a-zA-Z\.-]+)/',
  ),
  'files' => array(
    'js' => array(
      'mustache.js',
    ),

  ),
  'variants' => array(
    'minified' => array(
      'files' => array(
        'js' => array(
          'mustache.min.js',
        ),
      ),
    ),
  ),
);