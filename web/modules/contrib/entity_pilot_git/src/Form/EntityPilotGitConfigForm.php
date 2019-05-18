<?php

namespace Drupal\entity_pilot_git\Form;

use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Config form for the entity_pilot_git module.
 */
class EntityPilotGitConfigForm extends ConfigFormBase {

  /**
   * Entity type repository service.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * EntityPilotGitConfigForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repo
   *   The entity type repository service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeRepositoryInterface $entity_type_repo) {
    parent::__construct($config_factory);
    $this->entityTypeRepository = $entity_type_repo;
  }

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
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'entity_pilot_git.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_pilot_git_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('entity_pilot_git.settings');
    $form['export_directory'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Export Directory'),
      '#description' => $this->t('The export directory relative to the drupal root.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('export_directory'),
    );

    $entity_type_labels = $this->entityTypeRepository->getEntityTypeLabels(TRUE);
    $form['skip_entity_types'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Skip Entity Types'),
      '#options' => $entity_type_labels['Content'],
      '#description' => $this->t('Choose entity types to skip during the export process.'),
      '#default_value' => $config->get('skip_entity_types'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // TODO: validate export_dir field.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('entity_pilot_git.settings')
      ->set('export_directory', $form_state->getValue('export_directory'))
      ->set('skip_entity_types', $form_state->getValue('skip_entity_types'))
      ->save();
  }

}
