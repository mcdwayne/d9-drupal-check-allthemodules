<?php

namespace Drupal\markdown\Plugin\Filter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter for Markdown.
 *
 * @Filter(
 *   id = "markdown",
 *   title = @Translation("Markdown"),
 *   description = @Translation("Allows content to be submitted using Markdown, a simple plain-text syntax that is filtered into valid HTML."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   weight = -15,
 * )
 */
class Markdown extends FilterBase implements MarkdownFilterInterface {

  /**
   * The Markdown parser.
   *
   * @var \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   */
  protected $parser;

  /**
   * The MarkdownParser Plugin Manager service.
   *
   * @var \Drupal\markdown\MarkdownParsers
   */
  protected $parsers;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->parsers = \Drupal::service('plugin.manager.markdown.parser');
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($name, $default = NULL) {
    $settings = $this->getSettings();
    return isset($settings[$name]) ? $settings[$name] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getParser() {
    if (!isset($this->parser)) {
      $this->parser = $this->parsers->createInstance($this->getSetting('parser', 'thephpleague/commonmark'), ['filter' => $this]);
    }
    return $this->parser;
  }

  /**
   * {@inheritdoc}
   */
  public function getParserSetting($name, $default = NULL) {
    $settings = $this->getParserSettings();
    return isset($settings[$name]) ? $settings[$name] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getParserSettings() {
    return $this->getSetting('parser_settings', []);
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return !!$this->status;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Refactor before release.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $parser_options = [];
    foreach ($this->parsers->getParsers() as $plugin_id => $plugin) {
      // Cast to string for Drupal 7.
      $parser_options[$plugin_id] = (string) $plugin->label();
    }

    // Get the currently set parser.
    $parser = $this->getParser();

    if ($parser_options) {
      $form['parser'] = [
        '#type' => 'select',
        '#title' => $this->t('Parser'),
        '#options' => $parser_options,
        '#default_value' => $parser->getPluginId(),
      ];
    }
    else {
      $form['parser'] = [
        '#type' => 'item',
        '#title' => $this->t('No Markdown Parsers Found'),
        '#description' => $this->t('You need to use composer to install the <a href=":markdown_link">PHP Markdown Lib</a> and/or the <a href=":commonmark_link">CommonMark Lib</a>. Optionally you can use the Library module and place the PHP Markdown Lib in the root library directory, see more in README.', [
          ':markdown_link' => 'https://packagist.org/packages/michelf/php-markdown',
          ':commonmark_link' => 'https://packagist.org/packages/league/commonmark',
        ]),
      ];
    }

    // @todo Add parser specific settings.
//    $form['parser_settings'] = ['#type' => 'container'];
//
//    // Add any specific extension settings.
//    $form['parser_settings']['extensions'] = ['#type' => 'container'];
//    foreach ($parser->extensionList($this) as $plugin_id => $extension) {
//      $form['parser_settings']['extensions'][$plugin_id] = [];
//      $form['parser_settings']['extensions'][$plugin_id] = $extension->settingsForm($form['parser_settings']['extensions'][$plugin_id], $form_state, $this);
//    }

    return $form;
  }

  public static function processTextFormat(&$element, FormStateInterface $form_state, &$complete_form) {
    $formats = filter_formats();
    /** @var \Drupal\filter\FilterFormatInterface $format */
    $format = isset($formats[$element['#format']]) ? $formats[$element['#format']] : FALSE;
    if ($format && ($markdown = $format->filters('markdown')) && $markdown instanceof MarkdownFilterInterface && $markdown->isEnabled()) {
      $element['format']['help']['about'] = [
        '#type' => 'link',
        '#title' => t('@iconStyling with Markdown is supported', [
          // Shamelessly copied from GitHub's Octicon icon set.
          // @todo Revisit this?
          // @see https://github.com/primer/octicons/blob/master/lib/svg/markdown.svg
          '@icon' => new FormattableMarkup('<svg class="octicon octicon-markdown v-align-bottom" viewBox="0 0 16 16" version="1.1" width="16" height="16" aria-hidden="true" style="fill: currentColor;margin-right: 5px;vertical-align: text-bottom;"><path fill-rule="evenodd" d="M14.85 3H1.15C.52 3 0 3.52 0 4.15v7.69C0 12.48.52 13 1.15 13h13.69c.64 0 1.15-.52 1.15-1.15v-7.7C16 3.52 15.48 3 14.85 3zM9 11H7V8L5.5 9.92 4 8v3H2V5h2l1.5 2L7 5h2v6zm2.99.5L9.5 8H11V5h2v3h1.5l-2.51 3.5z"></path></svg>', []),
        ]),
        '#url' => Url::fromRoute('filter.tips_all')->setOptions([
          'attributes' => [
            'class' => ['markdown'],
            'target' => '_blank',
        ]]),
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Only use the parser to process the text if it's not empty.
    if (!empty($text)) {
      $text = (string) $this->getParser()->parse($text, \Drupal::languageManager()->getLanguage($langcode));
    }
    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->getParser()->tips($long);
  }

}
