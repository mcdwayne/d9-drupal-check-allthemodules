<?php

namespace Drupal\cloud\Service\Util;

use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Html generator utility for Entity link.
 */
class EntityLinkHtmlGenerator implements EntityLinkHtmlGeneratorInterface {

  /**
   * The link generator service.
   *
   * @var \Drupal\Core\Utility\LinkGenerator
   */
  protected $linkGenerator;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Utility\LinkGenerator $link_generator
   *   The link generator service.
   */
  public function __construct(LinkGenerator $link_generator) {
    $this->linkGenerator = $link_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('link_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function generate(Url $url, $id, $name = NULL, $alt_text = NULL) {
    $text = $id;
    if ($alt_text != NULL) {
      $text = $alt_text;
    }

    return $this->linkGenerator->generate($text, $url);
  }

}
