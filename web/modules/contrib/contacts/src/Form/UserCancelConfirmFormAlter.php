<?php

namespace Drupal\contacts\Form;

use Drupal\contacts\Event\UserCancelConfirmationEvent;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Form alter for the User Cancel Confirm form.
 *
 * @package Drupal\contacts\Form
 */
class UserCancelConfirmFormAlter {

  use StringTranslationTrait;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * UserCancelConfirmFormAlter constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $dispatcher) {
    $this->dispatcher = $dispatcher;
  }

  /**
   * Implements hook_form_alter().
   *
   * Alter the user cancel confirm form by allowing
   * Drupal\contacts\Event\UserCancelConfirmationEvent subscribers to provide
   * information, confirmations (which will be rendered as required checkboxes)
   * and errors (which will prevent form submission).
   */
  public function alter(array &$form, FormStateInterface $form_state, $form_id) {
    /** @var \Drupal\decoupled_auth\Entity\DecoupledAuthUser $auth_user */
    $user = $form_state->getFormObject()->getEntity();
    $event = new UserCancelConfirmationEvent($user);

    /* @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher*/
    $this->dispatcher->dispatch(UserCancelConfirmationEvent::NAME, $event);

    $report_groups = [];
    foreach ($event->getGroups() as $machine_name => $group_name) {
      $this->addGroup($event, $report_groups, $machine_name, $group_name);
    }

    // Only create the confirmation report if there are groups to include.
    if ($report_groups) {
      $form['report'] = [
        '#type' => 'container',
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h4',
          '#value' => $this->t('Confirmation report'),
        ],
        'groups' => $report_groups,
      ];
    }

    // If there are any errors, prevent submission.
    if ($event->hasError()) {
      $form['actions']['submit']['#access'] = FALSE;
      // Change the text from "Cancel" which becomes ambiguous without submit.
      $form['actions']['cancel']['#title'] = $this->t('Go back');
    }
  }

  /**
   * Gather a group of UserCancelConfirmationEvent data.
   *
   * @param \Drupal\contacts\Event\UserCancelConfirmationEvent $event
   *   The event to gather a group for.
   * @param array $groups
   *   An array of existing groups to add additional groups to.
   * @param string $machine_name
   *   The machine name for the group being added.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $group_name
   *   The human-readable, TranslatableMarkup name of the group.
   */
  protected function addGroup(UserCancelConfirmationEvent $event, array &$groups, $machine_name, TranslatableMarkup $group_name) {
    $info = $event->getInfo($machine_name);
    $confirmations = $event->getConfirmations($machine_name);
    $errors = $event->getErrors($machine_name);

    // If there is no information for this group, do nothing.
    if (empty($info) && empty($confirmations) && empty($errors)) {
      return;
    }

    // Set up a container for this group.
    $groups[$machine_name] = [
      '#type' => 'container',
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h4',
        '#value' => $group_name,
      ],
    ];

    if ($info) {
      $groups[$machine_name]['info'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Information'),
        'list' => [
          '#theme' => 'item_list',
          '#type' => 'ul',
          '#items' => $info,
        ],
      ];
    }

    if ($confirmations) {
      $groups[$machine_name]['confirmations'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Please confirm that you understand the following:'),
      ];

      foreach ($confirmations as $confirmation_machine_name => $confirmation) {
        $groups[$machine_name]['confirmations'][$confirmation_machine_name] = [
          '#type' => 'checkbox',
          '#title' => $confirmation,
          '#required' => TRUE,
        ];
      }
    }

    if ($errors) {
      $groups[$machine_name]['errors'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('This account cannot be cancelled because of the following:'),
        'list' => [
          '#theme' => 'item_list',
          '#type' => 'ul',
          '#items' => $errors,
        ],
      ];
    }
  }

}
