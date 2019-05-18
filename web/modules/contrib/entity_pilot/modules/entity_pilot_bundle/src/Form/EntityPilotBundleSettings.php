<?php

namespace Drupal\entity_pilot_bundle\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings for for selection of which entity-types to allows.
 *
 * Entity Pilot bundle allows creation of a departure containing all content
 * entities of a given bundle. This form allows configuring which entity-types
 * it should work with.
 */
class EntityPilotBundleSettings extends ConfigFormBase {

  /**
   * Entity type repository service.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.repository')
    );
  }

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   Entity type repository.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeRepositoryInterface $entity_type_repository) {
    parent::__construct($config_factory);
    $this->entityTypeRepository = $entity_type_repository;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['entity_pilot_bundle.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_pilot_bundle_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = $this->entityTypeRepository->getEntityTypeLabels(TRUE)['Content'];
    unset($options['ep_arrival'], $options['ep_departure']);
    $form['entity_types'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $this->config('entity_pilot_bundle.settings')->get('entity_types'),
      '#title' => $this->t('Enabled entity-types'),
      '#description' => $this->t('Select the entity-types to allow adding all entities to a departure in a single operation. Selected entity-types will have a new operation link added to enable creating the departure.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('entity_pilot_bundle.settings')
      ->set('entity_types', array_filter($form_state->getValue('entity_types')))
      ->save(TRUE);
    parent::submitForm($form, $form_state);
  }

}
