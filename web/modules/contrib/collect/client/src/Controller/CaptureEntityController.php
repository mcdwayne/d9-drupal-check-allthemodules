<?php
/**
 * @file
 * Contains \Drupal\collect_client\Controller\CaptureEntityController.
 */

namespace Drupal\collect_client\Controller;

use Drupal\collect_client\CaptureEntity;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Entity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Capture Entity Controller.
 */
class CaptureEntityController extends ControllerBase {

  /**
   * The entity capture service.
   *
   * @var \Drupal\collect_client\CaptureEntity
   */
  protected $entityCapturer;

  /**
   * Constructs a CaptureEntityController object.
   *
   * @param \Drupal\collect_client\CaptureEntity $entity_capturer
   *   The entity capture service.
   */
  public function __construct(CaptureEntity $entity_capturer) {
    $this->entityCapturer = $entity_capturer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('collect_client.capture_entity')
    );
  }

  /**
   * Loads an entity and delegates it to CaptureEntity for capturing.
   *
   * @param string $entity_type
   *   The entity type of the given entity.
   * @param string $entity_id
   *   The ID of the given entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A response redirecting to the entity page.
   */
  public function capture($entity_type, $entity_id) {
    $entity = entity_load($entity_type, $entity_id);
    $this->entityCapturer->capture($entity);
    drupal_set_message($this->t('The @entity_type %label has been captured as a new container.', [
      '@entity_type' => $entity->getEntityType()->getLowercaseLabel(),
      '%label' => $entity->label(),
    ]));
    $url = $entity->urlInfo();
    return $this->redirect($url->getRouteName(), $url->getRouteParameters(), $url->getOptions());
  }

}
