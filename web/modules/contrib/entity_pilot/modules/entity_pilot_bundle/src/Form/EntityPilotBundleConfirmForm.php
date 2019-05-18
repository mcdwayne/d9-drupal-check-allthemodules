<?php

namespace Drupal\entity_pilot_bundle\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A confirm form for creating a departure consisting of entities in a bundle.
 */
class EntityPilotBundleConfirmForm extends ConfirmFormBase {

  /**
   * The bundle entity from which the departure is to be created.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $bundle;

  /**
   * Entity type repository.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.repository'),
      $container->get('entity_type.manager'),
      $container->get('entity.query')
    );
  }

  /**
   * Constructs a new EntityPilotBundleConfirmForm object.
   */
  public function __construct(EntityTypeRepositoryInterface $entity_type_repository, EntityTypeManagerInterface $entity_type_manager, QueryFactory $query) {
    $this->entityTypeRepository = $entity_type_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->queryFactory = $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to create a new departure containing all @type entities from the @bundle @label?', [
      '@type' => $this->entityTypeRepository->getEntityTypeLabels()[$this->bundle->getEntityType()->getBundleOf()],
      '@label' => $this->bundle->getEntityType()->getLabel(),
      '@bundle' => $this->bundle->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    if ($this->bundle->hasLinkTemplate('collection')) {
      return $this->bundle->toUrl('collection');
    }
    else {
      return $this->bundle->toUrl();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_pilot_bundle_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type_id = $this->bundle->getEntityType()->getBundleOf();
    $query = $this->queryFactory->get($entity_type_id, 'AND');
    $query->condition($this->entityTypeManager->getDefinition($entity_type_id)->getKey('bundle'), $this->bundle->id());
    $results = $query->execute();
    $departure = $this->entityTypeManager->getStorage('ep_departure')->create([
      'account' => $form_state->getValue('ep_account'),
      'info' => $this->bundle->label() . ' (' . $this->bundle->getEntityType()->getLabel() . ')',
    ]);
    $passengers = [];

    foreach ($results as $id) {
      $passengers[] = ['target_id' => $id, 'target_type' => $entity_type_id];
    }
    $departure->passenger_list->setValue($passengers);
    $departure->save();
    $form_state->setRedirect('entity.ep_departure.approve_form', [
      'ep_departure' => $departure->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL, $entity_id = NULL) {
    $this->bundle = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    $form = parent::buildForm($form, $form_state);
    $options = [];
    foreach ($this->entityTypeManager->getStorage('ep_account')->loadMultiple() as $key => $account) {
      $options[$key] = $account->label();
    }
    $form['ep_account'] = [
      '#type' => 'select',
      '#default_value' => key($options),
      '#options' => $options,
      '#title' => $this->t('Entity Pilot account'),
      '#description' => $this->t('Select the account to send the departure to.'),
    ];
    return $form;
  }

}
