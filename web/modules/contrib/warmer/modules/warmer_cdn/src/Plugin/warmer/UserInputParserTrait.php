<?php

namespace Drupal\warmer_cdn\Plugin\warmer;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Common methods to parse user input.
 */
trait UserInputParserTrait {

  /**
   * Parses the string under $key in the $values collection.
   *
   * @param array $values
   *   The collection of values.
   * @param $key
   *   Indicates the element to parse.
   *
   * @return array
   *   The parsed textarea.
   */
  private function extractTextarea(array $values, $key) {
    if (!array_key_exists($key, $values)) {
      return [];
    }
    if (!is_string($values[$key])) {
      return $values[$key];
    }
    return array_filter(array_map(function ($val) {
      return trim($val, "\r");
    }, explode("\n", $values[$key])));
  }

  /**
   * Validate the input for the headers.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  private function validateHeaders(array $form, FormStateInterface $form_state) {
    $headers = $form_state->getValue('headers');
    $lines = array_filter(explode("\n", $headers));
    // Count the number of lines that have colons.
    $colons = array_reduce($lines, function ($carry, $line) {
      return strpos($line, ':') === FALSE ? $carry : $carry + 1;
    }, 0);
    if ($colons && $colons !== count($lines)) {
      $form_state->setError($form['headers'], $this->t('All headers must have a colon. Follow the format: <code>the-name: the-value</code>'));
    }
  }

  /**
   * Resolves a URI into a fully loaded URL.
   *
   * @param string $user_input
   *   The user input for the URL. Examples: internal://<front>,
   *   entity://user/1, /node/2, https://example.org.
   *
   * @return string
   *   The fully loaded URL.
   */
  private function resolveUri($user_input) {
    try {
      return Url::fromUri($user_input, ['absolute' => TRUE])->toString();
    }
    catch (\InvalidArgumentException $e) {}
    try {
      return Url::fromUserInput($user_input, ['absolute' => TRUE])->toString();
    }
    catch (\InvalidArgumentException $e) {}
    return $user_input;
  }
}
