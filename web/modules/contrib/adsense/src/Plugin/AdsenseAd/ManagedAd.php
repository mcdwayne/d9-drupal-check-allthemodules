<?php

namespace Drupal\adsense\Plugin\AdsenseAd;

use Drupal\adsense\ContentAdBase;
use Drupal\adsense\PublisherId;

/**
 * Provides an AdSense managed ad unit.
 *
 * @AdsenseAd(
 *   id = "managed",
 *   name = @Translation("Content ads"),
 *   isSearch = FALSE,
 *   needsSlot = TRUE
 * )
 */
class ManagedAd extends ContentAdBase {

  /**
   * Ad slot ID.
   *
   * @var string
   */
  private $slot;

  /**
   * Ad Shape (auto, horizontal, vertical, rectangle).
   *
   * @var string[]
   */
  private $shape;

  /**
   * Ad Layout key.
   *
   * @var string
   */
  private $layoutKey;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id = '', $plugin_definition = NULL, $config_factory = NULL, $module_handler = NULL, $current_user = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory, $module_handler, $current_user);
    $fo = (!empty($configuration['format'])) ? $configuration['format'] : '';
    $sl = (!empty($configuration['slot'])) ? $configuration['slot'] : '';
    $sh = (!empty($configuration['shape'])) ? $configuration['shape'] : ['auto'];
    $lk = (!empty($configuration['layout_key'])) ? $configuration['layout_key'] : '';

    if (($fo != 'Search Box') && !empty($fo) && !empty($sl)) {
      $this->format = $fo;
      $this->slot = $sl;
      $this->shape = $sh;
      $this->layoutKey = $lk;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAdPlaceholder() {
    if (!empty($this->format) && !empty($this->slot)) {
      $client = PublisherId::get();
      // Get width and height from the format.
      list($width, $height) = $this->dimensions($this->format);

      $content = $this->configFactory->get('adsense.settings')->get('adsense_placeholder_text');
      $content .= "\nclient = ca-$client\nslot = {$this->slot}";
      if (ManagedAd::isResponsive($this->format) || ManagedAd::isFluid($this->format)) {
        $format = ($this->format == 'responsive') ? implode(',', $this->shape) : $this->format;
        $content .= "\nformat = $format";
      }
      else {
        $content .= "\nwidth = $width\nheight = $height";
      }

      return [
        '#content' => ['#markup' => nl2br($content)],
        '#format' => $this->format,
        '#width' => $width,
        '#height' => $height,
      ];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getAdContent() {
    if (!empty($this->format) && !empty($this->slot)) {
      $config = $this->configFactory->get('adsense.settings');
      $client = PublisherId::get();
      $this->moduleHandler->alter('adsense', $client);

      if (ManagedAd::isResponsive($this->format)) {
        $shape = ($this->format == 'responsive') ? implode(',', $this->shape) : $this->format;

        // Responsive smart sizing code.
        $content = [
          '#theme' => 'adsense_managed_responsive',
          '#format' => $this->format,
          '#client' => $client,
          '#slot' => $this->slot,
          '#shape' => $shape,
        ];
      }
      elseif (ManagedAd::isFluid($this->format)) {
        $style = 'display:block';
        if ($this->format == 'in-article') {
          $style .= '; text-align:center;';
        }

        // Responsive smart sizing code.
        $content = [
          '#theme' => 'adsense_managed_fluid',
          '#format' => $this->format,
          '#client' => $client,
          '#slot' => $this->slot,
          '#layout_key' => $this->layoutKey,
          '#style' => $style,
        ];
      }
      else {
        // Get width and height from the format.
        list($width, $height) = $this->dimensions($this->format);

        if ($config->get('adsense_managed_async')) {
          // Asynchronous code.
          $content = [
            '#theme' => 'adsense_managed_async',
            '#format' => $this->format,
            '#width' => $width,
            '#height' => $height,
            '#client' => $client,
            '#slot' => $this->slot,
          ];
        }
        else {
          $lang = $config->get('adsense_secret_language');
          $secret = $lang ? "    google_language = '$lang';\n" : '';

          // Synchronous code.
          $content = [
            '#theme' => 'adsense_managed_sync',
            '#format' => $this->format,
            '#width' => $width,
            '#height' => $height,
            '#client' => $client,
            '#slot' => $this->slot,
            '#secret' => $secret,
          ];
        }
      }

      return $content;
    }
    return [];
  }

  /**
   * Helper function to determine if an ad format is responsive.
   *
   * @param string $format
   *   Ad format.
   *
   * @return bool
   *   TRUE if the ad is responsive.
   */
  private static function isResponsive($format) {
    return in_array($format, ['responsive', 'link', 'autorelaxed']);
  }

  /**
   * Helper function to determine if an ad format is fluid.
   *
   * @param string $format
   *   Ad format.
   *
   * @return bool
   *   TRUE if the ad is fluid.
   */
  private static function isFluid($format) {
    return in_array($format, ['in-article', 'in-feed']);
  }

  /**
   * {@inheritdoc}
   */
  public static function adsenseAdFormats($key = NULL) {
    $ads = [
      'responsive' => ['desc' => t('Responsive ad unit')],
      'custom' => ['desc' => t('Custom size ad unit')],
      'autorelaxed' => ['desc' => t('Matched content')],
      'in-article' => ['desc' => t('In-article ad')],
      'in-feed' => ['desc' => t('In-feed ad')],
      // Top performing ad sizes.
      '300x250' => ['desc' => t('Medium Rectangle')],
      '336x280' => ['desc' => t('Large Rectangle')],
      '728x90' => ['desc' => t('Leaderboard')],
      '300x600' => ['desc' => t('Large Skyscraper')],
      '320x100' => ['desc' => t('Large Mobile Banner')],
      // Other supported ad sizes.
      '320x50' => ['desc' => t('Mobile Banner')],
      '468x60' => ['desc' => t('Banner')],
      '234x60' => ['desc' => t('Half Banner')],
      '120x600' => ['desc' => t('Skyscraper')],
      '120x240' => ['desc' => t('Vertical Banner')],
      '160x600' => ['desc' => t('Wide Skyscraper')],
      '300x1050' => ['desc' => t('Portrait')],
      '970x90' => ['desc' => t('Large Leaderboard')],
      '970x250' => ['desc' => t('Billboard')],
      '250x250' => ['desc' => t('Square')],
      '200x200' => ['desc' => t('Small Square')],
      '180x150' => ['desc' => t('Small Rectangle')],
      '125x125' => ['desc' => t('Button')],
      // 4-links.
      'link' => ['desc' => t('Responsive links')],
      '120x90' => ['desc' => t('4-links Vertical Small')],
      '160x90' => ['desc' => t('4-links Vertical Medium')],
      '180x90' => ['desc' => t('4-links Vertical Large')],
      '200x90' => ['desc' => t('4-links Vertical X-Large')],
      '468x15' => ['desc' => t('4-links Horizontal Medium')],
      '728x15' => ['desc' => t('4-links Horizontal Large')],
    ];

    if (!empty($key)) {
      return (array_key_exists($key, $ads)) ? $ads[$key] : NULL;
    }
    else {
      return $ads;
    }
  }

}
