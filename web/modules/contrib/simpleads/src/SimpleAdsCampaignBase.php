<?php

namespace Drupal\simpleads;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

class SimpleAdsCampaignBase extends PluginBase implements SimpleAdsCampaignInterface {

  use StringTranslationTrait;

  /**
   * Get plugin name.
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL, $id = NULL) {

  }

  public function createFormSubmit($options, FormStateInterface $form_state, $type = NULL) {
    return $options;
  }

  public function updateFormSubmit($options, FormStateInterface $form_state, $type = NULL, $id = NULL) {
    return $options;
  }

  public function deleteFormSubmit($options, FormStateInterface $form_state, $type = NULL, $id = NULL) {
    return $options;
  }

  public function activate() {

  }

  public function deactivate() {

  }

}
