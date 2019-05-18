<?php


// jQuery Lodash
$libraries['lodash'] = array(

  'name' => 'jQuery Lodash',
  'vendor url' => 'https://github.com/desandro/lodash',
  'download url' => 'https://github.com/lodash/lodash/zipball/master',
  'version arguments' => array(
    'file' => 'dist/lodash.js',
    'pattern' => '/lodash ([0-9a-zA-Z\.-]+)/',
  ),
  'files' => array(
    'js' => array(
      'lodash.js',
    )
  ),
  'variants' => array(
    'minified' => array(
      'files' => array(
        'js' => array(
          'lodash.min.js',
        )
      ),
    ),
  ),
);
