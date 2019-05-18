<?php

namespace Drupal\bcubed;

use Drupal\Component\Utility\Random;
use Drupal\Core\Config\ConfigFactory;

/**
 * Register and generated strings.
 */
class StringGenerator {

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a new StringGenerator object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->config = $config_factory->getEditable('bcubed.generated_strings');
  }

  /**
   * Fetch generated strings in a given dictionary.
   */
  public function getStrings($dictionary) {
    $strings = $this->config->get('dictionaries.' . $dictionary . '.strings');
    if (empty($strings) && $this->config->get('dictionaries.' . $dictionary)) {
      $strings = $this->generateStrings($dictionary);
    }
    return $strings;
  }

  /**
   * Generate strings for a dictionary.
   */
  private function generateStrings($dictionary, $save = TRUE) {
    $definition = $this->config->get('dictionaries.' . $dictionary . '.definition');

    $strings = [];
    $random = new Random();

    foreach ($definition as $stringdef) {
      $strings[$stringdef['key']] = $stringdef['length']['min'] != $stringdef['length']['max'] ? $random->name(mt_rand($stringdef['length']['min'], $stringdef['length']['max']), TRUE) : $random->name($stringdef['length']['max'], TRUE);
    }

    $this->config->set('dictionaries.' . $dictionary . '.strings', $strings);

    if ($save) {
      $this->config->save();
    }

    return $strings;
  }

  /**
   * Regenerate all strings in all dictionaries.
   */
  public function regenerateAllStrings() {
    $dictionaries = $this->config->get('dictionaries');
    foreach ($dictionaries as $key => $value) {
      $this->generateStrings($key, FALSE);
    }
    $this->config->save();
  }

  /**
   * Create a dictionary.
   */
  public function registerDictionary($key, $definition) {
    // Format predefined lengths for config min/max schema.
    foreach ($definition as $delta => $def) {
      if (is_int($def['length'])) {
        $definition[$delta]['length'] = ['min' => $def['length'], 'max' => $def['length']];
      }
    }

    $this->config->set('dictionaries.' . $key . '.definition', $definition)->save();
  }

}
