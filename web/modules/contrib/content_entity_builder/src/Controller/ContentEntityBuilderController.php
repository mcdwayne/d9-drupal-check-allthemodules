<?php

namespace Drupal\content_entity_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class QuizController.
 *
 * @package Drupal\iquiz\Controller
 */
class ContentEntityBuilderController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @param \Drupal\Core\Render\RendererInterface $renderer
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  public function editContentType(Request $request, $content_type) {
    $content_type_entity = $this->entityTypeManager()->getStorage('content_type')->load($content_type);
    $form = $this->entityFormBuilder()->getForm($content_type_entity, 'edit');
    return $form;
  }

}
