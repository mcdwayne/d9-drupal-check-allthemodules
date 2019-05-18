<?php


namespace Drupal\healthcheck\Plugin\healthcheck;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\healthcheck\Finding\Finding;
use Drupal\healthcheck\Finding\Report;
use Drupal\healthcheck\Plugin\HealthcheckPluginBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\TermStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Healthcheck(
 *  id = "taxonomy",
 *  label = @Translation("Taxonomy"),
 *  description = "Checks tags and vocabularies for usage.",
 *  tags = {
 *   "content",
 *  }
 * )
 */
class Taxonomy extends HealthcheckPluginBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeMgr;

  /**
   * Entity Query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactoryInterface
   */
  protected $entityQuery;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * ContentTypes constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              $finding_service,
                              $entity_type_mgr,
                              $entity_query,
                              $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $finding_service);
    $this->entityTypeMgr = $entity_type_mgr;
    $this->entityQuery = $entity_query;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static (
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('healthcheck.finding'),
      $container->get('entity_type.manager'),
      $container->get('entity.query'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFindings() {
    $findings = [];

    // If the taxonomy module is not enabled, return a Not Performed.
    if (!$this->moduleHandler->moduleExists('taxonomy')) {
      $finding = $this->notPerformed('taxonomy');

      $finding->setLabel($this->t('Taxonomy not enabled'));

      $finding->setMessage($this->t('No checks against vocabularies and tags could be run.'));

      return [$finding];
    }

    // Load all vocabularies.
    $vocabs = Vocabulary::loadMultiple();

    /** @var \Drupal\taxonomy\VocabularyInterface $vocab */
    foreach ($vocabs as $vocab_id => $vocab) {

      /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
      $term_storage = $this->entityTypeMgr->getStorage('taxonomy_term');

      $terms = $term_storage->loadTree($vocab_id);

      // Count the number of tags.
      $count = count($terms);

      $key = 'count.' . $vocab_id;

      if ($count > 0) {
        $finding = $this->noActionRequired($key, [
          'vocab_id' => $vocab_id,
          'count' => $count,
        ]);

        $finding->setLabel($this->t(
          'Vocabulary :vocab_id in use', [
            ':vocab_id' => $vocab_id,
            ':count' => $count,
          ]
        ));

        $finding->setMessage($this->t(
          'The vocabulary :vocab_id has :count tags', [
            ':vocab_id' => $vocab_id,
            ':count' => $count,
          ]
        ));

        $findings[] = $finding;
      }
      else {
        $finding = $this->needsReview($key, [
          'vocab_id' => $vocab_id,
        ]);

        $finding->setLabel($this->t(
          'Unused vocabulary :vocab_id', [
            ':vocab_id' => $vocab_id,
          ]
        ));

        $finding->setMessage($this->t(
          'Vocabulary :vocab_id has zero tags, please consider deleting it.', [
            ':vocab_id' => $vocab_id,
          ]
        ));

        $findings[] = $finding;
      }
    }

    return $findings;
  }

}
