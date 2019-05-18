<?php

/**
 * @file
 * Contains \Drupal\file_chooser_field\Form\FileChooserFieldSettingsForm.
 */

namespace Drupal\file_chooser_field\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\file_chooser_field\FileChooserFieldCore;
use Drupal\file_chooser_field\Plugins;

/**
 * Configure site information settings for this site.
 */
class FileChooserFieldSettingsForm extends ConfigFormBase {

  /**
   * Constructs a \Drupal\file_chooser_field\FileChooserFieldSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

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
    return 'file_chooser_field_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['file_chooser_field.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('file_chooser_field.settings');

    $FileChooserFieldCore = new FileChooserFieldCore();

    $form['settings'] = [
      '#type' => 'vertical_tabs',
      '#attached' => [
        'library' => [
          'file_chooser_field/file_chooser_field.form'
        ],
      ],
    ];

    $plugins = $FileChooserFieldCore->loadPlugins();
    foreach ($plugins as $name => $plugin) {

      $form[$name] = [
        '#type' => 'details',
        '#title' => $plugin['name'],
        '#group' => 'settings',
      ];

      $form[$name][$name . '_enabled'] = [
        '#type' => 'checkbox',
        '#title' => t('Enable plugin'),
        '#default_value' => $config->get($name . '_enabled'),
        '#description' => $this->t("Disabled plugins are not visible in <em>File</em> and <em>Image</em> fields."),
      ];

      $configForm = $FileChooserFieldCore->pluginMethod($plugin['phpClassName'], 'configForm', [$config]);
      if (is_array($configForm)) {
        $form[$name] += $configForm;
      }

      $redirectCallback = $FileChooserFieldCore->pluginMethod($plugin['phpClassName'], 'redirectCallback', [$config]);
      if ($redirectCallback) {
        $form[$name]['file_chooser_field_' . $name . '_redirect_url'] = [
          '#markup' => '<em>' . \Drupal::url('file_chooser_field.redirect_callback', ['phpClassName' => $plugin['phpClassName']], ['absolute' => TRUE]) . '</em>',
          '#prefix' => '<div><strong>' . $this->t('Redirect URL') . '</strong></div>',
          '#suffix' => '<div class="description">'  .$this->t('This is the URL you will need for the redirect URL/OAuth authentication') . '</div>',
        ];
      }

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('file_chooser_field.settings');

    $FileChooserFieldCore = new FileChooserFieldCore();

    $plugins = $FileChooserFieldCore->loadPlugins();
    foreach ($plugins as $name => $plugin) {
      // Enable/Disable plugin.
      $config->set($name . '_enabled', $form_state->getValue($name . '_enabled'))
        ->save();
      // Save plugin settings.
      $FileChooserFieldCore->pluginMethod($plugin['phpClassName'], 'submitForm', [$config, $form_state]);
    }

    parent::submitForm($form, $form_state);

  }

}
