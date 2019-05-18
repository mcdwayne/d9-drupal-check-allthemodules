<?php

namespace Drupal\campaignmonitor_campaign\Plugin\Block;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Url;

/**
 * Provides a 'Campaign' block.
 *
 * @Block(
 *   id = "campaignmonitor_campaign_block",
 *   admin_label = @Translation("Campaign Block"),
 *   category = @Translation("Campaign Monitor Campaigns"),
 *   module = "campaignmonitor_campaign",
 * )
 */
class CampaignMonitorCampaignBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Date formatter variable.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new SwitchUserBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\DateTime\DateFormatter $date
   *   The date service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatter $date) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dateFormatter = $date;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $campaigns = campaignmonitor_campaign_get_campaigns();

    if ($campaigns) {
      $content = '<ul>';
      foreach ($campaigns as $campaign) {
        $url = Url::fromUri($campaign['Link']);
        $content .= '<li>' . Link::fromTextAndUrl($campaign['Name'], $url)->toString() . ' ' . $this->dateFormatter->format($campaign['Sent'], 'short') . '</li>';
      }
      $content .= '</ul>';

      // Build block.
      $block['subject'] = t('Newsletter archive');
      $block['content'] = ['#markup' => $content];
    }
    else {
      $this->messenger()
        ->addMessage(t('Unable to fetch campaigns from Campaign monitor.'), 'error');
    }
    return $block['content'];
  }

}
