<?php

namespace Drupal\select2boxes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Select2BoxesConfigForm.
 *
 * @package Drupal\select2boxes\Form
 */
class Select2BoxesConfigForm extends ConfigFormBase {
  /**
   * List of allowed versions of Select2 library.
   *
   * @var array
   */
  protected static $allowedVersions = [
    '4.0.1',
    '4.0.2',
    '4.0.3',
    '4.0.4',
    '4.0.5',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['select2boxes.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'select2boxes_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $conf      = $this->config($this->getEditableConfigNames()[0]);
    $versions  = array_combine(static::$allowedVersions, static::$allowedVersions);
    $providers = ['cdn' => 'CDN'];

    // Setting configurations.
    // Global widget configurations.
    $form['global_options'] = [
      '#type'  => 'details',
      '#title' => $this->t('Global settings'),
      '#open'  => TRUE,
      '#group' => 'container',
    ];
    $form['global_options']['select2_global'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable select2 widgets for all dropdown and multiselect lists'),
      '#default_value' => $conf->get('select2_global'),
    ];
    $form['global_options']['disable_for_admin_pages'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Disable select2 widgets for admin pages'),
      '#default_value' => $conf->get('disable_for_admin_pages'),
      '#states'        => [
        'visible' => [
          ":input[name=\"select2_global\"]" => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $form['global_options']['limited_search'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Limit search box visibility by list length'),
      '#default_value' => $conf->get('limited_search'),
    ];
    $form['global_options']['minimum_search_length'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Minimum list length'),
      '#default_value' => $conf->get('minimum_search_length'),
      '#states'        => [
        'visible' => [
          ":input[name=\"limited_search\"]" => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    // Library configurations.
    $form['library'] = [
      '#type'  => 'details',
      '#title' => $this->t('Library'),
      '#open'  => TRUE,
      '#group' => 'container',
    ];
    $form['library']['provider'] = [
      '#type'          => 'select',
      '#options'       => $providers,
      '#title'         => $this->t('Select2 library provider'),
      '#default_value' => $conf->get('provider'),
    ];
    $form['library']['version'] = [
      '#type'          => 'select',
      '#options'       => $versions,
      '#title'         => $this->t('Version'),
      '#default_value' => $conf->get('version'),
    ];
    $form['library']['url'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Select2 CDN URL'),
      '#description'   => $this->t('It is best to use https protocols here as it will allow more flexibility if the need ever arises'),
      '#default_value' => $conf->get('url'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fields = [
      'limited_search',
      'select2_global',
      'disable_for_admin_pages',
      'minimum_search_length',
      'provider',
      'version',
      'url',
    ];
    $editable = $this->configFactory
      ->getEditable($this->getEditableConfigNames()[0]);
    foreach ($fields as $value) {
      if ($form_state->hasValue($value)) {
        $editable->set($value, $form_state->getValue($value));
      }
    }
    $editable->save();
    parent::submitForm($form, $form_state);
  }

}
