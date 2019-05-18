<?php

/**
 * @file
 * Contains \Drupal\revision_ui\Form\NodeRevisionRevertForm.
 */

namespace Drupal\revision_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a form for reverting a node revision.
 */
class NodeRevisionRevertForm extends \Drupal\node\Form\NodeRevisionRevertForm {

  /**
   * {@inheritdoc}
   */
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
  public function buildForm(array $form, FormStateInterface $form_state, $node_revision = NULL) {
    $form = $this->buildFormERFT($form, $form_state, $node_revision);

    unset($form['revert_untranslated_fields']);

    // Display all fields influenced by revision.
    if (isset($form['changed'])) {
      $form['changed']['#type'] = 'details';
      $form['changed']['#open'] = TRUE;
      $form['changed']['#title'] = $this->t('Changed content');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareRevertedRevision(NodeInterface $revision, FormStateInterface $form_state) {
    return $this->prepareRevertedRevisionERFT($revision, $form_state);
  }

}
