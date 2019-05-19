<?php

namespace Drupal\ssf;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class Degenerator.
 *
 * @package Drupal\ssf
 */
class Degenerator implements DegeneratorInterface {

  use StringTranslationTrait;

  /**
   * Configuration setting for use of multibyte strings.
   *
   * @var bool
   */
  protected $multibyte = TRUE;

  /**
   * Configuration setting for use of encoding of multibyte strings.
   *
   * @var string
   */
  protected $encoding = 'UTF-8';

  /**
   * Array of degenerates per token/word.
   *
   * @var array
   */
  protected $degenerates = [];

  /**
   * The log.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $log;

  /**
   * Degenerator constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->log = $logger_factory->get('ssf');
  }

  /**
   * {@inheritdoc}
   */
  public function degenerate(array $words) {
    $degenerates = [];
    foreach ($words as $word) {
      $degenerates[$word] = $this->degenerateWord($word);
    }

    return $degenerates;
  }

  /**
   * Remove duplicates from a list of degenerates of a word.
   *
   * @param string $word
   *   The word.
   * @param array $list
   *   The list to process.
   *
   * @return array
   *   The list without duplicates.
   */
  protected function deleteDuplicates($word, array $list) {
    $list_processed = [];

    // Check each upper/lower version.
    foreach ($list as $alt_word) {
      if ($alt_word != $word) {
        array_push($list_processed, $alt_word);
      }
    }

    return $list_processed;
  }

  /**
   * Builds a list of "degenerated" versions of a word.
   *
   * @param string $word
   *   The word.
   *
   * @return array
   *   An array of degenerated words.
   */
  protected function degenerateWord($word) {
    // Check for any stored words so the process doesn't have to repeat.
    if (isset($this->degenerates[$word]) === TRUE) {
      return $this->degenerates[$word];
    }

    // Add different versions of upper and lower case and ucfirst.
    $upper_lower = [];

    if ($this->isMultibyte()) {
      // The multibyte upper/lower versions.
      $lower = mb_strtolower($word, $this->getEncoding());
      $upper = mb_strtoupper($word, $this->getEncoding());
      $first = mb_substr($upper, 0, 1, $this->getEncoding()) . mb_substr($lower, 1, mb_strlen($word), $this->getEncoding());
    }
    else {
      // The standard upper/lower versions.
      $lower = strtolower($word);
      $upper = strtoupper($word);
      $first = ucfirst($lower);
    }

    // Add the versions.
    $upper_lower[] = $lower;
    $upper_lower[] = $upper;
    $upper_lower[] = $first;

    // Delete duplicate upper/lower versions.
    $degenerate = $this->deleteDuplicates($word, $upper_lower);
    // Append the original word.
    $degenerate[] = $word;
    // Degenerate all versions.
    foreach ($degenerate as $alt_word) {
      // Look for stuff like !!! and ???.
      if (preg_match('/[!?]$/', $alt_word) > 0) {
        // Add versions with different !s and ?s.
        if (preg_match('/[!?]{2,}$/', $alt_word) > 0) {
          $tmp = preg_replace('/([!?])+$/', '$1', $alt_word);
          $degenerate[] = $tmp;
        }
        $tmp = preg_replace('/([!?])+$/', '', $alt_word);
        array_push($degenerate, $tmp);
      }

      // Look for "..." at the end of the word.
      $alt_word_int = $alt_word;

      while (preg_match('/[\.]$/', $alt_word_int) > 0) {
        $alt_word_int = substr($alt_word_int, 0, strlen($alt_word_int) - 1);
        $degenerate[] = $alt_word_int;
      }
    }

    // Some degenerates are the same as the original word. These don't have
    // to be fetched, so we create a new array with only new tokens.
    $degenerate = $this->deleteDuplicates($word, $degenerate);
    // Store the list of degenerates for the token to prevent unnecessary
    // re-processing.
    $this->degenerates[$word] = $degenerate;

    return $degenerate;
  }

  /**
   * {@inheritdoc}
   */
  public function getDegenerates($token) {
    return $this->degenerates[$token];
  }

  /**
   * Check whether multi-byte.
   *
   * @return bool
   *   Use multi-byte encoding.
   */
  public function isMultibyte() {
    return $this->multibyte;
  }

  /**
   * Setter multi-byte.
   *
   * @param bool $multibyte
   *   Use multi-byte.
   */
  public function setMultibyte($multibyte) {
    $this->multibyte = $multibyte;
  }

  /**
   * Getter encoding.
   *
   * @return string
   *   The multi-byte encoding.
   */
  public function getEncoding() {
    return $this->encoding;
  }

  /**
   * Setter encoding.
   *
   * @param string $encoding
   *   The multi-byte encoding.
   */
  public function setEncoding($encoding) {
    $this->encoding = $encoding;
  }

}
