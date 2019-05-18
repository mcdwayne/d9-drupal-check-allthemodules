<?php

namespace Drupal\bibcite_bibtex\Encoder;

use AudioLabs\BibtexParser\BibtexParser;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * BibTeX format encoder.
 */
class BibtexEncoder implements EncoderInterface, DecoderInterface {

  /**
   * The format that this encoder supports.
   *
   * @var string
   */
  protected static $format = 'bibtex';

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding($format) {
    return $format == static::$format;
  }

  /**
   * {@inheritdoc}
   */
  public function decode($data, $format, array $context = []) {
    $data = $this->lineEndingsReplace($data);

    /*
     * Handle type as case-insensitive.
     * Tags should be handled as case-insensitive as well, but it's done by BibtexParser library.
     * @see https://www.drupal.org/node/2890060
     */
    $data = preg_replace_callback('/^@(\w+){/m', function ($word) {
      return '@' . strtolower($word[1]) . '{';
    }, $data);

    /*
     * Ignore "type" tag inside records.
     * Type in BibTeX must go before content.
     * @see https://en.wikipedia.org/wiki/BibTeX
     * @see https://www.drupal.org/node/2882855
     */
    $data = preg_replace('/^ *type *= *{.*}.*$/m', '', $data);
    $parsed = BibtexParser::parse_string($data);

    foreach ($parsed as $i => $entry) {
      unset($entry['raw']);
      unset($entry['lines']);
      $parsed[$i] = $entry;
    }

    $keys = array_keys($parsed);
    if (count($keys) === 0 || $keys[0] === -1) {
      $format_definition = \Drupal::service('plugin.manager.bibcite_format')->getDefinition($format);
      throw new \Exception(t("Incorrect @format format or empty set.", ['@format' => $format_definition['label']]));
    }
    $this->processEntries($parsed);

    return $parsed;
  }

  /**
   * Convert line endings function.
   *
   * Different sources uses different line endings in exports.
   * Convert all line endings to unix which is expected by BibtexParser.
   *
   * @param string $data
   *   Input string from file.
   *
   * @return string
   *   Unix formatted string
   */
  public function lineEndingsReplace($data) {
    /*
     * \R is escape sequence of newline, equivalent to the following: (\r\n|\n|\x0b|\f|\r|\x85)
     * @see http://www.pcre.org/original/doc/html/pcrepattern.html Newline sequences.
     */
    return preg_replace("/\R/", "\n", $data);
  }

  /**
   * Workaround about some things in BibtexParser library.
   *
   * @param array $parsed
   *   List of parsed entries.
   */
  protected function processEntries(array &$parsed) {
    foreach ($parsed as &$entry) {
      if (!empty($entry['pages']) && is_array($entry['pages'])) {
        $entry['pages'] = implode('-', $entry['pages']);
      }

      if (!empty($entry['keywords'])) {
        $entry['keywords'] = array_map(function ($keyword) {
          return trim($keyword);
        }, explode(',', str_replace(';', ',', $entry['keywords'])));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return $format == static::$format;
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []) {
    if (isset($data['type'])) {
      $data = [$data];
    }

    $data = array_map(function ($raw) {
      return $this->buildEntry($raw);
    }, $data);

    return implode("\n", $data);
  }

  /**
   * Build BibTeX entry string.
   *
   * @param array $data
   *   Array of BibTeX values.
   *
   * @return string
   *   Formatted BibTeX string.
   */
  protected function buildEntry(array $data) {
    if (empty($data['reference'])) {
      $data['reference'] = $data['type'];
    }

    $entry = $this->buildStart($data['type'], $data['reference']);

    unset($data['type']);
    unset($data['reference']);

    foreach ($data as $key => $value) {
      $entry .= $this->buildLine($key, $value);
    }

    $entry .= $this->buildEnd();

    return $entry;
  }

  /**
   * Build first string for BibTeX entry.
   *
   * @param string $type
   *   Publication type in BibTeX format.
   * @param string $reference
   *   Reference key.
   *
   * @return string
   *   First entry string.
   */
  protected function buildStart($type, $reference) {
    return '@' . $type . '{' . $reference . ',' . "\n";
  }

  /**
   * Build entry line.
   *
   * @param string $key
   *   Line key.
   * @param string|array $value
   *   Line value.
   *
   * @return string
   *   Entry line.
   */
  protected function buildLine($key, $value) {
    switch ($key) {
      case 'author':
        $value = implode(' and ', $value);
        break;

      case 'keywords':
        $value = implode(', ', $value);
        break;
    }

    return '  ' . $key . ' = {' . $value . '},' . "\n";
  }

  /**
   * Build the end of BibTeX entry.
   *
   * @return string
   *   End line for the BibTeX entry.
   */
  protected function buildEnd() {
    return "}\n";
  }

}
