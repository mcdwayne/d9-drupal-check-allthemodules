<?php

namespace Drupal\copyscape\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Administrative form for Copyscape API and user settings route.
 */
class CopyscapeApiUserForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'copyscape_api_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $first = '') {
    $config = $this->config('copyscape.settings');

    $form['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API URL'),
      '#required' => TRUE,
      '#description' => $this->t('The Copyscape Premium API URL to execute queries against.'),
      '#default_value' => $config->get('api_url'),
    ];

    $form['api_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Username'),
      '#required' => TRUE,
      '#description' => $this->t('The username to authenticate with Copyscape Premium API'),
      '#default_value' => $config->get('api_user'),
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => t('API Key'),
      '#required' => TRUE,
      '#description' => $this->t('The API key to authenticate with Copyscape Premium API. You can create one')
        . ' <a href="http://www.copyscape.com/signup.php?sign_up&pro=1" target="_blank">' . t('here') . '</a>',
      '#default_value' => $config->get('api_key'),
    ];

    $form['users_bypass'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Bypass User IDs'),
      '#cols' => 60,
      '#rows' => 5,
      '#description' => $this->t('A list of user IDs that bypass the copyscape content verification, separated by comma.'),
      '#default_value' => $config->get('user_bypass'),
    ];

    $form['roles_bypass'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Bypass Roles'),
      '#options' => user_role_names(),
      '#description' => $this->t('Roles that bypass the copyscape content verification.'),
      '#default_value' => $config->get('roles_bypass'),
    ];

    $form['failures'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum fails number before user is blocked'),
      '#size' => '60',
      '#maxlength' => '5',
      '#description' => $this->t('0 = Disabled'),
      '#default_value' => $config->get('failures'),
    ];

    $form['logs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log successful responses'),
      '#require' => FALSE,
      '#default_value' => $config->get('logs'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('copyscape.settings')
      ->set('api_url', $values['api_url'])
      ->set('api_user', $values['api_user'])
      ->set('api_key', $values['api_key'])
      ->set('user_bypass', $values['users_bypass'])
      ->set('roles_bypass', $values['roles_bypass'])
      ->set('failures', $values['failures'])
      ->set('logs', $values['logs'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['copyscape.settings'];
  }
}
