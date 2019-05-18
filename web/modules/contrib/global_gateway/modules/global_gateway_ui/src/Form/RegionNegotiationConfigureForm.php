<?php

namespace Drupal\global_gateway_ui\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\global_gateway\RegionNegotiatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the selected region negotiation method for this site.
 */
class RegionNegotiationConfigureForm extends ConfigFormBase {

  /**
   * The language negotiator.
   *
   * @var \Drupal\global_gateway\RegionNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs a NegotiationConfigureForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\global_gateway\RegionNegotiatorInterface $negotiator
   *   The language negotiation methods manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    RegionNegotiatorInterface $negotiator
  ) {
    parent::__construct($config_factory);
    $this->negotiator = $negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('global_gateway_region_negotiator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'global_gateway_regin_negotiation_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['negotiators'] = [
      '#title'           => $this->t('Region detection'),
      '#tree'            => TRUE,
      '#show_operations' => FALSE,
      'weight'           => ['#tree' => TRUE],
    ];

    $form['negotiators'] = [
      '#type'       => 'table',
      '#tree'       => TRUE,
      '#attributes' => ['id' => 'region-negotiation-methods-'],
      '#tabledrag'  => [
        [
          'action'       => 'order',
          'relationship' => 'sibling',
          'group'        => 'negotiators-order-weight',
        ],
      ],
    ];

    $negotiators =& $form['negotiators'];

    foreach ($this->negotiator->getNegotiators() as $method) {
      $id = $method->id();
      $label = $method->getLabel();

      $negotiators[$id] = [
        '#weight'     => $method->getWeight(),
        '#attributes' => ['class' => ['draggable']],
      ];

      $negotiators[$id]['title'] = [
        '#prefix'     => '<strong>',
        '#plain_text' => $label,
        '#suffix'     => '</strong>',
      ];
      $negotiators[$id]['description'] = [
        '#markup' => $method->getDescription(),
      ];
      $negotiators[$id]['enabled'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Enable @title region detection method', ['@title' => mb_strtolower($label)]),
        '#title_display' => 'invisible',
        '#default_value' => $method->get('enabled'),
      ];

      $negotiators[$id]['weight'] = [
        '#type'          => 'weight',
        '#title'         => $this->t('Weight for @title region detection method', ['@title' => mb_strtolower($label)]),
        '#title_display' => 'invisible',
        '#default_value' => $method->getWeight(),
        '#attributes'    => ['class' => ["negotiators-order-weight"]],
        '#delta'         => 10,
      ];

      $config_op = [];
      $negotiators[$id]['operation'] = [];

      if ($method->getConfigRoute()) {
        $config_op['configure'] = [
          'title' => $this->t('Configure'),
          'url'   => Url::fromRoute($method->getConfigRoute()),
        ];
        $negotiators['#show_operations'] = TRUE;

        $negotiators[$id]['operation'] = [
          '#type'  => 'operations',
          '#links' => $config_op,
        ];
      }
    }
    uasort($negotiators, [
      'Drupal\Component\Utility\SortArray',
      'sortByWeightProperty',
    ]);

    $header = [
      'label'       => $this->t('Detection method'),
      'description' => $this->t('Description'),
      'enabled'     => $this->t('Enabled'),
      'weight'      => $this->t('Weight'),
    ];

    // If there is at least one operation enabled show the operation column.
    if ($negotiators['#show_operations']) {
      $header['operations'] = $this->t('Operations');
    }

    $form['negotiators']['#header'] = $header;

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type'        => 'submit',
      '#button_type' => 'primary',
      '#value'       => $this->t('Save settings'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->negotiator->saveConfiguration($form_state->getValue('negotiators'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

}
