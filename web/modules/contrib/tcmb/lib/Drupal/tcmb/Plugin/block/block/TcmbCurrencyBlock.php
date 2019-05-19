<?php

/**
 * @file
 * Contains \Drupal\tcmb\Plugin\block\block\TcmbCurrencyBlock
 */

namespace Drupal\tcmb\Plugin\block\block;

use Drupal\block\BlockBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Tcmb Currency' block.
 * @Plugin (
 *  id = "tcmb_currency_block",
 *  subject = @Translation("Tcmb: currency"),
 *  module = "tcmb"
 *  )
 */
class TcmbCurrencyBlock extends BlockBase {
  public function build() {
    dpm(__FUNCTION__);
    $records = db_select('tcmb', 't')
      ->fields('t', array(
        'updated',
      ))
      ->range(0, 1)
      ->execute()
      ->fetchAssoc();
    $updated = date('d.m.Y', strtotime($records['updated']));
    $tcmb_currency_codes = variable_get('tcmb_currency_codes', array());
    $records = db_select('tcmb', 't')
      ->fields('t', array(
        'currency_name',
        'currency_code',
        'updated',
        'buying',
        'selling',
      ))
      ->condition('currency_code', $tcmb_currency_codes, 'IN')
      ->orderBy('updated', 'DESC')
      ->orderBy('currency_code')
      ->groupBy('currency_code');
    $results = $records->execute()->fetchAll(PDO::FETCH_ASSOC);
    $rows = array();
    foreach ($results as $result) {
      $title = $result['currency_name'];
      $image = drupal_get_path('module', 'tcmb') . '/png/' . strtolower($result['currency_code']) . '.png';
      $rows[] = array('data' => array(theme('image', array('path' => $image, 'alt' => $title, 'title' => $title)) . " " . $result['currency_code'], $result['buying'], $result['selling']));
    }
    if (isset($rows)) { 
      return $rows;
    }
  }
}

