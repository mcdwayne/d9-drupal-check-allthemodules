<?php

namespace Drupal\cbo_location;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the location edit forms.
 */
class LocationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $location = $this->entity;
    $insert = $location->isNew();
    $location->save();
    $location_link = $location->link($this->t('View'));
    $context = ['%title' => $location->label(), 'link' => $location_link];
    $t_args = ['%title' => $location->link($location->label())];

    if ($insert) {
      $this->logger('location')->notice('Location: added %title.', $context);
      drupal_set_message($this->t('Location %title has been created.', $t_args));
    }
    else {
      $this->logger('location')->notice('Location: updated %title.', $context);
      drupal_set_message($this->t('Location %title has been updated.', $t_args));
    }
  }

}
