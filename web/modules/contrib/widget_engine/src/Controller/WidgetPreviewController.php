<?php

namespace Drupal\widget_engine\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\widget_engine\Entity\Widget;
use Drupal\widget_engine\Entity\WidgetInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for widget engine preview routes.
 */
class WidgetPreviewController extends ControllerBase {

  /**
   * Widget preview page.
   */
  public function widgetPreview($widget_id) {
    $build = [];
    // Check token.
    $token = \Drupal::request()->query->get('token');
    if ($widget_id && \Drupal::csrfToken()->validate($token, 'widgetTokenPreview')) {
      $entity_type = 'widget';

      $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
      $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
      $widget = $storage->load($widget_id);
      $build = $view_builder->view($widget);
      // To prevent any messages.
      drupal_get_messages();
    }

    return $build;
  }

  /**
   * Widget update/save preview image.
   */
  public function widgetPreviewSave($widget_id) {
    // Check token.
    $token = \Drupal::request()->query->get('token');

    $img_base_64 = \Drupal::request()->request->get('imgBase64');
    if ($img_base_64 && $widget_id && \Drupal::csrfToken()->validate($token, 'widgetTokenPreviewSave')) {
      $img = str_replace('data:image/png;base64,', '', $img_base_64);
      $data = base64_decode($img);
      $file_name = uniqid() . '.png';

      // Update widget with new image preview.
      $widget = Widget::load((int) $widget_id);
      if (is_object($widget)) {
        // Prepare directory.
        $directory = "public://widget_engine_preview/{$widget->getType()}/";
        file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
        $file = file_save_data($data, $directory . $file_name, FILE_EXISTS_REPLACE);

        try {
          $widget->set('widget_preview', ['target_id' => $file->id()]);
          $widget->save();
        }
        catch (\Exception $e) {
          \Drupal::logger('widget_engine')
            ->warning('Updating @widget failed with message @message.', [
              '@node' => $widget->getName(),
              '@message' => $e->getMessage(),
            ]);
        }

        // Build preview image.
        $data = $widget
          ->get('widget_preview')
          ->view(['label' => 'hidden']);
        $image_preview = widget_engine_build_preview_image($data);

        return new JsonResponse([
          'img' => drupal_render($image_preview),
          'wid' => $widget_id,
        ]);
      }
    }

    return new JsonResponse([]);
  }

  /**
   * Widget preview generate/save page controller.
   */
  public function widgetPreviewGenerate(WidgetInterface $widget) {
    // Prepare settings for JS.
    $drupalSettings = [
      'widget_engine' => [
        'wid' => $widget->id(),
        'redirect_path' => 'admin/content/widgets',
      ],
      'tokens' => [
        'token_preview' => \Drupal::csrfToken()->get('widgetTokenPreview'),
        'token_save' => \Drupal::csrfToken()->get('widgetTokenPreviewSave'),
      ],
    ];
    // Add progres bar for better page visualization.
    $progress_bar = [
      '#theme' => 'progress_bar',
      '#percent' => 100,
      '#message' => $this->t('Widget preview is generating...'),
    ];
    // Compose render array with progress bar and JS settings.
    $output = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => render($progress_bar),
      '#attributes' => [
        'id' => 'widget-generate-preview',
        'class' => 'widget-generate-preview',
      ],
      '#attached' => [
        'library' => [
          'widget_engine/make_preview',
        ],
        'drupalSettings' => $drupalSettings,
      ],
    ];

    return $output;
  }

}
