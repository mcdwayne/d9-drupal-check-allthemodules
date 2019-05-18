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
 *   id = "keyword",
 *   label = @Translation("keyword")
 * )
 */
class Keyword extends FieldMapperBase {

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
   * @return array
   */
  public function getSupportedTypes() {
    return [
      'text',
      'uri',
      'link',
      'string',
      'token',
      'uuid',
      'language',
      'path',
      'email',
    ];
  }

  /**
   * @param array $defaults
   *
   * @return array
   */
  public function getFormFields(array $defaults, int $depth = 0): array {
    $form = array_merge($this->getBoostField($defaults[$this->getBoostFieldId()]
                                             ?? $this->getBoostFieldDefault()),
                        $this->getDocValueField($defaults[$this->getDocValueFieldId()]
                                                ??
                                                $this->getDocValueFieldDefault()));
    $form['eager_global_ordinals'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Eager Global Ordinates'),
      '#description' => $this->t('Should global ordinals be loaded eagerly on refresh? Accepts true or false (default). Enabling this is a good idea on fields that are frequently used for terms aggregations.'),
      '#default'     => (bool) ($defaults['eager_global_ordinals'] ?? FALSE),
    ];

    $imax = 2147483647; // This is the 32bit max int count, but on a 64bit system using the constant PHP_INT_MAX will be 9223372036854775808
    $form['ignore_above'] = [
      '#type'          => 'numeric',
      '#title'         => $this->t('Ignore Above'),
      '#description'   => $this->t('Do not index any string longer than this value. Defaults to 2147483647 so that all values would be accepted.'),
      '#min'           => 0,
      '#max'           => $imax,
      '#default_value' => $defaults['ignore_above'] ?? $imax,
    ];
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
    $form = array_merge($form,
                        $this->getNullValueField($defaults[$this->getNullValueFieldId()]
                                                 ??
                                                 $this->getNullValueFieldDefault()),
                        $this->getStoreField($defaults[$this->getStoreFieldId()]
                                             ??$this->getStoreFieldDefault()));

    $form['similarity'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Similarity'),
      '#description'   => $this->t('Which scoring algorithm or similarity should be used. Defaults to classic, which uses TF/IDF.'),
      '#options'       => ['classic' => 'classic', 'BM25' => 'BM25'],
      '#default_value' => $defaults['similarity'] ?? 'classic',
    ];

    //TODO - NORMALIZER
    return $form;
  }

  /**
   * @return bool
   */
  public function supportsFields(): bool {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function getDslFromData(array $data): array {
    $data = parent::getDslFromData($data);
    $data = $this->buildAnalyzerDsl($data);
    return $data;
  }

}