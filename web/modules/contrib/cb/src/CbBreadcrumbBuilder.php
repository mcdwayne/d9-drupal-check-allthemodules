<?php

namespace Drupal\cb;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\token\TokenEntityMapperInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\RouteCompiler;

/**
 * Class to define the cb breadcrumb builder.
 */
class CbBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The cb_breadcrumb entity which will be applied.
   *
   * @var \Drupal\cb\Entity\Breadcrumb
   */
  protected $breadcrumb = NULL;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Token entity mapper.
   *
   * @var \Drupal\token\TokenEntityMapperInterface
   */
  protected $tokenEntityMapper;

  /**
   * Constructs a CbBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   The token utility.
   * @param \Drupal\token\TokenEntityMapperInterface $token_entity_mapper
   *   The token entity mapper.
   */
  public function __construct(Token $token, TokenEntityMapperInterface $token_entity_mapper) {
    $this->token = $token;
    $this->tokenEntityMapper = $token_entity_mapper;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $breadcrumbs = $this->getBreadcrumbs($route_match->getRouteObject()->getPath());
    if (!empty($breadcrumbs)) {
      foreach ($breadcrumbs as $breadcrumb) {
        if (($breadcrumb->applies() != '' && eval($breadcrumb->applies())) || $breadcrumb->applies() == '') {
          $this->breadcrumb = $breadcrumb;
        }
      }
    }

    return !is_null($this->breadcrumb) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $path_validator = \Drupal::service('path.validator');
    $data = [];
    $breadcrumb = new Breadcrumb();
    if (!is_null($this->breadcrumb->cache_contexts->value)) {
      $breadcrumb->addCacheContexts($this->breadcrumb->getBreadcrumbCacheContexts());
    }
    $parents = \Drupal::entityManager()->getStorage('cb_breadcrumb')->loadAllParents($this->breadcrumb->id());
    if ($this->breadcrumb->isHomeLink()) {
      $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
    }
    $entities = [];
    foreach ($route_match->getParameters() as $parameter) {
      if ($parameter instanceof \Drupal\Core\Entity\EntityInterface) {
        $entities[] = $parameter;
      }
    }
    if ($entities) {
      // Build token data.
      foreach ($entities as $entity) {
        $data = [
          $this->tokenEntityMapper->getTokenTypeForEntityType($entity->getEntityTypeId()) => $entity,
        ];
      }
    }

    if ($token_data = \Drupal::moduleHandler()->invokeAll('breadcrumb_token_data', [$route_match])) {
      $data = array_merge($data, $token_data);
    }

    foreach (array_reverse($parents) as $parent) {
      foreach ($parent->getLinkData() as $link_path => $link_title) {
        // Replace tokens in breadcrumb link data.
        // Add empty BubbleableMetadata object as cached will be breadcrumb render.
        $title = $data ? $this->token->replace($link_title, $data, [], new BubbleableMetadata()) : $link_title;
        $path = $data ? $this->token->replace($link_path, $data, [], new BubbleableMetadata()) : $link_path;
        $breadcrumb->addLink(Link::fromTextAndUrl($title, Url::fromUri('internal:' . $path)));
      }
      $parent = \Drupal::entityManager()->getTranslationFromContext($parent);
      // Add cache dependency on any changing of the breadcrumb or parent.
      $breadcrumb->addCacheableDependency($parent);
    }

    return $breadcrumb;
  }

  /**
   * Loads cb_breadcrumb entity by property path.
   *
   * @param $path
   *   The path of the cb_breadcrumb entity.
   *
   * @return
   *   An cb_breadcrumb entity.
   */
  public function getBreadcrumbs($path) {
    return cb_breadcrumbs_load_by_path(RouteCompiler::getPatternOutline($path));
  }

}
