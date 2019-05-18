<?php

namespace Drupal\block_in_form\Form;

use Drupal\block_in_form\BlockInFormCommon;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_group\FieldgroupUi;

/**
 * Provides a form for removing a fieldgroup from a bundle.
 */
class BlockInFormDeleteForm extends ConfirmFormBase {

  use BlockInFormCommon;

  /**
   * The fieldgroup to delete.
   *
   * @var stdClass
   */
  protected $blockInForm;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_in_form_delete_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $block_in_form_name = NULL, $entity_type_id = NULL, $bundle = NULL, $context = NULL) {

    if ($context == 'form') {
      $mode = $this->getRequest()->attributes->get('form_mode_name');
    }
    else {
      $mode = $this->getRequest()->attributes->get('view_mode_name');
    }

    if (empty($mode)) {
      $mode = 'default';
    }

    $this->blockInForm = $this->loadBlock($block_in_form_name, $entity_type_id, $bundle, $context, $mode);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bundles = \Drupal::entityTypeManager()->getAllBundleInfo();
    $bundle_label = $bundles[$this->blockInForm->entity_type][$this->blockInForm->bundle]['label'];

    $this->deleteBlock($this->blockInForm);

    drupal_set_message(t('The group %group has been deleted from the %type content type.', array('%group' => t($this->blockInForm->label), '%type' => $bundle_label)));

    // Redirect.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the group %group?', array('%group' => $this->blockInForm->label));
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
    return FieldgroupUi::getFieldUiRoute($this->blockInForm);
  }
}
