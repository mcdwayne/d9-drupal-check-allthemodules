<?php

namespace Drupal\fac\Plugin\Search;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\fac\FacConfigInterface;
use Drupal\fac\SearchBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a basic node title search plugin.
 *
 * @Search(
 *   id = "BasicTitleSearch",
 *   name = @Translation("Basic node title search plugin"),
 * )
 */
class BasicTitleSearch extends SearchBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * BasicTitleSearch constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, TranslationInterface $string_translation, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
    $this->stringTranslation = $string_translation;
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
      $container->get('database'),
      $container->get('string_translation'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Gets the configuration form for the search plugin.
   *
   * @param array $plugin_config
   *   The plugin config array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The configuration form for the search plugin.
   */
  public function getConfigForm(array $plugin_config, FormStateInterface $form_state) {
    $bundle_options = [];
    try {
      $bundles = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    }
    catch (InvalidPluginDefinitionException $e) {
      $bundles = [];
    }
    foreach ($bundles as $key => $bundle) {
      $bundle_options[$bundle->id()] = $bundle->label();
    }

    $form['bundle_filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter by bundle'),
      '#options' => $bundle_options,
      '#multiple' => TRUE,
      '#default_value' => isset($plugin_config['bundle_filter']) ? $plugin_config['bundle_filter'] : [],
      '#description' => $this->t('Select one or more bundles to include in the results. Select no bundles to disable the bundle filter.'),
    ];

    $form['language_filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Filter by language'),
      '#default_value' => isset($plugin_config['language_filter']) ? $plugin_config['language_filter'] : '',
      '#description' => $this->t('Check this option if you want the results to be filtered by language'),
    ];

    $form['langcode_includes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('No specific language'),
      '#options' => [
        LanguageInterface::LANGCODE_NOT_APPLICABLE => $this->t('Include "language not applicable"'),
        LanguageInterface::LANGCODE_NOT_SPECIFIED => $this->t('Include "Language not specified"'),
      ],
      '#default_value' => isset($plugin_config['langcode_includes']) ? $plugin_config['langcode_includes'] : [],
      '#states' => [
        'invisible' => [
          ':input[name="plugin[config][language_filter]"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Return the results for the given key.
   *
   * @param \Drupal\fac\FacConfigInterface $fac_config
   *   The FacConfig object to get the search results for.
   * @param string $langcode
   *   The language code.
   * @param string $key
   *   The query string to get results for.
   *
   * @return array
   *   The result entity ids for the given key.
   */
  public function getResults(FacConfigInterface $fac_config, $langcode, $key) {
    $results = [];

    $plugin_config = $fac_config->getSearchPluginConfig();

    $query = $this->database->select('node', 'n');
    $query->fields('n', ['nid'])
      ->condition('fd.title', '%' . $this->database->escapeLike($key) . '%', 'LIKE')
      ->condition('fd.status', NodeInterface::PUBLISHED, '=');

    if ($plugin_config['language_filter']) {
      $langcodes = array_filter($plugin_config['langcode_includes']);
      $langcodes[] = $langcode;
      $query->condition('n.langcode', $langcodes, 'IN');
    }

    if ($plugin_config['bundle_filter']) {
      $query->condition('n.type', $plugin_config['bundle_filter'], 'IN');
    }

    $query->orderBy('fd.title', 'asc')
      ->addTag('node_access')
      ->range(0, $fac_config->getNumberOfResults());
    $query->join('node_field_data', 'fd', 'n.vid = fd.vid');

    $entity_ids = $query->execute()->fetchCol();

    foreach ($entity_ids as $entity_id) {
      $results[] = [
        'entity_type' => 'node',
        'entity_id' => $entity_id,
      ];
    }

    return $results;
  }

}
