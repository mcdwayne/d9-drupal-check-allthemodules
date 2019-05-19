<?php

namespace Drupal\smallads;

use Drupal\views\EntityViewsData;

/**
 * Views data for the smallad entity.
 */
class SmalladViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    // @see Drupal\smallads\Plugin\views\argument_default\AdTypeFromContext
    $data['smallad_field_data']['type']['argument']['name field'] = 'type';
    $data['smallad_field_data']['smallad_bulk_form'] = [
      'title' => t('Bulk smallad update'),
      'help' => t('A form element that lets you run operations on multiple smallads.'),
      'field' => [
        'id' => 'bulk_form',
      ],
    ];
    //@temp
    //@see https://www.drupal.org/node/2337507
    $data['smallad_field_data']['created_fulldate'] = array(
      'title' => $this->t('Created date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_fulldate',
      ),
    );

    $data['smallad_field_data']['uid']['filter']['id'] = 'user_name';

    /* workaround for https://www.drupal.org/node/2846614 */
    //$data['smallad__categories']['categories_target_id'] = $data['smallad__categories']['categories'];
    //unset($data['smallad__categories']['categories']);

    return $data;
  }



}
