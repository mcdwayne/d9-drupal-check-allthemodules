<?php

namespace Drupal\owlcarousel2\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\owlcarousel2\Entity\OwlCarousel2;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ItemController.
 *
 * @package Drupal\owlcarousel2\Controller
 */
class ItemController extends ControllerBase {

  /**
   * The fileStorage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * The fileUsage.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContainerInterface $container) {
    $this->fileStorage = $container->get('entity_type.manager')
      ->getStorage('file');
    $this->fileUsage   = $container->get('file.usage');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static ($container);
  }

  /**
   * Revmove one item from OwlCarousel2.
   *
   * @param int $owlcarousel2
   *   The OwlCarousel2 id.
   * @param string $item_id
   *   The item id.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function remove($owlcarousel2, $item_id) {
    $carousel = OwlCarousel2::load($owlcarousel2);
    $items    = $carousel->getItems();
    foreach ($items[0] as $key => $value) {
      if ($value['id'] == $item_id) {
        unset($items[0][$key]);

        // Remove carousel usage from the file(s).
        if (isset($value['file_id'])) {
          $this->removeFile($value['file_id'], $carousel);
        }
        if (isset($value['navigation_image_id'])) {
          $this->removeFile($value['navigation_image_id'], $carousel);
        }
        break;
      }
    }

    $carousel->set('items', $items);
    $carousel->save();

    $url = new Url('entity.owlcarousel2.edit_form', [
      'owlcarousel2' => $owlcarousel2,
    ]);

    return new RedirectResponse($url->toString());
  }

  /**
   * Remove the link between the deleted OwlCarousel2 item and the image.
   *
   * It will delete the image if there is no other entity using it.
   *
   * @param int $fid
   *   The file id.
   * @param \Drupal\owlcarousel2\Entity\OwlCarousel2 $carousel
   *   The OwlCarousel2 entity.
   */
  private function removeFile($fid, OwlCarousel2 $carousel) {
    if (is_numeric($fid)) {
      $file = $this->fileStorage->load($fid);
      if ($file instanceof File) {
        $this->fileUsage->delete($file, 'owlcarousel2', $carousel->getEntityTypeId(), $carousel->id());

        $usage = $this->fileUsage->listUsage($file);
        if (count($usage) == 0) {
          try {
            $file->delete();
          }
          catch (EntityStorageException $e) {
            // Do nothing.
          }
        }
      }
    }
  }

}
