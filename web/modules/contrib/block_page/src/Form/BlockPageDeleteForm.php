<?php

/**
 * @file
 * Contains \Drupal\block_page\Form\BlockPageDeleteForm.
 */

namespace Drupal\block_page\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a block page.
 */
class BlockPageDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the block page %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return new Url('block_page.page_list');
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
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('The block page %name has been removed.', array('%name' => $this->entity->label())));
    $form_state['redirect_route'] = $this->getCancelRoute();
  }

}
