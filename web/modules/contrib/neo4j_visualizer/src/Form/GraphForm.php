<?php

namespace Drupal\neo4j_visualizer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for revision overview page.
 */
class GraphForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'revision_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $form['query'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Query'),
      '#description' => $this->t('Query to be executed in Cypher language'),
      '#default_value' => 'MATCH (n)-[o]-(m) RETURN n, o, m'
    ];
    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 999,
    ];
    $form['execute'] = [
      '#type' => 'submit',
      '#value' => t('Execute'),
      '#attributes' => ['class' => ['neo4j_visualizer_submit']],
    ];
    $form['content'] = array(
      '#type' => 'container',
      '#attributes' => [
        'id' => [
          'graph-container',
        ]
      ],
    );
    $form['#attached']['library'][] = 'neo4j_visualizer/sigmajs';
    $form['#redirect'] = FALSE;
    return $form;
  }

  /**
   * Ajax callback for the "Suggest tags" button.
   */
  public function executeSubmitAjax(array $form, FormStateInterface $form_state) {
    return $form['content'];
  }

  /**
   * Submission handler for the "Suggest tags" button.
   */
  public function executeSubmit(array $form, FormStateInterface $form_state) {
    $form_state->set('executed', TRUE);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
