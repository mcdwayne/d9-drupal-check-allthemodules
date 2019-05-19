<?php

namespace Drupal\wbm2cm\Plugin\Deriver;

class SaveDeriver extends ModerationDeriver {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = parent::getDerivativeDefinitions($base_plugin_definition);

    foreach ($this->derivatives as $id => &$derivative) {
      $derivative['source']['plugin'] = "content_entity_revision:$id";
    }
    return $this->derivatives;
  }

}
