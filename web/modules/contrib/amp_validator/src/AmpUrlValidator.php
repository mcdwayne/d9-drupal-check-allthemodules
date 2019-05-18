<?php

namespace Drupal\amp_validator;

use Drupal\Core\Url;

/**
 * Defines an AMP URL validator.
 */
class AmpUrlValidator extends AmpValidatorBase {

  /**
   * Url object which should be validated.
   *
   * @var \Drupal\Core\Url
   */
  protected $url = NULL;

  /**
   * Set URL object.
   *
   * @param \Drupal\Core\Url $url
   *   AMP URL which should be validated.
   */
  public function setUrl(Url $url) {
    $this->url = $url;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    if (!empty($this->url)) {

      /* @var $manager \Drupal\plugin_type_example\SandwichPluginManager */
      $manager = \Drupal::service('plugin.manager.amp_validator_plugin');

      // Create an instance of the Cloudflare AMP Validator plugin.
      $plugin = $manager->createInstance('cloudflare');
      $plugin->setData($this->url);
      $plugin->validate();
      $this->valid = $plugin->isValid();
      $this->errors = $plugin->getErrors();
    }
  }

}
