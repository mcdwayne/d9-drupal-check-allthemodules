<?php

/**
 * @file
 * Contains \Drupal\block_page\Form\AccessConditionFormBase.
 */

namespace Drupal\block_page\Form;

/**
 * Provides a base form for editing and adding an access condition.
 */
abstract class AccessConditionFormBase extends ConditionFormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    parent::submitForm($form, $form_state);

    $configuration = $this->condition->getConfiguration();
    // If this access condition is new, add it to the page.
    if (!isset($configuration['uuid'])) {
      $this->blockPage->addAccessCondition($configuration);
    }

    // Save the block page.
    $this->blockPage->save();

    $form_state['redirect_route'] = $this->blockPage->urlInfo('edit-form');
  }

}
