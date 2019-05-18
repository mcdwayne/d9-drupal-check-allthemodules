<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 12/10/16
 * Time: 13:21
 */

namespace Drupal\elastic_search\Plugin\FieldMapper;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\elastic_search\Annotation\FieldMapper;
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\AnalyzerDslProcessor;
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\AnalyzerField;
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\BoostField;
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\DocValueField;
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\IncludeInAllField;
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\IndexField;
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\NullValueField;
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\StoreField;
use Drupal\elastic_search\Plugin\FieldMapperBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NodeEntityMapper
 * This is special type of entity mapper, which will be used if a specific
 * class is not implemented for the type you are using
 *
 * @FieldMapper(
 *   id = "text",
 *   label = @Translation("Text")
 * )
 */
class Text extends FieldMapperBase {

  use StringTranslationTrait;

  use AnalyzerField;
  use BoostField;
  use DocValueField;
  use IncludeInAllField;
  use IndexField;
  use NullValueField;
  use StoreField;

  use AnalyzerDslProcessor;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $analyzerStorage;

  /**
   * FieldMapperBase constructor.
   *
   * @param array                                      $configuration
   * @param string                                     $plugin_id
   * @param mixed                                      $plugin_definition
   * @param \Drupal\Core\Entity\EntityStorageInterface $analyzerStorage
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityStorageInterface $analyzerStorage) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->analyzerStorage = $analyzerStorage;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
                ->getStorage('elastic_analyzer')
    );
  }

  /**
   * @var array
   */
  protected static $types = [
    'text',
    'text_long',
    'text_with_summary',
    'uri',
    'link',
    'string',
    'string_long',
    'token',
    'uuid',
    'language',
    'path',
    'email',
  ];

  /**
   * @return array
   */
  public function getSupportedTypes() {
    return self::$types;
  }

