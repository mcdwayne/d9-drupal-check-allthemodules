<?php

namespace Drupal\context_datalayer\Plugin\ContextReaction;

use Drupal\context\ContextReactionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * DataLayer Context Reaction Plugin.
 *
 * @ContextReaction(
 *   id = "datalayer",
 *   label = @Translation("DataLayer")
 * )
 */
class DataLayer extends ContextReactionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'data' => [],
      'overwrite' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Populates dataLayer with key/value pairs.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $config = $this->getConfiguration();
    $data = [];
    foreach ($config['data'] as $item) {
      switch ($item['type']) {
        case 'integer':
          $value = (int) $item['value'];
          break;

        case 'boolean':
          $value = (bool) $item['value'];
          break;

        default:
          $value = $item['value'];
      }
      $token = \Drupal::service('token');
      $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
      $data[$item['key']] = $token->replace($value, [], ['langcode' => $langcode]);
    }
    return [
      'data' => $data,
      'overwrite' => $config['overwrite'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $remove_options = [];

    if (!empty($this->getConfiguration()['data'])) {
      $remove_options = $this->getConfiguration()['data'];
    }
    array_unshift($remove_options, '');
    unset($remove_options[0]);

    $header = [
      'key' => $this->t('Key'),
      'value' => $this->t('Value'),
      'type' => $this->t('Type'),
    ];

    $form['remove'] = [
      '#type' => 'container',
      '#suffix' => '<br>',
    ];

    $form['remove']['remove_table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $remove_options,
      '#empty' => $this->t('No key/value pairs found'),
    ];

    $form['remove']['remove_selected'] = [
      '#type' => 'submit',
      '#value' => $this->t('Remove selected pairs'),
      '#attributes' => ['class' => ['button--small']],
      '#submit' => ['::submitForm', '::save'],
    ];

    $form['datalayer_overwrite'] = [
      '#type' => 'checkbox',
      '#title' => t('Overwrite existing values'),
      '#description' => t('If a datalayer key is already set on a page, checking this option will allow you to overwrite it.'),
      '#default_value' => $this->getConfiguration()['overwrite'],
    ];

    $form['add_new_pair'] = [
      '#type' => 'checkbox',
      '#title' => t('Add a key/value pair'),
    ];

    $form['new'] = [
      '#id' => 'add_new_pair_fieldset',
      '#type' => 'fieldset',
      '#title' => $this->t('Add a key/value pair'),
      '#states' => [
        'visible' => [
          ':input[name="reactions[datalayer][add_new_pair]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['new']['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#maxlength' => 255,
    ];

    $form['new']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#maxlength' => 255,
      '#description' => t('Note the value field can accept tokens. Please use 0/1 for representing booleans.'),
    ];

    $form['new']['type'] = [
      '#type' => 'select',
      '#title' => t('Type'),
      '#options' => [
        'string' => $this->t('String'),
        'integer' => $this->t('Integer'),
        'boolean' => $this->t('Boolean'),
      ],
    ];

    $form['new']['add_selected'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add new pair'),
      '#attributes' => ['class' => ['button--small']],
      '#submit' => ['::submitForm', '::save'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Setup config data.
    $config = [
      'data' => $this->getConfiguration()['data'],
      'overwrite' => $form_state->getValue('datalayer_overwrite'),
    ];
    // Add new pair if checkbox is checked.
    if (!!$form_state->getValue('add_new_pair')) {
      $new_pair = $form_state->getValue('new');
      unset($new_pair['add_selected']);
      $config['data'][] = $new_pair;
    }
    // Remove any pairs selected for removal.
    if (!!count($config['data'])) {
      foreach ($form_state->getValue(['remove', 'remove_table']) as $pair => $selected) {
        if (!!$selected) {
          unset($config['data'][--$pair]);
        }
      }
    }
    $this->setConfiguration($config);
  }

}
