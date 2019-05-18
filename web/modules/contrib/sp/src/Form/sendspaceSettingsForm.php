<?php
/**
 * @file
 * Contains \Drupal\sendspace\Form\sendspaceSettingsForm
 */
namespace Drupal\sendspace\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure sendspace settings for this site.
 */


class sendspaceSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sendspace_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sendspace.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $config_defaults = $this->configFactory->get('sendspace.settings');
    $config_defaults = \Drupal::service('config.factory')->getEditable('sendspace.settings');
    var_dump($config_defaults->get('session_cookie_key')); exit;

    //$config = $this->config('sendspace.settings');

    $form['sendspace_api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Sendspace api key'),
      //'#default_value' => $config->get('sendspace_api_key'),
    );

    $form['session_cookie_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Session cookie key'),
      //'#default_value' => $config->get('session_cookie_key') === ''? $config_defaults->get('session_cookie_key'):$config->get('session_cookie_key'),
    );

    $form['session_cookie_info_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Session cookie info key'),
      //'#default_value' => $config->get('session_cookie_info_key') === ''? $config_defaults->get('session_cookie_info_key'):$config->get('session_cookie_info_key'),
    );

    $form['my_application_version'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('My application version'),
      //'#default_value' => $config->get('my_application_version') === ''? $config_defaults->get('my_application_version'):$config->get('my_application_version'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('sendspace.settings');
    $config->set('sendspace_api_key', $form_state->getValue('sendspace_api_key'));
    $config->set('session_cookie_key', $form_state->getValue('session_cookie_key'));
    $config->set('session_cookie_info_key', $form_state->getValue('session_cookie_info_key'));
    $config->set('my_application_version', $form_state->getValue('my_application_version'));
    $config->save();

    parent::submitForm($form, $form_state);
  }
}
?>
