<?php

namespace Drupal\regions_demo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Xss;

/**
 * Advanced regions demo form.
 */
class RegionsDemoForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'regions_demo_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string|null $demo_theme
   *   The machine name of the theme to be used for finding the templates files.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $demo_theme = NULL) {

    $files = self::getPageTemplates($demo_theme);
    $options = [];

    foreach ($files as &$file) {
      $options[$file->suggestion['key']] = $file->filename;
    }

    $form['advanced-region-demo'] = [
      '#type' => 'details',
      '#title' => $this->t('Avdanced region demonstration'),
    ];

    $form['advanced-region-demo']['help'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('To see the block regions for a specific custom page template, select the template file below and click "View".') . '</p>',
    ];

    $form['advanced-region-demo']['template'] = [
      '#title' => $this->t('Page templates'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => 'page',
    ];

    $form['demo_theme'] = [
      '#type' => 'value',
      '#value' => $demo_theme,
    ];

    $form['advanced-region-demo']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('View'),
    ];

    return $form;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $demo_theme = $form_state->getValue('demo_theme', $this->config('system.theme')->get('default'));
    $files = self::getPageTemplates($demo_theme);

    // Let's start by assuming the the submitted template does exist as a file.
    $existing_file = FALSE;

    foreach ($files as $file) {
      if (empty($file->suggestion['key']) || empty($form_state->getValue('template'))) {
        continue;
      }
      if ($file->suggestion['key'] == $form_state->getValue('template')) {
        $existing_file = TRUE;
      }
    }

    if (!$existing_file) {
      $form_state->setErrorByName('template', $this->t("The template file does not exist. It may have been deleted. Please clear the cache and re-submit the form again."));
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $demo_theme = $form_state->getValue('demo_theme', $this->config('system.theme')->get('default'));

    $form_state->setRedirectUrl(Url::fromUri(
      "internal:/admin/structure/block/demo/{$demo_theme}", [
        'query' => [
          'page_template' => Xss::filter($form_state->getValue('template')),
        ],
      ]
    ));
  }

  /**
   * PRIVATE MEMBERS.
   */

  /**
   * Helper function to return the list of eligible template files.
   *
   * @param string $theme_key
   *   Machine name of the theme to scan for files.
   * @param bool $reset
   *   Flag to ignore the cached values.
   *
   * @return array
   *   Array of stdClass'es representing each file.
   */
  public static function getPageTemplates($theme_key, $reset = FALSE) {
    if (!$theme_key) {
      return [];
    }

    $files = &drupal_static(__FUNCTION__);

    if (!$files || ($reset === TRUE)) {
      $templates_dir = drupal_get_path('theme', $theme_key);
      $files = file_scan_directory($templates_dir, '/^page.*.html.twig/');

      foreach ($files as &$file) {
        $file->suggestion = [];
        $file->suggestion['key'] = str_replace([
          '-',
          '.html',
        ], [
          '_',
          '',
        ], $file->name);
        $file->suggestion['short-file-name'] = str_replace('.html', '', $file->name);
      }
    }

    return $files;
  }

}
