<?php

namespace Drupal\ajax_search_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'AJAX Search' Block.
 *
 * @Block(
 *   id = "ajax_search_block",
 *   admin_label = @Translation("AJAX search block"),
 * )
 */
class SearchBlock extends BlockBase implements BlockPluginInterface {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    if (!empty($config['node_types_settings'])) {
      $node_types_settings = $config['node_types_settings'];
    }
    if (!empty($config['taxonomy_types_settings'])) {
      $taxonomy_types_settings = $config['taxonomy_types_settings'];
    }

    return array(
      '#theme' => 'block_ajax_search',
      '#block_title' => NULL,
      '#attached' => array(
        'library' => array(
          'ajax_search_block/search-js',
        ),
      ),
      '#block_settings_key' => $config['ajax_search_block_settings_key'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $node_types = [];
    $types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();

    foreach ($types as $item) {
      $node_types[$item->get('originalId')] = $item->get('name');
    }

    $taxonomy_types = [];

    foreach (taxonomy_vocabulary_get_names() as $item) {
      $taxonomy_types[$item] = taxonomy_vocabulary_load($item)->get('name');
    }
    $selected_node_types = '';
    $selected_taxonomy_types = '';
    if(isset($config['ajax_search_block_settings_key'])){
      if($this->configuration[$this->configuration['ajax_search_block_settings_key']]['node_types']){
        $selected_node_types = $this->configuration[$this->configuration['ajax_search_block_settings_key']]['node_types'];
      }
      if($this->configuration[$this->configuration['ajax_search_block_settings_key']]['taxonomies']){
        $selected_taxonomy_types = $this->configuration[$this->configuration['ajax_search_block_settings_key']]['taxonomies'];
      }
    }

    $form['node_types_settings'] = [
      '#type' => 'checkboxes',
      '#title' => t('Enable node types'),
      '#description' => t('Please select node types that should be considered for Ajax search'),
      '#default_value' => $selected_node_types,
      '#options'  => $node_types,
    ];
    $form['taxonomy_types_settings'] = [
      '#type' => 'checkboxes',
      '#title' => t('Enable taxonomies'),
      '#description' => t('Please select taxonomies that should be considered for Ajax search'),
      '#default_value' => $selected_taxonomy_types,
      '#options'  => $taxonomy_types,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
//  echo '<pre>';print_r($this->getDerivativeId());exit;
    $node_array = [];
    $taxonomy_array = [];

    foreach ($values['node_types_settings'] as $key => $value) {
      if($value != '0') {
        $node_array[] = $key;
      }
    }
    foreach ($values['taxonomy_types_settings'] as $key => $value) {
      if($value != '0') {
        $taxonomy_array[] = $key;
      }
    }
    $configuration_values = array(
      'node_types' => $node_array,
      'taxonomies' => $taxonomy_array,
    );
    if(!isset($this->configuration['ajax_search_block_settings_key'])){
      $this->configuration['ajax_search_block_settings_key'] = 'block_' . time();
    }
    $this->configuration[$this->configuration['ajax_search_block_settings_key']] =  $configuration_values;

    \Drupal::getContainer()->get('config.factory')->getEditable($this->configuration['ajax_search_block_settings_key'] . '.settings')
    ->set('node_types', $node_array)
    ->set('taxonomy_types', $taxonomy_array)
    ->save();
  }
}
