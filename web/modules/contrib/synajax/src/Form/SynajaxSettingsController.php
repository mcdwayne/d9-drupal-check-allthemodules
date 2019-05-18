<?php

namespace Drupal\synajax\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings Form.
 */
class SynajaxSettingsController extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['synajax.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'synajax_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // If contact.module enabled, add contact forms.
    $config = $this->config('synajax.settings');
    $enity_types = [
      'contact_message' => [
        'name' => $this->t('Contact'),
        'module' => 'contact',
      ],
    ];

    foreach ($enity_types as $enity_type => $enity_info) {
      $name = $enity_info['name'];
      $form[$enity_type] = [
        '#type' => 'details',
        '#title' => $this->t('@name ajax only forms', ['@name' => $name]),
        '#open' => TRUE,
      ];
      if (!\Drupal::moduleHandler()->moduleExists($enity_info['module'])) {
        $form[$enity_type]['#open'] = FALSE;
        $form[$enity_type]["$enity_type-miss"] = [
          '#markup' => '<p>' . $this->t("Module '@module' does not enabled.", ['@module' => $name]) . '</p>',
        ];
      }
      else {
        $form[$enity_type]["$enity_type-mode"] = [
          '#title' => $this->t("Display mode"),
          '#type' => 'radios',
          '#options' => [
            'disable' => $this->t('Disable'),
            'all' => $this->t('All'),
            'custom' => $this->t('Custom bundles'),
          ],
          '#default_value' => $config->get("$enity_type-mode"),
        ];

        $options = [];
        $bundles = \Drupal::entityManager()->getBundleInfo($enity_type);
        if (!empty($bundles)) {
          foreach ($bundles as $key => $value) {
            $options[$key] = $value['label'];
          }
          $form[$enity_type]["$enity_type-bundles"] = [
            '#title' => $this->t("Custom ajax only @name bundles", ['@name' => $name]),
            '#type' => 'checkboxes',
            '#options' => $options,
          ];
          $default = $config->get("$enity_type-bundles");
          if (!empty($default)) {
            $form[$enity_type]["$enity_type-bundles"]['#default_value'] = $default;
          }
        }
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('synajax.settings');
    $enity_types = ['contact_message'];
    foreach ($enity_types as $enity_type) {
      $config
        ->set("$enity_type-mode", $form_state->getValue("$enity_type-mode"))
        ->set("$enity_type-bundles", $form_state->getValue("$enity_type-bundles"));
    }
    $config->save();
  }

}
