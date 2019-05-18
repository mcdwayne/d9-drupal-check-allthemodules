<?php

namespace Drupal\owntracks\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * OwnTracks entity form.
 */
class OwnTracksEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['uid']['#access'] = $this->currentUser()->hasPermission('administer owntracks') ? TRUE : FALSE;
    return $form;
  }

}
