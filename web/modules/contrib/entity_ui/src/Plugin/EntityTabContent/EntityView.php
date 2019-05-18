<?php

namespace Drupal\entity_ui\Plugin\EntityTabContent;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_ui\Plugin\EntityTabContentBase;
use Drupal\entity_ui\Plugin\EntityTabContentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @EntityTabContent(
 *   id = "entity_view",
 *   label = @Translation("Entity view"),
 *   description = @Translation("Shows the entity, in a given display mode."),
 * )
 */
class EntityView extends EntityTabContentBase implements ContainerFactoryPluginInterface, EntityTabContentInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Creates an EntityView instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $bundle_info_service,
    EntityDisplayRepositoryInterface $entity_display_repository
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $bundle_info_service);
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
      'view_mode' => 'default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function suggestedEntityTabValues($definition) {
    return [
      // This will be used for entity types that don't provide any built-in
      // routes.
      'path' => 'view',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $view_mode_options = $this->entityDisplayRepository->getViewModeOptions($this->targetEntityTypeId);
    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => t('View mode'),
      '#description' => t("The view mode in which to display the entity."),
      '#options' => $view_mode_options,
      '#default_value' => $this->configuration['view_mode'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildContent(EntityInterface $target_entity) {
    $view_builder = $this->entityTypeManager->getViewBuilder($this->targetEntityTypeId);

    return $view_builder->view($target_entity, $this->configuration['view_mode']);
  }

}
