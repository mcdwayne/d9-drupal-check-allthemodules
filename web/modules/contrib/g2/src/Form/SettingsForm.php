<?php

/**
 * @file
 * Contains the G2 settings form.
 */

namespace Drupal\g2\Form;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\g2\G2;
use Drupal\views\Entity\View;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;


/**
 * Class SettingsForm contains the G2 configuration form.
 *
 * @TODO Refactor like \Drupal\config_inspector\Form\ConfigInspectorItemForm.
 * @TODO Relate service.alphabar.contents configuration with routes like g2.initial.
 */
class SettingsForm extends ConfigFormBase {
  /**
   * The schema information for the module configuration.
   *
   * @var array
   */
  protected $configSchema;

  /**
   * The query.factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The router.builder service.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity.query service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param array $config_schema
   *   The schema array for the configuration data.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $router_builder
   *   The router.builder service.
   */
  public function __construct(QueryFactory $entity_query, ConfigFactoryInterface $config_factory, array $config_schema,
    RouteBuilderInterface $router_builder) {
    parent::__construct($config_factory);
    $this->entityQuery = $entity_query;
    $this->configSchema = $config_schema;
    $this->routerBuilder = $router_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /* @var \Drupal\Core\Entity\Query\QueryFactory $entity_query */
    $entity_query = $container->get('entity.query');

    /* @var \Drupal\Core\Config\ConfigFactoryInterface  $config_factory */
    $config_factory = $container->get('config.factory');

    /* @var \Drupal\Core\Routing\RouteBuilderInterface $router_builder */
    $router_builder = $container->get('router.builder');

    /* @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config */
    $typed_config = $container->get('config.typed');

    return new static($entity_query, $config_factory, $typed_config->getDefinition(G2::CONFIG_NAME), $router_builder);
  }

