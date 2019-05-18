<?php

namespace Drupal\dea_request\Form;

use Drupal\Core\Form\ConfigFormBase;

class SettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dea_request.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dea_request.settings';
  }

}