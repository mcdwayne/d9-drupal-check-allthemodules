<?php

namespace Drupal\social_simple;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Controller\TitleResolver;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\social_simple\SocialNetwork\SocialNetworkInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Render\Renderer;

/**
 * Class SocialSimpleGenerator.
 *
 * @package Drupal\social_simple
 */
class SocialSimpleGenerator implements SocialSimpleGeneratorInterface {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Controller\TitleResolver definition.
   *
   * @var \Drupal\Core\Controller\TitleResolver
   */
  protected $titleResolver;

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal\Core\Render\Renderer .
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Drupal\social_simple\SocialSimpleManagerInterface.
   *
   * @var \Drupal\social_simple\SocialSimpleManagerInterface
   */
  protected $socialSimpleManager;

  /**
   * An array of available social network.
   *
   * @var array
   */
  protected $networks = [];

  /**
   * Constructs a new SocialSimpleGenerator object.
   *
   * @param \Drupal\Core\Controller\TitleResolver $title_resolver
   *   The title resolver service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\Core\Render\Renderer $renderer_service
   *   The renderer service.
   * @param \Drupal\social_simple\SocialSimpleManagerInterface $social_simple_manager
   *   The social simple manager.
   */
  public function __construct(TitleResolver $title_resolver, CurrentRouteMatch $current_route_match, RequestStack $request_stack, ConfigFactory $config_factory, Renderer $renderer_service, SocialSimpleManagerInterface $social_simple_manager) {
    $this->titleResolver = $title_resolver;
    $this->currentRouteMatch = $current_route_match;
    $this->requestStack = $request_stack;
    $this->configFactory = $config_factory;
    $this->renderer = $renderer_service;
    $this->socialSimpleManager = $social_simple_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildSocialLinks(array $networks, $title, EntityInterface $entity = NULL, array $options = []) {
    $links = $this->generateSocialLinks($networks, $entity, $options);

    $build = [
      '#theme' => 'social_simple_buttons',
      '#links' => $links,
      '#attributes' => [
        'class' => ['links', 'inline', 'social-buttons-links'],
      ],
      '#heading' => [
        'text' => $title,
        'level' => 'div',
        'attributes' => [
          'class' => ['social-buttons-title'],
        ],
      ],
    ];
    $build['#attached']['library'][] = 'social_simple/buttons';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function generateSocialLinks(array $networks, EntityInterface $entity = NULL, array $options = []) {
    $links = [];
    $title = $this->getTitle($entity);
    $share_url = $this->getShareUrl($entity);

    $networks_supported = $this->getNetworks();
    $networks = array_intersect_key($networks_supported, $networks);

    foreach ($networks as $network_id => $network_name) {
      $additional_options = isset($options[$network_id]) ? $options[$network_id] : [];
      if ($this->socialSimpleManager->get($network_id) instanceof SocialNetworkInterface) {
        $links[$network_id] = $this->socialSimpleManager->get($network_id)->getShareLink($share_url, $title, $entity, $additional_options);
      }

    }
    return $links;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(EntityInterface $entity = NULL) {
    if ($entity) {
      $title = $entity->label();
    }
    else {
      $title = $this->titleResolver->getTitle($this->requestStack->getCurrentRequest(), $this->currentRouteMatch->getRouteObject());
    }

    if (is_string($title)) {
      return $title;
    }
    elseif (is_array($title)) {
      return $this->renderer->render($title);
    }
    elseif ($title instanceof MarkupInterface) {
      return (string) $title;
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getShareUrl(EntityInterface $entity = NULL) {
    if ($entity) {
      $share_url = $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
    }
    else {
      $share_url = Url::fromRoute('<current>', [], ['absolute' => 'true'])->toString();
    }

    return $share_url;
  }

  /**
   * {@inheritdoc}
   */
  public function getNetworks() {
    if (empty($this->networks)) {
      $this->networks = $this->socialSimpleManager->getNetworks();
    }
    return $this->networks;
  }

}
