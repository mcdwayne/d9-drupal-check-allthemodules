<?php

namespace Drupal\micro_user\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

class MicroUserConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['micro_user.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'micro_user_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('micro_user.settings');
    $form['prevent_login_master_host'] = [
      '#title' => t('Prevent users whithout permission to login on the master host.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('prevent_login_master_host'),
      '#description' => $this->t('Checking this option will prevent users without the permission "login master host" to login on the master hosts. Be careful before enable this setting. Users with the permission "Administer site entities" can always login on the master host.')
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('micro_user.settings');
    $config->set('prevent_login_master_host', $form_state->getValue('prevent_login_master_host'));
    $config->save();
  }

}
