<?php

namespace Drupal\group_taxonomy;

use Drupal\Core\Access\AccessResult;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContentType;

abstract class GroupTaxonomyAccessCheck {


  /**
   * @param null $op
   * @param null $vocabulary_id
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function checkCreatorAccess($op = NULL, $vocabulary_id = NULL) {

    // Admin: always.
    if (\Drupal::currentUser()->hasPermission('administer taxonomy')) {
      return TRUE;
    }

    $guid = Group::getCurrentUserId();
    $group_vocabulary_id = key(GroupContentType::loadByEntityTypeId('taxonomy_vocabulary'));
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group_contents = \Drupal::entityTypeManager()
      ->getStorage('group_content')
      ->loadByProperties([
          'type' => $group_vocabulary_id,
          'uid' => $guid,
        ]
      );

    foreach ($group_contents as $group_content) {
      /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
      $group_vacab = $group_content->toArray();
      $gvid = $group_vacab['entity_id'][0]['target_id'];
      if (is_null($vocabulary_id)) {
        return TRUE;
      }
      elseif ($gvid === $vocabulary_id) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * @param $vid
   * @param $operation
   * @param $account
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultNeutral
   */
  public static function CheckAccess($operation, $vid, $account) {
    switch ($operation) {
      case 'view terms':
        return AccessResult::allowedIfHasPermissions($account, ["view terms in $vid", 'administer taxonomy'], 'OR');
      break;

      case 'create terms':
        return AccessResult::allowedIfHasPermissions($account, ["create terms in $vid", 'administer taxonomy'], 'OR');
      break;

      case 'edit terms':
        return AccessResult::allowedIfHasPermissions($account, ["edit terms in $vid", 'administer taxonomy'], 'OR');
      break;

      case 'delete terms':
        return AccessResult::allowedIfHasPermissions($account, ["delete terms in $vid", 'administer taxonomy'], 'OR');
      break;

      default:
        // No opinion.
        return AccessResult::neutral();
    }
  }

}
