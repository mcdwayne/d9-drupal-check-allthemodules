<?php

namespace Drupal\yasm\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\yasm\Services\DatatablesInterface;
use Drupal\yasm\Services\EntitiesStatisticsInterface;
use Drupal\yasm\Utility\YasmUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * YASM Statistics site entities controller.
 */
class Entities extends ControllerBase {

  /**
   * The datatables service.
   *
   * @var \Drupal\yasm\Services\DatatablesInterface
   */
  protected $datatables;

  /**
   * The entities statitistics service.
   *
   * @var \Drupal\yasm\Services\EntitiesStatisticsInterface
   */
  protected $entitiesStatistics;

  /**
   * Site content page output.
   */
  public function siteContent() {
    $site_entities = $this->entitiesStatistics->getEntitiesInfo();

    return $this->buildContent($site_entities);
  }

  /**
   * Build page content.
   */
  private function buildContent($entities) {
    if (!empty($entities)) {
      // Collect data rows.
      $entities_rows = $bundles_rows = [];
      $entities_total = $bundles_total = 0;
      foreach ($entities as $entity) {
        $count_bundles = isset($entity['bundles']) ? count($entity['bundles']) : 0;
        $entities_rows[] = [
          $entity['label'] . ' (' . $entity['id'] . ')',
          $count_bundles,
          $entity['count'],
        ];
        // Count totals.
        $entities_total += $entity['count'];
        $bundles_total += $count_bundles;

        if ($count_bundles > 0) {
          foreach ($entity['bundles'] as $bundle) {
            $bundles_rows[] = [
              $entity['label'] . ' (' . $entity['id'] . ')',
              $bundle['label'] . ' (' . $bundle['id'] . ')',
              $bundle['count'],
            ];
          }
        }
      }

      // Total rows.
      $entities_rows[] = [
        'data' => [$this->t('Total'), $bundles_total, $entities_total],
        'class' => ['total-row'],
      ];
      $bundles_rows[] = [
        'data' => [$this->t('Total'), '', $entities_total],
        'class' => ['total-row'],
      ];

      // Entities table.
      $entities_table = YasmUtility::table([
        $this->t('Entity'),
        $this->t('Count entity types'),
        $this->t('Count'),
      ], $entities_rows);

      // Bundles table.
      $bundles_table = YasmUtility::table([
        $this->t('Entity'),
        $this->t('Entity type'),
        $this->t('Count'),
      ], $bundles_rows);

      // Render content output.
      $build = [];
      $build[] = YasmUtility::title($this->t('Entities'), 'fas fa-puzzle-piece');
      $build[] = $entities_table;

      $build[] = YasmUtility::title($this->t('Entity types'), 'fas fa-puzzle-piece');
      $build[] = $bundles_table;

      $build[] = [
        '#attached' => [
          'library' => ['yasm/global', 'yasm/fontawesome', 'yasm/datatables'],
          'drupalSettings' => ['datatables' => ['locale' => $this->datatables->getLocale()]],
        ],
        '#cache' => ['max-age' => 3600],
      ];

      return $build;
    }

    return ['#markup' => $this->t('No data found.')];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(DatatablesInterface $datatables, EntitiesStatisticsInterface $entities_statistics) {
    $this->datatables = $datatables;
    $this->entitiesStatistics = $entities_statistics;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('yasm.datatables'),
      $container->get('yasm.entities_statistics')
    );
  }

}
