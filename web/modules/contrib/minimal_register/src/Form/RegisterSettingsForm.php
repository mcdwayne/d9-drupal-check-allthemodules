<?php

namespace Drupal\minimal_register\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;

/**
 * Configure Register settings for this site.
 */
class RegisterSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'minimal_register_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'minimal_register.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('minimal_register.settings');
    $options = $this->getRoleList();
    //Role selected
    $form['role_selected'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Role selected'),
      '#default_value' => $config->get('role_selected'),
    );

    //Welcome message text area
    $form['welcome_message'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Welcome message'),
      '#required' => TRUE,
      '#default_value' => $config->get('welcome_message'),
      '#base_type' => 'textarea',
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration
    $this->configFactory->getEditable('minimal_register.settings')
      // Set the submitted configuration setting
      ->set('role_selected', $form_state->getValue('role_selected'))
      ->set('welcome_message', $form_state->getValue('welcome_message')['value'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * @return array, list of available roles
   */
  public function getRoleList() {
    $roles = array();
    foreach (Role::loadMultiple() as $role) {
      if ($role->id() != "anonymous" && $role->id() != "authenticated") {
        $roles[$role->id()] = $role->id();
      }
    }
    return $roles;
  }
}
