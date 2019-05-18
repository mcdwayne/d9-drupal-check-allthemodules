<?php

/**
 * @file
 * Provides functions for creating XFDF files.
 */

/**
 * Generates an XFDF file from values given in an associative array.
 *
 * @param string|null $file
 *   The PDF file: URL or file path accepted. Use NULL to skip setting
 *   file-related properties.
 * @param array $info
 *   Key/value pairs of the field data.
 * @param string $enc
 *   The character encoding. Must match server output: default_charset in
 *   php.ini.
 *
 * @return string
 *   The contents of the XFDF file.
 */
function create_xfdf($file, array $info, $enc = 'UTF-8') {
  $doc = new DOMDocument('1.0', $enc);

  $xfdf_ele = $doc->appendChild($doc->createElement('xfdf'));
  $xfdf_ele->setAttribute('xmlns', 'http://ns.adobe.com/xfdf/');
  $xfdf_ele->setAttribute('xml:space', 'preserve');

  $fields_ele = $xfdf_ele->appendChild($doc->createElement('fields'));
  foreach ($info as $name => $value) {
    $field_ele = $fields_ele->appendChild($doc->createElement('field'));
    $field_ele->setAttribute('name', $name);

    $value_ele = $field_ele->appendChild($doc->createElement('value'));
    $value_ele->appendChild($doc->createTextNode($value));
  }

  $ids_ele = $xfdf_ele->appendChild($doc->createElement('ids'));
  if ($file) {
    $ids_ele->setAttribute('original', md5($file));
  }
  $ids_ele->setAttribute('modified', REQUEST_TIME);

  if ($file) {
    $f_ele = $xfdf_ele->appendChild($doc->createElement('f'));
    $f_ele->setAttribute('href', $file);
  }

  return $doc->saveXML();
}
