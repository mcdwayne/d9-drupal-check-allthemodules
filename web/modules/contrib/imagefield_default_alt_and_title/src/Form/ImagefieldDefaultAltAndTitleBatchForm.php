<?php

/**
 * @file
 * Contains \Drupal\imagefield_default_alt_and_title\Form\ImagefieldDefaultAltAndTitleBatchForm.
 */

namespace Drupal\imagefield_default_alt_and_title\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Batch form.
 *
 * @package Drupal\imagefield_default_alt_and_title\Form
 */
class ImagefieldDefaultAltAndTitleBatchForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'imagefield-alt-title-batch';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['default_alt'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Default Alt'),
    );
    $form['default_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Default Title'),
    );

    $node_options = $this->ImagefieldDefaultAltAndTitleEntityListByType('node_type');
    if (!empty($node_options)) {
      $form['node_type'] = array(
        '#type' => 'details',
        '#title' => $this->t('Node types'),
        '#open' => TRUE,
      );
      $form['node_type']['node_entity_types'] = array(
        '#type' => 'checkboxes',
        '#options' => $this->ImagefieldDefaultAltAndTitleEntityListByType('node_type'),
      );
    }

    $taxonomy_options = $this->ImagefieldDefaultAltAndTitleEntityListByType('taxonomy_vocabulary');
    if (!empty($taxonomy_options)) {
      $form['taxonomy_vocabulary'] = array(
        '#type' => 'details',
        '#title' => $this->t('Taxonomy vocabularies'),
        '#open' => TRUE,
      );
      $form['taxonomy_vocabulary']['taxonomy_entity_types'] = array(
        '#type' => 'checkboxes',
        '#options' => $this->ImagefieldDefaultAltAndTitleEntityListByType('taxonomy_vocabulary'),
      );
    }

    $commerce_options = $this->ImagefieldDefaultAltAndTitleEntityListByType('commerce_product_type');
    if (!empty($commerce_options)) {
      $form['commerce_product'] = array(
        '#type' => 'details',
        '#title' => $this->t('Commerce product type'),
        '#open' => TRUE,
      );
      $form['commerce_product']['commerce_entity_types'] = array(
        '#type' => 'checkboxes',
        '#options' => $this->ImagefieldDefaultAltAndTitleEntityListByType('commerce_product_type'),
      );
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Start'),
    );

    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operations = [];

    \Drupal::configFactory()
           ->getEditable('system.site')
           ->set('imagefield_default_alt_and_title_default_values', [
             'alt' => $form_state->getValue(['default_alt']),
             'title' => $form_state->getValue(['default_title']),
           ])
           ->save();

    if (!empty($form_state->getValue(['node_entity_types']))) {
      $node_query = \Drupal::database()->select('node', 'n');
      $node_query->fields('n', ['nid']);
      $node_query->condition('n.type', $form_state->getValue(['node_entity_types']), 'IN');
      $node_result = $node_query->execute()->fetchCol();
      $operations[] = [
        '\Drupal\imagefield_default_alt_and_title\ImagefieldDefaultAltAndTitleBatch::addedData',
        [$node_result, 'node_entity'],
      ];
    }
    if (!empty($form_state->getValue(['taxonomy_entity_types']))) {
      $tax_query = \Drupal::database()->select('taxonomy_term_field_data', 't');
      $tax_query->fields('t', ['tid']);
      $tax_query->condition('t.vid', $form_state->getValue(['taxonomy_entity_types']), 'IN');
      $node_result = $tax_query->execute()->fetchCol();

      $operations[] = [
        '\Drupal\imagefield_default_alt_and_title\ImagefieldDefaultAltAndTitleBatch::addedData',
        [$node_result, 'taxonomy_vocabulary'],
      ];
    }
    if (!empty($form_state->getValue(['commerce_entity_types']))) {
      $com_query = \Drupal::database()->select('commerce_product', 'cp');
      $com_query->fields('cp', ['product_id']);
      $com_query->condition('cp.type', $form_state->getValue(['commerce_entity_types']), 'IN');
      $node_result = $com_query->execute()->fetchCol();

      $operations[] = [
        '\Drupal\imagefield_default_alt_and_title\ImagefieldDefaultAltAndTitleBatch::addedData',
        [$node_result, 'commerce_product_type'],
      ];
    }

    $batch = array(
      'title' => $this->t('Update images...'),
      'operations' => $operations,
      'finished' => '\Drupal\imagefield_default_alt_and_title\ImagefieldDefaultAltAndTitleBatch::addedDataFinishedCallback',
      'init_message' => t('Processing Modules'),
      'progress_message' => t('Processed @current out of @total.'),
      'error_message' => t('Batch has encountered an error.'),
    );
    batch_set($batch);
  }

  /**
   * Load all entity types available on the site.
   */
  public function ImagefieldDefaultAltAndTitleEntityListByType($type) {
    $all_types = array();
    $entity_all_types_list = \Drupal::entityTypeManager()->getDefinitions();
    if (!empty($entity_all_types_list)) {
      foreach ($entity_all_types_list as $key => $data) {
        if ($key == $type) {
          $contentTypesList = \Drupal::service('entity.manager')
                                     ->getStorage($key)
                                     ->loadMultiple();
          if (!empty($contentTypesList)) {
            foreach ($contentTypesList as $key_bundles => $data_bundles) {
              $all_types[$key_bundles] = $key_bundles;
            }
          }
        }
      }
    }

    return $all_types;
  }
}
