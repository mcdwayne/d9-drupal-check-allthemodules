<?php

namespace Drupal\views_polygon_search\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormState;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Views filters handler plugins for geo field.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsFilter("polygon_filter")
 */
class PolygonFilter extends FilterPluginBase {
  protected $alwaysMultiple = TRUE;
  protected $exposedStylePlugins = NULL;

  /**
   * Constructs a PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param mixed $style_plugins
   *   The style plugins.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $style_plugins) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->definition = $plugin_definition + $configuration;
    $this->exposedStylePlugins = $style_plugins;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $style_plugins = $container->get('plugin.manager.views_polygon_search_plugin');
    return new static($configuration, $plugin_id, $plugin_definition, $style_plugins);
  }

  /**
   * Information about options.
   *
   * @return array
   *   Returns the options of this handler.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['filter_plugin_style'] = [
      'contains' => [
        'plugin_type' => ['default' => 'none'],
        'options' => ['default' => ''],
      ],
    ];
    return $options;
  }

  /**
   * Information about operators.
   *
   * @return array
   *   Returns available operators of this handler.
   */
  public function operatorOptions() {
    return [
      '=' => $this->t('Is equal to'),
      'IN:AND' => $this->t('AND (IN)'),
      'IN:OR' => $this->t('OR (IN)'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    if (!empty($this->options['exposed'])) {
      $plugin_type = $this->options['filter_plugin_style']['plugin_type'];
      if (($values = $form_state->getValues()) && isset($values['options']['filter_plugin_style']['plugin_type'])) {
        $plugin_type = $values['options']['filter_plugin_style']['plugin_type'];
      }
      $form['filter_plugin_style'] = array(
        '#theme_wrappers' => array('container'),
        '#weight' => -1000,
      );
      $types = array();
      foreach ($this->exposedStylePlugins->getDefinitions() as $definition) {
        $types[$definition['id']] = $definition['label'];
      }
      $form['filter_plugin_style']['plugin_type'] = [
        '#title' => $this->t('Widget style type'),
        '#type' => 'select',
        '#options' => $types,
        '#default_value' => $plugin_type,
        '#empty_option' => $this->t('- None -'),
        '#ajax' => [
          'callback' => 'Drupal\views_polygon_search\Plugin\views\filter\PolygonFilter::getPluginForm',
          'wrapper' => 'filter-plugin-style-options',
        ],
      ];
      $form['filter_plugin_style']['options'] = [];
      if (!empty($plugin_type) && isset($types[$plugin_type])) {
        $plugin = $this->exposedStylePlugins->createInstance($plugin_type);
        $options = [];
        if ($plugin_type == $this->options['filter_plugin_style']['plugin_type']) {
          $options = $this->options['filter_plugin_style']['options'];
        }
        $plugin->formOptions($form['filter_plugin_style']['options'], $form_state, $options);
      }
      $form['filter_plugin_style']['options'] += [
        '#type' => 'fieldset',
        '#title' => $this->t('Widget options'),
        '#attributes' => array(
          'id' => 'filter-plugin-style-options',
        ),
      ];
    }
    if (isset($form['value'])) {
      $form['value']['#attributes'] = ['class' => ['views-polygon-search']];
      unset($form['value']['#attached']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);
    if (($values = $form_state->getValues()) && isset($values['options']['filter_plugin_style']['plugin_type'])) {
      $plugin_type = $values['options']['filter_plugin_style']['plugin_type'];
      $types = $this->exposedStylePlugins->getDefinitions();
      if (isset($types[$plugin_type])) {
        $plugin = $this->exposedStylePlugins->createInstance($plugin_type);
        $plugin->validateOptionsForm($form['filter_plugin_style']['options'], $form_state, $this);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    if (($values = $form_state->getValues()) && isset($values['options']['filter_plugin_style']['plugin_type'])) {
      $plugin_type = $values['options']['filter_plugin_style']['plugin_type'];
      $types = $this->exposedStylePlugins->getDefinitions();
      if (isset($types[$plugin_type])) {
        $plugin = $this->exposedStylePlugins->createInstance($plugin_type);
        $plugin->submitOptionsForm($form['filter_plugin_style']['options'], $form_state, $this);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $field = "{$this->tableAlias}.{$this->realField}_value";
    $query = $this->query;
    $value = is_array($this->value) ? reset($this->value) : $this->value;

    $polygons = [];
    foreach (explode('+', $value) as $polygon) {
      $geom = \geoPHP::load($polygon, 'wkt');
      if ($geom) {
        $polygons[] = $polygon;
      }
    }

    if (!empty($polygons)) {
      if ($this->operator == '=') {
        $string = 'ST_Contains(ST_GeomFromText(:polygon), ST_GeomFromText(' . $field . '))';
        $params = [':polygon' => reset($polygons)];
      }
      else {
        $string = $params = [];
        list(, $operator) = explode(':', $this->operator);
        $i = 0;
        foreach ($polygons as $polygon) {
          $string[] = "ST_Contains(ST_GeomFromText(:polygon_{$i}), ST_GeomFromText({$field}))";
          $params[":polygon_{$i}"] = $polygon;
          $i++;
        }
        $string = imlplode(" {$operator} ", $string);
      }
      $query->addWhereExpression($this->options['group'], $string, $params);
    }
  }

  /**
   * Provide a simple text area for inputting wkt value.
   */
  protected function valueForm(&$form, FormState $form_state) {
    $form['value'] = [
      '#type' => 'textarea',
      '#default_value' => $this->value,
    ];
    $plugin_type = $this->options['filter_plugin_style']['plugin_type'];
    $types = $this->exposedStylePlugins->getDefinitions();
    if (!empty($plugin_type) && $types[$plugin_type]) {
      $plugin = $this->exposedStylePlugins->createInstance($plugin_type);
      $plugin->valueForm($form, $form_state, $this);
    }
  }

}
