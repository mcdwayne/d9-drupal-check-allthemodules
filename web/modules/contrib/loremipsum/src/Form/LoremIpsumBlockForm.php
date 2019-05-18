<?php

/**
 * @file
 * Contains \Drupal\loremipsum\Form\BlockFormController
 */

namespace Drupal\loremipsum\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Lorem Ipsum block form
 */
class LoremIpsumBlockForm extends FormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'loremipsum_block_form';
  }

  /**
   * {@inheritdoc}
   * Lorem ipsum generator block.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // How many paragraphs?
    // $options = new array();
    for ($i = 1; $i <= 10; $i++) $options[$i] = $i;
    $form['paragraphs'] = array(
      '#type' => 'select',
      '#title' => $this->t('Paragraphs'),
      '#options' => $options,
      '#default_value' => 4,
      '#description' => $this->t('How many?'),
    );

    // How many phrases?
    $form['phrases'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Phrases'),
      '#default_value' => '20',
      '#description' => $this->t('Maximum per paragraph'),
    );

    // Submit
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $phrases = $form_state->getValue('phrases');
    if (!is_numeric($phrases)) $form_state->setErrorByName('phrases', $this->t('Please use a number.'));
    if (floor($phrases) != $phrases) $form_state->setErrorByName('phrases', $this->t('No decimals, please.'));
    if ($phrases < 1) $form_state->setErrorByName('phrases', $this->t('Please use a number greater than zero.'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect(
      'loremipsum.generate',
      array(
        'paragraphs' => $form_state->getValue('paragraphs'),
        'phrases' => $form_state->getValue('phrases'),
      )
    );
  }
}