<?php

/**
 * @file
 * Contains G2 homonyms controller.
 */

namespace Drupal\g2\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\g2\G2;
use Drupal\node\Entity\Node;
use Drupal\views\Entity\View;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class Homonyms contains the controller for the entry list pages.
 *
 * Pages are:
 * - Entries by full name.
 * - Entries by initial.
 */
class Homonyms implements ContainerInjectionInterface {
  const CONFIG_REDIRECT_SINGLE = 'redirect_on_single_match';

  const CONFIG_REDIRECT_STATUS = 'redirect_status';

  /**
   * Title of the G2 by-initial pages.
   */
  const ENTRIES_BY_INITIAL = 'G2 entries starting with initial %initial';

  const VIEW_MODE = 'g2_entry_list';

  /**
   * The g2.settings configuration.
   *
   * @var array
   */
  protected $config;

  /**
   * The entity.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Homonyms constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity.manager service.
   * @param array $config
   *   The g2.settings/controller.homonyms configuration.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, array $config) {
    $this->entityManager = $entity_manager;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /* @var \Drupal\Core\Config\ConfigFactoryInterface  $config_factory */
    $config_factory = $container->get('config.factory');

    $config = $config_factory->get(G2::CONFIG_NAME)->get('controller.homonyms');

    /* @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager */
    $entity_manager = $container->get('entity.manager');
    return new static($entity_manager, $config);
  }

  /**
   * Build a "no match" themed response.
   *
   * @param string $raw_match
   *   The raw, unsafe string requested.
   *
   * @return array<string,array|string>
   *   A render array.
   *
   * @FIXME passing "+" (unquoted) causes notice in getAliasByPath().
   */
  protected function indexNoMatch($raw_match) {
    $message = t('There are currently no entries for %entry.', ['%entry' => $raw_match]);

    $may_create = $this->entityManager->getAccessControlHandler('node')->createAccess(G2::NODE_TYPE);
    if ($may_create) {
      $arguments = [
        'node_type' => G2::NODE_TYPE,
      ];
      $options = [
        'query' => ['title' => urlencode($raw_match)],
      ];
      $offer = t('Would you like to <a href=":url" title="Create new entry for @entry">create one</a> ?', [
        ':url'   => Url::fromRoute('node.add', $arguments, $options)->toString(),
        '@entry' => $raw_match,
      ]);
    }
    else {
      $offer = NULL;
    }

    $result = [
      '#theme' => 'g2_entries',
      '#offer' => $offer,
      '#message' => $message,
    ];

    return $result;
  }

  /**
   * Build a redirect response to the matching G2 entry canonical URL.
   *
   * @param \Drupal\node\NodeInterface[] $g2_match
   *   The match array, containing a single node entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  protected function indexRedirectSingleMatch(array $g2_match) {
    $status = $this->config[static::CONFIG_REDIRECT_STATUS];
    assert('is_int($status) && ($status === 201 || $status >= 300 && $status <= 399)', 'redirect is a redirect');
    assert('count($g2_match) === 0');
    /* @var \Drupal\Core\Entity\EntityInterface $node */
    $node = reset($g2_match);
    $redirect = $node->toUrl()->toString();
    $response = new RedirectResponse($redirect, $status);
    return $response;
  }

  /**
   * Build the generic multi-match themed response.
   *
   * @param string $raw_match
   *   The raw, unsafe string requested.
   * @param \Drupal\node\NodeInterface[] $g2_match
   *   The match array, containing node entities indexed by nid.
   *
   * @return array<string,array|string>
   *   A render array.
   */
  protected function indexMatches($raw_match, array $g2_match) {
    $entries = node_view_multiple($g2_match, 'g2_entry_list');
    $result = [
      '#theme' => 'g2_entries',
      '#raw_entry' => $raw_match,
      '#entries' => $entries,
    ];
    return $result;
  }

  /**
   * Build a homonyms page using a node instead of the match information.
   *
   * This is an old feature, included for compatibility with antique versions,
   * but it is better to avoid it and use a custom route instead, which will be
   * able to take advantage of the converted parameters and have versions code.
   *
   * @param int $nid
   *   The node to use to build the page.
   *
   * @return array<string,array|string>
   *   A render array.
   *
   * @deprecated in Drupal 8.x. Will be removed before 9.x. Use a view instead.
   */
  protected function indexUsingNode($nid) {
    /* @var \Drupal\node\NodeInterface $node */
    $node = Node::load($nid);
    $result = node_view($node, 'g2_homonyms_page');
    return $result;
  }

  /**
   * Build a homonyms page using a view instead of the match information.
   *
   * View is invoked using the unsafe raw_match.
   *
   * @param string $raw_match
   *   The raw, unsafe string requested.
   * @param int $view_id
   *   The id of the view to use.
   *
   * @return array<string,array|string>
   *   A render array.
   */
  protected function indexUsingView($raw_match, $view_id) {
    /* @var \Drupal\views\ViewEntityInterface $view */
    $view = View::load($view_id);
    assert('$view instanceof \Drupal\views\ViewEntityInterface');

    $executable = $view->getExecutable();
    assert('$executable instanceof \Drupal\views\ViewExecutable');

    $result = $executable->access('default')
      ? $executable->preview('default', [$raw_match])
      : [];

    return $result;

  }

  /**
   * Controller for g2.entries.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route
   *   The current route.
   * @param \Drupal\node\NodeInterface[] $g2_match
   *   Unsafe. The entry for which to find matching G2 entries.
   *
   * @return array<string,array|string>|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Render array or redirect response.
   */
  public function indexAction(RouteMatchInterface $route, array $g2_match) {
    $raw_match = $route->getRawParameter('g2_match');

    switch (count($g2_match)) {
      case 0:
        $result = $this->indexNoMatch($raw_match);
        break;

      /* @noinspection PhpMissingBreakStatementInspection */
      case 1:
        $redirect = $this->config[static::CONFIG_REDIRECT_SINGLE];
        if ($redirect) {
          $result = $this->indexRedirectSingleMatch($g2_match);
          break;
        }
        /* Single match handled as any other non-0 number, so fall through. */

      default:
        $use_node = $this->config['nid'] > 0;
        $use_view = !empty($this->config['vid']);
        if ($use_node) {
          $result = $this->indexUsingNode($this->config['nid']);
        }
        elseif ($use_view) {
          $result = $this->indexUsingView($raw_match, $this->config['vid']);
        }
        else {
          $result = $this->indexMatches($raw_match, $g2_match);
        }
        break;
    }
    if (!isset($result)) {
      $result = ['#plain_text' => 'Nix'];
    }
    return $result;

  }

  /**
   * Title callback for g2.entries.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route
   *   The current route match.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup The page title.
   *   The page title.
   */
  public function indexTitle(RouteMatchInterface $route) {
    $raw_match = $route->getRawParameter('g2_match');
    return t('G2 entries matching %entry', ['%entry' => $raw_match]);
  }

}
