<?php

/**
 * @file
 * Contains \Drupal\jquery_ui_filter\Plugin\Filter\jQueryUiFilter.
 */

namespace Drupal\jquery_ui_filter\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to generate jQuery UI accordion and tabs widgets.
 *
 * @Filter(
 *   id = "jquery_ui_filter",
 *   module = "jquery_ui_filter",
 *   title = @Translation("jQuery UI accordion and tabs widgets"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class jQueryUiFilter extends FilterBase {

  /**
   * Supported jQuery UI widgets.
   *
   * @var array
   */
  public static $widgets = [
    'accordion' => [
      'title' => 'Accordion',
      'api' => 'https://api.jqueryui.com/accordion/',
      'options' => [
        'headerTag' => 'h3',
        'mediaType' => 'screen',
        'scrollTo' => TRUE,
        'scrollToDuration' => 500,
        'scrollToOffset' => 'auto',
      ],
    ],
    'tabs' => [
      'title' => 'Tabs',
      'api' => 'https://api.jqueryui.com/tabs/',
      'options' => [
        'headerTag' => 'h3',
        'mediaType' => 'screen',
        'scrollTo' => TRUE,
        'scrollToDuration' => 500,
        'scrollToOffset' => 'auto',
      ],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    // Track if widget has been found so that we can attached the
    // jquery_ui_filter library and settings.
    $has_widget = FALSE;
    foreach (self::$widgets as $name => $widget) {
      if (strpos($text, '[' . $name) === FALSE) {
        continue;
      }

      $has_widget = TRUE;

      // Remove block tags around tokens.
      $text = preg_replace('#<(p|div)[^>]*>\s*(\[/?' . $name . '[^]]*\])\s*</\1>#', '\2', $text);

      // Convert opening [token] to opening <div data-ui-*> tag.
      $text = preg_replace_callback('#\[' . $name . '([^]]*)?\]#is', function ($match) use ($name) {
        // Set data-ui-* attributes from role and options.
        $attributes = new Attribute(['data-ui-role' => $name]);
        $options = $this->parseOptions($match[1]);
        foreach ($options as $name => $value) {
          $attributes->setAttribute('data-ui-' . $name, $value);
        }
        return "<div$attributes>";
      }, $text);

      // Convert closing [/token] to closing </div> tag.
      $text = str_replace('[/' . $name . ']', '</div>', $text);
    }

    if ($has_widget) {
      $result->setAttachments([
        'library' => ['jquery_ui_filter/jquery_ui_filter'],
        'drupalSettings' => ['jquery_ui_filter' => \Drupal::config('jquery_ui_filter.settings')->get()],
      ]);
      $result->addCacheableDependency(\Drupal::config('jquery_ui_filter.settings'));
    }

    return $result->setProcessedText($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      $html = '<p>' . $this->t('You can create jQuery UI accordion or tabs by inserting  <code>[accordion]</code> or <code>[tabs]</code> wrappers. Examples:') . '</p>';
      $html .= '<ul>';
      foreach (self::$widgets as $name => $widget) {
        $t_args = [
          '@title' => $widget['title'],
          '@name' => $name,
          '@tag' => \Drupal::config('jquery_ui_filter.settings')->get($name . '.options.headerTag') ?: 'h3',
          '@href' => "http://jqueryui.com/demos/$name/",
        ];
        $html .= '<li>' . $this->t('Use <code>[@name]</code> and <code>[/@name]</code> with <code>&lt;@tag&gt;</code> header tags to create a jQuery UI <a href="@href">@title</a> widget.', $t_args) . '</li>';
      }
      $html .= '</ul>';
      return $html;
    }
    else {
      return '<p>' . $this->t('You can create jQuery UI accordion or tabs by inserting <code>[accordion]</code> or <code>[tabs]</code> token wrappers.') . '</p>';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['settings'] = [
      '#markup' => $this->t('See the <a href="@href">jQuery UI filter</a> settings form to modify the accordion and tabs widget\'s global settings', ['@href' => Url::fromRoute('jquery_ui_filter.settings')->toString()]),
    ];
    return $form;
  }

  /**
   * Parse options from an attributes string.
   *
   * @param string $text
   *   A string of options.
   *
   * @return array
   *   An associative array of parsed name/value pairs.
   */
  public function parseOptions($text) {
    // Decode special characters.
    $text = html_entity_decode($text);

    // Convert decode &nbsp; to expected ASCII code 32 character.
    // See: http://stackoverflow.com/questions/6275380/does-html-entity-decode-replaces-nbsp-also-if-not-how-to-replace-it
    $text = str_replace("\xA0", ' ', $text);

    // Convert camel case to hyphen delimited because HTML5 lower cases all
    // data-* attributes.
    // See: Drupal.jQueryUiFilter.getOptions.
    $text = strtolower(preg_replace('/([a-z])([A-Z])/', '\1-\2', $text));

    // Create a DomElement so that we can parse its attributes as options.
    $html = Html::load('<div ' . $text . ' />');
    $dom_node = $html->getElementsByTagName('div')->item(0);

    $options = [];
    foreach ($dom_node->attributes as $attribute_name => $attribute_node) {
      // Convert empty attributes (ie nothing inside the quotes) to 'true' string.
      $options[$attribute_name] = $attribute_node->nodeValue ?: 'true';
    }
    return $options;
  }

}
