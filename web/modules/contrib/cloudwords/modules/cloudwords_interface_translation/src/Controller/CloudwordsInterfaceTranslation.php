<?php

namespace Drupal\cloudwords_interface_translation\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Renderer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Return response for manual check translations.
 */
class CloudwordsInterfaceTranslation extends ControllerBase {

  protected $renderer;
  public function __construct(Renderer $renderer) {
    $this->renderer = $renderer;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }
  /**
   * Shows the string search screen.
   *
   * @return array
   *   The render array for the string search screen.
   */
  public function inventoryOverview() {
//    return array(
//      'filter' => $this->formBuilder()->getForm('Drupal\locale\Form\TranslateFilterForm'),
//      'form' => $this->formBuilder()->getForm('Drupal\cloudwords_interface_translation\Form\TranslateEditForm'),
//    );

    // load translatable
//    $translatables =  \Drupal\cloudwords\Entity\CloudwordsTranslatable::loadMultiple([433]);
//    $translatable = reset($translatables);
//    $source_controller = new \Drupal\cloudwords_interface_translation\CloudwordsInterfaceSourceController('interface');
//    $data =  $source_controller->data($translatable);

    $view = views_embed_view('cloudwords_translatable', 'block_2');
    return [
      '#markup' => $this->renderer->render($view),
      '#attached' => [
        'library' =>  [
          'cloudwords/cloudwords.create_project',
        ],
        'drupalSettings' => [
          'cloudwords' => [
            'token' => \Drupal::csrfToken()->get('cloudwords'),
            'ajaxUrl' => 'admin/cloudwords/ajax'
          ]
        ],
      ],
    ];
  }

}
