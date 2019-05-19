<?php

namespace Drupal\user_attendance\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;
use Drupal\user_attendance\Entity\UserAttendanceTypeInterface;
use Drupal\user_attendance\UserAttendanceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Course Registration edit forms.
 *
 * @ingroup nhl_flex_course
 */
class UserAttendanceCheckboxForm extends FormBase {

  /**
   * @var \Drupal\user_attendance\UserAttendanceManagerInterface
   */
  protected $userAttendanceManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * StudentCourseRegistrationsForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Session\AccountInterface $user
   */
  public function __construct(UserAttendanceManagerInterface $user_attendance_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->userAttendanceManager = $user_attendance_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user_attendance'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_attendance_checkbox_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL, UserAttendanceTypeInterface $bundle = NULL) {
    if (!$user) {
      $user = $this->currentUser();
    }

    $form['user_id'] = [
      '#type' => 'value',
      '#value' => $user->id(),
    ];

    $form['bundle_id'] = [
      '#type' => 'value',
      '#value' => $bundle->id(),
    ];

    $triggeringElement = $form_state->getTriggeringElement();
    if($triggeringElement['#name'] == 'attendance_status') {
      $this->updateAttendance($form, $form_state);
    }

    $userAttendance = $this->userAttendanceManager->getCurrentActiveUserAttendance($user, $bundle);
    $wrapper = 'attendance__wrapper-' . $user->id() . '_' . $bundle->id();

    $form['attendance_status'] = [
      '#prefix' => '<div id="' . $wrapper . '">',
      '#suffix' => '</div>',
      '#type' => 'checkbox',
      '#default_value' => $userAttendance ? TRUE : FALSE,
      '#ajax' => [
        'wrapper' => $wrapper,
        'event' => 'change',
        'method' => 'replace',
        'callback' => array($this, 'getAttendanceState'),
        'progress' => array(
          'type' => 'throbber',
          'message' => NULL,
        ),
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->updateAttendance($form, $form_state);
  }

  public function getAttendanceState(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    return $form['attendance_status'];
  }

  public function updateAttendance(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $values = $form_state->getValues();

    /** @var \Drupal\user\UserStorageInterface $userStorage */
    $userStorage = \Drupal::entityTypeManager()
      ->getStorage('user');

    /** @var \Drupal\user\UserStorageInterface $userStorage */
    $userAttendanceTypeStorage = \Drupal::entityTypeManager()
      ->getStorage('user_attendance_type');

    /** @var \Drupal\Core\Session\AccountInterface $user */
    if ($user = $userStorage->load($values['user_id'])) {
      /** @var UserAttendanceTypeInterface $bundle */
      $bundle = $userAttendanceTypeStorage->load($values['bundle_id']);

      if (!$values['attendance_status']) {
        $this->userAttendanceManager->userIsNotAttending($user, $bundle);
      }
      elseif ($values['attendance_status']) {
        $this->userAttendanceManager->userIsAttending($user, $bundle);
      }
    }
  }
}