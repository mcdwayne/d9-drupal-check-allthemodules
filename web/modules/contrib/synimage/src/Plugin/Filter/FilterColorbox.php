<?php

namespace Drupal\synimage\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to align elements.
 *
 * @Filter(
 *   id = "filter_cbox",
 *   title = @Translation("Colorbox filter (synimage)"),
 *   description = @Translation("Colorbox style <code>colorbox</code> to attach lib."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterColorbox extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'colorbox') !== FALSE) {
      $settings = \Drupal::config('colorbox.settings');
      $style = $settings->get('custom.style');
      if ($settings->get('custom.activate')) {
        $js_settings = array(
          'transition' => $settings->get('custom.transition_type'),
          'speed' => $settings->get('custom.transition_speed'),
          'opacity' => $settings->get('custom.opacity'),
          'slideshow' => $settings->get('custom.slideshow.slideshow') ? TRUE : FALSE,
          'slideshowAuto' => $settings->get('custom.slideshow.auto') ? TRUE : FALSE,
          'slideshowSpeed' => $settings->get('custom.slideshow.speed'),
          'slideshowStart' => $settings->get('custom.slideshow.text_start'),
          'slideshowStop' => $settings->get('custom.slideshow.text_stop'),
          'current' => $settings->get('custom.text_current'),
          'previous' => $settings->get('custom.text_previous'),
          'next' => $settings->get('custom.text_next'),
          'close' => $settings->get('custom.text_close'),
          'overlayClose' => $settings->get('custom.overlayclose') ? TRUE : FALSE,
          'returnFocus' => $settings->get('custom.returnfocus') ? TRUE : FALSE,
          'maxWidth' => $settings->get('custom.maxwidth'),
          'maxHeight' => $settings->get('custom.maxheight'),
          'initialWidth' => $settings->get('custom.initialwidth'),
          'initialHeight' => $settings->get('custom.initialheight'),
          'fixed' => $settings->get('custom.fixed') ? TRUE : FALSE,
          'scrolling' => $settings->get('custom.scrolling') ? TRUE : FALSE,
          'mobiledetect' => $settings->get('advanced.mobile_detect') ? TRUE : FALSE,
          'mobiledevicewidth' => $settings->get('advanced.mobile_device_width'),
        );
      }
      else {
        $js_settings = array(
          'opacity' => '0.85',
          'current' => $this->t('{current} of {total}'),
          'previous' => $this->t('« Prev'),
          'next' => $this->t('Next »'),
          'close' => $this->t('Close'),
          'maxWidth' => '98%',
          'maxHeight' => '98%',
          'fixed' => TRUE,
          'mobiledetect' => $settings->get('advanced.mobile_detect') ? TRUE : FALSE,
          'mobiledevicewidth' => $settings->get('advanced.mobile_device_width'),
        );
      }

      $result->addAttachments([
        'library' => [
          'colorbox/colorbox',
          'colorbox/init',
          'colorbox/' . $style,
        ],
        'drupalSettings' => ['colorbox' => $js_settings],
      ]);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('Tip long: synimage/src/Plugin/Filter/FilterColorbox.php');
    }
    else {
      return $this->t('Tip short: synimage/src/Plugin/Filter/FilterColorbox.php');
    }
  }

}
