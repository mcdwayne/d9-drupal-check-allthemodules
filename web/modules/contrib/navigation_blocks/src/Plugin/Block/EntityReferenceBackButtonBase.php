<?php

namespace Drupal\navigation_blocks\Plugin\Block;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\navigation_blocks\BackButtonManagerInterface;
use Drupal\navigation_blocks\EntityButtonManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for entity reference buttons.
 *
 * @package Drupal\navigation_blocks\Plugin\Block
 */
abstract class EntityReferenceBackButtonBase extends EntityBackButtonBase {

  /**
   * The entity back button manager.
   *
   * @var \Drupal\navigation_blocks\EntityButtonManager
   */
  protected $entityButtonManager;

  /**
   * Constructs a new entity reference back button.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $pluginId
   *   The plugin_id for the plugin instance.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\navigation_blocks\BackButtonManagerInterface $backButtonManager
   *   The back button manager.
   * @param \Drupal\navigation_blocks\EntityButtonManagerInterface $entityButtonManager
   *   The entity button manager.
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, EntityTypeManagerInterface $entityTypeManager, BackButtonManagerInterface $backButtonManager, EntityButtonManagerInterface $entityButtonManager) {
    parent::__construct($configuration, $pluginId, $pluginDefinition, $backButtonManager, $entityTypeManager);
    $this->entityButtonManager = $entityButtonManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition): BackButton {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('entity_type.manager'),
      $container->get('navigation_blocks.back_button_manager'),
      $container->get('navigation_blocks.entity_button_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $configuration = parent::defaultConfiguration();
    $configuration['entity_reference_field'] = '';
    return $configuration;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function blockForm($form, FormStateInterface $formState): array {
    $form = parent::blockForm($form, $formState);

    /** @var \Drupal\Core\Plugin\Context\EntityContextDefinition $entityContextDefinition */
    $entityContextDefinition = $this->getContextDefinition('entity')->getDataDefinition();
    /** @var string $entityTypeId */
    $entityTypeId = $entityContextDefinition->getConstraint('EntityType');
    $entityType = $this->entityButtonManager->getEntityType($entityTypeId);

    $form['entity_reference_field'] = [
      '#type' => 'select',
      '#empty_value' => '',
      '#title' => t('Entity Reference Field'),
      '#default_value' => $this->configuration['entity_reference_field'],
      '#options' => $this->getEntityReferenceOptions($entityType),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);
    $this->setConfigurationValue('entity_reference_field', $form_state->getValue('entity_reference_field'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntity(): EntityInterface {
    return $this->getReferencedEntity();
  }

  /**
   * Get available entity reference options for an entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return string[]
   *   The entity reference options for the entity type.
   */
  abstract protected function getEntityReferenceOptions(EntityTypeInterface $entity_type): array;

  /**
   * Get the referenced entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The referenced entity.
   */
  abstract protected function getReferencedEntity(): EntityInterface;

}
