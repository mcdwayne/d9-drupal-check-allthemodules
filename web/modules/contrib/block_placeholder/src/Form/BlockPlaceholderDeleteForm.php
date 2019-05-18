<?php

namespace Drupal\block_placeholder\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Define block placeholder delete form.
 */
class BlockPlaceholderDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete @label?', [
      '@label' => $this->entity->label()
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $enabled_blocks = [];

    foreach ($entity->getPlaceholderBlocks() as $block_id => $block_info) {
      $enabled_blocks[] = "{$block_info['label']} (${block_id})";
    }

    if (!empty($enabled_blocks)) {
      $list_blocks = [
        '#theme' => 'item_list',
        '#items' => $enabled_blocks
      ];
      $form_state->setError(
        $form['confirm'],
        $this->t('Unable to delete block placeholder @label, due to the 
        following block configurations existing: @list_blocks', [
          '@label' => $entity->label(),
          '@list_blocks' => render($list_blocks)
        ])
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->entity->delete();
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
