<?php

/**
 * @file
 * Contains \Drupal\rng_quick\Form\RegisterBlockForm.
 */

namespace Drupal\rng_quick\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rng\EventManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rng\Entity\Registration;
use Drupal\user\Entity\User;

/**
 * Builds the form for the quick registration block.
 */
class RegisterBlockForm extends FormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface.
   */
  protected $account;

  /**
   * The RNG event manager.
   *
   * @var EventManagerInterface
   */
  protected $eventManager;

  /**
   * The event entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $event;

  /**
   * Constructs a new RegisterBlockForm instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(AccountInterface $account, EventManagerInterface $event_manager) {
    $this->account = $account;
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('rng.event_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rng_quick_register_block_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityInterface $event
   *   The event entity.
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $event = NULL) {
    $this->event = $event;
    $event_meta = $this->eventManager->getMeta($event);

    $registration_types = $event_meta->getRegistrationTypes();
    if (!$registration_types) {
      // Normally RegisterBlock::blockAccess() will prevent this, but Display
      // Suite skips that check.
      return $form;
    }

    if (1 == count($registration_types)) {
      $t_args = [
        '@event' => $event->label(),
        '@user' => $this->account->getDisplayName(),
      ];

      $form['actions'] = ['#type' => 'actions'];

      $self = 0;
      if ($event_meta->identitiesCanRegister('user', [$this->account->id()])) {
        $self = 1;
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Create registration for @user', $t_args),
          '#button_type' => 'primary',
          '#dropbutton' => 'register',
        ];
      }

      if (($event_meta->countProxyIdentities() - $self) > 0) {
        $form['actions']['other'] = [
          '#type' => 'submit',
          // Don't use 'other' wording when it is the only option.
          '#value' => $self ? $this->t('Create registration for other person') : $this->t('Create a registration'),
          '#submit' => array('::registerOther'),
          '#dropbutton' => 'register',
        ];
      }

      $form['description']['#markup'] = '<p>' . $this->t('Register for @event.', $t_args) . '</p>';
    }
    else {
      $form['description']['#markup'] = $this->t('Multiple registration types are available for this event. Click register to continue.');
      // redirect to /register
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Register'),
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $event = $this->event;
    $event_meta = $this->eventManager->getMeta($event);
    $user = User::load(\Drupal::currentUser()->id());
    $route_parameters[$event->getEntityTypeId()] = $event->id();

    $registration_types = $event_meta->getRegistrationTypes();
    if (1 == count($registration_types)) {
      $registration_type = reset($registration_types);
      $registration = Registration::create([
        'type' => $registration_type->id(),
      ]);
      $registration
        ->addIdentity($user)
        ->setEvent($event);
      if (!$registration->validate()->count()) {
        $registration->save();
        drupal_set_message($this->t('@entity_type has been created.', [
          '@entity_type' => $registration->getEntityType()->getLabel(),
        ]));
        if ($registration->access('view')) {
          $form_state->setRedirectUrl($registration->toUrl());
          return;
        }
      }
    }
    else {
      $form_state->setRedirect('rng.event.' . $event->getEntityTypeId() . '.register.type_list', $route_parameters);
      return;
    }

    drupal_set_message($this->t('Unable to create registration.'), 'error');
  }

  /**
   * Submit handler for 'other person'.
   */
  public function registerOther(array &$form, FormStateInterface $form_state) {
    $event = $this->event;
    $event_meta = $this->eventManager->getMeta($event);
    $registration_types = $event_meta->getRegistrationTypes();
    $registration_type = reset($registration_types);
    $route_parameters[$event->getEntityTypeId()] = $event->id();
    $route_parameters['registration_type'] = $registration_type->id();
    $form_state->setRedirect('rng.event.' . $event->getEntityTypeId() . '.register', $route_parameters);
  }

}
