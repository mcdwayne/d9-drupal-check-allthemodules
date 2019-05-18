<?php

namespace Drupal\paragraphs_react;

/**
 * Interface ParagraphsReactManagerInterface.
 */
interface ParagraphsReactManagerInterface {
  public function saveReactParagraphSetting($reactParagraphSetting);
  public function loadParagraphSetting($entity_id,$field_name);
  public function manageFormSubmit($form,\Drupal\Core\Form\FormStateInterface $form_state);
}
