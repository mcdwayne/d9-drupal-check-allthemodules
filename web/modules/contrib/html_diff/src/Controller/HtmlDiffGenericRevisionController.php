<?php
/**
 * @file
 * Contains \Drupal\html_diff\Controller\HtmlDiffGenericRevisionController.
 */

namespace Drupal\html_diff\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\PhpStorage\PhpStorageFactory;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\diff\Controller\GenericRevisionController;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\diff\DiffEntityParser;
use Drupal\diff\DiffFormatter;
use Drupal\Component\Plugin\PluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use HtmlDiffAdvancedInterface;

/**
 * Returns responses for Node Revision routes.
 */
class HtmlDiffGenericRevisionController extends GenericRevisionController {

  /**
   * The filter name provided by this controller.
   */
  const FILTER = 'html_diff';

  /**
   * The html diff service.
   *
   * @var \HtmlDiffAdvancedInterface
   */
  protected $htmlDiff;

  /**
   * The current filter in use.
   */
  protected $usedFilter;

  /**
   * Constructs an EntityComparisonBase object.
   *
   * @param DiffFormatter $diff_formatter
   *   Diff formatter service.
   * @param DateFormatter $date
   *   DateFormatter service.
   * @param PluginManagerInterface $plugin_manager
   *   The Plugin manager service.
   * @param DiffEntityParser $entityParser
   *   The diff field builder plugin manager.
   * @param \HtmlDiffAdvancedInterface $html_diff
   *   The html diff service.
   */
  public function __construct(DiffFormatter $diff_formatter, DateFormatter $date, PluginManagerInterface $plugin_manager, DiffEntityParser $entityParser, HtmlDiffAdvancedInterface $html_diff) {
    parent::__construct($diff_formatter, $date, $plugin_manager, $entityParser);
    $storage = PhpStorageFactory::get('html_purifier_serializer');
    if (!$storage->exists('cache.php')) {
      $storage->save('cache.php', 'dummy');
    }
    $html_diff->setPurifierSerializerCachePath(dirname($storage->getFullPath('cache.php')));
    $this->htmlDiff = $html_diff;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('diff.diff.formatter'),
      $container->get('date.formatter'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('diff.entity_parser'),
      $container->get('html_diff.html_diff')
    );
  }

  /**
   * Returns a table which shows the differences between two entity revisions.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityInterface $left_revision
   *   The left revision
   * @param \Drupal\Core\Entity\EntityInterface $right_revision
   *   The right revision.
   * @param string $filter
   *   If $filter == 'raw' raw text is compared (including html tags)
   *   If filter == 'raw-plain' markdown function is applied to the text before comparison.
   *   If filter == 'html-diff' the HTML is rendered for a better visual comparison.
   *
   * @return array
   *   Table showing the diff between the two entity revisions.
   */
  public function compareEntityRevisions(RouteMatchInterface $route_match, EntityInterface $left_revision, EntityInterface $right_revision, $filter) {
    if ($filter == 'html-diff') {
      $filter = 'html_diff';
    }
    $this->usedFilter = $filter;

    $build = parent::compareEntityRevisions($route_match, $left_revision, $right_revision, $filter);
    if ($this->usedFilter != static::FILTER) {
      return $build;
    }

    foreach ($build['diff']['#rows'] as $key => &$row) {
      if ($key >= 3) {
        $row[0]['colspan'] = 4;
      }
    }

    $build['#attached']['library'][] = 'html_diff/diff';

    return $build;

  }

  /**
   * @inheritdoc
   */
  protected function getRows($a, $b, $show_header = FALSE, &$line_stats = NULL) {
    if ($this->usedFilter != static::FILTER) {
      return parent::getRows($a, $b, $show_header, $line_stats);
    }

    $a = is_array($a) ? implode("\n", $a) : $a;
    $b = is_array($b) ? implode("\n", $b) : $b;

    if ($a != $b) {
      $this->htmlDiff->setOldHtml($a);
      $this->htmlDiff->setNewHtml($b);
      $this->htmlDiff->build();
      $diff = $this->htmlDiff->getDifference();
      return [[['data' => ['#markup' => $diff]]]];
    }
    else {
      return [];
    }
  }

  /**
   * @inheritdoc
   */
  protected function buildMarkdownNavigation(EntityInterface $entity, $left_vid, $right_vid, $active_filter) {
    $row = parent::buildMarkdownNavigation($entity, $left_vid, $right_vid, $active_filter);

    $row[0]['data']['#links'][static::FILTER] = [
      'title' => $this->t('Inline'),
      'url' => $this->diffRoute($entity, $left_vid, $right_vid, 'html-diff')
    ];

    // Set the default filter where it is not set, because in our
    // RouteSubscriber we exchange the default filter.
    foreach ($row[0]['data']['#links'] as $link_key => $link) {
      $url = $link['url'];
      if (!isset($url->getRouteParameters()['filter'])) {
        $url->setRouteParameter('filter', 'raw');
      }
    }

    if ($this->usedFilter == static::FILTER) {
      // Set as the first element the current filter.
      $filter = $row[0]['data']['#links'][static::FILTER];
      unset($row[0]['data']['#links'][static::FILTER]);
      array_unshift($row[0]['data']['#links'], $filter);
    }

    return $row;
  }
}
