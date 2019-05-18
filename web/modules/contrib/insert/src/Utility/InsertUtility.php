<?php

namespace Drupal\insert\Utility;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;

class InsertUtility {

  /**
   * @param string $pluginId
   * @param string|array (optional) $insertTypes
   * @return bool
   */
  public static function isSourceWidget($pluginId, $insertTypes = null) {
    return in_array($pluginId, static::getSourceWidgets($insertTypes));
  }

  /**
   * @param string|array (optional) $insertTypes
   * @return string[]
   */
  protected static function getSourceWidgets($insertTypes = null) {
    if (is_string($insertTypes)) {
      $insertTypes = [$insertTypes];
    }

    $sources = \Drupal::moduleHandler()->invokeAll('insert_widgets');
    $widgets = [];

    foreach ($sources as $insertType => $widgetIds) {
      if (
        count($widgetIds) > 0
        && ($insertTypes === null || in_array($insertType, $insertTypes))
      ) {
        $widgets = array_merge($widgets, $widgetIds);
      }
    }

    return $widgets;
  }

  /**
   * @param string $insertType
   * @return array
   */
  public static function aggregateStyles($insertType) {
    $styles = \Drupal::moduleHandler()->invokeAll(
      'insert_styles',
      [$insertType]
    );

    uasort($styles, function($a, $b) {
      $weightA = !($a instanceof ImageStyle) && isset($a['weight'])
        ? $a['weight'] : 0;
      $weightB = !($b instanceof ImageStyle) && isset($b['weight'])
        ? $b['weight'] : 0;
      if ($weightA === 0 && $weightB === 0) {
        $labelA = $a instanceof ImageStyle ? $a->label() : $a['label'];
        $labelB = $b instanceof ImageStyle ? $b->label() : $b['label'];
        return strcasecmp($labelA, $labelB);
      }
      return $weightA < $weightB ? -1 : 1;
    });

    return $styles;
  }

  /**
   * @param array $stylesList
   * @return array
   */
  public static function stylesListToOptions(array $stylesList) {
    foreach ($stylesList as $styleName => $style) {
      /* @var ImageStyle|array $style */
      $stylesList[$styleName] = is_array($style)
        ? $style['label']
        : $style->label();
    }
    return $stylesList;
  }

  /**
   * An #element_validate function lists on the settings form.
   * Since, when all list items are activated, items added later on should be
   * enabled by default, the setting value needs to be changed to be able to
   * detect that all items were enabled when having set the value the last time.
   *
   * @param array $element
   * @param FormStateInterface $form_state
   */
  public static function validateList(array $element, FormStateInterface &$form_state) {
    if (array_key_exists('#options', $element)
      && array_values($element['#value']) == array_keys($element['#options'])
    ) {
      $form_state->setValue('<all>', '<all>');
    }
  }

  /**
   * @param FileInterface $file
   * @return bool
   */
  public static function isImage($file) {
    /** @var \Drupal\Core\Image\Image $image */
    $image = \Drupal::service('image.factory')->get($file->getFileUri());

    return $image->isValid();
  }

  /**
   * @param FileInterface $file
   * @param string $styleName
   * @param bool (optional) $absolute
   * @return null|string
   */
  public static function buildDerivativeUrl(FileInterface $file, $styleName, $absolute = FALSE) {
    /** @var ImageStyle $style */
    $style = ImageStyle::load($styleName);

    if ($style !== null) {
      $url = $style->buildUrl($file->getFileUri());
      if (!$absolute) {
        $parsedUrl = parse_url($url);
        $url = $parsedUrl['path'];
        if (!empty($parsedUrl['query'])) {
          $url .= '?' . $parsedUrl['query'];
        }
      }
      return $url;
    }

    return null;
  }

  /**
   * Adds allowed content (tags, attributes) to the editor settings.
   *
   * @param array $settings
   * @param array $extraAllowedContent
   */
  public static function addEditorExtraAllowedContent(array &$settings, array $extraAllowedContent) {
    $config = \Drupal::config('insert.config');
    $text_formats = $config->get('text_formats');

    foreach (array_keys($settings['editor']['formats']) as $text_format_id) {
      if (in_array($text_format_id, $text_formats)) {
        static::combineEditorExtraAllowedContent(
          $settings['editor']['formats'][$text_format_id]['editorSettings']['extraAllowedContent'],
          $extraAllowedContent
        );
      }
    }
  }

