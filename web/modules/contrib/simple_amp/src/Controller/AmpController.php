<?php

namespace Drupal\simple_amp\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\simple_amp\AmpBase;

class AMPController extends ControllerBase {

  protected $amp;
  protected $renderer;

  public function __construct(Renderer $render) {
    $this->renderer = $render;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * AMP version of the node.
   */
  public function page(Request $request, $entity = NULL) {
    if (is_object($entity)) {
      $this->amp = (new AmpBase())
        ->setEntity($entity)
        ->parse();
      if ($this->amp->isAmpEnabled()) {
        $response = new Response();
        $pagearray = $this->assembleAmpPage();
        $markup = $this->renderer->render($pagearray);
        $response->setContent($markup->__toString());
        return $response;
      }
    }
    $url = Url::fromRoute('entity.node.canonical', ['node' => $entity->id()]);
    return new RedirectResponse($url->toString(), 301);
  }

  /**
   * Build renderable page array.
   */
  protected function assembleAmpPage() {
    $entity = $this->amp->getEntity();
    return [
      '#theme'     => 'amp',
      '#title'     => $entity->getTitle(),
      '#entity'    => $entity,
      '#content'   => $this->amp->getContent(),
      '#params'    => [
        'metadata'  => $this->amp->getMetadata(),
        'scripts'   => $this->amp->getScripts(),
        'canonical' => $this->amp->getCanonicalUrl(),
        'ga'        => $this->amp->getGoogleAnalytics(),
      ],
      '#cache' => [
        'contexts' => [
          'url',
          'url.site',
          'url.query_args',
        ],
        'tags' => [
          'node:' . $entity->id(),
        ],
      ],
    ];
  }

}
