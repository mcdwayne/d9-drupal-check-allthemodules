<?php
/**
 * @file
 * Contains \Drupal\field_quiz\Form\FieldQuizAdminSettingsForm.
 */

namespace Drupal\field_quiz\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Field Quiz settings for this site.
 */
class FieldQuizAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_quiz_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['field_quiz.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('field_quiz.settings');

    // Shuffle the answers?
    $form['field_quiz_shuffle_answers'] = [
      '#type' => 'checkbox',
      '#title' => t('Shuffle answers?'),
      '#default_value' => $config->get('field_quiz.shuffle_answers'),
      '#description' => t('If checked the answers will be shuffled. Attention: If enabled, page caching is disabled for these pages!'),
    ];

    // Text, if answer(s) were correct.
    $form['field_quiz_test_answer_correct'] = [
      '#type' => 'textarea',
      '#title' => t('Text, if the answer(s) were correct.'),
      '#default_value' => $config->get('field_quiz.test_answer_correct'),
      '#description' => t('Please enter the text, that should be displayed if the user has answered correctly.'),
    ];

    // Text, if answer(s) were wrong.
    $form['field_quiz_test_answer_wrong'] = [
      '#type' => 'textarea',
      '#title' => t('Text, if the answer(s) were wrong.'),
      '#default_value' => $config->get('field_quiz.test_answer_wrong'),
      '#description' => t('Please enter the text, that should be displayed if the user has answered wrong.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('field_quiz.settings');
    $config
      ->set('field_quiz.shuffle_answers', $form_state->getValue('field_quiz_shuffle_answers'))
      ->set('field_quiz.test_answer_correct', $form_state->getValue('field_quiz_test_answer_correct'))
      ->set('field_quiz.test_answer_wrong', $form_state->getValue('field_quiz_test_answer_wrong'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
