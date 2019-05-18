<?php

namespace Drupal\search_api_tableselect\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api_tableselect\TableSelectFormBase;

/**
 * Class ListsController.
 *
 * @package Drupal\search_api_tableselect\Form
 */
class TableSelectExampleForm extends TableSelectFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_tableselect_example_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $variables = []) {
    $form = parent::buildForm($form, $form_state, $variables);
    $form['actions']['submit']['#value'] = $this->t('Changed button');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('This output is from example form.'));
    parent::submitForm($form, $form_state);
  }

}
