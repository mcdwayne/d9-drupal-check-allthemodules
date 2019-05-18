<?php

/**
 * @file
 * Contains \Drupal\block_page\Form\BlockPageFormBase.
 */

namespace Drupal\block_page\Form;

use Drupal\Core\Entity\EntityForm;

/**
 * Provides a base form for editing and adding a block page.
 */
abstract class BlockPageFormBase extends EntityForm {

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\block_page\BlockPageInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('The label for this block page.'),
      '#default_value' => $this->entity->label(),
      '#maxlength' => '255',
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#disabled' => !$this->entity->isNew(),
      '#maxlength' => 64,
      '#required' => TRUE,
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
      ),
    );
    $form['path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->getPath(),
      '#required' => TRUE,
    );

    return parent::form($form, $form_state);
  }

  /**
   * Determines if the block page already exists.
   *
   * @param string $id
   *   The block page ID.
   *
   * @return bool
   *   TRUE if the format exists, FALSE otherwise.
   */
  public function exists($id) {
    return (bool) \Drupal::entityQuery('block_page')
      ->condition('id', $id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $this->entity->save();
  }

}
