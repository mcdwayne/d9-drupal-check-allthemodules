<?php

namespace Drupal\competition;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a trait for competition judging.
 */
trait CompetitionJudgingTrait {

  /**
   * AJAX callback.
   *
   * Called on 'Add round' or 'Remove this round' button clicks.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   *
   * @return array
   *   Round fields
   */
  public function roundCallback(array &$form, FormStateInterface $form_state) {

    return $form['judging']['rounds'];

  }

  /**
   * Implements callback for Ajax event on 'Add round' button click.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   */
  public function addRound(array &$form, FormStateInterface $form_state) {

    $num_rounds = $form_state->get('num_rounds');
    $form_state->set('num_rounds', ($num_rounds + 1));
    $form_state->setRebuild();

  }

  /**
   * Implements callback for Ajax event on 'Add round' button click.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   */
  public function removeRound(array &$form, FormStateInterface $form_state) {

    $num_rounds = $form_state->get('num_rounds');
    if ($num_rounds > 1) {
      $form_state->set('num_rounds', ($num_rounds - 1));
    }
    $form_state->setRebuild();

  }

  /**
   * Implements callback for Ajax event on 'Add round' button click.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   */
  public function validateRounds(array &$form, FormStateInterface $form_state) {

    $num_rounds = $form_state->get('num_rounds');
    for ($i = 1; $i <= $num_rounds; $i++) {

      $value = $form_state->getValue([
        'judging',
        'rounds',
        $i,
        'weighted_criteria',
      ]);
      if (!empty($value)) {

        $lines = explode("\n", $value);
        if (is_array($lines)) {

          $criteria = [];
          foreach ($lines as $line) {

            $items = explode('|', $line);
            if (count($items) != 2 || !is_numeric($items[0])) {
              $form_state->setErrorByName("judging][rounds][$i][weighted_criteria", t('Weighted judging criteria must use number|label format'));
            }

          }

        }

      }

    }

  }

}
