<?php

namespace Drupal\funnel\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the form controller.
 */
class Settings extends ConfigFormBase {
  /**
   * AJAX Wrapper.
   *
   * @var wrapper
   */
  private $wrapper = 'funnel-results';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'funnel';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['funnel.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('funnel.settings');
    if (\Drupal::moduleHandler()->moduleExists('taxonomy')) {
      $form['funnel'] = [
        '#type' => 'details',
        '#title' => $this->t('Funnel Taxonomy'),
        '#open' => FALSE,
      ];
      $vocabulares = taxonomy_vocabulary_get_names();
      $default = [];
      if (is_array($config->get('vocabulary'))) {
        $default = $config->get('vocabulary');
      }
      $form['funnel']['vocabulary'] = [
        '#title' => $this->t('Funnel Vocabulary'),
        '#type' => 'checkboxes',
        '#options' => $vocabulares,
        '#default_value' => $default,
      ];
    }
    $options = [];
    $enity_types = [
      'node' => [
        'name' => 'Node',
        'module' => 'node',
      ],
    ];

    foreach ($enity_types as $enity_type => $enity_info) {
      $name = $enity_info['name'];
      $form[$enity_type] = [
        '#type' => 'details',
        '#title' => $this->t('@name funnels', ['@name' => $name]),
        '#open' => TRUE,
      ];
      if (!\Drupal::moduleHandler()->moduleExists($enity_info['module'])) {
        $form[$enity_type]['#open'] = FALSE;
        $form[$enity_type]["$enity_type-miss"] = [
          '#markup' => '<p>' . $this->t("Module '@module' not enabled.", ['@module' => $name]) . '</p>',
        ];
      }
      else {
        $options = [];
        $bundles = \Drupal::entityManager()->getBundleInfo($enity_type);
        if (!empty($bundles)) {
          foreach ($bundles as $key => $value) {
            $options[$key] = $value['label'];
          }
          $form[$enity_type]["$enity_type-bundles"] = [
            '#title' => $this->t("@name funnels display on", ['@name' => $name]),
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
   * Implements a form submit handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('funnel.settings');
    $enity_types = [
      'node' => [
        'name' => 'Node',
        'module' => 'node',
      ],
    ];
    foreach ($enity_types as $enity_type => $enity_info) {
      $config
        ->set("$enity_type-bundles", $form_state->getValue("$enity_type-bundles"));
    }

    $config
      ->set('vocabulary', $form_state->getValue('vocabulary'))
      ->save();
  }

}
