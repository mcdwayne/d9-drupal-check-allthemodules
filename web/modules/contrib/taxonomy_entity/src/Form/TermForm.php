<?php

namespace Drupal\taxonomy_entity\Form;

use Drupal\taxonomy\TermForm as CoreTermForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Base for handler for taxonomy term edit forms.
 */
class TermForm extends CoreTermForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $term = $this->entity;
    $vocab_storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    $vocabulary = $vocab_storage->load($term->bundle());

    $hierachy = $vocabulary->getHierarchy();
    if ($hierachy === VocabularyInterface::HIERARCHY_DISABLED) {
      $form['relations']['#access'] = FALSE;
    }

    if ($hierachy === VocabularyInterface::HIERARCHY_SINGLE) {
      $form['relations']['parent']['#multiple'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $term = parent::buildEntity($form, $form_state);

    $vocab_storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    $vocabulary = $vocab_storage->load($term->bundle());

    $hierachy = $vocabulary->getHierarchy();
    if ($hierachy === VocabularyInterface::HIERARCHY_SINGLE) {
      // Assign parents with proper delta values starting from 0.
      $term->parent = $form_state->getValue('parent');
    }

    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $vocabulary = $form_state->get(['taxonomy', 'vocabulary']);
    $hierarchy = $vocabulary->getHierarchy();

    parent::save($form, $form_state);

    if ($vocabulary->getHierarchy() !== $hierarchy) {
      $vocabulary->setHierarchy($hierarchy);
      $vocabulary->save();
    }
  }

}
