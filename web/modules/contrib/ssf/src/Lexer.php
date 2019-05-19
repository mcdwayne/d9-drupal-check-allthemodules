<?php

namespace Drupal\ssf;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class Lexer.
 *
 * @package Drupal\ssf
 */
class Lexer implements LexerInterface {

  use StringTranslationTrait;

  /**
   * Configuration setting for minimum word length.
   *
   * @var int
   */
  protected $minimumSize = 3;

  /**
   * Configuration setting for maximum word length.
   *
   * @var int
   */
  protected $maximumSize = 30;

  /**
   * Configuration setting for allowing numbers in the tokens.
   *
   * @var bool
   */
  protected $allowNumbers = FALSE;

  /**
   * Configuration setting for detecting URI's in the text.
   *
   * @var bool
   */
  protected $detectUris = TRUE;

  /**
   * Configuration setting for detecting html in the text.
   *
   * @var bool
   */
  protected $detectHtml = TRUE;

  /**
   * Configuration setting for detecting bbcode in the text.
   *
   * @var bool
   */
  protected $detectBbcode = FALSE;

  /**
   * List of tokens found in the text.
   *
   * @var array
   */
  protected $tokens = [];

  /**
   * Remainder of the text from which detected tokens have been removed.
   *
   * @var string
   */
  protected $processedText;

  /**
   * The regular expressions we use to split the text to tokens.
   *
   * @var array
   */
  protected $regexp = [
    'raw_split' => '/[\s,\.\/"\:;\|<>\-_\[\]{}\+=\)\(\*\&\^%]+/',
    'ip'        => '/([A-Za-z0-9\_\-\.]+)/',
    'uris'      => '/([A-Za-z0-9\_\-]*\.[A-Za-z0-9\_\-\.]+)/',
    'html'      => '/(<.+?>)/',
    'bbcode'    => '/(\[.+?\])/',
    'tagname'   => '/(.+?)\s/',
    'numbers'   => '/^[0-9]+$/',
  ];

  /**
   * The log.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $log;

  /**
   * Lexer constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory interface.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->log = $logger_factory->get('ssf');
  }

  /**
   * {@inheritdoc}
   */
  public function getTokens($text) {
    // Re-convert the text to the original characters coded in UTF-8, as
    // they have been coded in html entities during the post process.
    $this->processedText = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

    // Reset the token list.
    $this->tokens = [];

    if ($this->detectUris()) {
      $this->getUris($this->processedText);
    }

    if ($this->detectHtml()) {
      // Get HTML.
      $this->getMarkup($this->processedText, $this->regexp['html']);
    }

    if ($this->detectBbcode()) {
      // Get BBCode.
      $this->getMarkup($this->processedText, $this->regexp['bbcode']);
    }

    // We always want to do a raw split of the (remaining) text, so:
    $this->rawSplit($this->processedText);

    // Be sure not to return an empty array.
    if (count($this->tokens) == 0) {
      $this->tokens['bayes*no_tokens'] = 1;
    }

    return $this->tokens;
  }

