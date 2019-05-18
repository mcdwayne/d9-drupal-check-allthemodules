<?php

namespace Drupal\entity_ui\Plugin;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\entity_ui\Entity\EntityTabInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Entity tab content plugins whose content is a form.
 *
 * This plugin doubles up as a form class.
 */
abstract class EntityTabContentFormBase extends EntityTabContentBase implements
    EntityTabContentInterface,
    ContainerFactoryPluginInterface,
    FormInterface {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface;
   */
  protected $formBuilder;

  /**
   * Creates a plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   *   The bundle info service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $bundle_info_service,
    FormBuilderInterface $form_builder
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $bundle_info_service);

    $this->formBuilder = $form_builder;
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
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildContent(EntityInterface $target_entity) {
    // Return the form that this class provides.
    return $this->formBuilder->getForm($this, $target_entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // Build the form ID from a prefix, and the tab ID, which is unique.
    $entity_tab_id = $this->entityTab->id();

    return 'entity_tab_' . str_replace('.', '__', $entity_tab_id);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $target_entity = NULL) {
    // This is just here for DX to show the $target_entity parameter.
    // It can be overridden without calling the parent.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validation is optional.
  }

  /**
   * Gets the target entity from the form state.
   *
   * This is a helper method to save having to figure out the build info.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_state
   *  The form state.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *  The target entity.
   */
  protected function getTargetEntity(FormStateInterface $form_state) {
    $target_entity = $form_state->getBuildInfo()['args'][0];
    return $target_entity;
  }

}
