<?php

namespace Drupal\courier_ui\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\courier\TemplateCollectionInterface;

/**
 * Form controller for courier_template_collection.
 */
class EditTemplateCollection extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state, TemplateCollectionInterface $templateCollection = NULL) {
    $form = parent::form($form, $form_state);
    return $form;
  }

}
