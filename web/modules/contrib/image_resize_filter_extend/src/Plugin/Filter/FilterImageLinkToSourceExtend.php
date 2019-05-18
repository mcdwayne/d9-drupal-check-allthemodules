<?php

namespace Drupal\image_resize_filter_extend\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a filter to link images derivates to source (original) image.
 *
 * @Filter(
 *   id = "filter_image_link_to_source_extend",
 *   title = @Translation("Link images derivates to source with custom class/rel and support colorbox."),
 *   description = @Translation("Link an image derivate to its source (original) image with custom class/rel and support colorbox."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterImageLinkToSourceExtend extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);

    // Get settings form.
    $colorboxSetting = !empty($this->settings['colorbox']) ? ' colorbox' : '';
    $classSetting = !empty($this->settings['link_class']) ? $this->settings['link_class'] . $colorboxSetting : '' . $colorboxSetting;
    $relSetting = !empty($this->settings['link_rel']) ? $this->settings['link_rel'] . $colorboxSetting : '' . $colorboxSetting;

    /** @var \DOMNode $node */
    foreach ($xpath->query('//img') as $node) {
      // Read the data-align attribute's value, then delete it.
      $width = $node->getAttribute('width');
      $height = $node->getAttribute('height');
      $src = $node->getAttribute('src');
      $class = $node->getAttribute('class');
      $rel = $node->getAttribute('rel');

      // Add class and rel colorbox.
      $rel = ($rel) ? $rel . ' ' . $relSetting : $relSetting;
      $class = ($class) ? $class . ' ' . $classSetting : $classSetting;

      if (!UrlHelper::isExternal($src)) {
        if ($width || $height) {

          /** @var \DOMNode $element */
          $element = $dom->createElement('a');
          $element->setAttribute('href', $src);

          $element->setAttribute('class', $class);
          $element->setAttribute('rel', $rel);

          $node->parentNode->replaceChild($element, $node);
          $element->appendChild($node);
        }
      }
    }
    $result->setProcessedText(Html::serialize($dom));
    // Associate assets to be attached.
    if ($this->settings['colorbox']) {
      $lib = $this->getConfigColorbox();
      $result->setAttachments($lib);
    }

    return $result;
  }

  /**
   * Function Setting form.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['colorbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('add colorbox on link'),
      '#default_value' => !empty($this->settings['colorbox']) ? $this->settings['colorbox'] : 0,
    ];
    $form['link_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('add class attribute'),
      '#default_value' => !empty($this->settings['link_class']) ? $this->settings['link_class'] : '',
    ];
    $form['link_rel'] = [
      '#type' => 'textfield',
      '#title' => $this->t('add rel attribute'),
      '#default_value' => !empty($this->settings['link_rel']) ? $this->settings['link_rel'] : '',
    ];
    return $form;
  }

  /**
   * Get settings colorbox and attachment.
   */
  private function getConfigColorbox() {
    $settings = \Drupal::config('colorbox.settings');
    $lib = [];

    if ($settings->get('custom.activate')) {
      $js_settings = [
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
      ];
    }
    else {
      $js_settings = [
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
      ];
    }

    $style = $settings->get('custom.style');

    $lib['drupalSettings']['colorbox'] = $js_settings;
    if ($style != 'none') {
      $lib['library'][] = "colorbox/$style";
    }

    $lib['library'][] = 'image_resize_filter_extend/image_resize_filter_extend';

    return $lib;
  }

}
