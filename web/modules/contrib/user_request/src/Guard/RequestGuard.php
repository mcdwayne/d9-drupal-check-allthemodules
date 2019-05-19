<?php

namespace Drupal\user_request\Guard;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user_request\Entity\RequestInterface;
use Drupal\user_request\Entity\RequestType;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;

/**
 * Checks if the user is allowed to perform request transitions.
 */
class RequestGuard implements GuardInterface {

  /**
   * {@inheritdoc}
   */
  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    // Gets the account whose permissions will be checked (current user).
    $account = \Drupal::currentUser();

    // Checks if the account has permission to perform the transition.
    return $this->checkPermissions($transition, $entity, $account);
  }

  /**
   * Checks if an account has permission to perform some transition on a 
   * request.
   *
   * @param \Drupal\state_machine\Plugin\Workflow\WorkflowTransition $transition
   *   The transition.
   * @param \Drupal\user_request\Entity\RequestInterface $request
   *   The request entity whose state will change.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account whose access will be checked.
   *
   * @return bool
   *   Returns TRUE if the account has permission to perform the transition. 
   *   Otherwise, returns FALSE.
   */
  public function checkPermissions(WorkflowTransition $transition, RequestInterface $request, AccountInterface $account) {
    $transition_id = $transition->getId();
    $bundle = $request->bundle();

    // First checks if user has permission to perform the transition on 
    // requests of any type.
    if ($account->hasPermission("transition_$transition_id any $bundle user_request")) {
      return TRUE;
    }

    // Checks the permission for sent requests.
    if ($account->hasPermission("transition_$transition_id own $bundle user_request")) {
      // Checks if the account belongs to the sender.
      if ($account->id() == $request->getOwnerId()) {
        return TRUE;
      }
    }

    // Checks the permission for requests responded by the user.
    if ($account->hasPermission("transition_$transition_id responded $bundle user_request")) {
      // Checks if the account belongs to the user who responded the request.
      if ($response = $request->getResponse()) {
        if ($account->id() == $response->getOwnerId()) {
          return TRUE;
        }
      }
    }

    // Checks the permission for received requests.
    if ($account->hasPermission("transition_$transition_id received $bundle user_request")) {
      // Checks if the account belongs to one of the recipients.
      $recipients = $request->getRecipients();
      foreach ($recipients as $recipient) {
        if ($account->id() == $recipient->id()) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Generates permissions used by the guard.
   *
   * @return array
   *   Permission definitions.
   */
  public static function permissions() {
    $permissions = [];
    $workflow_manager = \Drupal::service('plugin.manager.workflow');

    // Generates permissions per request type.
    $request_types = RequestType::loadMultiple();
    foreach ($request_types as $bundle => $request_type) {
      if ($workflow_id = $request_type->getWorkflow()) {
        // Generates permissions for available transitions.
        $workflow = $workflow_manager->createInstance($workflow_id);
        $transitions = $workflow->getTransitions();
        $response_transitions = $request_type->getResponseTransitions();
        foreach ($transitions as $transition_id => $transition) {
          $permissions += [
            "transition_$transition_id any $bundle user_request" => [
              'title' => \t('@bundle: @transition any request', [
                '@bundle' => $request_type->label(),
                '@transition' => $transition->getLabel(),
              ]),
            ],
            "transition_$transition_id own $bundle user_request" => [
              'title' => \t('@bundle: @transition own requests', [
                '@bundle' => $request_type->label(),
                '@transition' => $transition->getLabel(),
              ]),
            ],
            "transition_$transition_id received $bundle user_request"=> [
              'title' => \t('@bundle: @transition received requests', [
                '@bundle' => $request_type->label(),
                '@transition' => $transition->getLabel(),
              ]),
            ],
          ];

          // Only adds permissions for transitions of responded requests if the
          // transitions is not performed on response form.
          if (!in_array($transition_id, $response_transitions)) {
            $permissions += [
              "transition_$transition_id responded $bundle user_request" => [
                'title' => \t('@bundle: @transition responded requests', [
                  '@bundle' => $request_type->label(),
                  '@transition' => $transition->getLabel(),
                ]),
              ],
            ];
          }
        }
      }
    }
    return $permissions;
  }

}
