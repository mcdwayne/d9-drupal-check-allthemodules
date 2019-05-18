<?php

namespace Drupal\entity_form_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with edit form for a specific entity.
 *
 * @Block(
 *   id = "entity_form",
 *   deriver = "Drupal\entity_form_block\Plugin\Deriver\EntityFormDeriver",
 * )
 */
class EntityForm extends BlockBase implements ContextAwarePluginInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a new EntityView.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'form_mode' => 'default',
      'entity_bundle' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $definition = $this->getPluginDefinition();

    $bundles = [];
    foreach ($this->entityTypeBundleInfo->getBundleInfo($definition['entity_type_id']) as $bundle_key => $bundle_info) {
      $bundles[$bundle_key] = $bundle_info['label'];
    }

    if ($this->entityTypeManager->getDefinition($definition['entity_type_id'])->hasKey('bundle')) {
      $form['entity_bundle'] = array(
        '#title' => $this->t('Entity Bundle'),
        '#type' => 'select',
        '#options' => $bundles,
        '#required' => TRUE,
        '#default_value' => $this->configuration['entity_bundle'],
      );
    }

    $form['form_mode'] = [
      '#type' => 'select',
      '#options' => $this->entityDisplayRepository->getFormModeOptions($definition['entity_type_id']),
      '#title' => $this->t('Form mode'),
      '#default_value' => $this->configuration['form_mode'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['entity_bundle'] = $form_state->getValue('entity_bundle');
    $this->configuration['form_mode'] = $form_state->getValue('form_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $definition = $this->getPluginDefinition();

    /** @var $context_entity \Drupal\Core\Entity\EntityInterface */
    $context_entity = $this->getContextValue('entity');

    $entity = NULL;
    if (is_null($context_entity)) {
      $values = [];
      // Add bundle to values if entity has a bundle.
      if ($this->entityTypeManager->getDefinition($definition['entity_type_id'])->hasKey('bundle')) {
        $values[$this->entityTypeManager->getDefinition($definition['entity_type_id'])->getKey('bundle')] = $this->configuration['entity_bundle'];
      }

      $entity = $this->entityTypeManager->getStorage($definition['entity_type_id'])->create($values);

      if ($entity instanceof EntityOwnerInterface) {
        $entity->setOwnerId(\Drupal::currentUser()->id());
      }
    }
    elseif ($context_entity->getEntityTypeId() == $definition['entity_type_id']) {
      $entity = $context_entity;
    }
    else {
      // Trying load the entity by entity key.
      if (isset($definition['entity_key']) && $context_entity->get($definition['entity_key'])) {
        $entities = $this->entityTypeManager
          ->getStorage($definition['entity_type_id'])
          ->loadByProperties([$definition['entity_key'] => $context_entity->get($definition['entity_key'])->value]);
        if (count($entities) == 1) {
          $entity = reset($entities);
        }
      }

      if (!$entity) {
        $values = [];
        // Add bundle to values if entity has a bundle.
        if ($this->entityTypeManager->getDefinition($definition['entity_type_id'])->hasKey('bundle')) {
          $values[$this->entityTypeManager->getDefinition($definition['entity_type_id'])->getKey('bundle')] = $this->configuration['entity_bundle'];
        }

        $entity = $this->entityTypeManager->getStorage($definition['entity_type_id'])->create($values);

        // Set entity key if exists.
        if (isset($definition['entity_key'])) {
          $entity->set($definition['entity_key'], $context_entity->id());
        }
      }

    }

    return \Drupal::service('entity.form_builder')->getForm($entity, $this->configuration['form_mode']);
  }

}
