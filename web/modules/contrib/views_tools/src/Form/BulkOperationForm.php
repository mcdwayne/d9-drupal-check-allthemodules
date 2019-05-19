<?php

namespace Drupal\views_tools\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Entity\View;
use Drupal\views_tools\ViewsTools;

/**
 * Contribute form.
 */
class BulkOperationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_tools_bulk_operation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $header = NULL, $rows = NULL, $view = NULL) {
    $form['display'] = array(
      '#title' => $view->label(),
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $rows,
      '#empty' => $this->t('No displays found'),
    );

    $form['view_id'] = [
      '#type' => 'hidden',
      '#value' => $view->id(),
    ];

    $form['display_operation'] = [
      '#title' => $this->t('Select Operation'),
      '#type' => 'select',
      '#options' => [
        'export' => $this->t('Export into a single new view'),
        'delete' => $this->t('Delete displays'),
        'multi_export' => $this->t('Export to YAML'),
      ],
    ];
    $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Submit')];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $values = $form_state->getValue('display');
    $action = $form_state->getValue('display_operation');
    $displays = array_filter($values, function ($display) {
      return !empty($display) ? $display : NULL;
    });
    $view = View::load($form_state->getValue('view_id'));
    if ($action == 'export') {
      $newView = ViewsTools::exportDisplaysAsView($view, $displays);
      $form_state->setRedirect('entity.view.edit_form', ['view' => $newView->id()]);
    }
    elseif ($action == 'delete') {
      ViewsTools::deleteDisplay($view, $displays);
    }
    elseif ($action == 'multi_export') {
      ViewsTools::exportDisplaysToYaml($view, $displays);
      $form_state->setRedirect('views_tools.view', ['view' => $view->id()]);
    }
  }

}
