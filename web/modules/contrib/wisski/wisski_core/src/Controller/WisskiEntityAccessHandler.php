<?php

namespace Drupal\wisski_core\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the comment entity.
 *
 * @see \Drupal\comment\Entity\Comment.
 */
class WisskiEntityAccessHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Link the activities to the permissions. checkAccess is called with the
   * $operation as defined in the routing.yml file.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
#    return AccessResult::forbidden("You are missing the correct permissions to see this content.")->cachePerPermissions();
#  dpm(func_get_args(),__METHOD__);

    // I don't know what this does... but node does it, so we do it, too.
    $account = $this->prepareUser($account);

    #\Drupal::logger('UPDATE IN '.$operation)->debug('{u}',array('u'=>serialize($entity) . " and " . $operation));
    
    if ($account->hasPermission('bypass wisski access')) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $result;
    }
    
    if($operation == "view" || $operation == "edit" || $operation == "delete" ) {

      // two special cases for view
      if($operation == "view") {
#        \Drupal::logger('UPDATE IN '.$operation)->debug('{u}',array('u'=>serialize($entity->get('status')->getValue())));
        // if it is a published one and you may view published ones...    
        if($account->hasPermission($operation . ' published wisski content')) {

          // a little bit problematic getting this value....
          $value = $entity->get('status')->getValue();

          if(isset($value[0]) && isset($value[0]["value"]) && $value[0]["value"] == TRUE) {
            $result = AccessResult::allowed()->cachePerPermissions();
            return $result;
          }
        }
        
        $uid = $entity->get('uid');
        if(!empty($uid))
          $uid = $uid->entity;

        if(!empty($uid))
          $uid = $uid->id();
        
        // if we may view our own unpublished content or we may view other unpublished content
        if((!is_null($uid) && ($account->hasPermission($operation . ' own unpublished wisski content') & $uid == $account->id())) || $account->hasPermission($operation . ' other unpublished wisski content')) {
          $result = AccessResult::allowed()->cachePerPermissions();
          return $result;
        }
      }      
         
      // if the user may view any content or he/she may view the whole bundle - exit here.
      if($account->hasPermission($operation . ' any wisski content') || $account->hasPermission($operation . ' any ' . $entity->bundle() . ' WisskiBundle')) {
        $result = AccessResult::allowed()->cachePerPermissions();
        return $result;
      }
      
      // both above was not correct, so it may be that he is the owner of the thing.
      if($account->hasPermission($operation . ' own wisski content') || $account->hasPermission($operation . ' own ' . $entity->bundle() . ' WisskiBundle')) {
        // get the uid
        $uid = $entity->get('uid');
        
        // see if there was something in the field
        if(!empty($uid))
          $uid = $uid->entity; // only get the entity if there was something
        
        // only get the id if there was something in there
        if(!empty($uid))
          $uid = $uid->id();
        
        if(!empty($uid) && $uid == $account->id()) {
          $result = AccessResult::allowed()->cachePerPermissions();
          return $result;
        }
      }

      // if none holds, we forbid it.
      $result = AccessResult::forbidden("You are missing the correct permissions to see this content.")->cachePerPermissions();
      return $result;      
    }
    // update
  
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   *
   * Separate from the checkAccess because the entity does not yet exist, it
   * will be created during the 'add' process.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {

    $route_match = \Drupal::service('current_route_match');
    $bundle_from_url = $route_match->getParameter('wisski_bundle');
    // try to get it from url if not available otherwise
    if(empty($entity_bundle) && !empty($bundle_from_url))
      $entity_bundle = $bundle_from_url->ID();
                          

    //dpm(func_get_args(),__METHOD__);
    //return AccessResult::allowedIfHasPermission($account, 'administer wisski');
    $account = $this->prepareUser($account);

    #\Drupal::logger('UPDATE IN '.$operation)->debug('{u}',array('u'=>serialize($entity) . " and " . $operation));
    
    if ($account->hasPermission('bypass wisski access')) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $result;
    }
    
    // if the user may view any content or he/she may view the whole bundle - exit here.
    if($account->hasPermission('create any wisski content') || $account->hasPermission('create ' . $entity_bundle . ' WisskiBundle')) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $result;
    }

    // if none holds, we forbid it.
    $result = AccessResult::forbidden("You are missing the correct permissions to see this content.")->cachePerPermissions();
    return $result;      
  }

}
