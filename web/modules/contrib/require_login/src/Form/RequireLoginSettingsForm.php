<?php

namespace Drupal\require_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Require Login settings for this site.
 */
class RequireLoginSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'require_login_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['require_login.config'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('require_login.config');

    // Login and destination paths.
    $form['require_login_auth_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login form path'),
      '#description' => $this->t('Login path. Default is /user/login.'),
      '#default_value' => $config->get('auth_path') ? $config->get('auth_path') : 'user/login',
    ];
    $form['require_login_destination_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login destination path'),
      '#description' => $this->t('Login destination path. Leave blank to use default referer path.'),
      '#default_value' => $config->get('destination_path'),
    ];

    // Access denied message.
    $form['require_login_deny_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Access denied message'),
      '#description' => $this->t('Message shown to anonymous users attempting to access a restricted page. Leave blank to disable.'),
      '#default_value' => $config->get('deny_message'),
    ];

    // Excluded exceptions and paths.
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
    ];
    $form['advanced']['excluded_403'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude 403 (access denied) page'),
      '#description' => $this->t('Allow anonymous users to view the 403 (access denied) page.'),
      '#default_value' => $config->get('excluded_403'),
    ];
    $form['advanced']['excluded_404'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude 404 (not found) page'),
      '#description' => $this->t('Allow anonymous users to view the 404 (not found) page.'),
      '#default_value' => $config->get('excluded_404'),
    ];
    $items = [
      '#theme' => 'item_list',
      '#prefix' => $this->t('Disable login requirement on specific paths. <strong>Limit one per line.</strong>'),
      '#items' => [
        $this->t('Use &lt;front&gt; to exclude the front page.'),
        $this->t('Use internal paths to exclude site pages. <em>Examples: /about/contact, /blog?hello=world</em>'),
      ],
    ];
    $form['advanced']['require_login_excluded_paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Path exclusions'),
      '#description' => render($items),
      '#default_value' => $config->get('excluded_paths'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $absolutes = [];

    // Validate excluded paths formatting.
    $exclude_paths = explode(PHP_EOL, $form_state->getValue('require_login_excluded_paths'));
    foreach ($exclude_paths as $key => $exclude_path) {
      $exclude_path = trim($exclude_path);
      if (empty($exclude_path) || $exclude_path == '<front>') {
        continue;
      }
      $url = parse_url($exclude_path);

      // Detect protocol or domain name.
      if (isset($url['scheme']) || isset($url['host']) || preg_match('/^www./i', $url['path'])) {
        $absolutes[] = trim($exclude_path);
      }

      // Confirm leading forward slash present.
      elseif (substr($exclude_path, 0, 1) != '/') {
        $exclude_paths[$key] = '/' . $exclude_path;
      }

      // Trim whitespace.
      else {
        $exclude_paths[$key] = $exclude_path;
      }
    }
    $form_state->setValue('require_login_excluded_paths', implode(PHP_EOL, $exclude_paths));

    if ($absolutes) {
      $form_state->setErrorByName('require_login_excluded_paths', $this->t("Excluded paths cannot include a protocol or domain name. Invalid paths:<br />!paths", [
        '!paths' => implode('<br />', $absolutes),
      ]));
    }

    // Validate login and destination path formatting.
    foreach (['require_login_auth_path', 'require_login_destination_path'] as $form_key) {
      $path = trim($form_state->getValue($form_key));
      if (!empty($path)) {
        $url = parse_url($path);

        // Detect protocol or domain name.
        if (isset($url['scheme']) || isset($url['host']) || preg_match('/^www./i', $url['path'])) {
          $form_state->setErrorByName($form_key, $this->t('External URL detected. Must enter a relative path.'));
        }

        // Confirm leading forward slash present.
        elseif (substr($path, 0, 1) != '/') {
          $form_state->setValue($form_key, '/' . $path);
        }

        // Trim whitespace.
        else {
          $form_state->setValue($form_key, $path);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('require_login.config')
      ->set('auth_path', $form_state->getValue('require_login_auth_path'))
      ->set('destination_path', $form_state->getValue('require_login_destination_path'))
      ->set('deny_message', $form_state->getValue('require_login_deny_message'))
      ->set('excluded_403', $form_state->getValue('excluded_403'))
      ->set('excluded_404', $form_state->getValue('excluded_404'))
      ->set('excluded_paths', $form_state->getValue('require_login_excluded_paths'))
      ->save();

    // Must rebuild caches for settings to take immediate effect.
    drupal_flush_all_caches();

    parent::submitForm($form, $form_state);
  }

}
