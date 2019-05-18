<?php

namespace Drupal\searchcloud_block\Services;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Extension\ModuleHandlerInterface;

class SearchCloudServiceProvider extends ServiceProviderBase implements SearchCloudServiceProviderInterface {

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\Config;
   */
  protected $config;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a SearchCloudProvider object.
   */
  public function __construct(ConfigFactory $config, ModuleHandlerInterface $module_handler) {
    $this->config        = $config->get('searchcloud_block.settings');
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getTermFromUrl($position = 0) {
    // TODO: Maybe rewrite arg function. Don't know it has to be used anymore.
    $term = arg($position);
    if (!empty($term)) {
      return $this->getTerm($term);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTerm($term = FALSE) {
    $ret = FALSE;
    if (!empty($term)) {
      $result = $this->getResult(TRUE, $term);
      foreach ($result as $row) {
        $ret = $row;
      }
    }

    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function getResult($all = FALSE, $key = FALSE, $amount = FALSE, $order = FALSE, $raw = FALSE) {
    $config = $this->config;

    if (empty($order) || $order == 'RAND') {
      $order = FALSE;
    }

    try {
      $query = db_select('searchcloud_block_count', 'sbc');
      $query->fields('sbc', array('keyword', 'count', 'hide'));

      // TODO: Maybe rewrite arg function. Don't know it has to be used anymore.
      $currentpath = implode('/', arg());

      $this->getResultBuildQuery($query, $key, $all, $amount, $order);

      $usepaths = $config->get('searchcloud_block_usepaths');
      $paths    = $config->get('searchcloud_block_usepaths_pathlist');
      if (!empty($usepaths) && !empty($paths)) {
        $paths = $this->sanitizePaths($paths);
        if (in_array($currentpath, $paths)) {
          $query->join('searchcloud_block_paths', 'sbp', 'sbc.keyword = sbp.keyword AND sbp.path = :path', array(':path' => $currentpath));
        }
      }

      $this->moduleHandler->invokeAll('searchcloud_block_get_result_alter', array($query));

      $result = $query->execute();
    }
    catch (Exception $e) {
      watchdog('searchcloud_block', $e);
    }

    if ($raw) {
      return array(
        'query'  => $query,
        'result' => $result,
      );
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getResultBuildQuery(&$query, $key, $all, $amount, $order) {
    if ($key) {
      $query->condition('sbc.keyword', $key);
    }

    if (!$all) {
      $query->condition('sbc.hide', 1, '<>');
    }

    if ($amount) {
      $query->range(0, $amount);
    }

    if (!empty($order)) {
      $query->orderBy('count', $order);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sanitizePaths($paths) {
    $paths = explode(',', $paths);
    foreach ($paths as $key => $val) {
      $paths[$key] = trim($val);
    }

    return $paths;
  }

  /**
   * {@inheritdoc}
   */
  public function checkKeys($key) {
    $config = $this->config;

    $data     = $this->getResult(TRUE, $key, FALSE, FALSE, TRUE);
    $row      = $data['query']->countQuery()->execute()->fetchAssoc();
    $rowcount = $row['expression'];
    $result   = $data['result'];

    if (!empty($rowcount)) {
      foreach ($result as $record) {
        db_update('searchcloud_block_count')->fields(array('count' => $record->count + 1))->condition('keyword', $key)
          ->execute();
      }
    }
    else {
      db_insert('searchcloud_block_count')->fields(array(
          'keyword' => $key,
          'count'   => 1,
        ))->execute();
    }

    $usepaths = $config->get('searchcloud_block_usepaths');
    $paths    = $config->get('searchcloud_block_usepaths_pathlist');
    if (!empty($usepaths) && !empty($paths)) {
      $paths = $this->sanitizePaths($paths);
      global $base_url;
      $ref = str_replace($base_url . '/', '', $_SERVER['HTTP_REFERER']);

      if (in_array($ref, $paths)) {
        $this->setPathResult($key, $ref);
      }
    }

    // Save parameters.
    $_key = $key;
    if (isset($record)) {
      $_count = $record->count + 1;
    }
    else {
      $_count = 1;
    }

    // Execute hook.
    $this->moduleHandler->invokeAll('searchcloud_block_check_keys_execute', array($_key, $_count));
  }

  /**
   * {@inheritdoc}
   */
  public function setPathResult($key, $ref) {
    dsm($key);
    dsm($ref);
  }

}
