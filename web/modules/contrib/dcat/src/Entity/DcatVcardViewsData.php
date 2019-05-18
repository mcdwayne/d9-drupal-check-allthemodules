<?php

namespace Drupal\dcat\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for vCard entities.
 */
class DcatVcardViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['dcat_vcard']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('vCard'),
      'help' => $this->t('The vCard ID.'),
    );

    return $data;
  }

}
