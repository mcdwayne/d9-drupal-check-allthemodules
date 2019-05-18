<?php

namespace Drupal\mymodule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Flood\DatabaseBackend;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Component\Datetime\Time;

/**
 * Class MyCustomForm.
 */
class MyCustomForm extends FormBase {

  /**
   * Drupal\Core\Flood\DatabaseBackend definition.
   *
   * @var \Drupal\Core\Flood\DatabaseBackend
   */
  protected $flood;

  /**
   * Drupal\Core\Datetime\DateFormatter definition.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Drupal\Component\Datetime\Time definition.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $datetimeTime;

  /**
   * Constructs a new MyCustomForm object.
   */
  public function __construct(
  DatabaseBackend $flood, DateFormatter $date_formatter, Time $datetime_time
  ) {
    $this->flood = $flood;
    $this->dateFormatter = $date_formatter;
    $this->datetimeTime = $datetime_time;
  }

  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('flood'), $container->get('date.formatter'), $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'my_custom_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $interval = $this->config('floodcontrol_settings_api.settings')->get('mycustomform1_window');
    $limit = $this->config('floodcontrol_settings_api.settings')->get('mycustomform1_threshold');

    if (!$this->flood->isAllowed('mycustomform1', $limit, $interval)) {
      $form_state->setErrorByName('', $this->t('You cannot submit the form more than %number times in @interval. Try again later.', [
            '%number' => $limit,
            '@interval' => $this->dateFormatter->formatInterval($interval)
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Register for the floodcontrol.
    $this->flood->register('mycustomform1', $this->config('floodcontrol_settings_api.settings')->get('floodcontrol_settings_api_mycustomform1_window'));

    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }
  }

}
