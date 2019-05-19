<?php

namespace Drupal\yasm\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\yasm\Services\DatatablesInterface;
use Drupal\yasm\Services\EntitiesStatisticsInterface;
use Drupal\yasm\Utility\YasmUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * YASM Statistics site users controller.
 */
class Files extends ControllerBase {

  /**
   * The stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(AccountInterface $account) {
    return ($this->moduleHandler->moduleExists('file')) ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Site files page output.
   */
  public function siteContent(Request $request) {
    $filters = [];

    $year = $request->query->get('year', 'all');
    if (is_numeric($year)) {
      $filters = YasmUtility::getYearFilter('created', $year);
    }

    $first_content_date = $this->entitiesStatistics->getFirstDateContent('file');
    $build['tabs'] = YasmUtility::getYearLinks(date('Y', $first_content_date), $year);
    $build['data'] = $this->buildContent($year, $filters);

    return $build;
  }

  /**
   * Build files page html.
   */
  private function buildContent($year, $filters = []) {
    $filecount = $this->entitiesStatistics->count('file', $filters);
    if ($filecount > 0) {
      $aggregates = [
        'fid'      => 'COUNT',
        'filesize' => 'SUM',
      ];

      // Build count files by status table.
      $files_status = $this->entitiesStatistics->aggregate('file', $aggregates, 'status', $filters);
      $status_label = [
        1 => $this->t('Permanent'),
        0 => $this->t('Temporary'),
      ];
      $rows = [];
      if (!empty($files_status)) {
        foreach ($files_status as $files) {
          $rows[] = [
            $status_label[$files['status']],
            $files['fid_count'],
            format_size($files['filesize_sum']),
          ];
        }
      }
      $table_by_status = YasmUtility::table([
        $this->t('Status'),
        $this->t('Count'),
        $this->t('Size'),
      ], $rows, 'files_status');

      // Build count files by mimetype table.
      $files_mime = $this->entitiesStatistics->aggregate('file', $aggregates, 'filemime', $filters);
      $rows = [];
      if (!empty($files_mime)) {
        foreach ($files_mime as $files) {
          $mime_parts = explode('/', $files['filemime']);
          $rows[] = [
            $this->t($mime_parts[0]),
            $mime_parts[1],
            $files['fid_count'],
            format_size($files['filesize_sum']),
          ];
        }
      }
      $table_by_mime = YasmUtility::table([
        $this->t('Type'),
        $this->t('Extension'),
        $this->t('Count'),
        $this->t('Size'),
      ], $rows, 'files_type');

      // Build count files by stream wrapper table.
      $wrappers = $this->streamWrapperManager->getWrappers();
      $rows = [];
      foreach ($wrappers as $key => $wrapper) {
        $filter_wrapper = [
          [
            'key'      => 'uri',
            'value'    => $key . '://%',
            'operator' => 'LIKE',
          ],
        ];
        $count = $this->entitiesStatistics->aggregate('file', $aggregates, NULL, array_merge($filters, $filter_wrapper));
        // Only add rows with content.
        if (isset($count[0]['fid_count']) && $count[0]['fid_count'] > 0) {
          $rows[] = [
            $key,
            $count[0]['fid_count'],
            format_size($count[0]['filesize_sum']),
          ];
        }
      }
      $table_by_stream = YasmUtility::table([
        $this->t('Stream wrapper'),
        $this->t('Count'),
        $this->t('Size'),
      ], $rows, 'files_stream');

      // Build new files monthly table.
      $dates = YasmUtility::getLastMonths($year);
      // Collect data for all cols.
      $rows = $labels = [];
      foreach ($dates as $date) {
        // Filter data.
        $labels[] = $date['label'];
        $filter = YasmUtility::getIntervalFilter('created', $date['max'], $date['min']);
        $rows['data'][] = $this->entitiesStatistics->count('file', $filter);
      }
      $table_new_files_monthly = YasmUtility::table($labels, $rows, 'files_monthly');

      // Build array content output.
      $build = [];

      $build[] = YasmUtility::markup($this->t('There are currently @count managed files.', [
        '@count' => $filecount,
      ]));

      $cards = [];
      $cards[] = [
        YasmUtility::title($this->t('Files by status'), 'far fa-file'),
        $table_by_status,
      ];
      $cards[] = [
        YasmUtility::title($this->t('Files by stream wrapper'), 'far fa-file'),
        $table_by_stream,
      ];
      $cards[] = [
        YasmUtility::title($this->t('Files by type'), 'far fa-file'),
        $table_by_mime,
      ];
      // First region in two cols.
      $build[] = YasmUtility::columns($cards, ['yasm-files'], 2);

      $cards = [];
      $cards[] = [
        YasmUtility::title($this->t('New files monthly'), 'far fa-file'),
        $table_new_files_monthly,
      ];
      // Second region in one col.
      $build[] = YasmUtility::columns($cards, ['yasm-files'], 1);

      $build[] = [
        '#attached' => [
          'library' => ['yasm/global', 'yasm/fontawesome', 'yasm/datatables'],
          'drupalSettings' => ['datatables' => ['locale' => $this->datatables->getLocale()]],
        ],
        '#cache' => [
          'id' => ['yasm_files'],
          'tags' => ['file_list'],
        ],
      ];

      return $build;
    }

    return ['#markup' => $this->t('No data found.')];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ModuleHandlerInterface $module_handler, StreamWrapperManagerInterface $stream_wrapper_manager, DatatablesInterface $datatables, EntitiesStatisticsInterface $entities_statistics) {
    $this->moduleHandler = $module_handler;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->datatables = $datatables;
    $this->entitiesStatistics = $entities_statistics;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('stream_wrapper_manager'),
      $container->get('yasm.datatables'),
      $container->get('yasm.entities_statistics')
    );
  }

}
