<?php

namespace Drupal\mailing_list_confirm;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\mailing_list\Entity\MailingList;

/**
 * Provides dynamic confirm permissions for each mailing list.
 */
class MailingListConfirmPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of mailing list confirm permissions.
   *
   * @return array
   *   The mailing list confirm permissions.
   */
  public function bypassConfirmPermissions() {
    $perms = [];
    // Generate bypass subscription confirm permission for each list.
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
      "bypass $list_id mailing list subscription confirm" => [
        'title' => $this->t('Bypass %list_name mailing list subscription confirm', $list_params),
      ],
    ];
  }

}
