<?php

namespace Drupal\spammaster\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides 'Total Threats Count' Block.
 *
 * @Block(
 *   id = "total_threats_count_block",
 *   admin_label = @Translation("Spam Master Total Threats Count"),
 * )
 */
class SpamMasterTotalCountBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->getConfiguration();
    // Get Total Threats Count from module settings.
    $spammaster_settings = \Drupal::config('spammaster.settings');
    $spammaster_total_threats_count = number_format($spammaster_settings->get('spammaster.license_protection'));

    return [
      '#theme' => 'total_count',
      '#type' => 'block',
      '#attached' => [
        'library' => [
          'spammaster/spammaster-styles',
        ],
      ],
      '#spammaster_total_threats_count' => $spammaster_total_threats_count,
      '#spammaster_total_threats_footer' => $this->t('by <a href="@spammaster_url">Spam Master</a>.', ['@spammaster_url' => 'https://spammaster.techgasp.com/']),
    ];

  }

}
