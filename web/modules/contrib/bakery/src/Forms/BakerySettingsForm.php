<?php

namespace Drupal\bakery\Forms;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure bakery settings for this site.
 */
class BakerySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bakery_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bakery.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('bakery.settings');

    $form['bakery_is_master'] = array(
      '#type' => 'checkbox',
      '#title' => 'Is this the master site?',
      '#default_value' => $config->get('bakery_is_master'),
      '#description' => t('On the master site, accounts need to be created by traditional processes, i.e by a user registering or an admin creating them.'),
    );

    $form['bakery_master'] = array(
      '#type' => 'textfield',
      '#title' => 'Master site',
      '#default_value' => $config->get('bakery_master'),
      '#description' => t('Specify the master site for your bakery network.'),
    );

    $form['bakery_slaves'] = array(
      '#type' => 'textarea',
      '#title' => 'Slave sites',
      '#default_value' => implode("\n", $config->get('bakery_slaves') || array()),
      '#description' => t('Specify any slave sites in your bakery network that you want to update if a user changes email or username on the master. Enter one site per line, in the form "http://sub.example.com/".'),
    );

    $form['bakery_help_text'] = array(
      '#type' => 'textarea',
      '#title' => 'Help text for users with synch problems.',
      '#default_value' => $config->get('bakery_help_text'),
      '#description' => t('This message will be shown to users if/when they have problems synching their accounts. It is an alternative to the "self repair" option and can be blank.'),
    );

    $form['bakery_freshness'] = array(
      '#type' => 'textfield',
      '#title' => 'Seconds of age before a cookie is old',
      '#default_value' => $config->get('bakery_freshness'),
    );

    $form['bakery_key'] = array(
      '#type' => 'textfield',
      '#title' => 'Private key for cookie validation',
      '#default_value' => $config->get('bakery_key'),
    );

    $form['bakery_domain'] = array(
      '#type' => 'textfield',
      '#title' => 'Cookie domain',
      '#default_value' => $config->get('bakery_domain'),
    );

    $default = $config->get('bakery_supported_fields');
    $default['mail'] = 'mail';
    $default['name'] = 'name';
    $options = array(
      'name' => t('username'),
      'mail' => t('e-mail'),
      'status' => t('status'),
      'picture' => t('user picture'),
      'language' => t('language'),
      'signature' => t('signature'),
    );
    // TODO: need to add profile fileds
    /*
    if (module_exists('profile')) {
    $result = db_query('SELECT name, title FROM {profile_field}
    ORDER BY category, weight');
    foreach ($result as $field) {
    $options[$field->name] = check_plain($field->title);
    }
    }
     */
    $form['bakery_supported_fields'] = array(
      '#type' => 'checkboxes',
      '#title' => 'Supported profile fields',
      '#default_value' => $default,
      '#options' => $options,
      '#description' => t('Choose the profile fields that should be exported by the master and imported on the slaves. Username and E-mail are always exported. The correct export of individual fields may depend on the appropriate settings for other modules on both master and slaves. You need to configure this setting on both the master and the slaves.'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('bakery.settings')
      ->set('bakery_is_master', $form_state->getValue('bakery_is_master'))
      ->set('bakery_master', trim($form_state->getValue('bakery_master'), '/') . '/')
      ->set('bakery_help_text', $form_state->getValue('bakery_help_text'))
      ->set('bakery_freshness', $form_state->getValue('bakery_freshness'))
      ->set('bakery_key', $form_state->getValue('bakery_key'))
      ->set('bakery_domain', $form_state->getValue('bakery_domain'))
      ->set('bakery_supported_fields', $form_state->getValue('bakery_supported_fields'));
    if ($form_state->getValue('bakery_slaves') && !empty($form_state->getValue('bakery_slaves'))) {
      // Transform the text string into an array.
      $slaves = explode("\n", trim(str_replace("\r", '', $form_state->getValue('bakery_slaves'))));
      // For each entry, remove the trailing slash
      // (if present) and concatenate with a new trailing slash.
      foreach ($slaves as &$slave) {
        $slave = trim($slave, '/') . '/';
      }
      $this->config('bakery.settings')
        ->set('bakery_slaves', $slaves)
        ->save();
    }
    else {
      $this->config('bakery.settings')
        ->set('bakery_slaves', array())
        ->save();
    }
    parent::submitForm($form, $form_state);
  }

}
