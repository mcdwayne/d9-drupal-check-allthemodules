<?php

namespace Drupal\commerce_order_pdf\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CommerceOrderPdfForm.
 */
class CommerceOrderPdfForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_order_pdf.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_order_pdf_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_order_pdf.settings');

    $form['invoice_html'] = [
      '#type' => 'text_format',
      '#format' => 'restricted_html',
      '#base_type' => 'textarea',
      '#rows' => 20,
      '#title' => ' HTML Template',
      '#description' => 'The HTML code to be placed within the pdf. HTML can be added through this function or on the pdf invoice.',
      '#default_value' => $config->get('invoice_html.value'),
    ];

    $form['invoice_css'] = [
      '#type' => 'text_format',
      '#format' => 'restricted_html',
      '#rows' => 20,
      '#title' => 'Custom CSS',
      '#description' => 'write custom css for the above html code ',
      '#default_value' => $config->get('invoice_css.value'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Display result.
    $config = $this->config('commerce_order_pdf.settings');

    $invoice_html = $form_state->getValue('invoice_html')['value'];
    $invoice_css = $form_state->getValue('invoice_css')['value'];

    // Set the values the user submitted in the form.
    $config->set('invoice_html.value', $invoice_html)
      ->set('invoice_css.value', $invoice_css)
      ->save();
  }

}