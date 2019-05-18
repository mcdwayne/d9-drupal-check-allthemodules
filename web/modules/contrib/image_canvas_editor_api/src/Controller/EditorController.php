<?php

namespace Drupal\image_canvas_editor_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\image_canvas_editor_api\Plugin\EditorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Image canvas editor API routes.
 */
class EditorController extends ControllerBase {

  /**
   * Editor plugin manager service.
   *
   * @var \Drupal\image_canvas_editor_api\Plugin\EditorPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs the controller object.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, EditorPluginManager $manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_manager;
    $this->pluginManager = $manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.image_editor_plugin'),
      $container->get('module_handler')
    );
  }

  /**
   * Builds the response.
   */
  public function build($field_name, $entity_type, $bundle, $form_mode, $fid) {
    $form_display = $this->entityTypeManager
      ->getStorage('entity_form_display')
      ->load($entity_type . '.' . $bundle . '.' . $form_mode);
    if (!$widget = $form_display->getComponent($field_name)) {
      throw new NotFoundHttpException();
    }
    $editors = $this->pluginManager->getDefinitions();
    if (empty($editors)) {
      throw new \Exception('No image editors found');
    }
    // See which one to use.
    $editor_ids = array_keys($editors);
    $editor_id = reset($editor_ids);
    if (!empty($widget['settings']['editor'])) {
      $editor_id = $widget['settings']['editor'];
    }
    /** @var \Drupal\image_canvas_editor_api\Plugin\EditorInterface $instance */
    $instance = $this->pluginManager->createInstance($editor_id);
    if (!$file = $this->entityTypeManager->getStorage('file')->load($fid)) {
      throw new NotFoundHttpException();
    }
    /** @var \Drupal\file\Entity\File $file */
    $image_url = file_create_url($file->getFileUri());
    // Append some cache bust parameter. Could be a user is editing files over
    // and over again.
    $image_url .= '?cache_buster=' . time();

    $editor = $instance->renderEditor($image_url);
    $this->moduleHandler->alter('image_canvas_editor_api_editor_render', $editor);
    $build['editor'] = $editor;
    $build['save'] = [
      '#type' => 'inline_template',
      '#template' => '<button class="btn button image-canvas-editor-save">{{ save }}</button>',
      '#context' => [
        'save' => $this->t('Save'),
      ],
      '#attached' => [
        'library' => [
          'image_canvas_editor_api/editor',
        ],
      ],
    ];
    $build['#prefix'] = '<div class="editor-wrapper">';
    $build['#suffix'] = '</div>';
    $build['#attached'] = [
      'drupalSettings' => [
        'imageCanvasEditorApi' => [
          'fid' => $fid,
        ],
      ],
    ];

    return $build;
  }

  /**
   * Saves an image.
   */
  public function saveImage($fid, Request $request) {
    /* @var \Drupal\file\Entity\File $file */
    if (!$file = $this->entityTypeManager->getStorage('file')->load($fid)) {
      throw new NotFoundHttpException();
    }
    $content = $request->getContent();
    if (!$json = json_decode($content)) {
      throw new BadRequestHttpException('No image data specified as JSON');
    }
    if (empty($json->image)) {
      throw new BadRequestHttpException('No image data specified');
    }
    $image_data = $json->image;
    $image_data = str_replace('data:image/png;base64,', '', $image_data);
    $image_data = str_replace(' ', '+', $image_data);
    $data = base64_decode($image_data);
    // Then brute-force it into place. Use a new name, so we do not end up
    // saving a png file as JPG. Turns out, this can confuse Internet explorer,
    // but also, lets be frank. Its not semantically correct. Also make sure we
    // do not end up with a file called img-edited.png-edited.png and so on if a
    // user edits it multiple times. This could mean we end up re-saving on the
    // same filename, but this is fine, as long as we use the correct extension.
    $uri = str_replace('-edited.png', '', $file->getFileUri());
    $new_uri = $uri . '-edited.png';
    file_put_contents($new_uri, $data);
    $file->setFileUri($new_uri);
    $file->save();
    image_path_flush($file->getFileUri());
    return new JsonResponse([
      'fid' => $fid,
      'url' => file_create_url($file->getFileUri()),
    ]);
  }

}
