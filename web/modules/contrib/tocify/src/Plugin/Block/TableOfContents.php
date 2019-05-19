<?php

namespace Drupal\tocify\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Provides a 'TableOfContents' block.
 *
 * @Block(
 *  id = "tocify_table_of_contents",
 *  admin_label = @Translation("Table of contents"),
 * )
 */
class TableOfContents extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * ConfigManager definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config manager definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactory $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $defaults = $this->configFactory
      ->getEditable('tocify.settings');

    $form['theme'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Theme'),
      '#description' => $this->t('Choose the theme, e.g. "bootstrap", "jqueryui" or "none"'),
      '#default_value' => isset($this->configuration['_theme']) ? $this->configuration['_theme'] : $defaults->get('_theme'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['context'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Context'),
      '#description' => $this->t('Choose any valid jQuery selector, e.g. "body"'),
      '#default_value' => isset($this->configuration['_context']) ? $this->configuration['_context'] : $defaults->get('_context'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['selectors'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Selectors'),
      '#description' => $this->t('Each comma separated selector must be a header element, e.g. "h1,h2,h3"'),
      '#default_value' => isset($this->configuration['selectors']) ? $this->configuration['selectors'] : $defaults->get('selectors'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['show_and_hide'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Show and hide'),
      '#description' => $this->t('Should elements be shown and hidden, e.g. "true" or "false"'),
      '#default_value' => isset($this->configuration['show_and_hide']) ? $this->configuration['show_and_hide'] : $defaults->get('show_and_hide'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['show_effect'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Show effect'),
      '#description' => $this->t('Any of the jQuery show effects, e.g. "none", "fadeIn", "show", or "slideDown"'),
      '#default_value' => isset($this->configuration['show_effect']) ? $this->configuration['show_effect'] : $defaults->get('show_effect'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['show_effect_speed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Show effect speed'),
      '#description' => $this->t('The time duration of the show effect, e.g. "slow", "medium", "fast", or any numeric number (milliseconds)'),
      '#default_value' => isset($this->configuration['show_effect_speed']) ? $this->configuration['show_effect_speed'] : $defaults->get('show_effect_speed'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['hide_effect'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hide effect'),
      '#description' => $this->t('Any of the jQuery hide effects, e.g. "none", "fadeOut", "hide" or "slideUp"'),
      '#default_value' => isset($this->configuration['hide_effect']) ? $this->configuration['hide_effect'] : $defaults->get('hide_effect'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['hide_effect_speed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hide effect speed'),
      '#description' => $this->t('The time duration of the hide effect, e.g. "slow", "medium", "fast", or any numeric number (milliseconds)'),
      '#default_value' => isset($this->configuration['hide_effect_speed']) ? $this->configuration['hide_effect_speed'] : $defaults->get('hide_effect_speed'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['smooth_scroll'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Smooth scroll'),
      '#description' => $this->t('Animates the page scroll when specific table of content items are clicked and the page moves up or down, e.g. "true" or "false"'),
      '#default_value' => isset($this->configuration['smooth_scroll']) ? $this->configuration['smooth_scroll'] : $defaults->get('smooth_scroll'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['smooth_scroll_speed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Smooth scroll speed'),
      '#description' => $this->t('The time duration of the animation'),
      '#default_value' => isset($this->configuration['smooth_scroll_speed']) ? $this->configuration['smooth_scroll_speed'] : $defaults->get('smooth_scroll_speed'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['scroll_to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Scroll to'),
      '#description' => $this->t('The amount of space between the top of page and the selected table of contents item after the page has been scrolled'),
      '#default_value' => isset($this->configuration['scroll_to']) ? $this->configuration['scroll_to'] : $defaults->get('scroll_to'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['show_and_hide_on_scroll'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Show and hide on scroll'),
      '#description' => $this->t('Determines if table of content nested items should be shown and hidden while a user scrolls the page'),
      '#default_value' => isset($this->configuration['show_and_hide_on_scroll']) ? $this->configuration['show_and_hide_on_scroll'] : $defaults->get('show_and_hide_on_scroll'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['highlight_on_scroll'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Highlight on scroll'),
      '#description' => $this->t('Determines if table of content nested items should be highlighted while scrolling'),
      '#default_value' => isset($this->configuration['highlight_on_scroll']) ? $this->configuration['highlight_on_scroll'] : $defaults->get('highlight_on_scroll'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['highlight_offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Highlight offset'),
      '#description' => $this->t('The offset distance in pixels to trigger the next active table of contents item'),
      '#default_value' => isset($this->configuration['highlight_offset']) ? $this->configuration['highlight_offset'] : $defaults->get('highlight_offset'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['extend_page'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extend page'),
      '#description' => $this->t('If a user scrolls to the bottom of the page and the page is not tall enough to scroll to the last table of contents item, then the page height is increased'),
      '#default_value' => isset($this->configuration['extend_page']) ? $this->configuration['extend_page'] : $defaults->get('extend_page'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['extend_page_offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extend page offset'),
      '#description' => $this->t('How close to the bottom of the page a user must scroll before the page is extended'),
      '#default_value' => isset($this->configuration['extend_page_offset']) ? $this->configuration['extend_page_offset'] : $defaults->get('extend_page_offset'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['history'] = [
      '#type' => 'textfield',
      '#title' => $this->t('History'),
      '#description' => $this->t('Adds a hash to the page url to maintain history'),
      '#default_value' => isset($this->configuration['history']) ? $this->configuration['history'] : $defaults->get('history'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['hash_generator'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Hash generator"),
      '#description' => $this->t("How the URL hash value get's generated"),
      '#default_value' => isset($this->configuration['hash_generator']) ? $this->configuration['hash_generator'] : $defaults->get('hash_generator'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['highlight_default'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Highlight default'),
      '#description' => $this->t("Set's the first table of content item as active if no other item is active"),
      '#default_value' => isset($this->configuration['highlight_default']) ? $this->configuration['highlight_default'] : $defaults->get('highlight_default'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['ignore_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ignore selector'),
      '#description' => $this->t('Elements that you do not want to be used to generate the table of contents'),
      '#default_value' => isset($this->configuration['ignore_selector']) ? $this->configuration['ignore_selector'] : $defaults->get('ignore_selector'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['scroll_history'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Scroll history'),
      '#description' => $this->t('Adds a hash to the page URL, to maintain history, when scrolling to a table of contents item'),
      '#default_value' => isset($this->configuration['scroll_history']) ? $this->configuration['scroll_history'] : $defaults->get('scroll_history'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['_theme'] = $form_state->getValue('theme');
    $this->configuration['_context'] = $form_state->getValue('context');
    $this->configuration['selectors'] = $form_state->getValue('selectors');
    $this->configuration['show_and_hide'] = $form_state->getValue('show_and_hide');
    $this->configuration['show_effect'] = $form_state->getValue('show_effect');
    $this->configuration['show_effect_speed'] = $form_state->getValue('show_effect_speed');
    $this->configuration['hide_effect'] = $form_state->getValue('hide_effect');
    $this->configuration['hide_effect_speed'] = $form_state->getValue('hide_effect_speed');
    $this->configuration['smooth_scroll'] = $form_state->getValue('smooth_scroll');
    $this->configuration['smooth_scroll_speed'] = $form_state->getValue('smooth_scroll_speed');
    $this->configuration['scroll_to'] = $form_state->getValue('scroll_to');
    $this->configuration['show_and_hide_on_scroll'] = $form_state->getValue('show_and_hide_on_scroll');
    $this->configuration['highlight_on_scroll'] = $form_state->getValue('highlight_on_scroll');
    $this->configuration['highlight_offset'] = $form_state->getValue('highlight_offset');
    $this->configuration['extend_page'] = $form_state->getValue('extend_page');
    $this->configuration['extend_page_offset'] = $form_state->getValue('extend_page_offset');
    $this->configuration['history'] = $form_state->getValue('history');
    $this->configuration['hash_generator'] = $form_state->getValue('hash_generator');
    $this->configuration['highlight_default'] = $form_state->getValue('highlight_default');
    $this->configuration['ignore_selector'] = $form_state->getValue('ignore_selector');
    $this->configuration['scroll_history'] = $form_state->getValue('scroll_history');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#theme' => 'tableofcontents',
      '#_theme' => $this->configuration['_theme'],
      '#context' => $this->configuration['_context'],
      '#selectors' => $this->configuration['selectors'],
      '#show_and_hide' => $this->formatBoolean($this->configuration['show_and_hide']),
      '#show_effect' => $this->configuration['show_effect'],
      '#show_effect_speed' => $this->configuration['show_effect_speed'],
      '#hide_effect' => $this->configuration['hide_effect'],
      '#hide_effect_speed' => $this->configuration['hide_effect_speed'],
      '#smooth_scroll' => $this->formatBoolean($this->configuration['smooth_scroll']),
      '#smooth_scroll_speed' => $this->configuration['smooth_scroll_speed'],
      '#scroll_to' => (string) $this->configuration['scroll_to'],
      '#show_and_hide_on_scroll' => $this->formatBoolean($this->configuration['show_and_hide_on_scroll']),
      '#highlight_on_scroll' => $this->formatBoolean($this->configuration['highlight_on_scroll']),
      '#highlight_offset' => (string) $this->configuration['highlight_offset'],
      '#extend_page' => $this->formatBoolean($this->configuration['extend_page']),
      '#extend_page_offset' => (string) $this->configuration['extend_page_offset'],
      '#history' => $this->formatBoolean($this->configuration['history']),
      '#hash_generator' => $this->configuration['hash_generator'],
      '#highlight_default' => $this->formatBoolean($this->configuration['highlight_default']),
      '#ignore_selector' => $this->configuration['ignore_selector'],
      '#scroll_history' => $this->formatBoolean($this->configuration['scroll_history']),
      '#attached' => [
        'library' => [
          'tocify/tocify',
        ],
      ],
    ];
    return $build;
  }

  /**
   * Format a boolean as string.
   *
   * @param bool $bool
   *   A boolean to be reformatted as string.
   *
   * @return string
   *   A string in the form of 'true' or 'false'.
   */
  private function formatBoolean($bool) {
    return $bool ? 'true' : 'false';
  }

}
