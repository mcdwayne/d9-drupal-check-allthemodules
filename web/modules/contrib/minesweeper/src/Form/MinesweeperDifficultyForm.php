<?php
/**
 * @file
 * Contains \Drupal\minesweeper\Form\MinesweeperDifficultyForm.
 */

namespace Drupal\minesweeper\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\minesweeper\Entity\Gametype;

/**
 * Difficulty form.
 */
class MinesweeperDifficultyForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'minesweeper_difficulty_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Gametype $gametype = NULL) {
    $form['title'] = array(
      '#markup' => $gametype->label,
      '#prefix' => '<h3>',
      '#suffix' => '</h3>',
    );

    $form['description'] = array(
      '#markup' => $gametype->getDescription(),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    );

    $form['game_type'] = array(
      '#type' => 'value',
      '#value' => $gametype->id(),
    );

    $form['difficulty'] = array(
      '#type' => 'radios',
      '#title' => t('Choose your difficulty'),
      '#options' => $this->getDifficultyOptions($gametype),
      '#default_value' => 'novice',
    );

    $form['next'] = array(
      '#type' => 'submit',
      '#value' => t('Play minesweeper'),
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
    $game_type = $form_state->getValue('game_type');
    $difficulty = $form_state->getValue('difficulty');

    $url = \Drupal\Core\Url::fromRoute('minesweeper.game_page')
      ->setRouteParameters(array('gametype' => $game_type, 'difficulty' => $difficulty));

    $form_state->setRedirectUrl($url);
  }

  private function getDifficultyOptions(Gametype $gametype) {
    $options = array();
    foreach ($gametype->getAllowedDifficulties() as $difficulty) {
      $difficulty = \Drupal\minesweeper\Entity\Difficulty::load($difficulty);
      $options[$difficulty->id()] = $difficulty->label();
    }
    return $options;
  }
}