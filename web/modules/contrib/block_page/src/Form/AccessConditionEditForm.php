<?php

/**
 * @file
 * Contains \Drupal\block_page\Form\AccessConditionEditForm.
 */

namespace Drupal\block_page\Form;

/**
 * Provides a form for editing an access condition.
 */
class AccessConditionEditForm extends AccessConditionFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_page_access_condition_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareCondition($condition_id) {
    // Load the access condition directly from the block page.
    return $this->blockPage->getAccessCondition($condition_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function submitButtonText() {
    return $this->t('Update access condition');
  }

  /**
   * {@inheritdoc}
   */
  protected function submitMessageText() {
    return $this->t('The %label access condition has been updated.', array('%label' => $this->condition->getPluginDefinition()['label']));
  }

}
