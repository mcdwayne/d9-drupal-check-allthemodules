<?php

namespace Drupal\taxonomy_entity\Form;

use Drupal\taxonomy\VocabularyForm as CoreVocabularyForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Base form for vocabulary edit forms.
 */
class VocabularyForm extends CoreVocabularyForm {

  /**
   * The vocabulary storage.
   *
   * @var \Drupal\taxonomy\VocabularyStorageInterface
   */
  protected $vocabularyStorage;

  /**
   * Form element validation handler for hierarchy vocabulary element.
   */
  public static function validateHierarchy(&$element, FormStateInterface $form_state, &$complete_form) {
    /** @var VocabularyForm $form */
    $form = $form_state->getFormObject();
    $vocabulary = $form->getEntity();

    $tree = \Drupal::service('entity_type.manager')
      ->getStorage('taxonomy_term')
      ->loadTree($vocabulary->id());
    $hierarchy = VocabularyInterface::HIERARCHY_DISABLED;
    foreach ($tree as $term) {
      // Check this term's parent count.
      if (count($term->parents) > 1) {
        $hierarchy = VocabularyInterface::HIERARCHY_MULTIPLE;
        break;
      }
      elseif (count($term->parents) == 1 && (!isset($term->parents[0]) || $term->parents[0] != 0)) {
        $hierarchy = VocabularyInterface::HIERARCHY_SINGLE;
      }
    }

    if ($element['#value'] > $hierarchy) {
      return;
    }

    if ($hierarchy === VocabularyInterface::HIERARCHY_SINGLE) {
      $form_state->setError($element, t('This cannot be changed to the chosen setting because existing terms already have a relational pattern of children with a single parent.'));
      return;
    }

    if ($hierarchy === VocabularyInterface::HIERARCHY_MULTIPLE) {
      $form_state->setError($element, t('This cannot be changed to the chosen setting because existing terms already have a relational pattern of children with multiple parents.'));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
    $vocabulary = $this->entity;

    $form['hierarchy'] = [
      '#type' => 'select',
      '#title' => $this->t('Hierarchy'),
      '#default_value' => $vocabulary->getHierarchy(),
      '#options' => [
        VocabularyInterface::HIERARCHY_DISABLED => $this->t('No hierarchy'),
        VocabularyInterface::HIERARCHY_SINGLE => $this->t('Single parent hierarchy'),
        VocabularyInterface::HIERARCHY_MULTIPLE => $this->t('Multiple parent hierarchy'),
      ],
      '#element_validate' => [
        [$this, 'validateHierarchy'],
      ],
    ];

    return $this->protectBundleIdElement($form);
  }

}
