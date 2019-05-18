<?php

namespace Drupal\private_messages\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Dialog entities.
 */
class DialogViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    $data['dialogs']['table']['group'] = t('Dialog');
    $data['dialogs']['table']['base']['access query tag'] = 'dialog_access';

    $data['dialogs']['table']['join']['messages'] = [
      'left_field' => 'dialog_id',
      'field' => 'id'
    ];

    $data['dialogs']['dialog_label'] = array(
      'title' => t('Dialog label'),
      'help' => t('Dialog label views field.'),
      'field' => array(
        'id' => 'dialog_label',
      ),
    );

    return $data;
  }

}
