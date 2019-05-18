<?php

namespace Drupal\flags_ui\Form;

use Drupal\Core\Url;

class CountryMappingDeleteForm extends FlagMappingDeleteForm {

  /**
   * @inheritDoc
   */
  public function getCancelUrl() {
    return new Url('entity.country_flag_mapping.list');
  }

}
