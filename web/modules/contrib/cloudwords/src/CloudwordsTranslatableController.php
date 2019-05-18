<?php

namespace Drupal\cloudwords;

class CloudwordsTranslatableController extends EntityAPIController {

  /**
   * Overrides EntityAPIController::delete().
   */
  public function delete($ids, DatabaseTransaction $transaction = NULL) {
    parent::delete($ids, $transaction);

    // Since we are deleting one or multiple translatables, we need to delete
    // the content mappings as well.
    if ($ids) {
      \Drupal::database()->delete('cloudwords_content')
        ->condition('ctid', $ids)
        ->execute();
    }
  }

}
