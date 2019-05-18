<?php

namespace Drupal\ajax_search_block\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures forms module settings.
 */
class ModuleConfigurationForm extends ConfigFormBase {

  protected $entity;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entity = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_search_block_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ajax_search_block.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('ajax_search_block.settings');
    $node_types = [];
    $types = $this->entity->getStorage('node_type')->loadMultiple();

    foreach ($types as $item) {
      $node_types[$item->get('originalId')] = $item->get('name');
    }

    $taxonomy_types = [];

    foreach (taxonomy_vocabulary_get_names() as $item) {
      $taxonomy_types[$item] = taxonomy_vocabulary_load($item)->get('name');
    }

    $form['node_types'] = [
      '#type' => 'checkboxes',
      '#title' => t('Enable node types'),
      '#description' => t('Please select node types that should be considered for Ajax search'),
      '#default_value' => $config->get('node_types'),
      '#options'  => $node_types,
    ];

    $form['taxonomy_types'] = [
      '#type' => 'checkboxes',
      '#title' => t('Enable taxonomies'),
      '#description' => t('Please select taxonomies that should be considered for Ajax search'),
      '#default_value' => $config->get('taxonomy_types'),
      '#options'  => $taxonomy_types,
    ];

    $form['ajax_base_url'] = [
      '#type' => 'url',
      '#title' => t('Base URL'),
      '#description' => t('Please provide the base URL. <b>Do not add trailing slash</b>'),
      '#default_value' => $config->get('ajax_base_url'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $node_array = [];
    $taxonomy_array = [];

    foreach ($values['node_types'] as $key => $value) {
      if($value != '0') {
        $node_array[] = $key;
      }
    }
    foreach ($values['taxonomy_types'] as $key => $value) {
      if($value != '0') {
        $taxonomy_array[] = $key;
      }
    }
    $this->config('ajax_search_block.settings')
      ->set('node_types', $values['node_types'])
      ->set('taxonomy_types', $values['taxonomy_types'])
      ->set('node_types_selected', $node_array)
      ->set('taxonomy_types_selected', $taxonomy_array)
      ->set('ajax_base_url', $values['ajax_base_url'])
      ->save();
  }
}
