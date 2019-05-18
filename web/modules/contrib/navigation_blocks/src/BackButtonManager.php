<?php

namespace Drupal\navigation_blocks;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Manager for back buttons.
 *
 * @package Drupal\navigation_blocks
 */
class BackButtonManager implements BackButtonManagerInterface {

  use StringTranslationTrait;

  /**
   * Block Path Matcher.
   *
   * @var \Drupal\navigation_blocks\PathMatcher
   */
  protected $blockPathMatcher;

  /**
   * CurrentRouteMatch service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The request stack to get the request object from.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a manager for back buttons.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $currentRouteMatch
   *   The current route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\navigation_blocks\PathMatcherInterface $blockPathMatcher
   *   The patch matcher.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(RouteMatchInterface $currentRouteMatch, RequestStack $requestStack, PathMatcherInterface $blockPathMatcher, EntityTypeManagerInterface $entityTypeManager) {
    $this->requestStack = $requestStack;
    $this->blockPathMatcher = $blockPathMatcher;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function addLinkAttributes(array &$link, $useJavascript = FALSE): void {
    $link['#options']['attributes']['class'][] = 'back-button';
    $link['#options']['attributes']['rel'][] = 'nofollow';

    if ($useJavascript) {
      $link['#options']['attributes']['class'][] = 'js-history-back';
      $link['#attached'] = [
        'library' => [
          'navigation_blocks/history-back',
        ],
      ];
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getPreferredLink(string $preferredPaths, $useJavascript = FALSE): array {
    $refererPath = $this->getRefererPath();
    if (empty($refererPath)) {
      return [];
    }

    if (!empty($preferredPaths) && !$this->blockPathMatcher->matchPath($refererPath, $preferredPaths)) {
      return [];
    }

    $url = Url::fromUserInput($refererPath);
    if (!$this->blockPathMatcher->validateCurrentPath($url)) {
      return [];
    }

    return $this->getLink($url, $this->getBackButtonText($url), $useJavascript);
  }

  /**
   * {@inheritdoc}
   */
  public function getLink(Url $url, string $text, $useJavascript = FALSE): array {
    if ($url->isRouted() && $this->currentRouteMatch->getRouteObject()->getPath() === $url->getInternalPath()) {
      return [];
    }

    $link = Link::fromTextAndUrl($text, $url)->toRenderable();
    $this->addLinkAttributes($link, $useJavascript);
    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public function getRefererPath(): string {
    $headers = $this->requestStack->getCurrentRequest()->headers->all();
    if (!isset($headers['referer'])) {
      return '';
    }
    return $this->stripBaseUrl($headers['referer'][0]);
  }

  /**
   * {@inheritdoc}
   */
  public function isCanonicalPath(): bool {
    return \strpos($this->currentRouteMatch->getRouteName(), '.canonical') !== FALSE;
  }

  /**
   * Strips the base URL from the referer.
   *
   * @param string $referer
   *   The referer.
   *
   * @return string
   *   The referer with the base URL stripped
   */
  private function stripBaseUrl($referer): string {
    global $base_url;
    return \str_replace($base_url, '', $referer);
  }

  /**
   * Get the back button text for a url.
   *
   * @param \Drupal\Core\Url $url
   *   The url to get the back button text for.
   *
   * @return string
   *   The back button text.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getBackButtonText(Url $url): string {
    $backButtonText = $this->t('Back');
    $params = $url->getRouteParameters();

    if (empty($params)) {
      return $backButtonText;
    }

    $entityType = \key($params);
    if (!$this->entityTypeManager->hasDefinition($entityType) && !$this->entityTypeManager->hasHandler($entityType, 'storage')) {
      return $backButtonText;
    }

    $entity = $this->entityTypeManager->getStorage($entityType)->load($params[$entityType]);
    return isset($entity) ? $entity->label() : $backButtonText;
  }

}
