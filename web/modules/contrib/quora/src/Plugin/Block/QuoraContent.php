<?php

namespace Drupal\quora\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\quora\QuoraDataProcess;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Quora' block.
 *
 * @Block(
 *   id = "quora_block",
 *   admin_label = @Translation("Related Quora Questions"),
 *   category = @Translation("Quora"),
 * )
 */
class QuoraContent extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * QuoraDataProcess services object.
   *
   * @var \Drupal\quora\QuoraDataProcess
   */
  private $quoraDataProcess;

  /**
   * Current route object.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRouteMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QuoraDataProcess $quoraDataProcess, CurrentRouteMatch $currentRouteMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->quoraDataProcess = $quoraDataProcess;
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('quora.services'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected $configuration;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = [
      'no_questions' => 3,
      'description' => 'enable',
      'description_size' => 0,
      'search_sensitivity' => 0,
      'exclude' => '',
      'include' => '',
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#cache']['max-age'] = 0;
    $node = $this->currentRouteMatch->getParameter('node');
    if (!($node && $node->id())) {
      $results = NULL;
    }
    else {
      $results = $this->quoraDataProcess->buildContent($node, $this->configuration);
    }
    if (isset($results) && !empty($results)) {
      $build[] = [
        '#theme' => 'quora_results',
        '#results' => $results,
      ];
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['display_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Display Options'),
    ];
    $form['quora_display_options']['no_questions'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of related questions to be shown'),
      '#options' => [
        1 => '1',
        2 => '2',
        3 => '3',
        4 => '4',
        5 => '5',
        6 => '6',
        7 => '7',
        8 => '8',
      ],
      '#default_value' => $this->configuration['no_questions'],
    ];
    $form['display_options']['description'] = [
      '#type' => 'select',
      '#title' => $this->t('Description with questions'),
      '#options' => array(
        'enable' => $this->t('Enable'),
        'disable' => $this->t('Disable'),
      ),
      '#default_value' => $this->configuration['description'],
    ];
    $form['display_options']['description_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Limit Description text'),
      '#description' => $this->t('Enter size, 0 for no limit'),
      '#element_validate' => ['element_validate_integer'],
      '#default_value' => $this->configuration['description_size'],
      '#states' => [
        'visible' => [
          ':input[name="description"]' => ['value' => 'enable'],
        ],
      ],
    ];
    $form['search_options'] = [
      '#type' => 'fieldset',
      '#title' => t('Advanced Search Settings'),
      '#collapsible' => TRUE,
      '#open' => TRUE,
    ];
    $form['search_options']['search_sensitivity'] = [
      '#type' => 'select',
      '#title' => $this->t('Search Sensitivity'),
      '#options' => [
        0 => $this->t('Auto'),
        1 => $this->t('3 Words'),
        2 => $this->t('5 Words'),
        3 => $this->t('7 Words'),
        4 => $this->t('Maximum'),
      ],
      '#default_value' => $this->configuration['search_sensitivity'],
    ];
    $form['search_options']['include'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Always include certain words'),
      '#description' => $this->t('Use comma to separate multiple words. (Case Insensitive)'),
      '#default_value' => $this->configuration['include'],
    ];
    $form['search_options']['exclude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Always exclude certain words'),
      '#description' => $this->t('Use comma to separate multiple words. (Case Insensitive)'),
      '#default_value' => $this->configuration['exclude'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->setConfigurationValue('no_questions', $values['no_questions']);
    $this->setConfigurationValue('description', $values['description']);
    $this->setConfigurationValue('description_size', $values['description_size']);
    $this->setConfigurationValue('search_sensitivity', $values['search_sensitivity']);
    $this->setConfigurationValue('include', $values['include']);
    $this->setConfigurationValue('exclude', $values['exclude']);
  }

}
