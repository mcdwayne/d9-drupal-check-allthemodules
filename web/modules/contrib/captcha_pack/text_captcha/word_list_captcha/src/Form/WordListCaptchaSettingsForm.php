<?php

namespace Drupal\word_list_captcha\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Administration form.
 */
class WordListCaptchaSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'word_list_captcha_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['word_list_captcha.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('word_list_captcha.settings');
    drupal_set_message($this->t('WARNING: this module is not completely ported to Drupal 8 and does not work yet.'), 'warning');
    $code_length_options = [4, 5, 6, 7, 8, 9, 10];
    // Form element for the number of words in the word list.
    $form['word_list_captcha_list_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of words in word list'),
      '#default_value' => $config->get('word_list_captcha_list_size'),
      '#options' => array_combine($code_length_options, $code_length_options),
    ];
    // Form elements for the word pools.
    _text_captcha_word_pool_form_items($form,
      'word_list_captcha_word_pool_1',
      'Word pool 1',
      'Enter a bunch of related words (space separated, no punctuation). Make sure they are not related in the same way to the words of the other word pool.',
      WORD_LIST_CAPTCHA_WORD_POOL1,
      2
    );
    _text_captcha_word_pool_form_items($form,
      'word_list_captcha_word_pool_2',
      'Word pool 2',
      'Enter a bunch of related words (space separated, no punctuation). Make sure they are not related in the same way to the words of the other word pool.',
      WORD_LIST_CAPTCHA_WORD_POOL2,
      2
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('word_list_captcha.settings');
    $config
      ->set('word_list_captcha_list_size', $form_state->getValue('word_list_captcha_list_size'));
    foreach ($form_state->getValues() as $label => $value) {
      if (strpos($label, 'word_list_captcha_word_pool') !== FALSE) {
        $config->set($label, $value);
      }
    }
    $config->save();

    parent::SubmitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check the number of words in the pools.
    $list_size = (int) $form_state->getValue('word_list_captcha_list_size');
    _text_captcha_word_pool_validate('word_list_captcha_word_pool_1', $form_state, $list_size, 0, '');
    _text_captcha_word_pool_validate('word_list_captcha_word_pool_2', $form_state, $list_size, 0, '');

    parent::validateForm($form, $form_state);
  }

}
