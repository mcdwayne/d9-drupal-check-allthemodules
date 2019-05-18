<?php

namespace Drupal\drd_pi\Plugin\Action;

use Drupal\drd\Plugin\Action\BaseGlobal;

/**
 * Provides a 'Sync' action.
 *
 * @Action(
 *  id = "drd_action_pi_sync",
 *  label = @Translation("Platform integration sync"),
 *  type = "drd",
 * )
 */
class Sync extends BaseGlobal {

  /**
   * Return a list of all configured accounts of this type.
   *
   * @return \Drupal\drd_pi\DrdPiAccountInterface[]
   *   List of accounts.
   */
  protected function getAccounts() {
    $accounts = [];

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $manager */
    $manager = \Drupal::service('entity_type.manager');
    foreach ($manager->getDefinitions() as $definition) {
      if ($definition->entityClassImplements('\Drupal\drd_pi\DrdPiAccount')) {
        $storage = \Drupal::entityTypeManager()->getStorage($definition->id());
        /** @var \Drupal\drd_pi\DrdPiAccountInterface $account */
        foreach ($storage->loadMultiple() as $account) {
          if ($account->status()) {
            $accounts[] = $account;
          }
        }
      }
    }
    return $accounts;
  }

  /**
   * {@inheritdoc}
   */
  public function executeAction() {
    foreach ($this->getAccounts() as $account) {
      $this->log('info', 'Syncing @platform account @label', [
        '@platform' => $account->getPlatformName(),
        '@label' => $account->label(),
      ]);
      $account->sync();
    }
    return TRUE;
  }

}