  /**
   * @param string|null $extraAllowedContent
   * @param array $additionalAllowedContent
   */
  protected static function combineEditorExtraAllowedContent(&$extraAllowedContent, array $additionalAllowedContent) {
    $additionalAllowedContent = join('; ', $additionalAllowedContent);
    if ($extraAllowedContent === null) {
      $extraAllowedContent = $additionalAllowedContent;
      return;
    }
    $extraAllowedContent .= '; ' . $additionalAllowedContent;
  }

  /**
   * Adds allowed HTML tags and attributes to a HTML validation string.
   *
   * Additional validation for the filter format edit form.
   * This function is supposed to alter the allowed HTML filter tags and
   * attributes settings as to what is required for the Insert module to work
   * properly. To prevent confusion, this should be done minimally invasive. The
   * tag an attribute detection logic is copied over from
   * \Drupal\filter\Plugin\Filter\FilterHtml.
   * A cleaner, though rather less usable, method would be an individual Filter
   * extending FilterHtml overwriting FilterHtml::getHtmlRestrictions with
   * adding necessary tags and attributes to $restrictions['allowed'].
   * @see \Drupal\filter\Plugin\Filter\FilterHtml::prepareAttributeValues
   *
   * @param string $value
   * @param array $tags
   * @param array $attributes
   * @return string
   */
  public static function addAllowedHtml($value, array $tags, array $attributes) {
    // see \Drupal\filter\Plugin\Filter\FilterHtml::prepareAttributeValues
    $html = str_replace('>', ' />', $value);
    $star_protector = '__zqh6vxfbk3cg__';
    $html = str_replace('*', $star_protector, $html);
    $body_child_nodes = Html::load($html)->getElementsByTagName('body')->item(0)->childNodes;

    // Detect which tags an attributes are allowed already.
    foreach ($body_child_nodes as $node) {
      if ($node->nodeType !== XML_ELEMENT_NODE) {
        continue;
      }
      $tag = $node->tagName;

      if (array_key_exists($tag, $tags)) {
        $tags[$tag] = TRUE;
      }
      else {
        continue;
      }

      /** @var \DOMNode $node */
      if ($node->hasAttributes()) {
        foreach ($node->attributes as $name => $attribute) {
          // see \Drupal\filter\Plugin\Filter\FilterHtml::prepareAttributeValues
          $name = str_replace($star_protector, '*', $name);
          $allowed_attribute_values = preg_split('/\s+/', str_replace($star_protector, '*', $attribute->value), -1, PREG_SPLIT_NO_EMPTY);
          $allowed_attribute_values = array_filter($allowed_attribute_values, function ($value) { return $value !== '*'; });

          // $allowed_attribute_values needs to be empty to allow all values.
          if (array_key_exists($name, $attributes[$tag])) {
            $attributes[$tag][$name] = empty($allowed_attribute_values);
          }
        }
      }
    }

    // Add missing tags and attributes required by the Insert module. This is done
    // using string parsing as the actually saved string should be altered as
    // minimally as possible.
    foreach ($tags as $tag => $found_tag) {
      if (!$found_tag) {
        $value .= ' <' . $tag . '>';
      }
      foreach ($attributes[$tag] as $name => $found_attribute) {
        if ($found_attribute === TRUE) {
          // The attribute is set already and allows all values.
          continue;
        }
        elseif ($found_attribute === null) {
          // The attribute is not yet set, just add it.
          $value = preg_replace('/<' . $tag . '/', '<' . $tag . ' ' . $name, $value);
        }
        else {
          // The attribute is set but limited to particular values; Remove that
          // limitation.
          $value = preg_replace('/(<' . $tag . '[^>]+' . $name . ')(=("|\')[^"\']+("|\'))/', '$1', $value);
        }

      }
    }

    return $value;
  }

}