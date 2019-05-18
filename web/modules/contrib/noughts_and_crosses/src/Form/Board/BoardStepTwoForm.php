<?php

/**
 * @file
 * Contains \Drupal\noughts_and_crosses\Form\Board\BoardStepTwoForm.
 */

namespace Drupal\noughts_and_crosses\Form\Board;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class BoardStepTwoForm extends BoardFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'board_form_two';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['board'] = array(
      '#prefix' => '<div class="board-wrap">',
      '#tree' => TRUE,
      '#suffix' => '</div>',
    );

    for ($i=0; $i < 3; $i++) {
      for ($j=0; $j < 3; $j++) {
        $form['board'][$i][$j] = array(
          '#type' => 'textfield',
          '#required' => TRUE,
          '#attributes' => array(
            'size' => 1,
            'maxlength' => 1,
            'autocomplete' => 'off',
          ),         
        );
      }
    }

    $markup = $this->t("Play Again !");
    $form['play_again'] = array(
      '#prefix' => '<div class="play-again">',
      '#markup' => $markup,
      '#suffix' => '</div>',
    );

    $form['actions']['previous'] = array(
      '#type' => 'link',
      '#title' => $this->t('&laquo; Back'),
      '#attributes' => array(
        'class' => array('button', 'back'),
      ),
      '#weight' => 0,
      '#url' => Url::fromRoute('noughts_and_crosses.board'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {     
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the data
    parent::saveData();
    $form_state->setRedirect('noughts_and_crosses.board');
  }
}
