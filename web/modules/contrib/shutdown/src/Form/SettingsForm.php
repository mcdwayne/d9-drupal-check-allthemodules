<?php

namespace Drupal\shutdown\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure shutdown settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shutdown_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['shutdown.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('shutdown.settings');

    $form['shutdown_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Shut down website?'),
      '#default_value' => $config->get('shutdown_enable'),
      '#description' => $this->t('If you enable this option, your website will be closed for all visitors except those who have adequate permission.'),
    ];
    $form['shutdown_redirect_page'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect page'),
      '#default_value' => $config->get('shutdown_redirect_page'),
      '#description' => $this->t('Specify the page to redirect to. This could be a Drupal internal path (e.g. /node/123), an external URL or a file relative to drupal directory (e.g. /closed.html).'),
    ];
    $form['shutdown_excluded_paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paths to exclude'),
      '#default_value' => $config->get('shutdown_excluded_paths'),
      '#description' => $this->t("Specify the internal paths that should remain accessible. Enter one path per line. The '*' character is a wildcard. An example path is /user/* for every user page."),
    ];

    $form['advanced_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];
    $form['advanced_settings']['shutdown_allow_http_cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow cron.php to be run during website closed?'),
      '#default_value' => $config->get('shutdown_allow_http_cron'),
      '#description' => $this->t('If you disable this option, cron requests made through cron.php file will be ignored.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $redirect_page = $form_state->getValue('shutdown_redirect_page');

    // In case website should be shut down, make sure redirect page is not
    // empty.
    if ($form_state->getValue('shutdown_enable') && empty($redirect_page)) {
      $form_state->setErrorByName('shutdown_redirect_page', $this->t('Please specify a redirect page'));
    }
    // If page is an external URL, make sure it is valid.
    elseif (UrlHelper::isExternal($redirect_page)) {
      if (!UrlHelper::isValid($redirect_page)) {
        $form_state->setErrorByName('shutdown_redirect_page', $this->t('%page is not valid.', ['%page' => $redirect_page]));
      }
    }
    // Make sure redirect page is not index.php to avoid infinite redirections.
    elseif ($redirect_page == 'index.php') {
      $form_state->setErrorByName('shutdown_redirect_page', $this->t('%page cannot be used for page redirection.', ['%page' => $redirect_page]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get previous settings.
    $config = $this->config('shutdown.settings');

    // If state changed from previously set value, trigger an action.
    if ($config->get('shutdown_enable') != $form_state->getValue('shutdown_enable')) {
      $shutdown = \Drupal::service('shutdown.core');
      switch ($form_state->getValue('shutdown_enable')) {
        case 1:
          $shutdown->shutWebsite();
          drupal_set_message($this->t('Website is now shut down.'), 'warning');
          break;

        case 0:
          $shutdown->openWebsite();
          drupal_set_message($this->t('Website is now opened up.'));
          break;
      }
    }

    // Save configuration.
    $this->config('shutdown.settings')
      ->set('shutdown_enable', $form_state->getValue('shutdown_enable'))
      ->set('shutdown_redirect_page', $form_state->getValue('shutdown_redirect_page'))
      ->set('shutdown_excluded_paths', $form_state->getValue('shutdown_excluded_paths'))
      ->set('shutdown_allow_http_cron', $form_state->getValue('shutdown_allow_http_cron'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
