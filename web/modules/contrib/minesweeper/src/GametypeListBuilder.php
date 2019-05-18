<?php

/**
 * @file
 * Contains \Drupal\minesweeper\GametypeListBuilder.
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
class GametypeListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'minesweeper_gametype_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Name');
    $header['description'] = t('Description');
    $header['multiplayer'] = t('Multiplayer');
    $header['difficulty'] = t('Difficulty');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['description']['#markup'] = $entity->getDescription();
    $row['multiplayer']['#markup'] = $entity->getMultiplayer() ? $this->t('enabled') : $this->t('disabled');
    $row['difficulty']['#markup'] = '<ul><li>' . implode('</li><li>', $entity->getAllowedDifficulties()) . '</li></ul>';
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
