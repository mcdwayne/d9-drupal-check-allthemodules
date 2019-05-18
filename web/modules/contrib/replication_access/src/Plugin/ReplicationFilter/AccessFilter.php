<?php

namespace Drupal\replication_access\Plugin\ReplicationFilter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\replication\Plugin\ReplicationFilter\ReplicationFilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Drupal\Core\Session\AccountSwitcherInterface;
/**
 * Provides a filter for checking access on enities/nodes.
 *
 *
 * @ReplicationFilter(
 *   id = "access",
 *   label = @Translation("Filter Nodes by user Access"),
 *   description = @Translation("Replicate only nodes that a user has access to.")
 * )
 */
class AccessFilter extends ReplicationFilterBase {


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function filter(EntityInterface $entity = NULL) {
    // Check if an entity is found (some db issues were causing the filter to fail so this should probably be removed later)
    if($entity) {
    //get the user from the url
    if(isset($_GET['parameters']['uid'])) {
      $uid = $_GET['parameters']['uid'];
      $param = User::load($uid);
    } else {
      //if the user is not set then deny access to all
      return FALSE;
    }
        $accountSwitcher = \Drupal::service('account_switcher');
        //temporarily switch user to parameters[uid] (setting the user directly in the access function doesn't work for some reason)
        $accountSwitcher->switchTo($param);
        // Check the user access to the entity
        $access = $entity->access('view', $param, TRUE);
        // restore the user account.
        $accountSwitcher->switchBack();
        //Check if the user is explicitly allowed access.
        if($access->isAllowed()) {
          //\Drupal::logger('replication_access')->notice($param->getAccountName()." is forbidden from ".$entity->uuid());
          return TRUE;
        }
        //If access is not allowed then do not replicate.
        return FALSE;
    } else {
     // \Drupal::logger('replication_access')->notice("NULL entity");
    }
  //no entity so do not replicate
  return FALSE;
}
}