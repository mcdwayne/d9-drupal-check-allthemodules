<?php

namespace Drupal\forena\Form;


use Drupal\Core\Form\FormStateInterface;
use Drupal\forena\Controller\AjaxPageControllerBase;

class ReportModalForm extends AjaxFormBase {

  const FORM_ID = 'e_modal_report_form';

  public function buildForm(array $form, FormStateInterface $form_state) {
    $controller = AjaxPageControllerBase::service();
    $form['report'] = $this->getController()->modal_content;
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Ok'
    ];
    $this->bindAjaxForm($controller, $form, $form_state);
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->getController()->preventAction();
  }

}