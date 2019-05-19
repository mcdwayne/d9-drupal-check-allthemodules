<?php

namespace Drupal\taxonomy_entity\Form;

use Drupal\taxonomy\Form\OverviewTerms as CoreOverviewTerms;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Provides terms overview form for a taxonomy vocabulary.
 */
class OverviewTerms extends CoreOverviewTerms {

  /**
   * Form constructor.
   *
   * Display a tree of all the terms in a vocabulary, with options to edit
   * each one. The form is made drag and drop by the theme function.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\taxonomy\VocabularyInterface $taxonomy_vocabulary
   *   The vocabulary to display the overview form for.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, VocabularyInterface $taxonomy_vocabulary = NULL) {
    $form = parent::buildForm($form, $form_state, $taxonomy_vocabulary);

    $hierarchy = $taxonomy_vocabulary->getHierarchy();
    if ($hierarchy === VocabularyInterface::HIERARCHY_DISABLED) {
      $tabledrags = $form['terms']['#tabledrag'];
      foreach ($tabledrags as $key => $tabledrag) {
        if ($tabledrag['relationship'] == 'parent' || $tabledrag['relationship'] == 'group') {
          unset($form['terms']['#tabledrag'][$key]);
        }
      }
      if (($key = array_search('taxonomy/drupal.taxonomy', $form['terms']['#attached']['library'])) !== FALSE) {
        unset($form['terms']['#attached']['library'][$key]);
      }
      unset($form['terms']['#attached']['drupalSettings']['taxonomy']);
    }

    return $form;
  }

  /**
   * Form submission handler.
   *
   * Rather than using a textfield or weight field, this form depends entirely
   * upon the order of form elements on the page to determine new weights.
   *
   * Because there might be hundreds or thousands of taxonomy terms that need to
   * be ordered, terms are weighted from 0 to the number of terms in the
   * vocabulary, rather than the standard -10 to 10 scale. Numbers are sorted
   * lowest to highest, but are not necessarily sequential. Numbers may be
   * skipped when a term has children so that reordering is minimal when a child
   * is added or removed from a term.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $vocabulary = $form_state->get(['taxonomy', 'vocabulary']);
    // Update the current hierarchy type as we go.
    $hierarchy = $vocabulary->getHierarchy();

    parent::submitForm($form, $form_state);

    $vocabulary->setHierarchy($hierarchy);
    $vocabulary->save();
  }

}
