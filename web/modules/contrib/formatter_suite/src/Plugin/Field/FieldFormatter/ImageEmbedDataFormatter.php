<?php

namespace Drupal\formatter_suite\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

use Drupal\formatter_suite\Branding;

/**
 * Embeds an image as a data URL instead of a file URL.
 *
 * Normally, images are included on a page by using an <img> tag with a
 * file URL that causes the browser to issue another HTTP request to get
 * the file. When there are a large number of images on a page, the latency
 * inherent in HTTP requests adds up and can substantially slow down page
 * loads.
 *
 * This formatter optionally replaces file URLs with data URLs that include
 * the base 64 encoding of the image directly within the page. There is no
 * need for a further HTTP request, which reduces load time wasted on
 * latency. The downside is that the image data can be up to 3 times larger
 * when base 64 encoded. This makes the page itself larger and delays it
 * being loaded. Often, though, the savings in reduced latency is much
 * greater than the time lost in longer page load times.
 *
 * This is most effective when showing a large number of small images, such
 * as image lists with thumbnails. It can also be effective when a page has
 * only a few images to convert to data URLs, even if they are large.
 *
 * This formatter's options select maximum image width and height for
 * embedding as a data URL. Images larger than this are left as file URLs.
 *
 * @ingroup formatter_suite
 *
 * @FieldFormatter(
 *   id          = "formatter_suite_image_embed_data",
 *   label       = @Translation("Formatter Suite - Image with embedded data URL"),
 *   weight      = 1001,
 *   field_types = {
 *     "image",
 *   }
 * )
 */
class ImageEmbedDataFormatter extends ImageFormatter {

  /*---------------------------------------------------------------------
   *
   * Configuration.
   *
   *---------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array_merge(
      [
        'maximumEmbedWidth'  => '',
        'maximumEmbedHeight' => '',
      ],
      parent::defaultSettings());
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // Get current settings.
    $maximumEmbedWidth = $this->getSetting('maximumEmbedWidth');
    $maximumEmbedHeight = $this->getSetting('maximumEmbedHeight');

    // Sanitize & validate.
    //
    // Security: The maximum embed width and height have been entered by
    // an administrator. They both should be simple integers and should
    // not include any HTML or HTML entities.
    //
    // Parsing these as integers ignores any extra text that may be in
    // the value.
    if (isset($maximumEmbedWidth) === TRUE) {
      $maximumEmbedWidth = intval($maximumEmbedWidth);
    }
    else {
      $maximumEmbedWidth = 0;
    }

    if (isset($maximumEmbedHeight) === TRUE) {
      $maximumEmbedHeight = intval($maximumEmbedHeight);
    }
    else {
      $maximumEmbedHeight = 0;
    }

    // Summarize.
    $summary = [];
    $summary[] = $this->t('Embed image data URL');
    if ($maximumEmbedWidth > 0 && $maximumEmbedHeight > 0) {
      $summary[] = $this->t(
        '&nbsp;&nbsp;for width &le; @maximumEmbedWidth &amp; height &le; @maximumEmbedHeight.',
        [
          '@maximumEmbedWidth' => $maximumEmbedWidth,
          '@maximumEmbedHeight' => $maximumEmbedHeight,
        ]);
    }
    elseif ($maximumEmbedWidth > 0) {
      $summary[] = $this->t(
        '&nbsp;&nbsp;for width &le; @maximumEmbedWidth &amp; any height.',
        [
          '@maximumEmbedWidth' => $maximumEmbedWidth,
        ]);
    }
    elseif ($maximumEmbedHeight > 0) {
      $summary[] = $this->t(
        '&nbsp;&nbsp;for any width &amp; height &le; @maximumEmbedHeight.',
        [
          '@maximumEmbedHeight' => $maximumEmbedHeight,
        ]);
    }
    else {
      $summary[] = $this->t('&nbsp;&nbsp;for any width &amp; height.');
    }

    $summary = array_merge($summary, parent::settingsSummary());

    return $summary;
  }

  /*---------------------------------------------------------------------
   *
   * Settings form.
   *
   *---------------------------------------------------------------------*/

