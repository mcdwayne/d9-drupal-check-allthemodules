<?php

namespace Drupal\bcubed;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Provides a listing of Condition Set entities.
 */
class ConditionSetListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'condition_set_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Condition Set');
    $header['description'] = $this->t('Description');
    $header['status'] = $this->t('Enabled');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form[$this->entitiesKey]['#empty'] = $this->t('There are no condition sets yet.');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['description']['#markup'] = $entity->get('description') ? SafeMarkup::checkPlain($entity->get('description')) : 'No description has been provided.';
    $row['status'] = [
      '#type' => 'checkbox',
      '#default_value' => $entity->status(),
    ];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue($this->entitiesKey) as $id => $value) {
      if (isset($this->entities[$id]) && ($this->entities[$id]->get($this->weightKey) != $value['weight'] || $this->entities[$id]->status() != (bool) $value['status'])) {
        // Save entity only when its weight  or status was changed.
        $this->entities[$id]->set($this->weightKey, $value['weight']);
        $this->entities[$id]->set('status', (bool) $value['status']);
        $this->entities[$id]->save();
      }
    }

    drupal_set_message(t('The condition sets have been updated.'));
  }

}
