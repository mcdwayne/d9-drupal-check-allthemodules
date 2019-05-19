<?php

namespace Drupal\viewscss;

use Wikimedia\CSS\Parser\Parser;
use Wikimedia\CSS\Sanitizer\StylesheetSanitizer;

class CssSanitizer {

  public static function sanitize($cssText, &$errors, $identifier = 'CSS') {
    if (!isset($errors)) {
      $errors = [];
    }

    $parser = Parser::newFromString($cssText);
    $stylesheet = $parser->parseStylesheet();

    foreach ($parser->getParseErrors() as list($code, $line, $pos)) {
      $tArgs = ['@identifier' => $identifier, '@line' => $line, '@pos' => $pos, '@code' => $code,];
      $errors[] = t('Views CSS parser error @identifier#@line:@pos: @code', $tArgs);
    }
    $sanitizer = StylesheetSanitizer::newDefault();
    $newStylesheet = $sanitizer->sanitize( $stylesheet );
    foreach ($sanitizer->getSanitizationErrors() as list($code, $line, $pos)) {
      $tArgs = ['@identifier' => $identifier, '@line' => $line, '@pos' => $pos, '@code' => $code,];
      $errors[] = t('Views CSS sanitizer error @identifier#@line:@pos: @code', $tArgs);
    }
    $newText = (string)$newStylesheet;
    return $newText;
  }
}
