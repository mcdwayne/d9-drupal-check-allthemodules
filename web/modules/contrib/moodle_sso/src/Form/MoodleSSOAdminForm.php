<?php

namespace Drupal\moodle_sso\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 */
class MoodleSSOAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'moodle_sso_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['moodle_sso.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('moodle_sso.settings');

    $form['uri'] = [
      '#type' => 'textfield',
      '#title' => t("The url needed to reach moodle."),
      '#description' => t("Please provide the absolute url to reach moodle. Include the http or https, and do not include a trailing slash."),
      '#default_value' => $config->get('uri'),
    ];
    $form['logout'] = [
      '#type' => 'checkbox',
      '#title' => t("Log out of moodle"),
      '#description' => t("Log out of moodle when users log out of Drupal. Note: This just deletes the session cookie using the configuration below, and doesn't call any of moodle's logout processes."),
      '#default_value' => $config->get('logout'),
    ];
    $form['cookie_name'] = [
      '#type' => 'textfield',
      '#title' => t("Moodle Session Cookie Name."),
      '#description' => t("The name of the session cookie moodle uses. this will usually be \"MoodleSession\" but may be different in some cases. "),
      '#default_value' => $config->get('cookie_name'),
    ];
    $form['cookie_domain'] = [
      '#type' => 'textfield',
      '#title' => t("Cookie Domain."),
      '#description' => t("The cookie domain moodle has set. Make sure that the url/domain Drupal is running on has access to this cookie!"),
#TODO: default to drupal's cookie domain
      '#default_value' => $config->get('cookie_domain'),
#      '#default_value' => variable_get('moodle_sso_cookie_domain', $cookie_domain),
    ];
    $form['cookie_path'] = [
      '#type' => 'textfield',
      '#title' => t("Cookie Path"),
      '#description' => t("This is the path portion of the cookie. In most cases this will be \"/\" but in some cases it might be different. "),
      '#default_value' => $config->get('cookie_path'),
#      '#default_value' => variable_get('moodle_sso_cookie_path', '/'),
    ];


    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('moodle_sso.settings')
      ->set('uri', $form_state->getValue('uri'))
      ->set('logout', $form_state->getValue('logout'))
      ->set('cookie_name', $form_state->getValue('cookie_name'))
      ->set('cookie_domain', $form_state->getValue('cookie_domain'))
      ->set('cookie_path', $form_state->getValue('cookie_path'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
