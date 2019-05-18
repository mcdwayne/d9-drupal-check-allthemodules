<?php

namespace Drupal\entity_ui\Plugin\EntityTabContent;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_ui\Plugin\EntityTabContentBase;
use Drupal\entity_ui\Plugin\EntityTabContentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @EntityTabContent(
 *   id = "entity_form",
 *   label = @Translation("Entity form"),
 *   description = @Translation("Shows the entity's edit form, in a given display mode."),
 * )
 */
class EntityForm extends EntityTabContentBase implements ContainerFactoryPluginInterface, EntityTabContentInterface {

  /**
   * The entity form builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The entity display repository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Creates an EntityForm instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $bundle_info_service,
    EntityFormBuilderInterface $entity_form_builder,
    EntityDisplayRepositoryInterface $entity_display_repository
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $bundle_info_service);
    $this->entityFormBuilder = $entity_form_builder;
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
      $container->get('entity.form_builder'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'form_mode' => 'default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function suggestedEntityTabValues($definition) {
    return [
      // This will be used for entity types that don't provide any built-in
      // routes.
      'path' => 'edit',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form_mode_options = $this->entityDisplayRepository->getFormModeOptions($this->targetEntityTypeId);
    $form['form_mode'] = [
      '#type' => 'select',
      '#title' => t('Form mode'),
      '#description' => t("The form mode to display."),
      '#options' => $form_mode_options,
      '#default_value' => $this->configuration['form_mode'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildContent(EntityInterface $target_entity) {
    return $this->entityFormBuilder->getForm($target_entity, $this->configuration['form_mode']);
  }

}
