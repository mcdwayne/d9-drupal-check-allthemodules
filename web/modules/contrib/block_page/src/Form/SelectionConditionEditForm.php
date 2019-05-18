<?php

/**
 * @file
 * Contains \Drupal\block_page\Form\SelectionConditionEditForm.
 */

namespace Drupal\block_page\Form;

/**
 * Provides a form for editing an selection condition.
 */
class SelectionConditionEditForm extends SelectionConditionFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_page_selection_condition_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareCondition($condition_id) {
    // Load the selection condition directly from the page variant.
    return $this->pageVariant->getSelectionCondition($condition_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function submitButtonText() {
    return $this->t('Update selection condition');
  }

  /**
   * {@inheritdoc}
   */
  protected function submitMessageText() {
    return $this->t('The %label selection condition has been updated.', array('%label' => $this->condition->getPluginDefinition()['label']));
  }

}
