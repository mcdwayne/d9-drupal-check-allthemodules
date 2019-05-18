<?php

namespace Drupal\fragments\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for fragment entities.
 */
class FragmentViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['fragment_field_data']['type']['argument']['id'] = 'fragment_type';

    $data['fragment_field_data']['user_id']['help'] = $this->t('The user authoring the fragment. If you need more fields than the uid add the fragment author relationship');
    $data['fragment_field_data']['user_id']['filter']['id'] = 'user_name';
    $data['fragment_field_data']['user_id']['relationship']['title'] = $this->t('Fragment author');
    $data['fragment_field_data']['user_id']['relationship']['help'] = $this->t('Relate fragments to the user who created them.');
    $data['fragment_field_data']['user_id']['relationship']['label'] = $this->t('author');

    return $data;
  }

}