  /**
   * @inheritdoc
   *
   * @return array
   */
  public function getFormFields(array $defaults, int $depth = 0): array {

    $form['analyzer_language_deriver'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Derive analyzer from languages'),
      '#decription'    => $this->t('If this index will be generated into multiple languages select this field to disable the manual analyzer selection and let Elastic Search automatically set analyzers based on the index language'),
      '#default_value' => $defaults['analyzer_language_deriver'] ?? TRUE,
    ];

    $form['analyzer'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Analyzer'),
      '#description'   => $this->t('Set the analyzer to use for this text type. If selected it will be used across all languages'),
      '#options'       => $this->getAnalyzerOptions($this->analyzerStorage),
      '#default_value' => $defaults['analyzer'] ??
                          $this->getAnalyzerFieldDefault(),
      '#disabled'      => $form['analyzer_language_deriver']['#default_value'],
    ];

    $form = array_merge($form,
                        $this->getBoostField($defaults[$this->getBoostFieldId()]
                                             ??
                                             $this->getBoostFieldDefault()));

    $form['eager_global_ordinals'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Eager Global Ordinates'),
      '#description'   => $this->t('Should global ordinals be loaded eagerly on refresh? Accepts true or false (default). Enabling this is a good idea on fields that are frequently used for terms aggregations.'),
      '#default_value' => $defaults['eager_global_ordinals'] ?? FALSE,
    ];
    $form['fielddata'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Field Data'),
      '#description'   => $this->t('Can the field use in-memory fielddata for sorting, aggregations, or scripting? Accepts true or false (default).'),
      '#default_value' => $defaults['fielddata'] ?? FALSE,
    ];
    //TODO - fielddata_frequency_filter
    if ($depth === 0) {
      $form = array_merge($form,
                          $this->getIncludeInAllField($defaults[$this->getIncludeInAllFieldId()]
                                                      ??
                                                      $this->getIncludeInAllFieldDefault()));
    }
    $form = array_merge($form,
                        $this->getIndexField($defaults[$this->getIndexFieldId()]
                                             ??$this->getIndexFieldDefault()));
    $form['index_options'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Index Options'),
      '#description'   => $this->t('What information should be stored in the index, for scoring purposes. Defaults to docs but can also be set to freqs to take term frequency into account when computing scores.'),
      '#options'       => ['docs' => 'docs', 'freqs' => 'freqs'],
      '#default_value' => $defaults['index_options'] ?? 'docs',
    ];
    $form['norms'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Norms'),
      '#description'   => $this->t('Whether field-length should be taken into account when scoring queries. Accepts true or false (default).'),
      '#default_value' => $defaults['norms'] ?? FALSE,
    ];
    //Commented out for now as this seems to cause issues with the elasticsearch mappings
    //Cannot set position_increment_gap on field [filename] without positions enabled
    //$form['position_increment_gap'] = [
    //    '#type'          => 'number',
    //    '#title'         => $this->t('Position Increment Gap'),
    //    '#description'   => $this->t('The number of fake term position which should be inserted between each element of an array of strings. Defaults to the position_increment_gap configured on the analyzer which defaults to 100. 100 was chosen because it prevents phrase queries with reasonably large slops (less than 100) from matching terms across field values.'),
    //    '#default_value' => $defaults['position_increment_gap'] ?? 100,
    //    '#min'           => 0,
    //    '#step'          => 1,
    //];
    $form = array_merge($form,
                        $this->getStoreField($defaults[$this->getStoreFieldId()]
                                             ?? $this->getStoreFieldDefault()));

    $form['search_analyzer_language_deriver'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Derive Search Analyzer from Languages'),
      '#decription'    => $this->t('If this index will be generated into multiple languages select this field to disable the manual analyzer selection and let Elastic Search automatically set analyzers based on the index language'),
      '#default_value' => $defaults['search_analyzer_language_deriver'] ?? TRUE,
    ];
    $saDefault = $defaults['search_analyzer']
                 ??
                 $defaults['analyzer']
                 ??
                 $this->getAnalyzerFieldDefault();

    $form['search_analyzer'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Search Analyzer'),
      '#description'   => $this->t('The analyzer that should be used at search time on analyzed fields. Defaults to the analyzer setting.'),
      '#options'       => $this->getAnalyzerOptions($this->analyzerStorage),
      '#default_value' => $saDefault,
    ];

    $form['search_quote_analyzer_language_deriver'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Derive Search Quote Analyzer from Languages'),
      '#decription'    => $this->t('If this index will be generated into multiple languages select this field to disable the manual analyzer selection and let Elastic Search automatically set analyzers based on the index language'),
      '#default_value' => $defaults['search_quote_analyzer_language_deriver'] ??
                          TRUE,
    ];
    $sqaDefault = $defaults['search_quote_analyzer']
                  ??
                  $defaults['search_analyzer']
                  ??
                  $defaults['analyzer']
                  ??
                  $this->getAnalyzerFieldDefault();

    $form['search_quote_analyzer'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Search Quote Analyzer'),
      '#description'   => $this->t('The analyzer that should be used at search time when a phrase is encountered. Defaults to the search_analyzer setting. '),
      '#options'       => $this->getAnalyzerOptions($this->analyzerStorage),
      '#default_value' => $sqaDefault,
    ];

    $form['similarity'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Similarity'),
      '#description'   => $this->t('Which scoring algorithm or similarity should be used. Defaults to classic, which uses TF/IDF.'),
      '#options'       => ['classic' => 'classic', 'BM25' => 'BM25'],
      '#default_value' => $defaults['similarity'] ?? 'classic',
    ];
    $rawOptions = [
      'no',
      'yes',
      'with_positions',
      'with_offsets',
      'with_position_offsets',
    ];
    $termVectorOptions = array_combine($rawOptions, $rawOptions);
    $form['term_vector'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Term Vector'),
      '#description'   => $this->t('Whether term vectors should be stored for an analyzed field. Defaults to no.'),
      '#options'       => $termVectorOptions,
      '#default_value' => $defaults['term_vector'] ?? 'no',
    ];

    return $form;
  }

  /**
   * @return bool
   */
  public function supportsFields(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDslFromData(array $data): array {
    $data = parent::getDslFromData($data);
    $data = $this->buildAnalyzerDsl($data);
    return $data;
  }

}