<?php

namespace Drupal\fiu_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\fiu_ui\Generator\CSSGenerator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures fiu ui settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fiu_ui_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'fiu_ui.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('fiu_ui.settings');
    $moduleHandler = \Drupal::service('module_handler');

    $form['status'] = [
      '#title' => $this->t('Manage FIU widget'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('status') ?? 0,
    ];

    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General FIU UI widget settings.'),
      '#collapsible' => TRUE,
      '#attributes' => [
        'class' => [
          'fiu-fieldset-settings'
        ],
      ],
      '#states' => [
        'invisible' => [
          ':input[name="status"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $data = self::getPreviewData();
    $preview = [
      '#theme' => 'fiu_ui_preview',
      '#data' => $data,
    ];

    $form['general']['preview'] = [
      '#markup' => render($preview),
    ];

    $form['general']['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#default_value' => $config->get('width'),
    ];

    $form['general']['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#default_value' => $config->get('height'),
    ];

    $form['general']['background'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Background'),
      '#default_value' => $config->get('background'),
    ];

    // Label settings.
    $form['general']['label_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Label size'),
      '#default_value' => $config->get('label_size'),
    ];

    $form['general']['label_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Label color'),
      '#default_value' => $config->get('label_color'),
    ];

    $form['general']['label_color_hover'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Label hover color'),
      '#default_value' => $config->get('label_color_hover'),
    ];

    // 'Open File Browser' link settings.
    if ($moduleHandler->moduleExists('imce')) {
      $form['general']['imce_size'] = [
        '#type' => 'number',
        '#title' => $this->t('IMCE link size'),
        '#default_value' => $config->get('imce_size'),
      ];

      $form['general']['imce_color'] = [
        '#type' => 'jquery_colorpicker',
        '#title' => $this->t('IMCE link color'),
        '#default_value' => $config->get('imce_color'),
      ];

      $form['general']['imce_color_hover'] = [
        '#type' => 'jquery_colorpicker',
        '#title' => $this->t('IMCE link hover color'),
        '#default_value' => $config->get('imce_color_hover'),
      ];
    }

    // FileField Sources module links settings.
    if ($moduleHandler->moduleExists('filefield_sources')) {
      $form['general']['sources_links_size'] = [
        '#type' => 'number',
        '#title' => $this->t('FileField Sources links size'),
        '#default_value' => $config->get('sources_links_size'),
      ];

      // 'Upload' link colors.
      $form['general']['upload_color'] = [
        '#type' => 'jquery_colorpicker',
        '#title' => $this->t('Upload link color'),
        '#default_value' => $config->get('upload_color'),
      ];

      $form['general']['upload_color_hover'] = [
        '#type' => 'jquery_colorpicker',
        '#title' => $this->t('Upload link hover color'),
        '#default_value' => $config->get('upload_color_hover'),
      ];

      // 'Remote URL' link colors.
      $form['general']['remote_color'] = [
        '#type' => 'jquery_colorpicker',
        '#title' => $this->t('Remote URL link color'),
        '#default_value' => $config->get('remote_color'),
      ];

      $form['general']['remote_color_hover'] = [
        '#type' => 'jquery_colorpicker',
        '#title' => $this->t('Remote URL link hover color'),
        '#default_value' => $config->get('remote_color_hover'),
      ];

      // 'Reference existing' link colors.
      $form['general']['ref_color'] = [
        '#type' => 'jquery_colorpicker',
        '#title' => $this->t('Reference link color'),
        '#default_value' => $config->get('ref_color'),
      ];

      $form['general']['ref_color_hover'] = [
        '#type' => 'jquery_colorpicker',
        '#title' => $this->t('Reference link hover color'),
        '#default_value' => $config->get('ref_color_hover'),
      ];
    }

    $form['#attached']['library'][] = 'fiu_ui/admin';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    foreach ($values as $key => $value) {
      $this->config('fiu_ui.settings')->set($key, $value)->save();
    }

    CSSGenerator::generate();

    parent::submitForm($form, $form_state);
  }

  public static function getPreviewData() {
    $data = [];

    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('imce')) {
      $data['imce'] = t('Open File Browser');
    }
    if ($moduleHandler->moduleExists('filefield_sources')) {
      $data['filesource'] = TRUE;
    }

    return $data;
  }
}
