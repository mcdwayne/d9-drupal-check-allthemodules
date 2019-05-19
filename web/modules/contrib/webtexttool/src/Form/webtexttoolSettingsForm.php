<?php

/**
 * @file
 * Contains \Drupal\example\Form\webtexttoolSettingsForm
 */
namespace Drupal\webtexttool\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure example settings for this site.
 */
class webtexttoolSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webtexttool_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'webtexttool.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webtexttool.settings');

    $url = Url::fromRoute('system.admin_config_webtexttool.register');
    $form['webtexttool_register'] = array(
      '#prefix' => '<div class="intro">' . $this->t('Do you have an account on webtexttool.com? Please enter the credentials below. Or register at the @url', array('@url' => $this->l($this->t('webtexttool register form'), $url))),
      '#suffix' => '</div>',
    );

    $form['webtexttool_user'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $config->get('user'),
      '#description' => $this->t('The username to connect to webtexttool.com .'),
    );

    $form['webtexttool_pass'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $config->get('pass'),
      '#description' => $this->t('The passwortd to connect to webtexttool.com .') . $config->get('pass'),
    );

    $form['webtexttool_language'] = array(
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => array('en' => t('English'), 'nl' => t('Dutch')),
      '#default_value' => $config->get('language') ,
      '#description' => t('The default language of the tool itself. At this moment the tool itself is only avaible in Dutch and English.'),
    );

    $form['webtexttool_authenticate'] = array(
      '#type' => 'checkbox',
      '#title' => t('Drupal site protected'),
      '#default_value' => $config->get('authenticate', 0),
      '#description' => t('Check this when this site protected by a .htaccess or the shield module.'),
    );

    $form['webtexttool_authenticate_credentials'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Username:password'),
      '#attributes' => array('value' => $config->get('authenticate_credentials', '')),
      '#description' => $this->t('Fill in the username:password to connect to the local site.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('webtexttool.settings');
    $config->set('user', $form_state->getValue('webtexttool_user'))->save();
    $config->set('pass', $form_state->getValue('webtexttool_pass'))->save();
    $config->set('language', $form_state->getValue('webtexttool_language'))->save();
    $config->set('authenticate', $form_state->getValue('webtexttool_authenticate'))->save();
    $config->set('authenticate_credentials', $form_state->getValue('webtexttool_authenticate_credentials'))->save();

    \Drupal::service('webtexttool.webtexttool_controller')->webtexttoolSetToken('');

    parent::submitForm($form, $form_state);
  }
}