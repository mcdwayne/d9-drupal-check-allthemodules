<?php

/**
 * @file
 * Contains \Drupal\edit_ui_block\Form\SettingsForm.
 */

namespace Drupal\edit_ui_block\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Edit UI settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_ui_block_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['edit_ui.block'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'system/drupal.system';

    $config = $this->config('edit_ui.block');

    $form['blocks'] = array(
      '#type' => 'details',
      '#title' => t('Blocks'),
      '#open' => TRUE,
    );
    $form['blocks']['save_button'] = array(
      '#type' => 'checkbox',
      '#title' => t('Save button'),
      '#default_value' => $config->get('save_button'),
      '#description' => t('Add a save button that allows you to save your work only when you are done with the blocks layout.'),
    );
    $form['blocks']['revert_on_spill'] = array(
      '#type' => 'checkbox',
      '#title' => t('Revert on spill'),
      '#default_value' => $config->get('revert_on_spill'),
      '#description' => t('Revert the dragged element to its original place when dropped outside the region.'),
    );
    $form['blocks']['display_hidden_blocks'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display hidden blocks'),
      '#default_value' => $config->get('display_hidden_blocks'),
      '#description' => t('Display blocks that are normally not visible (e.g. login block when logged in) by using a placeholder block.'),
    );
    $form['blocks']['only_current_page'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add blocks for current page'),
      '#default_value' => $config->get('only_current_page'),
      '#description' => t('When adding a block from the toolbar it only will be available for the current page.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('edit_ui.block')
      ->set('save_button', $form_state->getValue('save_button'))
      ->set('revert_on_spill', $form_state->getValue('revert_on_spill'))
      ->set('display_hidden_blocks', $form_state->getValue('display_hidden_blocks'))
      ->set('only_current_page', $form_state->getValue('only_current_page'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
