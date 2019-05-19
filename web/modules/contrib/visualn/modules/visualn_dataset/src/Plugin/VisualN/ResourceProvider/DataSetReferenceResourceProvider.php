<?php

namespace Drupal\visualn_dataset\Plugin\VisualN\ResourceProvider;

use Drupal\visualn\Core\ResourceProviderBase;
use Drupal\Core\Form\FormStateInterface;
//use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a 'VisualN Data Set Reference' VisualN resource provider.
 *
 * @VisualNResourceProvider(
 *  id = "visualn_dataset_reference",
 *  label = @Translation("Data set entity reference"),
 *  context = {
 *    "entity_type" = @ContextDefinition("string", label = @Translation("Entity type")),
 *    "bundle" = @ContextDefinition("string", label = @Translation("Bundle")),
 *    "current_entity" = @ContextDefinition("any", label = @Translation("Current entity"))
 *  }
 * )
 */
class DataSetReferenceResourceProvider extends ResourceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'entity_reference_field' => '',
    ] + parent::defaultConfiguration();
  }



  /**
   * {@inheritdoc}
   */
  public function getResource() {
    // @todo: this actually returns VisualN Resource from the referenced resource provider field
    $resource_provider_plugin = $this->getResourceProviderPlugin();
    if ($resource_provider_plugin) {
      return $resource_provider_plugin->getResource();
    }

    return NULL;
  }

  /**
   * Get resource provider from the referenced Data Set entity.
   */
  protected function getResourceProviderPlugin() {
    $resource_provider_plugin = NULL;

    $current_entity = $this->getContextValue('current_entity');

    if (empty($current_entity)) {
      return [];
    }

    $entity_reference_field = $this->configuration['entity_reference_field'];
    if (empty($entity_reference_field) || !$current_entity->hasField($entity_reference_field)) {
      return [];
    }

    $field_instance = $current_entity->get($entity_reference_field);
    if (!$field_instance->isEmpty()) {
      $first_delta = $field_instance->first();

      // @todo: we use data_set_entity but not just an entity because data set entities have a method
      //    to return resource provider plugin specific to them
      $data_set_entity = $first_delta;
      // @todo: check that entity is of type visualn_dataset

      // @todo: check this line
      $resource_provider_plugin = $data_set_entity->get('entity')->getTarget()->getValue()->getResourceProviderPlugin();
    }

    return $resource_provider_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $form_state->getValues();
    $configuration =  $configuration + $this->configuration;

    $entity_type = $this->getContextValue('entity_type');
    $entity_bundle = $this->getContextValue('bundle');

    // @todo: this is a temporary solution
    if (empty($entity_type) || empty($entity_bundle)) {
      // @todo: throw an error
      $form['markup'] = [
        '#markup' => t('Entity type or bundle context not set.'),
      ];

      return $form;
    }

    // @todo: get fields of the attached the entity - so it needs a context of
    //    content entity it is attached to (actually entity type and bundle, as
    //    for fetcher plugin that fetches drawing from an entity field)

    // @todo: consider multiple delta case (see FileFieldDrawingFetcher for a similar case)

    $options = [];

    $entityManager = \Drupal::service('entity_field.manager');
    $bundle_fields = $entityManager->getFieldDefinitions($entity_type, $entity_bundle);

    foreach ($bundle_fields as $field_name => $field_definition) {
      // filter out base fields
      if ($field_definition->getFieldStorageDefinition()->isBaseField() == TRUE) {
        continue;
      }

      if ($field_definition->getType() == 'entity_reference') {
        $options[$field_name] = $field_definition->getLabel();
      }
    }

    $form['entity_reference_field'] = [
      '#type' => 'select',
      '#title' => t('Entity reference field'),
      '#options' => $options,
      '#default_value' => $this->configuration['entity_reference_field'],
      '#description' => t('Select the Entity Reference field for the data set entity source'),
      '#empty_value' => '',
      '#empty_option' => t('- Select the field -'),
    ];

    return $form;
  }

}