  /**
   * Helper for form builders : prepare top-level details.
   *
   * @param array $form
   *   The base form array.
   * @param array $schema
   *   The configuration schema from which to take the list of details.
   * @param string $section
   *   The configuration section in which to build the list of details.
   *
   * @return array
   *   The extended array.
   */
  protected static function prepareTopLevelDetails(array $form, array $schema, $section) {
    foreach ($schema as $top => $top_schema) {
      $form[$section][$top] = [
        '#type' => 'details',
        '#title' => $top_schema['label'],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [G2::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $match = $this->getRouteMatch();
    $section = $match->getParameter('section');
    $result = 'g2_settings-' . $section;
    return $result;
  }

  /**
   * Build the blocks configuration form.
   *
   * @param array $form
   *   The form array.
   * @param array $config
   *   The configuration for which to build a form.
   * @param array $schema
   *   The schema of the configuration for which to build a form.
   *
   * @return array
   *   The form array.
   */
  public function buildBlockForm(array $form, array $config, array $schema) {
    $section = 'block';
    $form = $this->prepareTopLevelDetails($form, $schema, $section);
    $service_config = $this->config(G2::CONFIG_NAME)->get('service');

    $element = &$form[$section]['alphabar'];
    $element['row_length'] = [
      '#type' => 'number',
      '#title' => $schema['alphabar']['mapping']['row_length']['label'],
      '#default_value' => $config['alphabar']['row_length'],
    ];

    $element = &$form[$section]['latest'];
    $element['count'] = [
      '#type' => 'number',
      '#title' => $schema['latest']['mapping']['count']['label'],
      '#default_value' => $config['latest']['count'],
      '#max' => $service_config['latest']['max_count'],
      '#min' => 1,
    ];

    $element = &$form[$section]['random'];
    $element['show_terms'] = [
      '#type' => 'checkbox',
      '#title' => $schema['random']['mapping']['show_terms']['label'],
      '#default_value' => $config['random']['show_terms'],
    ];
    $element['store'] = [
      '#type' => 'checkbox',
      '#title' => $schema['random']['mapping']['store']['label'],
      '#default_value' => $config['random']['store'],
    ];

    $element = &$form[$section]['top'];
    $element['count'] = [
      '#type' => 'number',
      '#title' => $schema['top']['mapping']['count']['label'],
      '#default_value' => $config['top']['count'],
      '#max' => $service_config['top']['max_count'],
      '#min' => 1,
    ];

    $element = &$form[$section]['wotd'];
    $element['auto_change'] = [
      '#type' => 'checkbox',
      '#title' => $schema['wotd']['mapping']['auto_change']['label'],
      '#default_value' => $config['wotd']['auto_change'],
    ];
    $element['body_size'] = [
      '#type' => 'number',
      '#title' => $schema['wotd']['mapping']['body_size']['label'],
      '#default_value' => $config['wotd']['body_size'],
    ];
    $element['links'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $schema['wotd']['mapping']['links']['label'],
    ];
    foreach ($config['wotd']['links'] as $name => $value) {
      $element['links'][$name] = [
        '#type' => 'checkbox',
        '#title' => $schema['wotd']['mapping']['links']['mapping'][$name]['label'],
        '#default_value' => $value,
      ];
    }

    return $form;
  }

  /**
   * Build the controllers configuration form.
   *
   * @param array $form
   *   The form array.
   * @param array $config
   *   The configuration for which to build a form.
   * @param array $schema
   *   The schema of the configuration for which to build a form.
   *
   * @return array
   *   The form array.
   *
   * @TODO provide an auto-complete for routes instead of using a plain string.
   * @TODO provide an auto-complete for node ids instead of using a plain number.
   */
  public function buildControllerForm(array $form, array $config, array $schema) {
    $section = 'controller';
    $form = $this->prepareTopLevelDetails($form, $schema, $section);
    drupal_set_message(t('Be aware that saving this configuration will rebuild the router.'));
    $element = &$form['controller']['main'];
    $element['nid'] = [
      '#type' => 'number',
      '#title' => $schema['main']['mapping']['nid']['label'],
      '#default_value' => $config['main']['nid']
    ];

    foreach (['main', 'entries', 'initial', 'adder', 'homonyms'] as $top) {
      $element = &$form['controller'][$top];
      $element['route'] = [
        '#type' => 'textfield',
        '#title' => $schema[$top]['mapping']['route']['label'],
        '#default_value' => $config[$top]['route']
      ];
    }

    $element = &$form['controller']['homonyms'];
    $element['redirect_on_single_match'] = [
      '#type' => 'checkbox',
      '#title' => $schema['homonyms']['mapping']['redirect_on_single_match']['label'],
      '#default_value' => $config['homonyms']['redirect_on_single_match']
    ];
    $element['nid'] = [
      '#type' => 'number',
      '#title' => $schema['homonyms']['mapping']['nid']['label'],
      '#default_value' => $config['homonyms']['nid']
    ];
    $redirects = [
      Response::HTTP_MOVED_PERMANENTLY,
      Response::HTTP_FOUND,
      Response::HTTP_TEMPORARY_REDIRECT,
      Response::HTTP_PERMANENTLY_REDIRECT,
    ];
    $options = [];
    foreach ($redirects as $redirect) {
      $options[$redirect] = t('@status: @text', [
        '@status' => $redirect,
        '@text' => Response::$statusTexts[$redirect],
      ]);
    }
    $element['redirect_status'] = [
      '#type' => 'select',
      '#title' => $schema['homonyms']['mapping']['redirect_status']['label'],
      '#options' => $options,
      '#default_value' => $config['homonyms']['redirect_status']
    ];

    $view_ids = $this->entityQuery->get('view')
      ->condition('tag', 'G2')
      ->execute();
    $views = View::loadMultiple($view_ids);
    $options = ['' => t('-- Use plain node list --')];
    /* @var \Drupal\views\ViewEntityInterface $view */
    foreach ($views as $vid => $view) {
      $options[$vid] = $view->label();
    }
    $element['vid'] = [
      '#type' => 'select',
      '#title' => $schema['homonyms']['mapping']['vid']['label'],
      '#options' => $options,
      '#default_value' => $config['homonyms']['vid']
    ];

    $element = &$form['controller']['wotd'];
    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $schema['wotd']['mapping']['title']['label'],
      '#default_value' => $config['wotd']['title']
    ];
    $element['description'] = [
      '#type' => 'textfield',
      '#title' => $schema['wotd']['mapping']['description']['label'],
      '#default_value' => $config['wotd']['description']
    ];
    $element['feed_author'] = [
      '#type' => 'checkbox',
      '#title' => $schema['wotd']['mapping']['feed_author']['label'],
      '#default_value' => $config['wotd']['feed_author']
    ];

    return $form;
  }

  /**
   * Build the formatting configuration form.
   *
   * @param array $form
   *   The form array.
   * @param array $config
   *   The configuration for which to build a form.
   * @param array $schema
   *   The schema of the configuration for which to build a form.
   *
   * @return array
   *   The form array.
   */
  public function buildFormattingForm(array $form, array $config, array $schema) {
    $element = 'hidden_extra_title';
    $form['formatting'][$element] = [
      '#type' => 'checkbox',
      '#title' => $schema[$element]['label'],
      '#default_value' => $config[$element],
    ];

    $element = 'hide_free_tagging';
    $form['formatting'][$element] = [
      '#type' => 'checkbox',
      '#title' => $schema[$element]['label'],
      '#default_value' => $config[$element],
    ];

    $element = 'tooltips_level';
    $form['formatting'][$element] = [
      '#type' => 'select',
      '#title' => $schema[$element]['label'],
      '#options' => [
        0 => t('None'),
        1 => t('Titles'),
        2 => t('Teasers'),
      ],
      '#default_value' => $config[$element],
    ];

    $element = 'title';
    $form['formatting'][$element] = [
      '#type' => 'textfield',
      '#title' => $schema[$element]['label'],
      '#default_value' => $config[$element],
    ];

    return $form;
  }

  /**
   * Build the RPC configuration form.
   *
   * @param array $form
   *   The form array.
   * @param array $config
   *   The configuration for which to build a form.
   * @param array $schema
   *   The schema of the configuration for which to build a form.
   *
   * @return array
   *   The form array.
   */
  public function buildRpcForm(array $form, array $config, array $schema) {
    $form['rpc']['client'] = [
      '#type' => 'details',
      '#title' => $schema['client']['label'],
    ];
    $form['rpc']['client']['remote'] = [
      '#type' => 'textfield',
      '#title' => $schema['client']['mapping']['remote']['label'],
      '#default_value' => $config['client']['remote'],
    ];

    $form['rpc']['server'] = [
      '#type' => 'details',
      '#title' => $schema['server']['label'],
    ];
    $form['rpc']['server']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $schema['server']['mapping']['enabled']['label'],
      '#default_value' => $config['server']['enabled'],
    ];
    $form['rpc']['server']['throttle'] = [
      '#type' => 'number',
      '#title' => $schema['server']['mapping']['throttle']['label'],
      '#default_value' => $config['server']['throttle'],
      '#max' => 1.0,
      '#min' => 0.0,
      '#step' => 0.1,
    ];

    $form['rpc']['local'] = [
      '#type' => 'textfield',
      '#title' => $schema['local']['label'],
      '#default_value' => $config['local'],
    ];
    return $form;
  }

  /**
   * Build the services configuration form.
   *
   * @param array $form
   *   The form array.
   * @param array $config
   *   The configuration for which to build a form.
   * @param array $schema
   *   The schema of the configuration for which to build a form.
   *
   * @return array
   *   The form array.
   */
  public function buildServiceForm(array $form, array $config, array $schema) {
    $section = 'service';
    $form = $this->prepareTopLevelDetails($form, $schema, $section);

    $element = &$form[$section]['alphabar'];
    $element['contents'] = [
      '#type' => 'textfield',
      '#title' => $schema['alphabar']['mapping']['contents']['label'],
      '#default_value' => $config['alphabar']['contents'],
    ];

    foreach (['latest', 'top'] as $service) {
      $element = &$form[$section][$service];
      $element['max_count'] = [
        '#type' => 'number',
        '#title' => $schema[$service]['mapping']['max_count']['label'],
        '#default_value' => $config[$service]['max_count'],
      ];
    }
    return $form;
  }

  /**
   * Build the settings form.
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The configuration for which to build a form.
   * @param RouteMatchInterface|NULL $route
   *   The current_route_match service.
   *
   * @return array
   *   The build form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, RouteMatchInterface $route = NULL) {
    $section = $route->getParameter('section');
    $form['#tree'] = TRUE;
    $form['section'] = [
      '#type' => 'value',
      '#value' => $section,
    ];
    $builder = 'build' . ucfirst($section) . 'Form';
    if (method_exists($this, $builder)) {
      $config = $this->config(G2::CONFIG_NAME)->get($section);
      $schema = $this->configSchema['mapping'][$section]['mapping'];
      $form = $this->{$builder}($form, $config, $schema);
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Additional submit handler for the block configuration form.
   */
  public function submitControllerForm() {
    // @TODO Really necessary ? We change selected routes, not modifying them.
    $this->routerBuilder->rebuild();
    drupal_set_message($this->t('The router has been rebuilt.'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $section = $values['section'];
    $values = $values[$section];
    $config = $this->config(G2::CONFIG_NAME);
    $config->set($section, $values)->save();

    $handler = 'submit' . ucfirst($section) . 'Form';
    if (method_exists($this, $handler)) {
      $this->{$handler}();
    }

    drupal_set_message($this->t('The configuration options have been saved.'));
  }

}
