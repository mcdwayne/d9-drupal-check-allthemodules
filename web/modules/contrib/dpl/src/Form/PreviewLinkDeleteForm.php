<?php

namespace Drupal\dpl\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Url;

class PreviewLinkDeleteForm extends EntityDeleteForm {
  
  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.decoupled_preview_link.collection');
  }

}
