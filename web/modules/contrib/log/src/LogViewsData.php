<?php

/**
 * @file
 * Contains \Drupal\log\Entity\Log.
 */

namespace Drupal\log;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Log entities.
 */
class LogViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['log']['log_bulk_form'] = array(
      'title' => t('Log operations bulk form'),
      'help' => t('Add a form element that lets you run operations on multiple log entities.'),
      'field' => array(
        'id' => 'log_bulk_form',
      ),
    );

    return $data;
  }

}
