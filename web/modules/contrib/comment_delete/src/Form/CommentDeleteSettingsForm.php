<?php

namespace Drupal\comment_delete\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Comment Delete settings for this site.
 */
class CommentDeleteSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'comment_delete_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['comment_delete.config'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('comment_delete.config');
    $form['comment_delete_default'] = [
      '#type' => 'radios',
      '#title' => t('Default Delete Option'),
      '#description' => t('Choose the default option selected when a comments deleted.'),
      '#options' => [
        0 => t('Delete comment and replies'),
        1 => t('Delete comment and move replies up'),
        2 => t('Delete comment and keep replies'),
      ],
      '#required' => TRUE,
      '#default_value' => $config->get('default_selection'),
    ];
    $form['comment_delete_soft'] = [
      '#type' => 'checkbox',
      '#title' => t('Soft delete comments'),
      '#description' => t('When enabled the comment subject+body is cleared but comment+author is retained.'),
      '#default_value' => $config->get('soft'),
    ];
    $form['comment_delete_threshold'] = [
      '#type' => 'textfield',
      '#title' => t('Threshold Period'),
      '#description' => t('Max allowable time comments can be deleted after creation. Enter zero (0) to disable.'),
      '#size' => 10,
      '#default_value' => $config->get('threshold'),
      '#required' => TRUE,
    ];
    $form['comment_delete_message'] = [
      '#type' => 'textarea',
      '#title' => t('Confirmation Message'),
      '#description' => t('Customize confirmation message shown after comment has been deleted.'),
      '#default_value' => $config->get('message'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $threshold = $form_state->getValue('comment_delete_threshold');

    // Check threshold is numerical value.
    if (!is_numeric($threshold)) {
      $form_state->setErrorByName('comment_delete_threshold', $this->t('Threshold should be greater than or equal to zero (0).'));
    }

    // Check threshold is not negative.
    if ($threshold < 0) {
      $form_state->setErrorByName('comment_delete_threshold', $this->t('Threshold should be greater than or equal to zero (0).'));
    }

    // Check threshold does not include decimals.
    if (preg_match('/\./i', $threshold)) {
      $form_state->setErrorByName('comment_delete_threshold', $this->t('Threshold should not include decimals.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('comment_delete.config')
      ->set('default_selection', $form_state->getValue('comment_delete_default'))
      ->set('soft', $form_state->getValue('comment_delete_soft'))
      ->set('threshold', $form_state->getValue('comment_delete_threshold'))
      ->set('message', $form_state->getValue('comment_delete_message'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
