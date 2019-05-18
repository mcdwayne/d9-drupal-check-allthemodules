<?php

/**
 * @file
 * Contains \Drupal\block_page\Form\BlockPageAddForm.
 */

namespace Drupal\block_page\Form;

use Drupal\Core\Url;

/**
 * Provides a form for adding a new block page.
 */
class BlockPageAddForm extends BlockPageFormBase {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    parent::save($form, $form_state);
    drupal_set_message($this->t('The %label block page has been added.', array('%label' => $this->entity->label())));
    $form_state['redirect_route'] = new Url('block_page.page_edit', array(
      'block_page' => $this->entity->id(),
    ));
  }

}
