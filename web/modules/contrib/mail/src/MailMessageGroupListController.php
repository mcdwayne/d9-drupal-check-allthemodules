<?php

namespace Drupal\mail;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides a listing of Mail message entities for a given message group.
 */
class MailMessageGroupListController extends ControllerBase {

  /**
   * Provides a listing page for mail messages, limited to a single group.
   *
   * @param string $group
   *   The group to filter the list by.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function listing($group) {
    $list_builder = $this->entityManager()->getListBuilder('mail_message');

    $list_builder->setGroup($group);
    $list_builder->setRedirect($this->getDestinationArray());

    return $list_builder->render();
  }

}
