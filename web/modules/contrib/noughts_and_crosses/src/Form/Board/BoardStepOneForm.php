<?php

/**
 * @file
 * Contains \Drupal\noughts_and_crosses\Form\Board\BoardStepOneForm.
 */

namespace Drupal\noughts_and_crosses\Form\Board;

use Drupal\Core\Form\FormStateInterface;

class BoardStepOneForm extends BoardFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'board_form_one';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $options_play_type = $this->_get_play_type_options();
    $play_type =  $form_state->getValue('play_type');
    if (!isset($play_type)) {
      $play_type =  $this->store->get('play_type');
    }
    $first_move = $form_state->getValue('first_move');
    if (!isset($first_move)) {
      $first_move =  $this->store->get('first_move');
    }
    $selected_play_type = isset($play_type) ? $play_type : key($options_play_type);

    $form['play_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('How should it be ?'),
      '#options' => $options_play_type,  
      '#description' => $this->t('Here you can set to play against human or computer.'),
      '#default_value' => $selected_play_type,
      '#required' => TRUE,
      '#ajax' => [
        'event' => 'change',
        'callback' => [ $this , 'choose_first_move_callback' ],
        'wrapper' => 'dropdown-first-move-replace',
      ],
    );

    $form['first_move'] = array(
      '#type' => 'select',
      '#title' => $this->t('First move by ?'),
      '#prefix' => '<div id="dropdown-first-move-replace">',
      '#suffix' => '</div>',
      '#options' => $this->_get_first_move_dropdown_options($selected_play_type),
      '#description' => $this->t('Here you can set the first move by wheather by human or computer.'),
      '#default_value' => !empty($first_move) ? $first_move : 0,
    );

    $form['actions']['submit']['#value'] = $this->t('Next &raquo;');
    
    return $form;
  }

  /**
   * Returns array of Play Type options for user(s) to choose from.
   */
  public function _get_play_type_options(){
    return array(
       0 => t('Please Select'), 
       1 => t('Human v/s Human'),
       //2 => t('Computer v/s Human'), @TODO: Implement Alpha–beta pruning algorithm.
      );
  }

  /**
   * Returns array of First Move By options for user(s) to choose from.
   */
  public function _get_first_move_dropdown_options($key = '') {
    $options = array(
      1 => array(
        1 => t('Human'),
      ),
      2 => array(
        0 => t('Please Select'), 
        1 => t('Human'),
        2 => t('Computer'),
      ),
    );
    if (isset($options[$key])) {
      return $options[$key];
    }
    else {
      return array();
    }
  }

  /**
   * AJAX function to execute upon selecting Play Type option.
   * It helps populate the second options i.e. First Move By.
   */
  public function choose_first_move_callback(array &$form, FormStateInterface $form_state) {
    return $form['first_move'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('play_type') == 0) {
      $form_state->setErrorByName('play_type', $this->t('Please select Play Type.'));
    }
    if ($form_state->getValue('first_move') == 0) {
      $form_state->setErrorByName('first_move', $this->t('Please select Who should move first.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->store->set('play_type', $form_state->getValue('play_type'));
    $this->store->set('first_move', $form_state->getValue('first_move'));    
    $form_state->setRedirect('noughts_and_crosses.board_step_two');
  }
}
