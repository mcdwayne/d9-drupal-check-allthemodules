<?php

namespace Drupal\content_entity_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\content_entity_builder\ContentTypeInterface;

/**
 * Provides an edit form for base field.
 */
class BaseFieldConfigEditForm extends BaseFieldConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ContentTypeInterface $content_type = NULL, $base_field = NULL) {
    $form = parent::buildForm($form, $form_state, $content_type, $base_field);

    $form['actions']['submit']['#value'] = $this->t('Update base field');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareBaseField($base_field) {
    return $this->contentType->getBaseField($base_field);
  }

}
