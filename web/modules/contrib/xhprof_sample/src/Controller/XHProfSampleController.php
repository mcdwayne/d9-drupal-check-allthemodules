<?php
/**
 * @file
 * Contains \Drupal\xhprof_sample\Controller\XHProfSampleController.
 */

namespace Drupal\xhprof_sample\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the xhprof_sample module.
 */
class XHProfSampleController extends ControllerBase {

  /**
   * Returns a table of collected samples.
   *
   * @return array
   *   Render array for the page.
   */
  public function sampleList() {
    $runclass = \Drupal::service('xhprof_sample.run');
    $samples = $runclass::collectAll();
    $build = array();
    $rows = array();
    $header = array(
      array('data' => t('Path')),
      array('data' => t('Runtime'), 'field' => 'runtime', 'sort' => 'desc'),
      array('data' => t('User')),
      array('data' => t('Method')),
      array('data' => t('Operations')),
    );

    $sort = tablesort_get_sort($header);

    // TODO: this should eventually be handled in a standard way
    // directly in each Run service.
    static::sampleRuntimeSort($samples, $sort);

    $page_samples = static::sampleArraySplice($samples, XHPROF_SAMPLE_OUTPUT_LIST_PER_PAGE);
    foreach ($page_samples as $uri => $meta) {
      $operation_links = xhprof_sample_run_operations($meta);
      $operations = array(
        '#type' => 'operations',
        '#links' => array_values($operation_links),
      );

      $rows[] = array(
        $meta['path'],
        $meta['runtime'],
        $meta['user'],
        $meta['method'],
        \Drupal::service('renderer')->render($operations),
      );
    }

    $build['samples'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

    $build['pager'] = array('#type' => 'pager');

    return $build;
  }

  /**
   * Sort an array of samples by runtime.
   *
   * @param array &$samples
   *   The sample array to sort.
   * @param string $sort
   *   The sort direction.
   *
   * @return array
   *   Sorted array.
   */
  private static function sampleRuntimeSort(&$samples, $sort = 'desc') {
    return usort($samples,
      function($a, $b) use ($sort) {
        switch ($sort) {
          case 'desc':
            return $b['runtime'] > $a['runtime'] ? 1 : -1;

          case 'asc':
            return $a['runtime'] > $b['runtime'] ? 1 : -1;

          default:
            return 1;
        }
      }
    );
  }

  /**
   * Splices an array of samples into pages.
   *
   * @param array $data
   *   An array of samples.
   * @param int $limit
   *   Number of items per-page.
   * @param int $element
   *   Pager element ID.
   *
   * @return array
   *   Spliced array.
   */
  private static function sampleArraySplice($data, $limit = 9, $element = 0) {
    global $pager_page_array, $pager_total, $pager_total_items;
    $page = isset($_GET['page']) ? $_GET['page'] : '';

    // Convert comma-separated $page to an array, used by other functions.
    $pager_page_array = explode(',', $page);

    // We calculate the total of pages as ceil(items / limit).
    $pager_total_items[$element] = count($data);
    $pager_total[$element] = ceil($pager_total_items[$element] / $limit);
    $pager_page_array[$element] = max(0, min((int) $pager_page_array[$element], ((int) $pager_total[$element]) - 1));

    return array_slice($data, $pager_page_array[$element] * $limit, $limit, TRUE);
  }
}
