<?php

namespace Drupal\micro_site\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Site entities.
 */
class SiteViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

//    $data['site_field_data']['table']['base']['help'] = $this->t('Sites created on your site.');
//    $data['site_field_data']['table']['base']['access query tag'] = 'site_access';
//
//    $data['site_field_data']['id']['argument']['id'] = 'site_id';
//    $data['site_field_data']['id']['argument'] += [
//      'name table' => 'site_field_data',
//      'name field' => 'name',
//      'empty field name' => 'No site name',
//    ];
//
//    $data['site_field_data']['name']['field']['id'] = 'site_name';

    return $data;
  }

}
