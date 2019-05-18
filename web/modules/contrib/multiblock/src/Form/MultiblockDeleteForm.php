<?php

namespace Drupal\multiblock\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class MultiblockDeleteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multiblock_delete_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $delta = NULL) {
    $block = multiblock_get_block($delta);

    if (empty($block)) {
      drupal_set_message(t('The multiblock with the delta @delta was not found.', [
        '@delta' => $delta
        ]), 'error');
      return [];
    }

    $form['delta'] = ['#type' => 'value', '#value' => $delta];
    return confirm_form($form, t('Delete the block instance %title?', [
      '%title' => $block->title
      ]), 'admin/structure/block/instances', t('This will delete the instance of the block %title.', [
      '%title' => $block->title
      ]), t('Delete'), t('Cancel'));
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if (multiblock_delete($form_state->getValue(['delta']))) {
      drupal_set_message(t('Block successfully deleted!'));
    }
    else {
      drupal_set_message(t('There was a problem deleting the block'));
    }
    $form_state->set(['redirect'], 'admin/structure/block/instances');
  }

}