  /**
   * Validates a token.
   *
   * @param string $token
   *   The token string.
   *
   * @return bool
   *   Returns TRUE if the token is valid, otherwise returns FALSE.
   */
  protected function isValid($token) {
    // Just to be sure that the token's name won't collide with b8's internal
    // variables.
    if (substr($token, 0, 6) == 'bayes*') {
      return FALSE;
    }

    // Validate the size of the token.
    $len = strlen($token);
    if ($len < $this->getMinimumSize() or $len > $this->getMaximumSize()) {
      return FALSE;
    }

    // We may want to exclude pure numbers.
    if (!$this->allowNumbers()) {
      if (preg_match($this->regexp['numbers'], $token) > 0) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Checks the validity of a token and adds it to the token list if it's valid.
   *
   * @param string $token
   *   The token.
   * @param bool $remove
   *   When set to TRUE, the string given in $word_to_remove is removed from the
   *   text passed to the lexer.
   * @param string $word_to_remove
   *   The word to remove.
   */
  protected function addToken($token, $remove, $word_to_remove) {
    // Check the validity of the token.
    if ($this->isValid($token) === FALSE) {
      return;
    }

    // Add it to the list or increase it's counter.
    if (isset($this->tokens[$token]) === FALSE) {
      $this->tokens[$token] = 1;
    }
    else {
      $this->tokens[$token] += 1;
    }

    // If requested, remove the word or it's original version from the text.
    if ($remove === TRUE) {
      $this->processedText = str_replace($word_to_remove, '', $this->processedText);
    }
  }

  /**
   * Gets URIs.
   *
   * @param string $text
   *   The text.
   */
  protected function getUris($text) {
    // Find URIs.
    preg_match_all($this->regexp['uris'], $text, $raw_tokens);

    foreach ($raw_tokens[1] as $word) {
      // Remove a possible trailing dot.
      $word = rtrim($word, '.');
      // Try to add the found tokens to the list.
      $this->addToken($word, TRUE, $word);
      // Also process the parts of the found URIs.
      $this->rawSplit($word);
    }
  }

  /**
   * Gets HTML or BBCode markup, depending on the regexp used.
   *
   * @param string $text
   *   The text.
   * @param string $regexp
   *   The regular expression.
   */
  protected function getMarkup($text, $regexp) {
    // Search for the markup.
    preg_match_all($regexp, $text, $raw_tokens);

    foreach ($raw_tokens[1] as $word) {
      $actual_word = $word;
      // If the tag has parameters, just use the tag itself.
      if (strpos($word, ' ') !== FALSE) {
        preg_match($this->regexp['tagname'], $word, $match);
        $actual_word = $match[1];
        $word = "{$actual_word}..." . substr($word, -1);
      }
      // Try to add the found tokens to the list.
      $this->addToken($word, TRUE, $actual_word);
    }
  }

  /**
   * Does a raw split.
   *
   * @param string $text
   *   The text.
   */
  protected function rawSplit($text) {
    foreach (preg_split($this->regexp['raw_split'], $text) as $word) {
      // Check the word and add it to the token list if it's valid.
      $this->addToken($word, FALSE, NULL);
    }
  }

  /**
   * Getter.
   *
   * @return int
   *   Minimum size of token.
   */
  public function getMinimumSize() {
    return $this->minimumSize;
  }

  /**
   * Setter.
   *
   * @param int $minimum_size
   *   Minimum size of token.
   */
  public function setMinimumSize($minimum_size) {
    $this->minimumSize = $minimum_size;
  }

  /**
   * Getter.
   *
   * @return int
   *   Maximum size of token.
   */
  public function getMaximumSize() {
    return $this->maximumSize;
  }

  /**
   * Setter.
   *
   * @param int $maximum_size
   *   Maximum size of token.
   */
  public function setMaximumSize($maximum_size) {
    $this->maximumSize = $maximum_size;
  }

  /**
   * Are numbers allowed as tokens?
   *
   * @return bool
   *   Are numbers allowed as tokens.
   */
  public function allowNumbers() {
    return $this->allowNumbers;
  }

  /**
   * Setter.
   *
   * @param bool $allow_numbers
   *   Are numbers allowed as tokens.
   */
  public function setAllowNumbers($allow_numbers) {
    $this->allowNumbers = $allow_numbers;
  }

  /**
   * Should uri's be detected as tokens?
   *
   * @return bool
   *   Should uri's be detected as tokens.
   */
  public function detectUris() {
    return $this->detectUris;
  }

  /**
   * Setter.
   *
   * @param bool $detect_uris
   *   Should uri's be detected as tokens.
   */
  public function setDetectUris($detect_uris) {
    $this->detectUris = $detect_uris;
  }

  /**
   * Should html be detected in text?
   *
   * @return bool
   *   Should html be detected in text.
   */
  public function detectHtml() {
    return $this->detectHtml;
  }

  /**
   * Setter.
   *
   * @param bool $detect_html
   *   Should html be detected in text.
   */
  public function setDetectHtml($detect_html) {
    $this->detectHtml = $detect_html;
  }

  /**
   * Should BB code be detected in text?
   *
   * @return bool
   *   Should BB code be detected.
   */
  public function detectBbcode() {
    return $this->detectBbcode;
  }

  /**
   * Setter.
   *
   * @param bool $detect_bbcode
   *   Should BB code be detected.
   */
  public function setDetectBbcode($detect_bbcode) {
    $this->detectBbcode = $detect_bbcode;
  }

}
