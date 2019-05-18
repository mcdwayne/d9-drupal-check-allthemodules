<?php

namespace Drupal\uc_report\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\Entity\OrderStatus;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates customized product reports.
 */
class CustomProductReport extends FormBase {

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
  public function buildForm(array $form, FormStateInterface $form_state, $values) {
    $form['search'] = [
      '#type' => 'details',
      '#title' => $this->t('Customize product report parameters'),
      '#description' => $this->t('Adjust these values and update the report to build your custom product report. Once submitted, the report may be bookmarked for easy reference in the future.'),
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

    $form['search']['status'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Order statuses'),
      '#description' => $this->t('Only orders with selected statuses will be included in the report.'),
      '#options' => OrderStatus::getOptionsList(),
      '#default_value' => $values['status'],
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
    $start_date = mktime(0, 0, 0, $form_state->getValue(['start_date', 'month']), $form_state->getValue(['start_date', 'day']), $form_state->getValue(['start_date', 'year']));
    $end_date = mktime(23, 59, 59, $form_state->getValue(['end_date', 'month']), $form_state->getValue(['end_date', 'day']), $form_state->getValue(['end_date', 'year']));

    $args = [
      'start_date' => $start_date,
      'end_date' => $end_date,
      'status' => implode(',', array_keys(array_filter($form_state->getValue('status')))),
    ];

    $form_state->setRedirect('uc_report.custom.products', $args);
  }

}
