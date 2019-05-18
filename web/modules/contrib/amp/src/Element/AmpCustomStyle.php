<?php

namespace Drupal\amp\Element;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Core\Render\Markup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Element\HtmlTag;

/**
 * Provides a render element for the amp-custom style tag.
 *
 * Properties:
 * - #tag: The tag name to output.
 * - #value: (string, optional) A string containing the textual contents of
 *   the tag.
 *
 * @RenderElement("amp_custom_style")
 */
class AmpCustomStyle extends HtmlTag {

  /**
   * {@inheritdoc}
   */
  public static function preRenderHtmlTag($element) {

    // An HTML tag should not contain any special characters. Escape them to
    // ensure this cannot be abused.
    $escaped_tag = HtmlUtility::escape($element['#tag']);

    // We can't pass amp-custom in as an attribute, a key/value pair will not
    // validate, we must force it to be rendered <style amp-custom>...</style>.
    $open_tag = '<' . $escaped_tag . ' amp-custom>';
    $close_tag = '</' . $escaped_tag . ">\n";

    // Avoid escaping valid css attributes.
    // For instance '.content > li' would be converted to '.content &gt; li'
    $markup = $element['#value'];
    $markup = str_replace('>', 'xxxxxxxxxx', $markup);
    $markup = $markup instanceof MarkupInterface ? $markup : Xss::filterAdmin($markup);
    $markup = str_replace('xxxxxxxxxx', '>', $markup);
    $markup = Markup::create($markup);

    // Avoid re-escaping valid css attributes in later sanitization if $markup
    // is set in #markup by setting the value in #children instead.
    $element['#markup'] = "\n";
    $element['#children'] = $markup;
    $element['#prefix'] = Markup::create($open_tag);
    $element['#suffix'] = Markup::create($close_tag);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderConditionalComments($element) {
    // Browser-specific comments won't apply or work in AMP inline styles.
    return $element;
  }
}
