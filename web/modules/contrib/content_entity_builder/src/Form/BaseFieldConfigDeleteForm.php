<?php

namespace Drupal\content_entity_builder\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\content_entity_builder\ContentTypeInterface;

/**
 * Form for deleting a base field config.
 */
class BaseFieldConfigDeleteForm extends ConfirmFormBase {

  /**
   * The content entity type containing the base field to be deleted.
   *
   * @var \Drupal\content_entity_builder\ContentTypeInterface
   */
  protected $contentType;

  /**
   * The base field to be deleted.
   *
   * @var \Drupal\content_entity_builder\BaseFieldConfigInterface
   */
  protected $baseField;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the @base_field base field from the @content_type content entity type?', ['@content_type' => $this->contentType->label(), '@base_field' => $this->baseField->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->contentType->urlInfo('edit-form');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'base_field_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ContentTypeInterface $content_type = NULL, $base_field = NULL) {
    $this->contentType = $content_type;
    $this->baseField = $this->contentType->getBaseField($base_field);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->contentType->deleteBaseField($this->baseField);
    drupal_set_message($this->t('The base field %name has been deleted.', ['%name' => $this->baseField->label()]));
    $form_state->setRedirectUrl($this->contentType->urlInfo('edit-form'));
  }

}
