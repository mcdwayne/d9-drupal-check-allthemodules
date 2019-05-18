<?php

namespace Drupal\route_iframes\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RouteIframeConfigEntityForm.
 *
 * @package Drupal\route_iframes\Form
 */
class RouteIframeConfigEntityForm extends EntityForm {

  /**
   * Drupal\Core\Entity\EntityTypeBundleInfo definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $bundleInfo;

  /**
   * Constructor for the Form.
   */
  public function __construct(EntityTypeBundleInfoInterface $bundle_info) {
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $config = $this->config('route_iframes.routeiframesconfiguration');

    $route_iframe_config_entity = $this->entity;
    $scope_type = $route_iframe_config_entity->get('scope_type');
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $route_iframe_config_entity->label(),
      '#description' => $this->t("A name for the Route Iframe Configuration."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $route_iframe_config_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\route_iframes\Entity\RouteIframeConfigEntity::load',
        'source' => ['name'],
      ],
      '#disabled' => !$route_iframe_config_entity->isNew(),
    ];
    $tabs = [];
    $default_tab = $route_iframe_config_entity->get('tab');
    foreach ($config->get('route_iframe_tabs') as $tab) {
      if (empty($default_tab)) {
        $default_tab = $tab['path'];
      }
      $tabs[$tab['path']] = $tab['name'];
    }

    if (count($tabs) > 1) {
      $form['tab'] = [
        '#type' => 'radios',
        '#title' => $this->t('Tab'),
        '#default_value' => $default_tab,
        '#options' => $tabs,
        '#required' => TRUE,
      ];
    }
    elseif (count($tabs) == 1) {
      $form['tab'] = [
        '#type' => 'value',
        '#value' => $default_tab,
      ];
    }

    $form['scope_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Scope'),
      '#default_value' => $scope_type,
      '#description' => $this->t('The nodes that should show this iframe page.'),
      '#options' => [
        'default' => $this->t('Default'),
        'bundle' => $this->t('Content types'),
        'specific' => $this->t('A list of IDs'),
      ],
      '#required' => TRUE,
    ];

    $options = [];
    $bundles = $this->bundleInfo->getBundleInfo('node');
    foreach ($bundles as $key => $bundle) {
      $options[$key] = $bundle['label'];
    }

    $form['scope_bundles'] = [
      '#type' => 'select',
      '#title' => $this->t('Content types'),
      '#description' => $this->t('Select content types that should show this iframe as a tab.'),
      '#options' => $options,
      '#multiple' => TRUE,
      '#size' => count($options),
      '#default_value' => ($scope_type == 'bundle') ? explode(',', $route_iframe_config_entity->get('scope')) : '',
      '#states' => [
        'visible' => [
          ':input[name = "scope_type"]' => ['value' => 'bundle'],
        ],
      ],
    ];

    $form['scope_ids'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ID list'),
      '#description' => $this->t('Provide a comma delimited list of content IDs that should show this iframe as a tab.'),
      '#default_value' => ($scope_type == 'specific') ? $route_iframe_config_entity->get('scope') : '',
      '#states' => [
        'visible' => [
          ':input[name = "scope_type"]' => ['value' => 'specific'],
        ],
      ],
    ];

    $base_url = $config->get('route_iframe_base_url');
    $form['config'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Configuration'),
      '#description' => $this->t('Enter the path and url parameters for the iframe (everything after the base url currently defined as @base_url). Tokens will be replaced in the configuration. The most commonly used token is [node:nid].',
        ['@base_url' => $base_url]),
      '#default_value' => $route_iframe_config_entity->get('config'),
      '#rows' => 1,
      '#cols' => 100,
    ];

    $token_tree = \Drupal::service('token.tree_builder')->buildRenderable(['node'],
      [
        'click_insert' => FALSE,
        'global_types' => FALSE,
        'show_nested' => FALSE,
        'recursion_limit' => 2,
      ]);

    $tokens = \Drupal::service('renderer')->render($token_tree);
    $form['browse_tokens'] = [
      '#type' => 'details',
      '#title' => $this->t('Browse Replacement Tokens'),
    ];

    $form['browse_tokens']['list'] = [
      '#type' => 'markup',
      '#markup' => $tokens,
    ];

    $form['iframe_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('iFrame Height'),
      '#maxlength' => 25,
      '#default_value' => $route_iframe_config_entity->get('iframe_height'),
      '#description' => $this->t("Controls the height of the iframe."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $values['scope'] = '';
    if ($values['scope_type'] == 'bundle') {
      $values['scope'] = implode(',', $values['scope_bundles']);
    }
    elseif ($values['scope_type'] == 'specific') {
      $values['scope'] = $values['scope_ids'];
    }
    unset($values['scope_bundles']);
    unset($values['scope_ids']);
    foreach ($values as $key => $value) {
      $entity->set($key, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $route_iframe_config_entity = $this->entity;
    $status = $route_iframe_config_entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Route Iframe Configuration.', [
          '%label' => $route_iframe_config_entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Route Iframe Configuration.', [
          '%label' => $route_iframe_config_entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($route_iframe_config_entity->toUrl('collection'));
  }

}
