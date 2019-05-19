<?php

namespace Drupal\timetable_cron\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TimetableCronForm.
 *
 * Form class for adding/editing timetable_cron config entities.
 */
class TimetableCronForm extends EntityForm {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs the TimetableCronForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(EntityTypeManager $entityTypeManager, MessengerInterface $messenger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    $timetablecron = $this->entity;

    // Change page title for the edit operation.
    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit cron: @id', ['@id' => $timetablecron->id]);
    }

    $form['id'] = [
      '#title' => $this->t('Function'),
      '#type' => 'machine_name',
      '#default_value' => $timetablecron->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#description' => $this->t('Insert a function name to execute on cron run. The function must be placed on .module file!'),
    ];

    if (!$timetablecron->isNew()) {
      $form['id']['#attributes']['readonly'] = 'readonly';
    }

    // Status field.
    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#options' => [0 => $this->t('Off'), 1 => $this->t('On')],
      '#default_value' => $timetablecron->status,
    ];

    // Force field.
    $form['force'] = [
      '#type' => 'select',
      '#title' => $this->t('Force onced'),
      '#options' => [0 => $this->t('No'), 1 => $this->t('Yes')],
      '#default_value' => $timetablecron->force,
    ];

    // Minute field.
    $minutes['*'] = '*';
    $minutes['0'] = '00';
    for ($i = 0; $i <= 55; $i = $i + 5) {
      $minutes[$i] = $i;
    }
    $minutes['*/10'] = '*/10';
    $minutes['*/20'] = '*/20';
    $minutes['*/30'] = '*/30';
    $form['minute'] = [
      '#type' => 'select',
      '#title' => $this->t('Minute'),
      '#options' => $minutes,
      '#default_value' => $timetablecron->minute,
    ];

    // Hour field.
    $hours['*'] = '*';
    for ($i = 1; $i <= 23; $i++) {
      $hours[$i] = $i;
    }
    for ($i = 1; $i <= 12; $i++) {
      $hours['*/' . $i] = '*/' . $i;
    }
    $form['hour'] = [
      '#type' => 'select',
      '#title' => $this->t('Hour'),
      '#options' => $hours,
      '#default_value' => $timetablecron->hour,
    ];

    // Day field.
    $days['*'] = '*';
    for ($i = 1; $i <= 31; $i++) {
      $days[$i] = $i;
    }
    $form['day'] = [
      '#type' => 'select',
      '#title' => $this->t('Day'),
      '#options' => $days,
      '#default_value' => $timetablecron->day,
    ];

    // Month field.
    $months['*'] = '*';
    for ($i = 1; $i <= 12; $i++) {
      $months[$i] = $i;
    }
    $form['month'] = [
      '#type' => 'select',
      '#title' => $this->t('Month'),
      '#options' => $months,
      '#default_value' => $timetablecron->month,
    ];

    // Weekday field.
    $weekdays['*'] = '*';
    for ($i = 1; $i <= 7; $i++) {
      $weekdays[$i] = $i;
    }
    $form['weekday'] = [
      '#type' => 'select',
      '#title' => $this->t('Week day'),
      '#options' => $weekdays,
      '#default_value' => $timetablecron->weekday,
    ];

    // Desc field.
    $form['desc'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $timetablecron->desc,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $timetablecron = $this->entity;
    $status = $timetablecron->save();

    if ($status) {
      // Setting the success message.
      $this->messenger->addMessage($this->t('Saved the cron: @id.', ['@id' => $timetablecron->id]));
    }
    else {
      $this->messenger->addMessage($this->t('Error on save cron @id.', ['@id' => $timetablecron->id]));
    }
    $form_state->setRedirect('entity.timetable_cron.collection');
  }

  /**
   * Helper function to check whether configuration entity exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('timetable_cron')
      ->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
