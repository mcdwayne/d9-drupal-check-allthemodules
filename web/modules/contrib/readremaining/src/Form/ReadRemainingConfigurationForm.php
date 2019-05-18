<?php

namespace Drupal\readremaining\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This class creates the ReadRemaining configuration form.
 *
 * @package Drupal\readremaining\Form
 */
class ReadRemainingConfigurationForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ReadRemainingConfigurationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactory $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'read_remaining_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['read_remaining_configuration.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $config = $this->config('read_remaining_configuration.settings');

    $form['active_on'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Active on'),
    ];

    $form['active_on']['contenttypes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Contenttypes'),
      '#options' => $this->getListOfContentTypes(),
      '#default_value' => ($config->get('contenttypes')) ? $config->get('contenttypes') : [],
      '#description' => $this->t('List of content types Read Remaining should be active on'),
    ];

    $form['active_on']['selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DOM Selector'),
      '#default_value' => ($config->get('selector')) ? $config->get('selector') : 'body',
      '#description' => $this->t('The selector ReadRemaining should be calculated on. For example body, .my-wrapper or #content'),
    ];

    $form['theming'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Look and feel'),
    ];

    $form['theming']['look_feel'] = [
      '#type' => 'select',
      '#title' => $this->t('Theming'),
      '#options' => [
        'dark' => $this->t('Dark'),
        'light' => $this->t('Light'),
      ],
      '#default_value' => ($config->get('look_feel')) ? $config->get('look_feel') : 'dark',
      '#description' => $this->t('Defines the look and feel of the read remaining element'),
    ];

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Javascript settings'),
    ];

    $form['settings']['show_gauge_delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Show gauge delay'),
      '#default_value' => ($config->get('show_gauge_delay')) ? $config->get('show_gauge_delay') : 1000,
      '#description' => $this->t('Delay before showing the indicator in milliseconds.'),
    ];

    $form['settings']['show_gauge_on_start'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show gauge on start'),
      '#default_value' => ($config->get('show_gauge_on_start')) ? $config->get('show_gauge_on_start') : FALSE,
      '#description' => $this->t('Show the gauge initially, even before the user scrolls.'),
    ];

    $form['settings']['time_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Time format'),
      '#default_value' => '%mm %ss left',
      '#description' => $this->t('Will replace %m and %s with minutes and seconds.'),
    ];

    $form['settings']['max_time_to_show'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum time to show (in seconds)'),
      '#default_value' => ($config->get('max_time_to_show')) ? $config->get('max_time_to_show') : 1200,
      '#description' => $this->t('Only show time if is lower than x seconds.'),
    ];

    $form['settings']['min_time_to_show'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum time to show (in seconds)'),
      '#default_value' => ($config->get('min_time_to_show')) ? $config->get('min_time_to_show') : 10,
      '#description' => $this->t('Only show time if is higher than x seconds  Only show time if is higher than x seconds.'),
    ];

    $form['settings']['gauge_container'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gauge container'),
      '#default_value' => ($config->get('gauge_container')) ? $config->get('gauge_container') : '',
      '#description' => $this->t('The element where the gauge will append to. If left empty, the container will be the same scrolling element.'),
    ];

    $form['settings']['insert_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Insert position'),
      '#options' => [
        'prepend' => $this->t('Prepend'),
        'append' => $this->t('Append'),
      ],
      '#default_value' => ($config->get('insert_position')) ? $config->get('insert_position') : 'prepend',
      '#description' => $this->t('append or prepend as required by style'),
    ];

    $form['settings']['verbose_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Verbose mode'),
      '#default_value' => FALSE,
      '#description' => $this->t('Enable the console logs. For testing only.'),
    ];

    $form['settings']['gauge_wrapper'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gauge wrapper'),
      '#default_value' => ($config->get('gauge_wrapper')) ? $config->get('gauge_wrapper') : '',
      '#description' => $this->t('Optional, the element that define the visible scope for the gauge. If left empty, the gauge will be visible all along.'),
    ];

    $form['settings']['top_offset'] = [
      '#type' => 'number',
      '#title' => $this->t('Top offset'),
      '#default_value' => 0,
      '#description' => $this->t('Distance between the top of the gaugeWrapper and the point where the gauge will start to appear. Some designs require this.'),
    ];

    $form['settings']['bottom_offset'] = [
      '#type' => 'number',
      '#title' => $this->t('Bottom offset'),
      '#default_value' => ($config->get('bottom_offset')) ? $config->get('bottom_offset') : 0,
      '#description' => $this->t('Distance between bottom border where the box will appear and the bottom of the element.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    $this->config('read_remaining_configuration.settings')
      ->set('contenttypes', $values['contenttypes'])
      ->set('selector', $values['selector'])
      ->set('look_feel', $values['look_feel'])
      ->set('show_gauge_delay', $values['show_gauge_delay'])
      ->set('show_gauge_on_start', $values['show_gauge_on_start'])
      ->set('time_format', $values['time_format'])
      ->set('max_time_to_show', $values['max_time_to_show'])
      ->set('min_time_to_show', $values['min_time_to_show'])
      ->set('gauge_container', $values['gauge_container'])
      ->set('insert_position', $values['insert_position'])
      ->set('verbose_mode', $values['verbose_mode'])
      ->set('gauge_wrapper', $values['gauge_wrapper'])
      ->set('top_offset', $values['top_offset'])
      ->set('bottom_offset', $values['bottom_offset'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Builds an option list of all content types and returns it.
   *
   * @return array
   *   An array of available content types, keyed by machine name.
   */
  public function getListOfContentTypes() {
    $options = [];

    $node_types = $this->entityTypeManager
      ->getStorage('node_type')
      ->loadMultiple();

    foreach ($node_types as $node_type) {
      $options[$node_type->id()] = $node_type->label();
    }

    return $options;
  }

}
