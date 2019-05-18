<?php

namespace Drupal\dpl\Plugin\Menu\LocalTask;

use Drupal\dpl\DecoupledPreviewLinks;
use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\dpl\Entity\DecoupledPreviewLink;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the local task for the consumer preview link.
 */
class DecoupledPreviewLinkTask extends LocalTaskDefault implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The consumer preview links.
   *
   * @var \Drupal\dpl\DecoupledPreviewLinks
   */
  protected $decoupledPreviewLinks;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RouteMatchInterface $route_match,
    DecoupledPreviewLinks $decoupled_preview_links
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $route_match;
    $this->decoupledPreviewLinks = $decoupled_preview_links;
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
      $container->get('current_route_match'),
      $container->get('dpl.preview_links')
    );
  }

  /**
   * Gets a preview link.
   *
   * @return \Drupal\Core\Link
   */
  protected function getPreviewLink() {
    if ($preview_link_instance = $this->getPreviewLinkInstance()) {
      // @todo make this generic
      $entity = $this->getPreviewEntity();
      return $this->decoupledPreviewLinks->getPreviewLink($preview_link_instance, $entity);
    }
  }

  /**
   * Returns the entity to be previewed.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function getPreviewEntity() {
    // @todo make this generic
    return $this->currentRouteMatch->getParameter('node');
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'dpl.preview';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    return $this->getPreviewLink()->getText();
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    if ($preview_link_instance = $this->getPreviewLinkInstance()) {
      return [
        'node' => $this->getPreviewEntity()->id(),
        'decoupled_preview_link' => $preview_link_instance->id(),
        'url' => $this->getPreviewLink()->getUrl()->toString(),
      ];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $url = $this->getPreviewLink()->getUrl();
    return [
      'attributes' => !empty($url->getOptions()['attributes'])
      ? $url->getOptions()['attributes']
      : [],
      'query' => [
        'entity_url' => $this->getPreviewEntity()->toUrl()->toString(),
      ],
    ];
  }

  /**
   * @return \Drupal\dpl\PreviewLinkInstance
   */
  protected function getPreviewLinkInstance() {
    return DecoupledPreviewLink::load(
      $this->getPluginDefinition()['decoupled_preview_link']
    )->toPreviewLinkInstance();
  }

}
