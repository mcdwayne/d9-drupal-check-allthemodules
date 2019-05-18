<?php

namespace Drupal\cloudwords\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\cloudwords\CloudwordsFile;

/**
 * Default controller for the cloudwords module.
 */
class DefaultController extends ControllerBase {

  public function cloudwords_file_download(CloudwordsDrupalProject $project, CloudwordsFile $file) {

  }

  public function cloudwords_ajax_select_all($display, $op, $ctids) {
    if (empty($_GET['token']) || !drupal_valid_token($_GET['token'], 'cloudwords')) {
      return MENU_ACCESS_DENIED;
    }

    $ctids = explode(',', $ctids);
    $user = \Drupal::currentUser();

    if ($display == 'block_2') {
      $translatables = cloudwords_translatable_load_multiple($ctids);
      if ($op == 'add') {
        foreach ($translatables as $translatable) {
          $translatable->queue();
        }
      }
      elseif ($op == 'remove') {
        foreach ($translatables as $translatable) {
          $translatable->dequeue();
        }
      }

      print cloudwords_queue_count() . ' items marked for translation.';
      return;
    }

    elseif ($display == 'block_1') {

      if ($op == 'remove') {
        cloudwords_project_user_remove($user, $ctids);
      }
      elseif ($op == 'add') {
        cloudwords_project_user_add($user, $ctids);
      }
      elseif ($op == 'all') {

      }

      print cloudwords_project_user_count($user) . ' items in project.';
      return;
    }
  }

}
