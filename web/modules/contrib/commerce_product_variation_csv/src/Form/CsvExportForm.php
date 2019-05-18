<?php

namespace Drupal\commerce_product_variation_csv\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class CsvExportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_product_variation_csv_csv_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $this->getRouteMatch()->getParameter('commerce_product');

    $form['description'] = [
      '#markup' => $this->t('<p>Click "Export variations as CSV" to retrieve a CSV of variation data</p>'),
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export variations as CSV'),
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = Link::createFromRoute($this->t('Back to variations'), 'entity.commerce_product_variation.collection', ['commerce_product' => $product->id()])->toRenderable();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $this->getRouteMatch()->getParameter('commerce_product');
    $csv_handler = \Drupal::getContainer()->get('commerce_product_variation_csv.csv_exporter');
    $csv = $csv_handler->createCsv($product);

    $response = new Response($csv);
    $response->headers->set('Content-Type', 'text/csv');
    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      sprintf('%s.csv', $product->label())
    );

    $response->headers->set('Content-Disposition', $disposition);
    $form_state->setResponse($response);
  }

}
