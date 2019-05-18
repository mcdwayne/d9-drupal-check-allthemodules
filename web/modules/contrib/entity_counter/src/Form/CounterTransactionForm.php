<?php

namespace Drupal\entity_counter\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\entity_counter\Entity\CounterTransactionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the counter transaction form.
 */
class CounterTransactionForm extends ContentEntityForm {

  /**
   * The current entity counter entity.
   *
   * @var \Drupal\entity_counter\Entity\EntityCounterInterface
   */
  protected $entityCounter;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a CounterTransactionForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityManagerInterface $entity_manager, RouteMatchInterface $route_match, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);

    $this->entityCounter = $route_match->getParameter('entity_counter');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('current_route_match'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    if ($route_match->getRawParameter($entity_type_id) !== NULL) {
      $entity = $route_match->getParameter($entity_type_id);
    }
    else {
      $values = [];
      // Fetch the entity counter from the route match.
      if ($route_match->getRawParameter('entity_counter')) {
        $values['entity_counter'] = $route_match->getParameter('entity_counter');
      }

      $entity = $this->entityTypeManager->getStorage($entity_type_id)->create($values);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\entity_counter\Entity\CounterTransactionInterface $entity */
    $entity = $this->entity;

    $is_new = $this->entity->isNew();
    if ($is_new) {
      $entity->setEntityCounter($this->entityCounter);
      foreach ($this->entityCounter->getSources() as $source) {
        if ($source->getPluginId() == CounterTransactionInterface::MANUAL_TRANSACTION) {
          $entity->setEntityCounterSourceId($source->getSourceId());
          break;
        }
      }
    }
    else {
      $form['transaction_value']['#disabled'] = TRUE;
    }
    $form['revision']['#access'] = FALSE;
    $form['advanced']['#access'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    drupal_set_message($this->t('The entity counter transaction has been saved.'));
    $form_state->setRedirect('entity.entity_counter_transaction.collection', ['entity_counter' => $this->entityCounter->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    if (!$this->entity->isNew()) {
      $actions['cancel'] = [
        '#type' => 'link',
        '#title' => $this->t('Cancel transaction'),
        '#access' => $this->entity->access('update'),
        '#attributes' => [
          'class' => ['button', 'button--danger'],
        ],
        '#url' => $this->entity->toUrl('cancel')->setRouteParameter('entity_counter', $this->entityCounter->id()),
      ];
    }

    return $actions;
  }

}
