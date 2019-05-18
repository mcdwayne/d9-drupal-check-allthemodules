<?php

namespace Drupal\mailing_list;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\mailing_list\Entity\MailingList;

/**
 * Provides dynamic permissions for each mailing list.
 */
class MailingListPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of mailing list permissions.
   *
   * @return array
   *   The mailing list type permissions.
   */
  public function mailListPermissions() {
    $perms = [];
    // Generate mailing list permissions for each list.
    foreach (MailingList::loadMultiple() as $list) {
      $perms += $this->buildPermissions($list);
    }

    return $perms;
  }

  /**
   * Returns a list of mailing list permissions for a given list.
   *
   * @param \Drupal\mailing_list\Entity\MailingList $list
   *   The mailing list.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(MailingList $list) {
    $list_id = $list->id();
    $list_params = ['%list_name' => $list->label()];

    return [
      "subscribe to $list_id mailing list" => [
        'title' => $this->t('Subscribe to %list_name mailing list', $list_params),
      ],
      "access inactive $list_id mailing list subscriptions" => [
        'title' => $this->t('%list_name: access inactive subscriptions', $list_params),
      ],
      "view any $list_id mailing list subscriptions" => [
        'title' => $this->t('%list_name: view any subscriptions', $list_params),
      ],
      "update any $list_id mailing list subscriptions" => [
        'title' => $this->t('%list_name: edit any subscriptions', $list_params),
      ],
      "delete any $list_id mailing list subscriptions" => [
        'title' => $this->t('%list_name: delete any subscriptions', $list_params),
      ],
    ];
  }

}
