<?php
namespace Drupal\monster_menus;

// Referred by monster_menus.permissions.yml

class Permissions {
  public function permissions() {
    $perms = array();
    if (mm_get_setting('comments.finegrain_readability')) {
      foreach (mm_get_setting('comments.readable_labels') as $label) {
        $perms[$label['perm']] = array(
          'title' => t('<em>Comment readability:</em> @perm', array('@perm' => $label['desc'])),
        );
      }
      $perms[Constants::MM_COMMENT_READABILITY_DEFAULT] = array(
        'title' => t('<em>Comment readability:</em> @perm', array('@perm' => t('can read comments by default'))),
        'description' => t('When no other comment readability setting is applied to a node, roles checked here will be able to read the comments'),
      );
    }

    return $perms;
  }
}