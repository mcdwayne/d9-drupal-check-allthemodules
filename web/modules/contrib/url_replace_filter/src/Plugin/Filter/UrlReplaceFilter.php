<?php

namespace Drupal\url_replace_filter\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Text filter for highlighting PHP source code.
 *
 * @Filter(
 *   id = "url_replace_filter",
 *   description = @Translation("Allows administrators to replace the base URL in &lt;img&gt; and &lt;a&gt; elements."),
 *   module = "url_replace_filter",
 *   title = @Translation("URL Replace filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   settings = {
 *     "replacements" = ""
 *   }
 * )
 */
class UrlReplaceFilter extends FilterBase implements ContainerFactoryPluginInterface {

  const ID = 'url_replace_filter';
  const SETTING_NAME = 'replacements';

  /**
   * The current_route_match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * UrlReplaceFilter constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   Optional: the current_route_match service. Only used by the settings
   *   form.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Optional: the messenger service. Only used by the settings form.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    CurrentRouteMatch $currentRouteMatch = NULL,
    MessengerInterface $messenger = NULL
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $currentRouteMatch;
    $this->messenger = $messenger;

    $this->base = base_path();
  }

  /**
   * Helper to build rows in the table built by _url_replace_filter_settings().
   *
   * @param array $form
   *   The form array.
   * @param int $index
   *   The format index.
   * @param string $original
   *   The original value to be replaced.
   * @param string $replacement
   *   The replacement for the original value.
   *
   * @return array
   *   A form array.
   */
  protected function buildRowForm(array $form, int $index, string $original, string $replacement) {
    $form[self::SETTING_NAME]["replacement-{$index}"]['original'] = [
      '#type' => 'textfield',
      '#size' => 50,
      '#default_value' => $original,
    ];
    $form[self::SETTING_NAME]["replacement-{$index}"]['replacement'] = [
      '#type' => 'textfield',
      '#size' => 50,
      '#default_value' => $replacement,
    ];

    return $form;
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
    $messenger = $container->get('messenger');
    $currentRouteMatch = $container->get('current_route_match');
    return new static($configuration, $plugin_id, $plugin_definition, $currentRouteMatch, $messenger);
  }

  /**
   * Return the list of input formats containing the active URL Replace filter.
   *
   * @return array
   *   The list of input format names, keyed by format_id.
   */
  public static function getFormats() {
    $formats = FilterFormat::loadMultiple();

    $ret = [];
    /** @var \Drupal\filter\FilterFormatInterface $format */
    foreach ($formats as $format_id => $format) {
      /** @var \Drupal\filter\FilterPluginCollection $filterCollection */
      $filterCollection = $format->filters();
      if (!$filterCollection->has(static::ID)) {
        continue;
      }

      /** @var \Drupal\filter\Plugin\FilterInterface $filter */
      $filter = $filterCollection->get(static::ID);
      if ($filter->getConfiguration()['status'] ?? FALSE) {
        $ret[$format_id] = $format->label();
      }
    }

    return $ret;
  }

  /**
   * Implements hook_help().
   */
  public static function help(string $routeName) {
    if ($routeName !== 'filter.admin_overview') {
      return;
    }

    return t('To configure url_replace_filter, enable its checkbox in each text format where you want it, then configure its rewriting rules in the vertical tab which will appear at the bottom of the format configuration page. The module does not contain any global configuration.');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $empty = 0;
    $form[self::SETTING_NAME] = [
      '#type' => 'details',
      '#description' => $this->t("
<p>Enter original base URLs and their replacements. Matching not case-sensitive. 
  You may use <em>%baseurl</em> in the replacement string to insert your site's 
  base URL (without the trailing slash).</p>

<p><strong>Warning</strong>: To avoid unexpected results, you must include
  trailing slashes in both the original and replacement strings.</p>
 
<p><strong>Warning</strong>: Replacements are executed in the order you give 
  them. Place the most specific URLs first. For example, 
  <em>http://example.com/somepath/</em> should be replaced before 
  <em>http://example.com/</em>.</p>
  
<p>If you need more replacement rules, more fields will be added after saving 
  the settings.</p>"),

      '#title' => $this->t('URL Replace Filter'),
      '#open' => TRUE,
      '#theme' => 'url_replace_filter_settings_form',
    ];
    $form['#element_validate'][] = [$this, 'settingsFormValidate'];

    $stringSettings = $this->settings[self::SETTING_NAME];
    $settings = $stringSettings ? array_values(unserialize($stringSettings)) : [];

    foreach ((array) $settings as $index => $setting) {
      $form = $this->buildRowForm($form, $index, $setting['original'], $setting['replacement']);
      if (!$setting['original']) {
        $empty++;
      }
    }

    // Append up to 3 empty fields.
    $index = count($settings);
    while ($empty < 3) {
      $form = $this->buildRowForm($form, $index, '', '');
      $index++;
      $empty++;
    }

    return $form;
  }

  /**
   * Element_validate handler for settingsForm().
   *
   * Remove useless empty settings to keep variable as small as possible.
   *
   * Needs to be public to be usable as a #element_validate callback.
   */
  public function settingsFormValidate(array $form, FormStateInterface &$form_state) {
    $settings = $form_state->getValue('filters')[self::ID]['settings'][self::SETTING_NAME];

    $validSettings = array_filter($settings, function (array $setting) {
      return !(empty($setting['original']) && empty($setting['replacement']));
    });

    $result = serialize(array_values($validSettings));
    $form_state->setValue([
      'filters',
      self::ID,
      'settings',
      self::SETTING_NAME,
    ], $result);

    if (empty($validSettings)) {
      $parameterName = 'filter_format';
      /** @var \Drupal\filter\FilterFormatInterface $parameterValue */
      $parameterValue = $this->currentRouteMatch->getParameter($parameterName);
      $this->messenger->addMessage($this->t('URL Replace filter configuration is empty for @format: you could <a href=":edit">remove it</a> from this input format.', [
        '@format' => $parameterValue->label(),
        ':edit' => Url::fromRoute('entity.filter_format.edit_form', [
          $parameterName => $parameterValue->id(),
        ])->toString(),
      ]), MessengerInterface::TYPE_WARNING);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('Selected URLs may be rewritten by url_rewrite_filter.');
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $settings = unserialize($this->settings['replacements']);
    foreach ($settings as $setting) {
      if (!empty($setting['original'])) {
        $pattern = '!((<a\s[^>]*href)|(<img\s[^>]*src))\s*=\s*"' . preg_quote($setting['original']) . '!iU';
        if (preg_match_all($pattern, $text, $matches)) {
          $replacement = str_replace('%baseurl', rtrim(base_path(), '/'), $setting['replacement']);
          foreach ($matches[0] as $key => $match) {
            $text = str_replace($match, $matches[1][$key] . '="' . $replacement, $text);
          }
        }
      }
    }

    return new FilterProcessResult($text);
  }

}
