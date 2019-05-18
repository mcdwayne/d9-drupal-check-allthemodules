<?php

namespace Drupal\uc_report\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\Entity\OrderStatus;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates customized sales reports.
 */
class CustomSalesReport extends FormBase {

  /**
   * The date.formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Form constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date.formatter service.
   */
  public function __construct(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $values, $statuses) {
    $form['search'] = [
      '#type' => 'details',
      '#title' => $this->t('Customize sales report parameters'),
      '#description' => $this->t('Adjust these values and update the report to build your custom sales summary. Once submitted, the report may be bookmarked for easy reference in the future.'),
    ];

    $form['search']['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start date'),
      '#default_value' => [
        'month' => $this->dateFormatter->format($values['start_date'], 'custom', 'n'),
        'day' => $this->dateFormatter->format($values['start_date'], 'custom', 'j'),
        'year' => $this->dateFormatter->format($values['start_date'], 'custom', 'Y'),
      ],
    ];
    $form['search']['end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('End date'),
      '#default_value' => [
        'month' => $this->dateFormatter->format($values['end_date'], 'custom', 'n'),
        'day' => $this->dateFormatter->format($values['end_date'], 'custom', 'j'),
        'year' => $this->dateFormatter->format($values['end_date'], 'custom', 'Y'),
      ],
    ];

    $form['search']['length'] = [
      '#type' => 'select',
      '#title' => $this->t('Results breakdown'),
      '#description' => $this->t('Large daily reports may take a long time to display.'),
      '#options' => [
        'day' => $this->t('daily'),
        'week' => $this->t('weekly'),
        'month' => $this->t('monthly'),
        'year' => $this->t('yearly'),
      ],
      '#default_value' => $values['length'],
    ];

    if ($statuses === FALSE) {
      $statuses = uc_report_order_statuses();
    }

    $form['search']['status'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Order statuses'),
      '#description' => $this->t('Only orders with selected statuses will be included in the report.'),
      '#options' => OrderStatus::getOptionsList(),
      '#default_value' => $statuses,
    ];

    $form['search']['detail'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show a detailed list of products ordered.'),
      '#default_value' => $values['detail'],
    ];

    $form['search']['actions'] = ['#type' => 'actions'];
    $form['search']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update report'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->isValueEmpty('status')) {
      $form_state->setErrorByName('status', $this->t('You must select at least one order status.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Build the start and end dates from the form.
    $start_date = mktime(0, 0, 0, $form_state->getValue(['start_date', 'month']), $form_state->getValue(['start_date', 'day']), $form_state->getValue(['start_date', 'year']));
    $end_date = mktime(23, 59, 59, $form_state->getValue(['end_date', 'month']), $form_state->getValue(['end_date', 'day']), $form_state->getValue(['end_date', 'year']));

    $args = [
      'start_date' => $start_date,
      'end_date' => $end_date,
      'length' => $form_state->getValue('length'),
      'status' => implode(',', array_keys(array_filter($form_state->getValue('status')))),
      'detail' => $form_state->getValue('detail'),
    ];

    $form_state->setRedirect('uc_report.custom.sales', $args);
  }

}
