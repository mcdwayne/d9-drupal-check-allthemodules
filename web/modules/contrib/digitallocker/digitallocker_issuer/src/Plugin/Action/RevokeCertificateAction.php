<?php

namespace Drupal\digitallocker_issuer\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\digitallocker_issuer\PushApi;

/**
 * Pathauto entity update action.
 *
 * @Action(
 *   id = "digitallocker_issuer_revoke_certificate",
 *   label = @Translation("Revoke from Digital Locker"),
 * )
 */
class RevokeCertificateAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    PushApi::publishSingleCertificate($entity, 'D');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return TRUE;
  }

}
