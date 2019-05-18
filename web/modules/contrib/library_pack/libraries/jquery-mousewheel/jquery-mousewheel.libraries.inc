<?php

// meanMenu.
$libraries['jquery-mousewheel'] = array(
  'name' => 'jquery-mousewheel',
  'vendor url' => 'https://github.com/jquery/jquery-mousewheel',
  'download url' => 'https://github.com/jquery/jquery-mousewheel',
  'version arguments' => array(
    'file' => 'package.json',
    'pattern' => '/"version": "([0-9a-zA-Z\.-]+)/',
  ),
  'files' => array(
    'js' => array(
      'jquery.mousewheel.js',
    ),
  ),
  'variants' => array(
    'minified' => array(
      'files' => array(
        'js' => array(
          'jquery.mousewheel.min.js',
        ),
      ),
    ),
  ),
);
