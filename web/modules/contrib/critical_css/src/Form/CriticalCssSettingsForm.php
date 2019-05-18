<?php

namespace Drupal\critical_css\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for critical css.
 */
class CriticalCssSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'critical_css_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['critical_css.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('critical_css.settings');

    if (!is_file('public://critical_css/loadCSS.min.js') ||
      !is_file('public://critical_css/cssrelpreload.min.js')) {
      drupal_set_message(
        $this->t(
          'Some Critical CSS libraries are missing. You should manually download Filament Group\'s <a href="@url1">loadCSS.min.js</a> and <a href="@url1">cssrelpreload.min.js</a> and place them into public://critical_css (typically sites/default/files/critical_css)',
          [
            '@url1' => 'https://github.com/filamentgroup/loadCSS/releases/download/v1.3.1/loadCSS.min.js',
            '@url2' => 'https://github.com/filamentgroup/loadCSS/releases/download/v1.3.1/cssrelpreload.min.js',
          ]
        ),
        'error'
      );
    }

    $form['critical_css_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $config->get('enabled'),
      '#description' => $this->t("Enable Critical CSS. Drupal cache must be rebuilt when this value changes."),
    ];

    $form['critical_css_enabled_for_logged_in_users'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled for logged-in users'),
      '#default_value' => $config->get('enabled_for_logged_in_users'),
      '#description' => $this->t("This option will enable Critical CSS for logged-in users. Since the contents of the critical CSS files are generated emulating an anonymous visit, it is not recommended to enable this option for logged-in users."),
    ];

    $form['critical_css_help'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('First, generate critical CSS files'),
      '#description' => $this->t("You MUST previously generate a critical CSS file for any bundle type or entity id you want to process. Using <a href=\"https://github.com/addyosmani/critical\" target='_blank'><strong>Addy Osmani's <em>critical</em></strong></a> or <a href=\"https://github.com/filamentgroup/criticalCSS\" target='_blank'><strong>Filament Group's criticalCSS</strong></a> is highly recommended:"),
    ];

    $form['critical_css_help']['gulp'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t("Addy Osmani's critical gulp task example"),
    ];

    $form['critical_css_help']['gulp']['example'] = [
      '#markup' => '<pre>' . file_get_contents(drupal_get_path('module', 'critical_css') . '/includes/sample-gulp.js') . '</pre>',
    ];

    $form['critical_css_dir_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Critical CSS files base directory (relative to %theme_path)', ['%theme_path' => drupal_get_path('theme', $this->config('system.theme')->get('default'))]),
      '#required' => TRUE,
      '#description' => $this->t('It must start with a leading slash. Enter a directory path relative to current theme, where critical CSS files are located (e.g., /css/critical). Inside that directory, "Critical CSS" will try to find any file named "{bundle_type}.css", "{entity_id}.css" or "{url}.css" (e.g., article.css, 1234.css, my-page-url.css, etc). If none of the previous filenames can be found, it will search for a file named "default-critical.css".'),
      '#default_value' => $config->get('dir_path'),
    ];

    $form['critical_css_excluded_ids'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exclude entity ids from Critical CSS processing'),
      '#required' => FALSE,
      '#description' => $this->t('Enter ids of entities (one per line) which should not be processed. These entities will load standard CSS (synchronously).'),
      '#default_value' => $config->get('excluded_ids'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $criticalCssDirPath = $form_state->getValue('critical_css_dir_path');
    if (substr($criticalCssDirPath, 0, 1) != '/') {
      $form_state->setErrorByName(
        'critical_css_dir_path',
        $this->t('Critical CSS files base directory must start with a leading slash.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('critical_css.settings');
    $config
      ->set('enabled', $form_state->getValue('critical_css_enabled'))
      ->set('enabled_for_logged_in_users', $form_state->getValue('critical_css_enabled_for_logged_in_users'))
      ->set('dir_path', $form_state->getValue('critical_css_dir_path'))
      ->set('excluded_ids', $form_state->getValue('critical_css_excluded_ids'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
