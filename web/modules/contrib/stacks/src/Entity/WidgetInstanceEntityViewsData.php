<?php

namespace Drupal\stacks\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Widget Instance entity entities.
 */
class WidgetInstanceEntityViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['widget_instance_entity']['table']['base'] = [
      'field' => 'id',
      'title' => t('Widget Instance entity'),
      'help' => t('The Widget Instance entity ID.'),
    ];

    $data['widget_instance_entity']['widget_instance_bulk_form'] = [
      'title' => t('Widget instance operations bulk form'),
      'help' => t('Add a form element that lets you run operations on multiple widget instances.'),
      'field' => [
        'id' => 'widget_instance_bulk_form',
      ],
    ];

    return $data;
  }

}