  /**
   * Returns a brief description of the formatter.
   *
   * @return string
   *   Returns a brief translated description of the formatter.
   */
  protected function getDescription() {
    return $this->t("Embed image pixels directly within the page by using a data URL instead of a file URL. This reduces the browser's time spent requesting images, but increases the page size and its download time. It is primarily a good trade-off when images are small, such as for thumbnails.");
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
    // Start with the parent form.
    $elements = parent::settingsForm($form, $formState);

    // Add branding.
    $elements = Branding::addFieldFormatterBranding($elements);
    $elements['#attached']['library'][] =
      'formatter_suite/formatter_suite.fieldformatter';

    // Add description.
    $elements['description'] = [
      '#type'          => 'html_tag',
      '#tag'           => 'div',
      '#value'         => $this->getDescription(),
      '#weight'        => -1000,
      '#attributes'    => [
        'class'        => [
          'formatter_suite-settings-description',
        ],
      ],
    ];

    $weight = 100;

    // Prompt for each setting.
    $elements['sectionBreak'] = [
      '#markup' => '<div class="formatter_suite-section-break"></div>',
      '#weight' => $weight++,
    ];

    $elements['maximumEmbedWidth'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Maximum width'),
      '#size'          => 10,
      '#default_value' => $this->getSetting('maximumEmbedWidth'),
      '#weight'        => $weight++,
    ];
    $elements['maximumEmbedHeight'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Maximum height'),
      '#size'          => 10,
      '#default_value' => $this->getSetting('maximumEmbedHeight'),
      '#description'   => $this->t("Images this size or smaller will be embedded, while larger images will be left as file URLs. If a maximum is empty or zero, that dimension has no maximum."),
      '#weight'        => $weight++,
    ];

    return $elements;
  }

  /*---------------------------------------------------------------------
   *
   * View.
   *
   *---------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langCode) {
    if (empty($items) === TRUE) {
      return [];
    }

    // Get current settings.
    $maximumEmbedWidth = $this->getSetting('maximumEmbedWidth');
    $maximumEmbedHeight = $this->getSetting('maximumEmbedHeight');

    // Sanitize & validate.
    //
    // Security: The maximum embed width and height have been entered by
    // an administrator. They both should be simple integers and should
    // not include any HTML or HTML entities.
    //
    // Parsing these as integers ignores any extra text that may be in
    // the value.
    if (isset($maximumEmbedWidth) === TRUE) {
      $maximumEmbedWidth = (int) $maximumEmbedWidth;
    }
    else {
      $maximumEmbedWidth = 0;
    }

    if (isset($maximumEmbedHeight) === TRUE) {
      $maximumEmbedHeight = (int) $maximumEmbedHeight;
    }
    else {
      $maximumEmbedHeight = 0;
    }

    // The parent image module does very little processing within the
    // formatter. Instead, it sets a theme template and later processing
    // of the template sets up the image's URL, including possibly use of
    // an image style.
    //
    // Let the parent class do its processing. The returned array has one
    // entry per item and a configuration that invokes the image module's
    // 'image_formatter' theme.
    $elements = parent::viewElements($items, $langCode);

    // Loop through results from the parent class and swap the theme
    // to point to our own 'formatter_suite_image_formatter'. Add the
    // configured maximum width and height. Then let theme handling
    // take over to handle inlining images.
    foreach ($items as $delta => $item) {
      if (isset($elements[$delta]) === TRUE &&
          isset($elements[$delta]['#theme']) === TRUE) {
        $elements[$delta]['#theme'] = 'formatter_suite_image_embed_formatter';
        $elements[$delta]['#maximumEmbedWidth'] = $maximumEmbedWidth;
        $elements[$delta]['#maximumEmbedHeight'] = $maximumEmbedHeight;
      }
    }

    return $elements;
  }

}
