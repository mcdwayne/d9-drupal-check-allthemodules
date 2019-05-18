<?php

namespace Drupal\nodeletter\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\nodeletter\MailchimpApiTrait;

class MailchimpTemplateSelectForm extends FormBase {

  use MailchimpApiTrait;

  public function getFormId() {
    return 'mailchimp_template_select';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $templates = $this->getMailChimpTemplates();

    $tpl_opts = [];
    foreach($templates as $tpl) {
      $tpl_opts[ $tpl->id ] = $tpl->name;
    }

    $form['template'] = [
      '#type' => 'select',
      '#title' => 'Mailchimp Templates',
      '#options' => $tpl_opts,
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }
}
