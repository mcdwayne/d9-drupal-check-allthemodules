<?php
namespace Drupal\splashify\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\splashify\Entity\SplashifyEntity;
use Drupal\splashify\Entity\SplashifyGroupEntity;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A splashify controller. Used in redirect-mode.
 */
class SplashifyController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content($id) {
    $entity = SplashifyEntity::load($id);

    if (empty($entity)) {
      throw new NotFoundHttpException();
    }

    $content = $entity->getContent();
    $group_id = $entity->getGroupId();
    $group = SplashifyGroupEntity::load($group_id);

    $splash_mode = $group->getSplashMode();

    // Render plain html or via site template.
    switch ($splash_mode) {
      case 'template':
        return [
          '#type' => 'markup',
          '#markup' => $content,
        ];

      case 'plain_text':
        echo $content;
        exit();

      default:
        throw new NotFoundHttpException();

    }
  }

}
