<?php

/**
 * @file
 * Contains \Drupal\collect\Form\CollectSettingsForm.
 */

namespace Drupal\collect\Form;

use Drupal\collect_common\Form\CollectSettingsFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure collect settings.
 */
class CollectSettingsForm extends CollectSettingsFormBase {

  /**
   * The configuration name.
   *
   * @var string
   */
  protected $configurationName = 'collect.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'collect_settings_page';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['collect.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config($this->configurationName)->save();
  }

}
