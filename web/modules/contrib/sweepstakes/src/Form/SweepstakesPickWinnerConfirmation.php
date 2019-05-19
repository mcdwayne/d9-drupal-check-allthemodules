<?php

/**
 * @file
 * Contains \Drupal\sweepstakes\Form\SweepstakesPickWinnerConfirmation.
 */

namespace Drupal\sweepstakes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class SweepstakesPickWinnerConfirmation extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sweepstakes_pick_winner_confirmation';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    return [
      'sid' => [
        '#type' => 'hidden',
        '#value' => arg(1),
      ],
      'redraw_interval' => [
        '#type' => 'textfield',
        '#default_value' => t('24'),
        '#title' => t('Redraw in another'),
        '#field_suffix' => 'hours',
      ],
      'pick_winners' => [
        '#type' => 'submit',
        '#value' => t('Pick Winners'),
        '#submit' => [
          'sweepstakes_pick_winners_and_set_redraw'
          ],
      ],
    ];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }


}
