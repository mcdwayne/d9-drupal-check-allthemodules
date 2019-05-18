<?php

namespace Drupal\broken_link;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the BrokenLink entity type.
 */
class BrokenLinkViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {

    $data = parent::getViewsData();

    $data['broken_link']['table']['base']['help'] = $this->t('Broken Links');
    $data['broken_link']['table']['base']['access query tag'] = 'manage broken link list';

    $data['broken_link']['table']['wizard_id'] = 'broken_link';

    $data['broken_link']['table']['group'] = t('Broken links');

    // Specify field real column name for additional entity fields.
    $data['broken_link__referers']['referers']['filter']['real field'] = 'referers_value';
    $data['broken_link__query_string']['query_string']['filter']['real field'] = 'query_string_value';
    $data['broken_link__referers']['referers']['sort']['real field'] = 'referers_value';
    $data['broken_link__query_string']['query_string']['sort']['real field'] = 'query_string_value';

    return $data;
  }

}
