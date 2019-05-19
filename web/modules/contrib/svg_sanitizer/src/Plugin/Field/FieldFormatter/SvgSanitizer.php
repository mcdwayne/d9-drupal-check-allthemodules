<?php

namespace Drupal\svg_sanitizer\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\svg_sanitizer\SvgSanitizerAttributes;
use Drupal\svg_sanitizer\SvgSanitizerTags;
use enshrined\svgSanitize\Sanitizer;

/**
 * Plugin implementation of the 'entity reference rendered entity' formatter.
 *
 * @FieldFormatter(
 *   id = "svg_sanitizer",
 *   label = @Translation("SVG Sanitizer"),
 *   description = @Translation("Makes the SVG Sanitizer library available to Drupal."),
 *   field_types = {
 *     "file",
 *     "svg_icon",
 *   }
 * )
 */

class SvgSanitizer extends FormatterBase {

  /**
   * Builds a renderable array for a field value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      if (!$item->entity) {
        continue;
      }
      // Get the contents of the svg
      $svg_path = $item->entity->getFileUri();
      if (file_exists($svg_path)) {
        $svg = file_get_contents($svg_path);

        // Remove all of the bad stuff from the svg.
        $svg_clean = $this->sanitize($svg);

        $element[$delta] = [
          '#type' => 'markup',
          '#markup' => Markup::create($svg_clean),
        ];
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['allowedattrs'] = [
      '#title' => $this->t('Allowed Attributes'),
      '#type' => 'textarea',
      '#description' => $this->t('Comma separated list of custom attributes that should be added to the list of allowed attributes'),
      '#default_value' => $this->getSetting('allowedattrs'),
    ];

    $form['allowedtags'] = [
      '#title' => $this->t('Allowed Tags'),
      '#type' => 'textarea',
      '#description' => $this->t('Comma separated list of custom tags that should be added to the list of allowed tags'),
      '#default_value' => $this->getSetting('allowedtags'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'allowedtags' => '',
      'allowedattrs' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $return = [];
    $return[] = $this->t('Custom tags: %tags', [
      '%tags' => $this->getSetting('allowedtags') ?: $this->t('None'),
    ]);
    $return[] = $this->t('Custom attributes: %attributes', [
      '%attributes' => $this->getSetting('allowedattrs') ?: $this->t('None'),
    ]);

    return $return;
  }

  /**
   * Sanitizes contents.
   *
   * @param string $svg
   *   SVG contents.
   *
   * @return string
   *   Markup.
   */
  protected function sanitize($svg) {
    // Instantiate the sanitizer class
    $sanitizer = new Sanitizer();

    // Get newly defined tags
    SvgSanitizerTags::setTags($this->getSetting('allowedtags'));
    $tags = new SvgSanitizerTags();
    $sanitizer->setAllowedTags($tags);

    // get newly defined attributes
    SvgSanitizerAttributes::setAttributes($this->getSetting('allowedattrs'));
    $attributes = new SvgSanitizerAttributes();
    $sanitizer->setAllowedAttrs($attributes);

    // run the svg through the sanitizer
    $clean_svg = $sanitizer->sanitize($svg);

    return $clean_svg;
  }


}
