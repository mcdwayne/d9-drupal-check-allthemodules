<?php

/**
 * @file
 * Contains \Drupal\pgn\Serializer\Encoder\PgnEncoder.
 */

namespace Drupal\pgn\Serializer\Encoder;

use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * Adds Games_Chess PGN support for serializer.
 */
class PgnEncoder implements EncoderInterface {

  /**
   * The formats that this Encoder supports.
   *
   * @var array
   */
  static protected $format = array('pgn');

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = array()) {
    $pgn = array();
    foreach ($data as $game) {
      $movetext = '';
      foreach($game['movetext'] as $number => $move) {
        $movetext .= $number . '. ' . $move[0] . ' ';
        if (isset($move[1])) {
          $movetext .= $move[1] . ' ';
        }
      }
      $movetext .= $game['tags']['Result'];

      $tag_pair_section = array();
      foreach ($game['tags'] as $name => $value) {
        $tag_pair_section[] = '[' . $name . ' "' . $value . '"]';
      }
      $pgn[] = implode("\n", $tag_pair_section) . "\n\n$movetext\n";
    }

    return implode("\n", $pgn);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return in_array($format, static::$format);
  }

}
