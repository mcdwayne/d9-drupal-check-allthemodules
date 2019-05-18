<?php

/**
 * @file
 * Contains \Drupal\minesweeper\DifficultyListBuilder.
 */

namespace Drupal\minesweeper;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a class to build a listing of Minesweeper Difficulty entities.
 *
 * @see \Drupal\minesweeper\Entity\Difficulty
 */
class DifficultyListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'minesweeper_difficulty_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Name');
    $header['width'] = t('Width');
    $header['height'] = t('Height');
    $header['mines'] = t('Mines');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label() . ' (' . $entity->id() . ')';
    $row['width']['#markup'] = $entity->getBoardWidth();
    $row['height']['#markup'] = $entity->getBoardHeight();
    $row['mines']['#markup'] = $entity->getMines();
    return $row + parent::buildRow($entity);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    drupal_set_message(t('The difficulty settings have been updated.'));
  }

}
