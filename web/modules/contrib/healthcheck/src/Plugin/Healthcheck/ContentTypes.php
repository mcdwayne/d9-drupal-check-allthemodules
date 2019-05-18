<?php


namespace Drupal\healthcheck\Plugin\healthcheck;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\healthcheck\Finding\Finding;
use Drupal\healthcheck\Finding\Report;
use Drupal\healthcheck\Plugin\HealthcheckPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Healthcheck(
 *  id = "content_types",
 *  label = @Translation("Content Types"),
 *  description = "Checks content types for usage.",
 *  tags = {
 *   "content",
 *  }
 * )
 */
class ContentTypes extends HealthcheckPluginBase implements ContainerFactoryPluginInterface {

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
   * ContentTypes constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $finding_service, $entity_type_mgr, $entity_query) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $finding_service);
    $this->entityTypeMgr = $entity_type_mgr;
    $this->entityQuery = $entity_query;
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
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFindings() {
    $findings = [];

    // Get a list of all node types.
    $node_types = $this->entityTypeMgr->getStorage('node_type')
      ->loadMultiple();

    // Go through each.
    foreach ($node_types as $bundle => $node_type) {
      // Build an entity query for the node type.
      $query = $this->entityQuery->get('node', $bundle);

      // Count the number of nodes.
      $count = $query->count()->execute();

      $key = $this->getPluginId() . '.count.' . $bundle;

      $data = [
        'bundle' => $bundle,
        'count' => $count,
      ];

      $placeholders = [
        ':bundle' => $bundle,
        ':count' => $count,
      ];

      if ($count > 0) {
        $finding = $this->noActionRequired($key, $data);

        $finding->setLabel($this->t(
          'Content type :bundle in use',
          $placeholders
        ));

        $finding->setMessage($this->t(
          'Content type :bundle has :count nodes.',
          $placeholders
        ));

        $findings[] = $finding;
      }
      else {
        $finding = $this->needsReview($key, $data);

        $finding->setLabel($this->t(
          'Unused Content type :bundle',
          $placeholders
        ));

        $finding->setMessage($this->t(
          'Content type :bundle is not used, please consider deleting it.',
          $placeholders
        ));

        $findings[] = $finding;
      }
    }

    return $findings;
  }

}
