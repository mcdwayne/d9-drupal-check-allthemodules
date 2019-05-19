<?php

namespace Drupal\views_taxonomy_name_default_argument\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Taxonomy name default argument.
 *
 * @ViewsArgumentDefault(
 *   id = "views_taxonomy_name",
 *   title = @Translation("Taxonomy term name from query parameter")
 * )
 */
class TaxonomyName extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new TaxonomyName instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // Convert legacy vids option to machine name vocabularies.
    if (!empty($this->options['vids'])) {
      $vocabularies = taxonomy_vocabulary_get_names();
      foreach ($this->options['vids'] as $vid) {
        if (isset($vocabularies[$vid], $vocabularies[$vid]->machine_name)) {
          $this->options['vocabularies'][$vocabularies[$vid]->machine_name] = $vocabularies[$vid]->machine_name;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['query_param'] = ['default' => ''];
    $options['fallback'] = ['default' => ''];
    $options['limit'] = ['default' => FALSE];
    $options['vids'] = ['default' => []];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['query_param'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Query parameter'),
      '#description' => $this->t('The query parameter to use.'),
      '#default_value' => $this->options['query_param'],
    ];

    $form['fallback'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fallback value'),
      '#description' => $this->t('The fallback value to use when the above query parameter is not present.'),
      '#default_value' => $this->options['fallback'],
    ];

    $form['limit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Limit terms by vocabulary'),
      '#default_value' => $this->options['limit'],
    ];

    $options = [];
    $voc_storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    $vocabularies = $voc_storage->loadMultiple();
    foreach ($vocabularies as $voc) {
      $options[$voc->id()] = $voc->label();
    }

    $form['vids'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Vocabularies'),
      '#options' => $options,
      '#default_value' => $this->options['vids'],
      '#states' => [
        'visible' => [
          ':input[name="options[argument_default][views_taxonomy_name][limit]"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state, &$options = []) {
    // Remove unselected items.
    $options['vids'] = array_filter($options['vids']);
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    $current_request = $this->view->getRequest();

    if ($current_request->query->has($this->options['query_param'])) {
      $t_names = $current_request->query->get($this->options['query_param']);
    }
    else {
      // Otherwise, use the fixed fallback value.
      $t_names = $this->options['fallback'];
    }

    if (!is_array($t_names)) {
      $t_names = [$t_names];
    }

    /** @var \Drupal\taxonomy\TermInterface[] $terms */
    $terms = [];
    foreach ($t_names as $t_name) {
      $terms += taxonomy_term_load_multiple_by_name($t_name);
    }

    $tids = [];
    foreach ($terms as $term) {
      // Filter by vocabulary if exists limitation.
      if ((!empty($this->options['limit']) && !empty($this->options['vids'][$term->bundle()])) ||
        empty($this->options['limit'])) {
        $tids[] = $term->id();
      }
    }
    return implode(',', $tids);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    $voc_storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    foreach ($voc_storage->loadMultiple(array_keys($this->options['vids'])) as $vocabulary) {
      $dependencies[$vocabulary->getConfigDependencyKey()][] = $vocabulary->getConfigDependencyName();
    }

    return $dependencies;
  }

}
