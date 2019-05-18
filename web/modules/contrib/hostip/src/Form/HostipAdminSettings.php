<?php

namespace Drupal\hostip\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class HostipAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hostip_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'hostip.settings',
    ];
  }

    /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('hostip.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }
  

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['hostip_user_profile'] = [
      '#type' => 'checkbox',
      '#title' => t('Display location information on user profile page'),
      '#default_value' => $this->config('hostip.settings')->get('hostip_user_profile'),
    ];

    return parent::buildForm($form, $form_state);
  }
}
