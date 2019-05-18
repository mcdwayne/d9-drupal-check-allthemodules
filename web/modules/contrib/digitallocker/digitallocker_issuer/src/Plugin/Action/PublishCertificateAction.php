<?php

namespace Drupal\digitallocker_issuer\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\digitallocker_issuer\PushApi;

/**
 * Pathauto entity update action.
 *
 * @Action(
 *   id = "digitallocker_issuer_publish_certificate",
 *   label = @Translation("Publish to Digital Locker"),
 * )
 */
class PublishCertificateAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    PushApi::publishSingleCertificate($entity, 'A');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return TRUE;
  }

}
