<?php

namespace Drupal\contactlist;

use Drupal\contactlist\Entity\ContactListEntryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ContactListController extends ControllerBase {

  public function createStuff($type = '') {
    return (object) array(
      'ctid' => '',
      'type' => $type,
      'title' => '',
    );
  }

  /**
   * A controller that displays failed import messages for the current user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user whose failed import messages are to be displayed.
   * @param string $hash
   *   An identification hash for the batch of messages.
   *
   * @return array
   *   Render array.
   *
   * @throws AccessDeniedHttpException
   *   If the user is not permitted to view the messages.
   */
  public function failedImports(AccountInterface $user, $hash) {
    if ($user->id() === 1 || $user->id() === \Drupal::currentUser()->id()) {
      // Get skip log and display.
      $skip_log = \Drupal::state()->get('contactlist.skiplog.' . $user->id() . '.' . $hash);
      $rows = [];
      foreach ($skip_log as $row) {
        $rows[] = [
          'csv' => implode(', ', $row['row']),
          'violations' => $this->violationsToText($row['violations']),
          'messages' => $row['messages'],
        ];
      }
      return [
        '#type' => 'table',
        '#rows' => $rows,
        '#empty' => $this->t('No errors found.'),
        '#header' => ['CSV', 'Violations', 'Messages'],
        'delete_link' => [
          '#type' => 'link',
          '#title' => 'Delete',
          '#url' => Url::fromRoute('contactlist.failed_imports.delete', [
            'user' => $user->id(),
            'hash' => $hash,
          ]),
        ]
      ];
    }
    else {
      throw new AccessDeniedHttpException();
    }
  }

  /**
   * Compiles the violation messages for a list of constraint violations.
   *
   * @param \Symfony\Component\Validator\ConstraintViolationListInterface|NULL $violations
   *   The list of constraint violations
   *
   * @return string
   *   The combined message string.
   */
  protected function violationsToText(ConstraintViolationListInterface $violations = NULL) {
    $message = '';
    if ($violations) {
      foreach ($violations as $violation) {
        $message .= $violation->getMessage() . ', ';
      }
      $message = substr($message, 0, -2);
    }
    else {
      $message = $this->t('Exception');
    }
    return $message;
  }

  public function deleteFailedImports(AccountInterface $user, $hash) {
    if ($user->id() === 1 || $user->id() === \Drupal::currentUser()->id()) {
      // Get skip log and display.
      \Drupal::state()->delete('contactlist.skiplog.' . $user->id() . '.' . $hash);
      drupal_set_message('Import skip log deleted');
      return new RedirectResponse(Url::fromRoute('entity.contactlist_entry.collection')->toString());
    }
    else {
      throw new AccessDeniedHttpException();
    }
  }

  /**
   * Title callback.
   */
  function pageTitle(ContactListEntryInterface $contactlist_entry, $action) {
    return $contactlist_entry->label() . ' - ' . $action;
  }

}
