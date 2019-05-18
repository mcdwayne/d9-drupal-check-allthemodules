<?php

namespace Drupal\prev_next\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Class PrevNextConfigForm.
 *
 * @package Drupal\prev_next\Form
 */
class PrevNextConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'prev_next_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('prev_next.settings');
    $form['batch_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Batch size'),
      '#description' => $this->t('Number of nodes to index during each cron run.'),
      '#size' => 6,
      '#maxlength' => 7,
      '#default_value' => $config->get('batch_size'),
      '#required' => TRUE,
    ];
    $form['node_type'] = [
      '#type' => 'details',
      '#title' => $this->t('Content types'),
      '#description' => $this->t('Define settings for each content type. If none of them is included, then all of them will be.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    foreach (NodeType::loadMultiple() as $bundle) {

      $type = $bundle->id();
      $bundle_config = \Drupal::configFactory()
        ->getEditable('prev_next.node_type.' . $type);
      $form['node_type'][$type] = [
        '#type' => 'details',
        '#title' => $bundle->label(),
        '#description' => $this->t('Note: changing one of these values will reset the entire Prev/Next index.'),
        '#open' => TRUE,
      ];
      $form['node_type'][$type]['include'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Include'),
        '#default_value' => $bundle_config->get('include'),
      ];
      $form['node_type'][$type]['current'] = [
        '#type' => 'hidden',
        '#default_value' => $bundle_config->get('current'),
      ];

      $form['node_type'][$type]['indexing_criteria'] = [
        '#title' => $this->t('Indexing criteria'),
        '#type' => 'select',
        '#options' => [
          'nid' => $this->t('Node ID'),
          'created' => $this->t('Post date'),
          'changed' => $this->t('Updated date'),
          'title' => $this->t('Title'),
        ],
        '#default_value' => $bundle_config->get('indexing_criteria'),
      ];
      $form['node_type'][$type]['indexing_criteria_current'] = [
        '#type' => 'hidden',
        '#value' => $bundle_config->get('indexing_criteria_current'),
      ];

      $form['node_type'][$type]['same_type'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Only nodes with same content type'),
        '#default_value' => $bundle_config->get('same_type'),
      ];
      $form['node_type'][$type]['same_type_current'] = [
        '#type' => 'hidden',
        '#default_value' => $bundle_config->get('same_type_current'),
      ];
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
    $this->config('prev_next.settings')
      ->set('batch_size', $form_state->getValue('batch_size'))
      ->save();
    // Save Node types settings.
    foreach ($form_state->getValue('node_type') as $bundle => $values) {
      $bundle_config = \Drupal::configFactory()
        ->getEditable('prev_next.node_type.' . $bundle);
      $bundle_config
        ->set('include', $values['include'])
        ->set('current', $values['current'])
        ->set('indexing_criteria', $values['indexing_criteria'])
        ->set('indexing_criteria_current', $values['indexing_criteria_current'])
        ->set('same_type', $values['same_type'])
        ->set('same_type_current', $values['same_type_current'])
        ->save();
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'prev_next.settings',
    ];
  }

}
