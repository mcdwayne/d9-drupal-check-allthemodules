<?php

namespace Drupal\node_subs\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;

/**
 * Provides a 'SubscribeBlock' block.
 *
 * @Block(
 *  id = "subscribe_block",
 *  admin_label = @Translation("Subscribe block"),
 * )
 */
class SubscribeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Form\FormBuilderInterface definition.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;
  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * Constructs a new SubscribeBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    FormBuilderInterface $form_builder,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('config.factory')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'node_subs_show_name_field' => FALSE,
      'node_subs_name_field_required' => FALSE,
      'node_subs_submit_label' => $this->t('Subscribe me'),
      'use_ajax' => FALSE,
      'notification_type' => 'messages',
          ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['node_subs'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Subscribe settings'),
      '#collapsible' => FALSE,
    ];
    $form['node_subs']['node_subs_show_name_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display name field'),
      '#default_value' => $this->configuration['node_subs_show_name_field'],
    ];
    $form['node_subs']['node_subs_name_field_required'] = [
      '#type' => 'checkbox',
      '#title' => t('Name field required'),
      '#default_value' => $this->configuration['node_subs_name_field_required'],
      '#states' => [
        'visible' => [
          ':input[name="settings[node_subs][node_subs_show_name_field]"]' => ['checked' => TRUE]
        ]
      ]
    ];
    $form['node_subs']['node_subs_submit_label'] = [
      '#type' => 'textfield',
      '#title' => t('Label of submit button'),
      '#default_value' => $this->configuration['node_subs_submit_label'],
      '#description' => t('Input untranslated string'),
      '#size' => 20
    ];
    $form['node_subs']['use_ajax'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use ajax'),
      '#default_value' => $this->configuration['use_ajax'],
    ];
    $form['node_subs']['notification_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Notification type'),
      '#options' => [
        'messages' => $this->t('Notification in messages area'),
        'popup' => $this->t('Notification in popup'),
        'popup' => $this->t('Notification in popup'),
      ],
      '#default_value' => $this->configuration['notification_type'],
      '#states' => [
        'visible' => [
          ':input[name="settings[node_subs][use_ajax]"]' => ['checked' => TRUE]
        ]
      ]
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValue('node_subs');
    $this->configuration['node_subs_show_name_field'] = $values['node_subs_show_name_field'];
    $this->configuration['node_subs_name_field_required'] = $values['node_subs_name_field_required'];
    $this->configuration['node_subs_submit_label'] = $values['node_subs_submit_label'];
    $this->configuration['use_ajax'] = $values['use_ajax'];
    $this->configuration['notification_type'] = $values['notification_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $form_state = new FormState();
    $form_state->addBuildInfo('notification_type', $this->configuration['notification_type']);
    $form = $this->formBuilder->buildForm('\Drupal\node_subs\Form\SubscribeForm', $form_state);
    if ($this->configuration['node_subs_show_name_field']) {
      $form['ajax_container']['name'] = [
        '#title' => $this->t('Name'),
        '#type' => 'textfield',
        '#required' => $this->configuration['node_subs_name_field_required'],
        '#weight' => 0,
      ];
    }

    if (!$this->configuration['use_ajax']) {
      unset($form['ajax_container']['actions']['submit']['#ajax']);
    }

    if ($title = $this->configuration['node_subs_submit_label']) {
      $form['ajax_container']['actions']['submit']['#value'] = $title;
    }

    $build['form'] = $form;

    return $build;
  }

}
