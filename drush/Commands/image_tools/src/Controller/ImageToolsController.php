<?php

namespace Drupal\image_tools\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\image_tools\Services\ImageService;
use Drupal\image_tools\Form\ResizeJpgsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for Image Tools.
 */
class ImageToolsController extends ControllerBase {
  const BATCH_IMAGE_COUNT = 50;

  /**
   * ImageService.
   *
   * @var \Drupal\image_tools\Services\ImageService
   */
  private $imageService;

  /**
   * Construct ImageToolsController.
   *
   * @param \Drupal\image_tools\Services\ImageService $imageService
   *   ImageService.
   */
  public function __construct(ImageService $imageService) {
    $this->imageService = $imageService;
  }

  /**
   * Create Controller.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Container.
   *
   * @return \Drupal\Core\Controller\ControllerBase|ImageToolsController
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\image_tools\Services\ImageService $imageService */
    $imageService = $container->get('image_tools.conversion.service');

    return new static($imageService);
  }

  /**
   * Displays the Overview page.
   *
   * @return array
   */
  public function overview() {
    return [
      '#title' => 'Image Tools Overview',
      '#theme' => 'overview_page',
    ];
  }

  /**
   * Displays the convertible PNGs.
   *
   * @return array
   */
  public function showConvertiblePngs() {
    $images = $this->imageService->loadPngImages();

    $rows = [];
    foreach ($images as $fid => $element) {
      $transparency = $element['transparency'] ? "x" : "";
      $rows[] = [
        'fid' => $fid,
        'name' => basename($element['path']),
        't' => $transparency,
      ];
    }

    $content = [
      '#title' => 'Convert PNGs',
      '#theme' => 'show_convertible_pngs_page',
      '#rows' => $rows,
    ];

    return $content;
  }

  /**
   * Display resizable JPGs.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request.
   *
   * @return array
   */
  public function showResizableJpgs(Request $request) {
    $include_png = $request->query->get('include_png') === NULL ? FALSE : TRUE;
    $max_width = $request->query->get('max_width', imageService::IMAGE_TOOLS_DEFAULT_MAX_WIDTH);

    if ($max_width <= 0) {
      $max_width = imageService::IMAGE_TOOLS_DEFAULT_MAX_WIDTH;
    }

    $images = $this->imageService->findLargeWidthImages($max_width, $include_png);

    $rows = [];
    foreach ($images as $fid => $element) {
      $transparency = isset($element['transparency']) && $element['transparency'] ? "x" : "";
      $rows[] = [
        'fid' => $fid,
        'name' => basename($element['path']),
        'size' => $element['width'] . 'x' . $element['height'],
        't' => $transparency,
      ];
    }

    $content = [
      '#title' => 'Resize JPGs',
      '#theme' => 'show_resizable_jpgs_page',
      '#max_width' => $max_width,
      '#png' => $include_png ? 'yes' : 'no',
      '#rows' => $rows,
      '#form' => $this->formBuilder()->getForm(ResizeJpgsForm::class, $max_width, $include_png),
    ];

    return $content;
  }

  /**
   * Create Batch Process for converting PNGs.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   */
  public function addBatchConvertPngs() {
    $images = $this->imageService->loadPngImages();

    $operations = [];
    if (count($images) > self::BATCH_IMAGE_COUNT) {
      $array_chunks = array_chunk($images, self::BATCH_IMAGE_COUNT, TRUE);

      foreach ($array_chunks as $images) {
        $operations[] = ['convert_pngs_to_jpg', [$images]];
      }
    }
    else {
      $operations[] = ['convert_pngs_to_jpg', [$images]];
    }

    $batch = [
      'title' => $this->t('Converting PNGs to JPGs'),
      'operations' => $operations,
      'finished' => 'png_conversion_finished',
      'file' => drupal_get_path('module', 'image_tools') . '/image_tools.batch.inc',
    ];

    batch_set($batch);
    return batch_process(Url::fromRoute('image_tools.show_convertible_pngs'));
  }

  /**
   * Create Batch Process for resizing JPGs.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   */
  public function addBatchResizeJpgs(Request $request) {
    $include_png = $request->query->get('png', 'no') === 'yes' ? TRUE : FALSE;
    $max_width = $request->query->get('max_width', imageService::IMAGE_TOOLS_DEFAULT_MAX_WIDTH);

    $images = $this->imageService->findLargeWidthImages($max_width, $include_png);

    $operations = [];
    if (count($images) > self::BATCH_IMAGE_COUNT) {
      $array_chunks = array_chunk($images, self::BATCH_IMAGE_COUNT, TRUE);

      foreach ($array_chunks as $images) {
        $operations[] = ['resize_jpgs', [$images, $max_width]];
      }
    }
    else {
      $operations[] = ['resize_jpgs', [$images, $max_width]];
    }

    $batch = [
      'title' => $this->t('Resizing JPGs'),
      'operations' => $operations,
      'finished' => 'jpg_resizing_finished',
      'file' => drupal_get_path('module', 'image_tools') . '/image_tools.batch.inc',
    ];

    batch_set($batch);
    return batch_process(Url::fromRoute('image_tools.show_resizeable_jpgs'));
  }

}
