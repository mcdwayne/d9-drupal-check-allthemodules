<?php

namespace Drupal\google_analytics_counter\Plugin\Filter;

use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\State\StateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\google_analytics_counter\GoogleAnalyticsCounterAppManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add filter to show google analytics counter number.
 *
 * @Filter(
 *   id = "google_analytics_counter_filter",
 *   title = @Translation("Google Analytics Counter token"),
 *   description = @Translation("Adds a token with pageview counts. Use <code>[gac]</code> or <code>[gac|all]</code>."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class GoogleAnalyticsCounterFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * An alias manager for looking up the system path and path alias.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The state where all the tokens are saved.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Drupal\google_analytics_counter\GoogleAnalyticsCounterCommon definition.
   *
   * @var \Drupal\google_analytics_counter\GoogleAnalyticsCounterAppManagerInterface
   */
  protected $appManager;

  /**
   * Constructs a new SiteMaintenanceModeForm.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   An alias manager for looking up the system path.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state of the drupal site.
   * @param \Drupal\google_analytics_counter\GoogleAnalyticsCounterAppManagerInterface $app_manager
   *   Google Analytics Counter App Manager object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentPathStack $current_path, AliasManagerInterface $alias_manager, StateInterface $state, GoogleAnalyticsCounterAppManagerInterface $app_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentPath = $current_path;
    $this->aliasManager = $alias_manager;
    $this->state = $state;
    $this->appManager = $app_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.current'),
      $container->get('path.alias_manager'),
      $container->get('state'),
      $container->get('google_analytics_counter.app_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $text = $this->handleText($text);
    return new FilterProcessResult($text);
  }

  /**
   * Finds 'gac' tags and replaces them by actual values.
   *
   * @param string $text
   *   String to replace.
   *
   * @return mixed
   *   Replaced string.
   */
  private function handleText($text) {
    $matchlink = '';
    $original_match = '';
    // This allows more than one pipe sign (|) ...
    // does not hurt and leaves room for possible extension.
    preg_match_all("/(\[)gac[^\]]*(\])/s", $text, $matches);

    foreach ($matches[0] as $match) {
      // Keep original value(s).
      $original_match[] = $match;

      // Display the page views.
      // [gac] will detect the current node's count.
      //
      // [gac|all] displays the totalsForAllResults for the given time period,
      // assuming cron has been run. Otherwise will print N/A.
      //
      // [gac|1234] displays the page views for node/1234. // Currently not working.
      //
      // [gac|node/1234] displays the page views for node/1234. // Currently not working.
      //
      // [gac|path/to/page] displays the pages views for path/to/page. // Currently not working.
      switch ($match) {
        case '[gac]':
          $matchlink[] = $this->appManager->gacDisplayCount($this->currentPath->getPath());
          break;

        case '[gac|all]':
          $matchlink[] = number_format($this->state->get('google_analytics_counter.total_pageviews', 'N/A'));
          break;

        default:
          $path = substr($match, strpos($match, "/") + 1);
          $path = rtrim($path, ']');

          // Make sure the path starts with a slash.
          $path = '/' . trim($path, ' /');
          $matchlink[] = $this->appManager->gacDisplayCount($this->aliasManager->getAliasByPath($path));
          break;
      }
    }

    return str_replace($original_match, $matchlink, $text);
  }

}
