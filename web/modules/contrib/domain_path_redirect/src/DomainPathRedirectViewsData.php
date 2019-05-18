<?php

namespace Drupal\domain_path_redirect;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the domain_path_redirect entity.
 */
class DomainPathRedirectViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['domain_path_redirect']['domain']['filter']['id'] = 'domain_autocomplete';
    return $data;
  }

}
