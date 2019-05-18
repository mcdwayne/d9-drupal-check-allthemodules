<?php

namespace Drupal\adsense_oldcode\Plugin\AdsenseAd;

use Drupal\adsense\SearchAdBase;
use Drupal\adsense\PublisherId;

/**
 * Provides an AdSense old search engine form.
 *
 * @AdsenseAd(
 *   id = "oldsearch",
 *   name = @Translation("Old search"),
 *   isSearch = TRUE,
 *   needsSlot = FALSE
 * )
 */
class OldSearchAd extends SearchAdBase {

  /**
   * Ad Channel.
   *
   * @var string
   */
  private $channel;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id = '', $plugin_definition = NULL, $config_factory = NULL, $module_handler = NULL, $current_user = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory, $module_handler, $current_user);
    $this->channel = (!empty($configuration['channel'])) ? $configuration['channel'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getAdPlaceholder() {
    $client = PublisherId::get();

    $content = "Old Search\nclient = $client";

    return [
      '#content' => ['#markup' => nl2br($content)],
      '#format' => 'Search Box',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAdContent() {
    $client = PublisherId::get();
    $this->moduleHandler->alter('adsense', $client);

    $config = $this->configFactory->get('adsense_oldcode.settings');
    $logo = $config->get('adsense_search_logo');
    $box_background_color = $config->get('adsense_search_color_box_background');

    $domain_0 = $config->get('adsense_search_domain_0');
    $domain_1 = $config->get('adsense_search_domain_1');
    $domain_2 = $config->get('adsense_search_domain_2');
    $domain   = $domain_1 ? "$domain_0;$domain_1" : $domain_0;
    $domain   = $domain_2 ? "$domain;$domain_2" : $domain;

    // @TODO this is necessary for unclean URLs.
    /* global $base_url;
    $results_path = $base_url;*/

    return [
      '#theme' => 'adsense_oldsearch_form',
      '#country' => $config->get('adsense_search_country'),
      '#bg_color' => $box_background_color,
      '#logo' => $logo,
      '#logo_color' => ($box_background_color == '#000000') ? 'blk' : (($box_background_color == '#CCCCCC') ? 'gry' : 'wht'),
      '#hidden_q' => FALSE,
      '#domain' => $domain,
      '#qsize' => $config->get('adsense_search_textbox_length'),
      '#search_button' => $config->get('adsense_search_button'),
      '#btn' => ($logo == 'adsense_search_logo_on_button') ? $this->t('Google Search') : $this->t('Search'),
      '#box_text_color' => $config->get('adsense_search_color_box_text'),
      '#domain_0' => $domain_0,
      '#domain_1' => $domain_1,
      '#domain_2' => $domain_2,
      '#client' => $client,
      '#channel' => $config->get('adsense_ad_channel_' . $this->channel),
      '#encoding' => $config->get('adsense_search_encoding'),
      '#safe_mode' => $config->get('adsense_search_safe_mode'),
      '#url' => $config->get('adsense_search_color_url'),
      '#border' => $config->get('adsense_search_color_border'),
      '#visited' => $config->get('adsense_search_color_visited_url'),
      '#bg' => $config->get('adsense_search_color_bg'),
      '#logobg' => $config->get('adsense_search_color_logo_bg'),
      '#title' => $config->get('adsense_search_color_title'),
      '#text' => $config->get('adsense_search_color_text'),
      '#light' => $config->get('adsense_search_color_light_url'),
      '#language' => $config->get('adsense_search_language'),
    ];
  }

}
