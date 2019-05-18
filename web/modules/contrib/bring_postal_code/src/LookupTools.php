<?php

namespace Drupal\bring_postal_code;
use Drupal\Core\Config\ConfigFactory;

/**
 * Class LookupToolss.
 */
class LookupTools {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Constructs a new LookupTools object.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->config = $config_factory->get('bring_postal_code.settings');

  }

  /**
   * Extracts values based on text split by newlines and |'s.
   *
   * @param string $string
   *   The string of data.
   *
   * @return array|null
   *   The array of extracted key/value pairs, or NULL if the string is invalid.
   */
  public function splitValues($string) {
    $values = [];
    $list = explode("\n", $string);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    foreach ($list as $position => $text) {
      // Check for an explicit key.
      $matches = [];
      if (preg_match('/(.*)\|(.*.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $input = trim($matches[1]);
        $output = trim($matches[2]);
        $country = trim($matches[3]);
      }
      elseif (preg_match('/(.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $input = trim($matches[1]);
        $output = trim($matches[2]);
        $country = $this->config->get('default_country');
      }
      else {
        return FALSE;
      };
      $values[] = [
        'input' => $input,
        'output' => $output,
        'country' => $country,
      ];
    }
    return $values;
  }

  /**
   * Attach needed stuff to a form.
   *
   * @param array $form
   *   The form.
   *
   * @return array|null
   *   Returns form, or false
   */
  public function attach(array &$form) {
    $form['#attached']['library'][] = 'bring_postal_code/bring-lookup';
    $config = $this->config;
    // Build the array of field settings.
    $selectors = $this->splitValues($config->get('selectors'));
    if (is_array($selectors)) {
      $form['#attached']['drupalSettings']['bring_postal_code'] = [
        'country' => $config->get('default_country'),
        'clientUrl' => $config->get('client_url'),
        'triggerLength' => $config->get('trigger_length'),
        'selectors' => $selectors,
      ];
    }
    return $form;
  }

}
