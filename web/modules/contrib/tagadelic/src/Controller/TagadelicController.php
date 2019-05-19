<?php

namespace Drupal\tagadelic\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TagadelicController extends ControllerBase {

  /**
   * An array of TagadelicTag objects.
   *
   */
  protected $tags;

  /**
   * Creates a TagadelicController instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $tags
   *   The tags from the cloud service for this block..
   */
  public function __construct($tags) {
    $this->tags = $tags;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tagadelic.tagadelic_taxonomy')->getTags()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function content() {
    return array(
      '#theme' => 'tagadelic_taxonomy_cloud',
      '#tags' => $this->tags,
      '#attached' => array(
        'library' =>  array(
          'tagadelic/base'
        ),
      ),
    );
  }
}
?>
