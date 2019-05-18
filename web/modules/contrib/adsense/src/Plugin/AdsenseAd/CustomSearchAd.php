<?php

namespace Drupal\adsense\Plugin\AdsenseAd;

use Drupal\Core\Url;

use Drupal\adsense\SearchAdBase;
use Drupal\adsense\PublisherId;

/**
 * Provides an AdSense custom search engine form.
 *
 * @AdsenseAd(
 *   id = "cse",
 *   name = @Translation("CSE Search"),
 *   isSearch = TRUE,
 *   needsSlot = TRUE
 * )
 */
class CustomSearchAd extends SearchAdBase {

  /**
   * Ad slot ID.
   *
   * @var string
   */
  private $slot;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id = '', $plugin_definition = NULL, $config_factory = NULL, $module_handler = NULL, $current_user = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory, $module_handler, $current_user);
    $this->slot = (!empty($configuration['slot'])) ? $configuration['slot'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getAdPlaceholder() {
    if (!empty($this->slot)) {
      $client = PublisherId::get();

      $content = "CSE\ncx = partner-$client:{$this->slot}";

      return [
        '#content' => ['#markup' => nl2br($content)],
        '#format' => 'Search Box',
      ];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getAdContent() {
    if (!empty($this->slot)) {
      $client = PublisherId::get();
      $this->moduleHandler->alter('adsense', $client);

      $cse_config = $this->configFactory->get('adsense.settings');
      $branding = $cse_config->get('adsense_cse_logo');
      $results_path = Url::fromRoute('adsense_cse.results')->toString();

      // @TODO this is necessary for unclean URLs.
      /*  $results_path = $base_url;
      $hidden_q_field = '<input type="hidden" name="q" value="." />';*/

      $forid = 0;
      switch ($cse_config->get('adsense_cse_ad_location')) {
        case 'adsense_cse_loc_top_right':
          $forid = 10;
          break;

        case 'adsense_cse_loc_top_bottom':
          $forid = 11;
          break;

        case 'adsense_cse_loc_right':
          $forid = 9;
          break;
      }

      if ($branding == 'adsense_cse_branding_watermark') {
        global $base_url;

        // When using a watermark, code is not reusable due to indentation.
        $content = [
          '#theme' => 'adsense_cse_watermark',
          '#language' => $cse_config->get('adsense_cse_language'),
          '#results_path' => $results_path,
          '#client' => $client,
          '#slot' => $this->slot,
          '#forid' => $forid,
          '#encoding' => $cse_config->get('adsense_cse_encoding'),
          '#qsize' => $cse_config->get('adsense_cse_textbox_length'),
          '#search' => $this->t('Search'),
          // Since we use as_q, we must use a modified copy of
          // Google's Javascript.
          '#script' => $base_url . '/' . drupal_get_path('module', 'adsense') . '/js/adsense_cse-v1.js',
        ];
      }
      else {
        $box_background_color = $cse_config->get('adsense_cse_color_box_background');

        $content = [
          '#theme' => 'adsense_cse_branding',
          '#class' => ($branding == 'adsense_cse_branding_right') ? 'cse-branding-right' : 'cse-branding-bottom',
          '#bg_color' => $box_background_color,
          '#color' => ($box_background_color == '000000') ? 'FFFFFF' : '000000',
          '#results_path' => $results_path,
          '#client' => $client,
          '#slot' => $this->slot,
          '#forid' => $forid,
          '#encoding' => $cse_config->get('adsense_cse_encoding'),
          '#qsize' => $cse_config->get('adsense_cse_textbox_length'),
          '#search' => $this->t('Search'),
          '#custom_search' => $this->t('Custom Search'),
        ];
      }

      return $content;
    }
    return [];
  }

}
