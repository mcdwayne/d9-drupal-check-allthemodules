<?php

namespace Drupal\spammaster\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides 'Firewall Status' Block.
 *
 * @Block(
 *   id = "firewall_status_block",
 *   admin_label = @Translation("Spam Master Firewall Status"),
 * )
 */
class SpamMasterFirewallStatusBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get Firewall data.
    $query = \Drupal::database()->select('spammaster_threats', 'u');
    $query->fields('u', ['id', 'date', 'threat']);
    // Pagination, we need to extend pagerselectextender and limit the query.
    $query->orderBy('id', 'DESC');
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(100);
    $spammaster_spam_buffer = $pager->execute()->fetchAll();

    $output_d = [];
    $output_t = [];
    foreach ($spammaster_spam_buffer as $results) {
      if (filter_var($results->threat, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $output_d[] = date('Y-m-d', strtotime($results->date));
        $output_t[] = $results->threat;
      }
    }

    // Images url.
    $image_path = \Drupal::request()->getSchemeAndHttpHost() . '/modules/spammaster/images/check-threat.png';

    return [
      '#theme' => 'firewall_status',
      '#type' => 'block',
      '#attached' => [
        'library' => [
          'spammaster/spammaster-styles',
        ],
      ],
      '#spammaster_table_head' => $this->t('Firewall Active'),
      '#image_path' => $image_path,
      '#output_d1' => @$output_d[0],
      '#output_t2' => @$output_t[0],
      '#output_d3' => @$output_d[1],
      '#output_t4' => @$output_t[1],
      '#output_d5' => @$output_d[2],
      '#output_t6' => @$output_t[2],
      '#output_d7' => @$output_d[3],
      '#output_t8' => @$output_t[3],
      '#output_d9' => @$output_d[4],
      '#output_t10' => @$output_t[4],
      '#output_d11' => @$output_d[5],
      '#output_t12' => @$output_t[5],
      '#output_d13' => @$output_d[6],
      '#output_t14' => @$output_t[6],
      '#output_d15' => @$output_d[7],
      '#output_t16' => @$output_t[7],
      '#spammaster_table_footer' => $this->t('<a href="@spammaster_url">Protected by Spam Master</a>', ['@spammaster_url' => 'https://spammaster.techgasp.com/']),
    ];

  }

}
