<?php

namespace Drupal\wsdata;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Service for processing WSData requests.
 */
class WSDataService {
  protected $error;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entity_type_manager = $entity_type_manager;
  }

  /**
   * Call method to make the WSCall.
   */
  public function call($wscall, $method = NULL, $replacements = [], $data = NULL, $options = [], $key = NULL, $tokens = []) {
    if (!is_object($wscall)) {
      $wscall = $this->entity_type_manager->getStorage('wscall')->load($wscall);
    }

    $data = $wscall->call($method, $replacements, $data, $options, $key, $tokens);
    return $data;
  }

  public function getError() {
    if ($this->error) {
      $error = $this->error;
      $this->error = NULL;
      return $error;
    }
    return NULL;
  }

  /**
   * Generid WSCall setting form.
   */
  public function wscallForm($configurations = [], $wscall_option = NULL) {
    $wscalls = $this->entity_type_manager->getStorage('wscall')->loadMultiple();
    $options = ['' => t('- Select -')];
    foreach ($wscalls as $wscall) {
      $options[$wscall->id()] = $wscall->label();
    }

    $element['wscall'] = [
      '#type' => 'select',
      '#title' => t('Web Service Call'),
      '#options' => $options,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => 'Drupal\wsdata\WSDataService::wscallConfigurationsReplacements',
        'wrapper' => 'wscall-replacement-tokens-wrapper',
      ],
      '#default_value' => (isset($configurations['wscall']) ? $configurations['wscall'] : '')
    ];

    // Fetch the replacement tokens for this wscall.
    $element['replacements'] = [
      '#id' => 'wscall-replacement-tokens-wrapper',
      '#type' => 'container',
    ];

    // Based on the wscall create the replacements section of the wscall.
    if (!empty($wscall_option)) {
      foreach ($wscalls[$wscall_option]->getReplacements() as $replacement) {
        $element['replacements'][$replacement] = [
          '#type' => 'textfield',
          '#title' => $replacement,
          '#default_value' => (isset($configurations['replacements'][$replacement]) ? $configurations['replacements'][$replacement] : '')
        ];
      }
    }

    $element['data'] = [
      '#type' => 'textarea',
      '#title' => t('Data'),
      '#default_value' => (isset($configurations['data']) ? $configurations['data'] : '')
    ];

    $element['returnToken'] = [
      '#type' => 'textfield',
      '#title' => t('Token to select'),
      '#description' => t('Seperate element names with a ":" to select nested elements.'),
      '#default_value' => (isset($configurations['returnToken']) ? $configurations['returnToken'] : '')
    ];
    return $element;
  }

  /**
   * Ajax call back for the replacement pattern.
   */
  function wscallConfigurationsReplacements(array &$form, FormStateInterface $form_state) {
    if (isset($form['replacements'])) {
      return $form['replacements'];
    }
    elseif(isset($form['settings']['replacements'])) {
      return $form['settings']['replacements'];
    }
    else {
      return $form;
    }
  }
}
