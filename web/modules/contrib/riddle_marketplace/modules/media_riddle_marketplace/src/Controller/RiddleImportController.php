<?php

namespace Drupal\media_riddle_marketplace\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\media\Entity\Media;
use Drupal\media_riddle_marketplace\RiddleMediaServiceInterface;
use Drupal\riddle_marketplace\Exception\NoApiKeyException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class RiddleImportController.
 *
 * @package Drupal\media_riddle_marketplace\Controller
 */
class RiddleImportController extends ControllerBase {

  /**
   * The riddle media service.
   *
   * @var \Drupal\media_riddle_marketplace\RiddleMediaServiceInterface
   */
  protected $riddleMediaService;

  /**
   * The current request.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * RiddleImportController constructor.
   *
   * @param \Drupal\media_riddle_marketplace\RiddleMediaServiceInterface $riddleMediaService
   *   The riddle media service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(RiddleMediaServiceInterface $riddleMediaService, RequestStack $requestStack) {
    $this->riddleMediaService = $riddleMediaService;
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('media_riddle_marketplace'),
      $container->get('request_stack')
    );
  }

  /**
   * The controller route.
   *
   * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Return a batch.
   */
  public function content() {

    $batch = [
      'title' => 'Import riddles',
      'operations' => [],
    ];

    try {
      foreach ($this->riddleMediaService->getNewRiddles() as $type => $riddles) {
        /** @var \Drupal\media\Entity\MediaType $type */
        $type = $this->entityTypeManager()->getStorage('media_type')
          ->load($type);
        $sourceField = $type->get('source_configuration')['source_field'];

        foreach ($riddles as $riddle) {

          $batch['operations'][] = [
            get_class($this) . '::import',
            [
              [
                'type' => $type->id(),
                'source_field' => $sourceField,
                'riddle_id' => $riddle,
              ],
            ],
          ];
        }
      }
    }
    catch (NoApiKeyException $exception) {
      drupal_set_message($this->t('No API key provided. Please configure the riddle module.'), 'error');
      return new RedirectResponse($this->request->server->get('HTTP_REFERER'));
    }

    batch_set($batch);
    return batch_process($this->request->server->get('HTTP_REFERER'));
  }

  /**
   * The import function, used by batch.
   *
   * @param array $data
   *   Containing keys bundle, source_field and riddleId.
   */
  public static function import(array $data) {
    Media::create([
      'bundle' => $data['type'],
      $data['source_field'] => $data['riddle_id'],
    ])->save();
  }

}
