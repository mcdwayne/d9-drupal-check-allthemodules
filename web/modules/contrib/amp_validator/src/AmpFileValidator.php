<?php

namespace Drupal\amp_validator;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Defines an AMP file validator.
 */
class AmpFileValidator extends AmpValidatorBase {

  /**
   * Path to file which should be validated.
   *
   * @var string
   *
   * TODO: Find a way to reference a file object or entity.
   */
  protected $file = NULL;

  /**
   * Set $file.
   *
   * @param string $file
   *   Path to file which should be validated.
   *
   *   TODO: Find a way to reference a file object or entity.
   */
  public function setFile(string $file) {
    $this->file = $file;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    if (!empty($this->file)) {

      /* @var $manager \Drupal\plugin_type_example\SandwichPluginManager */
      $manager = \Drupal::service('plugin.manager.amp_validator_plugin');

      // Create an instance of the Cloudflare AMP Validator plugin.
      $plugin = $manager->createInstance('cloudflare');
      $plugin->setData($this->file);
      $plugin->validate('file');
      $this->valid = $plugin->isValid();
      $this->errors = $plugin->getErrors();
    }
  }

}
