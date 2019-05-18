<?php

namespace Drupal\phrase_captcha\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Administration form.
 */
class PhraseCaptchaSettingsForm extends ConfigFormBase {

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
    return 'phrase_captcha_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['phrase_captcha.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('phrase_captcha.settings');
    drupal_set_message($this->t('WARNING: this module is not completely ported to Drupal 8 and does not work yet.'), 'warning');
    // Radio buttons for selecting the kind of words to use.
    $form['phrase_captcha_words'] = [
      '#type' => 'radios',
      '#title' => $this->t('Kind of words to use in the CAPTCHA phrase'),
      '#options' => [
        PHRASE_CAPTCHA_GENERATE_NONSENSE_WORDS => $this->t('Generate nonsense words'),
        PHRASE_CAPTCHA_USER_DEFINED_WORDS => $this->t('Use user defined words'),
      ],
      '#default_value' => $config->get('phrase_captcha_words'),
      '#required' => TRUE,
    ];
    // Form elements for the word pools.
    _text_captcha_word_pool_form_items($form,
      'phrase_captcha_userdefined_word_pool', 'User defined word pool',
      'Enter the words to use in the CAPTCHA phrase (space separated, no punctuation).',
      ''
    );
    // Select form element for the number of words in the CAPTCHA phrase.
    $form['phrase_captcha_word_quantity'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of words in the CAPTCHA phrase'),
      '#default_value' => $config->get('phrase_captcha_word_quantity'),
      '#options' => array_combine([4, 5, 6, 7, 8, 9, 10], [4, 5, 6, 7, 8, 9, 10]),
      '#required' => TRUE,
    ];
    // Select form element for the number of additional words.
    $form['phrase_captcha_additional_word_quantity'] = [
      '#type' => 'select',
      '#title' => $this->t('Maximum number of additional words to let the user choose from'),
      '#default_value' => $config->get('phrase_captcha_additional_word_quantity'),
      '#options' => array_combine([0, 1, 2, 3, 4, 5], [0, 1, 2, 3, 4, 5]),
      '#required' => TRUE,
    ];
    $form['phrase_captcha_word_selection_challenges'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Word selection challenges'),
      '#options' => _phrase_captcha_available_word_challenges(),
      '#default_value' => _phrase_captcha_enabled_word_challenges(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('phrase_captcha.settings');
    $config
      ->set('phrase_captcha_word_selection_challenges', $form_state->getValue('phrase_captcha_word_selection_challenges'))
      ->set('phrase_captcha_additional_word_quantity', $form_state->getValue('phrase_captcha_additional_word_quantity'))
      ->set('phrase_captcha_word_quantity', $form_state->getValue('phrase_captcha_word_quantity'));
    foreach ($form_state->getValues() as $label => $value) {
      if (strpos($label, 'phrase_captcha_userdefined_word_pool') !== FALSE) {
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
    if ($form_state->getValue('phrase_captcha_words') == PHRASE_CAPTCHA_USER_DEFINED_WORDS) {
      $word_count_minimum = $form_state->getValue('phrase_captcha_word_quantity') + $form_state->getValue('phrase_captcha_additional_word_quantity') + 2;
      _text_captcha_word_pool_validate('phrase_captcha_userdefined_word_pool', $form_state, $word_count_minimum, NULL, NULL);
    }
    // Check word selection.
    if (count(array_filter($form_state->getValue('phrase_captcha_word_selection_challenges'))) < 1) {
      $form_state->setErrorByName('phrase_captcha_word_selection_challenges', $this->t('You need to select at least one word selection criterium'));
    }

    parent::validateForm($form, $form_state);
  }

}
