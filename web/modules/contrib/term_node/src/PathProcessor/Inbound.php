<?php

namespace Drupal\term_node\PathProcessor;

use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\term_node\NodeResolverInterface;
use Drupal\term_node\ResolverInterface;
use Drupal\term_node\TermResolverInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound path using path alias lookups.
 */
class Inbound implements InboundPathProcessorInterface {

  /**
   * The core alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Figures out if a different path should be used.
   *
   * @var TermResolverInterface
   */
  protected $termResolver;

  /**
   * Figures out if a different path should be used.
   *
   * @var NodeResolverInterface
   */
  protected $nodeResolver;

  /**
   * Constructs a Inbound object.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *  An alias manager for looking up the system path.
   * @param ResolverInterface $resolver
   *  Resolves which path to use.
   */
  public function __construct(
    AliasManagerInterface $alias_manager,
    TermResolverInterface $term_resolver,
    NodeResolverInterface $node_resolver
  ) {
    $this->aliasManager = $alias_manager;
    $this->termResolver = $term_resolver;
    $this->nodeResolver = $node_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    // Get the internal path.
    $alias = $request->getPathInfo();
    $alias = $alias === '/' ? $alias : rtrim($request->getPathInfo(), '/');
    $internal_path = $this->aliasManager->getPathByAlias($alias);

    $parts = explode('/', trim($internal_path, '/'));
    $count = count($parts);
    if ($count == 2 && $parts[0] == 'node') {
      // If the node is a term_node, do not redirect to the term path
      // when using the node's own path.
      if ($this->nodeResolver->getReferencedBy($parts[1])) {
        // Don't redirect.
        $request->attributes->add(['_disable_route_normalizer' => TRUE]);
      }
    }
    elseif ($count == 3 && $parts[1] == 'term') {
      // If the term has node referenced, show the node content
      // but do not redirect to the node itself.
      $new_path = $this->termResolver->getPath($path, $parts[2]);
      if ($new_path != $path) {
        $path = $new_path;
        // Don't redirect due to the path changing.
        $request->attributes->add(['_disable_route_normalizer' => TRUE]);
      }
    }

    return $path;
  }

}
