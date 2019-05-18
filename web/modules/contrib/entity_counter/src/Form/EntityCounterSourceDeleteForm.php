<?php

namespace Drupal\entity_counter\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\State;
use Drupal\entity_counter\Entity\CounterTransaction;
use Drupal\entity_counter\Entity\EntityCounterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for deleting an entity counter source.
 */
class EntityCounterSourceDeleteForm extends ConfirmFormBase {

  /**
   * The entity counter containing the entity counter source to be deleted.
   *
   * @var \Drupal\entity_counter\Entity\EntityCounterInterface
   */
  protected $entityCounter;

  /**
   * The entity counter source to be deleted.
   *
   * @var \Drupal\entity_counter\Plugin\EntityCounterSourceInterface
   */
  protected $entityCounterSource;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The state storage service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * EntityCounterSourceDeleteForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\State\State $state
   *   The state storage service.
   */
  public function __construct(EntityTypeManager $entity_type_manager, State $state) {
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the @source source from the %entity_counter entity counter?', ['%entity_counter' => $this->entityCounter->label(), '@source' => $this->entityCounterSource->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entityCounter->toUrl('canonical');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_counter_source_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityCounterInterface $entity_counter = NULL, $entity_counter_source = NULL) {
    $this->entityCounter = $entity_counter;
    $this->entityCounterSource = $this->entityCounter->getSource($entity_counter_source);

    // @TODO: Do not allow to delete the entity counter if it has transactions.
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove the associated transactions.
    $items = $this->entityTypeManager
      ->getStorage('entity_counter')
      ->getQuery()
      ->condition('entity_counter.target_id', $this->entityCounter->id())
      ->condition('entity_counter_source.value', $this->entityCounterSource->getSourceId())
      ->execute();

    CounterTransaction::deleteTransactionsBatch($items);

    // Update counter value.
    $values = $this->state->get('entity_counter.' . $this->entityCounter->id(), []);
    if (!empty($values)) {
      unset($values['by_source'][$this->entityCounterSource->getPluginId()][$this->entityCounterSource->getSourceId()]);
      $total = 0.00;
      foreach ($values['by_source'] as $source_id) {
        foreach ($source_id as $source) {
          $total += $source;
        }
      }
      $values['total'] = $total;

      $this->state->set('entity_counter.' . $this->entityCounter->id(), $values);
      $this->entityCounter->invalidateCache();
    }

    $this->entityCounter->deleteSource($this->entityCounterSource);

    $context = [
      '%name' => $this->entityCounterSource->label(),
      'link' => $this->entityCounter->toLink($this->t('View'))->toString(),
    ];
    $this->logger('entity_counter')->notice('Entity counter %name deleted.', $context);

    drupal_set_message($this->t('The entity counter source %name has been deleted.', ['%name' => $this->entityCounterSource->label()]));
    $form_state->setRedirectUrl($this->entityCounter->toUrl('canonical'));
  }

}
