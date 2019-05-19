<?php

namespace Drupal\wsc_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Webform select collection form.
 */
class ExampleForm extends FormBase {

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs a ExampleForm object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   A logger instance.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_select_collection_example';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['select_collection_example'] = [
      '#type' => 'webform_select_collection',
      '#title' => $this->t('Select collection'),
      '#line_items' => [
        'item_1' => 'Item 1',
        'item_2' => 'Item 2',
      ],
      '#line_options' => [
        'option_1' => 'Option 1',
        'option_2' => 'Option 2',
      ],
    ];

    $form['checkbox_collection_example'] = [
      '#type' => 'webform_select_collection',
      '#title' => $this->t('Webform select checkbox collection'),
      '#line_items' => [
        'item_1' => 'Item 1',
        'item_2' => 'Item 2',
      ],
      '#checkbox_collection' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $test = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $test = TRUE;
  }

}
