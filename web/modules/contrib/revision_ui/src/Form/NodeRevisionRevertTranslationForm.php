<?php

/**
 * @file
 * Contains \Drupal\revision_ui\Form\NodeRevisionRevertTranslationForm.
 */

namespace Drupal\revision_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a form for reverting a node revision for a single translation.
 */
class NodeRevisionRevertTranslationForm extends \Drupal\node\Form\NodeRevisionRevertTranslationForm {

  use EntityRevertFormTrait {
    EntityRevertFormTrait::buildForm as buildFormERFT;
    EntityRevertFormTrait::prepareRevertedRevision as prepareRevertedRevisionERFT;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Choose the fields you want to revert to the revision from %revision-date.', ['%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime())]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node_revision = NULL, $langcode = NULL) {
    $form = $this->buildFormERFT($form, $form_state, $node_revision, $langcode);

    unset($form['revert_untranslated_fields']);

    if (isset($form['translatable'])) {
      $form['translatable']['#type'] = 'details';
      $form['translatable']['#open'] = TRUE;
      $form['translatable']['#title'] = $this->t('Translated content');
    }

    if (isset($form['untranslatable'])) {
      $form['untranslatable']['#type'] = 'details';
      $form['translatable']['#open'] = FALSE;
      $form['untranslatable']['#title'] = $this->t('Content shared among translations');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareRevertedRevision(NodeInterface $revision, FormStateInterface $form_state) {
    $this->latestRevision = $this->latestRevision->getTranslation($this->langcode);

    return $this->prepareRevertedRevisionERFT($revision->getTranslation($this->langcode), $form_state);
  }

}
