<?php

/**
 * @file Contains \Drupal\feeds_xpathparser\XPathQueryParser.
 */

namespace Drupal\feeds_xpathparser;

/**
 * Pseudo-parser of XPath queries.
 *
 * When an XML document has a default namespace this gets called so that adding
 * the __default__ namepace where appropriate.
 *
 * Aren't we nice?
 *
 * @param string $query
 *   An XPath query string.
 *
 * @return string
 *   An XPath query string with the __default__ namespace added.
 *
 * @todo
 *   Cleanup.
 */
class XPathQueryParser {

  /**
   * Characters that represent word boundaries.
   *
   * @var array
   */
  protected $wordBoundaries = array(
    '[', ']', '=', '(', ')', '.', '<', '>', '*', '!', '|', '/', ',', ' ', ':',
  );

  /**
   * The XPath query to modify.
   *
   * @var string
   */
  protected $query;

  /**
   * The parsing index.
   *
   * @var int
   */
  protected $i;

  protected $inQuotes = FALSE;
  protected $quoteChar = '';
  protected $word = '';
  protected $output = '';
  protected $prevBoundary = '';
  protected $axis = '';
  protected $skipNextWord = FALSE;

  /**
   * Constructs a XPathQueryParser object.
   *
   * @param string $query
   *   The input XPath query string.
   */
  public function __construct($query) {

    // Normalize space.
    $this->query = preg_replace('/\s+\(\s*/', '(', $query);
  }

  /**
   * Returns the modified XPath query.
   *
   * @return string
   *   A modified XPath query.
   */
  public function getQuery() {
    $this->start();
    return $this->output;
  }

  /**
   * Begin parsing.
   */
  protected function start() {
    for ($i = 0; $i < drupal_strlen($this->query); $i++) {
      $this->i = $i;
      $c = drupal_substr($this->query, $i, 1);

      if ($c === '"' || $c === "'") {
        $this->handleQuote($c);
        continue;
      }
      if ($this->inQuotes) {
        $this->word .= $c;
        continue;
      }

      if (in_array($c, $this->wordBoundaries)) {
        $this->handleWordBoundary($c);
      }
      else {
        $this->word .= $c;
      }
    }
    $this->handleWord();
  }

  /**
   * Handles quote pairs.
   */
  protected function handleQuote($c) {
    if ($this->inQuotes && $c === $this->quoteChar) {
      $this->inQuotes = FALSE;
      $this->word .= $c;
      $this->output .= $this->word;
      $this->word = '';
    }
    elseif (!$this->inQuotes) {
      $this->inQuotes = TRUE;
      $this->handleWord();
      $this->word = $c;
      $this->quoteChar = $c;
    }
    else {
      $this->word .= $c;
    }
  }

  /**
   * Handles word boundaries.
   *
   * @param string $c
   *   One character.
   */
  protected function handleWordBoundary($c) {
    if (in_array($this->word, array('div', 'or', 'and', 'mod')) &&
        $this->prevBoundary === ' ' && $c === ' ') {
      $this->output .= $this->word;
    }
    else {
      $this->handleWord($c);
    }

    $this->output .= $c;
    $this->word = '';
    $this->prevBoundary = $c;
  }

  /**
   * Handles one word.
   *
   * @param string $c
   *   (optional) A single character. Defaults to an empty string.
   */
  protected function handleWord($c = '') {
    if ($this->word === '') {
      return;
    }

    if ($c === ':' && drupal_substr($this->query, $this->i + 1, 1) === ':') {
      $this->axis = $this->word;
    }

    if ($c === ':' && drupal_substr($this->query, $this->i - 1, 1) !== ':'  &&
        drupal_substr($this->query, $this->i + 1, 1) !== ':') {
      $this->output .= $this->word;
      $this->skipNextWord = TRUE;

      return;
    }

    if ($this->skipNextWord) {
      $this->skipNextWord = FALSE;
      $this->output .= $this->word;

      return;
    }

    if (is_numeric($this->word) ||
        $this->axis === 'attribute' ||
        strpos($this->word, '@') === 0 ||
        $c === '(' ||
        $c === ':') {
      $this->output .= $this->word;

      return;
    }

    // Apply namespace.
    $this->output .= '__default__:' . $this->word;
  }

}
