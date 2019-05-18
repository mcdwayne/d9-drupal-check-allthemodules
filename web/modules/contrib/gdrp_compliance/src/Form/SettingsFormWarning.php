<?php

namespace Drupal\gdrp_compliance\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the form controller.
 */
class SettingsFormWarning extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gdrp_compliance';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['gdrp_compliance.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gdrp_compliance.settings');
    $enity_types = [
      'contact_message' => [
        'name' => 'Contact',
        'module' => 'contact',
      ],
      'node' => [
        'name' => 'Node',
        'module' => 'node',
      ],
    ];
    $form['from-morelink'] = [
      '#title' => $this->t('Url for form [Link to site policy agreement].'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $config->get('from-morelink'),
      '#description' => $this->t('Relative path starts with "/", or absolute start with http/https.'),
    ];
    $form['user'] = [
      '#type' => 'details',
      '#title' => $this->t('@module GDRP form warning', ['@module' => 'user']),
      '#open' => TRUE,
    ];
    $form["user"]['user-register'] = [
      '#title' => $this->t('Enable on user registration'),
      '#description' => $this->t('Display alert on user-register form (/user/register).'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('user-register'),
    ];
    $form["user"]['user-login'] = [
      '#title' => $this->t('Enable on user login'),
      '#description' => $this->t('Display alert on user-login form (/user/login).'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('user-login'),
    ];
    foreach ($enity_types as $enity_type => $enity_info) {
      $name = $enity_info['name'];
      $form[$enity_type] = [
        '#type' => 'details',
        '#title' => $this->t('@name GDRP form warning', ['@name' => $name]),
        '#open' => TRUE,
      ];
      if (!\Drupal::moduleHandler()->moduleExists($enity_info['module'])) {
        $form[$enity_type]['#open'] = FALSE;
        $form[$enity_type]["$enity_type-miss"] = [
          '#markup' => '<p>' . $this->t("Module '@module' not enabled.", ['@module' => $name]) . '</p>',
        ];
      }
      else {
        $form[$enity_type]["$enity_type-mode"] = [
          '#title' => $this->t("Display mode"),
          '#type' => 'radios',
          '#options' => [
            'disable' => 'Disable',
            'all' => 'All',
            'custom' => 'Custom bundles',
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
            '#title' => $this->t("@name bundles warning display on", ['@name' => $name]),
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
    $form_state->setRebuild(TRUE);
    $config = $this->config('gdrp_compliance.settings');
    $enity_types = [
      'contact_message' => [
        'name' => 'Contact',
        'module' => 'contact',
      ],
      'node' => [
        'name' => 'Node',
        'module' => 'node',
      ],
    ];
    foreach ($enity_types as $enity_type => $enity_info) {
      $config
        ->set("$enity_type-mode", $form_state->getValue("$enity_type-mode"))
        ->set("$enity_type-bundles", $form_state->getValue("$enity_type-bundles"));
    }
    $config
      ->set('from-morelink', $form_state->getValue('from-morelink'))
      ->set('user-login', $form_state->getValue('user-login'))
      ->set('user-register', $form_state->getValue('user-register'))
      ->save();
  }

}
