<?php

namespace Drupal\prod_check\Plugin\ProdCheck\Server;

use Drupal\prod_check\Plugin\ProdCheck\ProdCheckBase;

/**
 * Dblog report
 *
 * @ProdCheck(
 *   id = "dblog",
 *   title = @Translation("PHP errors"),
 *   category = "server",
 *   provider = "dblog"
 * )
 */
class DbLog extends ProdCheckBase {

  /**
   * Error level
   */
  public $errorLevel;


  /**
   * Threshold
   */
  public $threshold;


  /**
   * The result of the query
   */
  public $result;

  /**
   * {@inheritdoc}
   */
  public function init() {

    $this->errorLevel = 0;
    $this->threshold = 0;

    // @todo this query is broken
    $this->result = db_query(
      'SELECT COUNT(*) FROM (SELECT count(wid) FROM {watchdog} WHERE type = :type AND severity <= :severity GROUP BY variables HAVING COUNT(wid) >= :threshold) subquery',
      array(
        ':type' => 'php',
        ':severity' => $this->errorLevel,
        ':threshold' => $this->threshold
      )
    )->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function state() {
    return !$this->result;
  }

  /**
   * {@inheritdoc}
   */
  public function successMessages() {
    return [
      'value' => $this->t('No PHP errors reported.'),
      'description' => $this->t('Status is OK for production use.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function failMessages() {
    $link_array = $this->generateLinkArray($this->title(), 'dblog.overview');

    return [
      'value' => $this->t('PHP errors reported.'),
      'description' => $this->formatPlural(
        $this->result,
        '@count PHP error occuring more than @threshold time(s) has been reported! Check the %link for details!',
        '@count PHP errors occuring more than @threshold time(s) have been reported! Check the %link for details!',
        array(
          '%link' => implode($link_array),
          '@threshold' => $this->threshold,
        )
      )
    ];
  }

}
