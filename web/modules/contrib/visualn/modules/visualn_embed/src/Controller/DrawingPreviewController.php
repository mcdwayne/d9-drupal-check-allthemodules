<?php

namespace Drupal\visualn_embed\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Link;
use Drupal\visualn_drawing\Entity\VisualNDrawingInterface;

//use Drupal\Core\Ajax\SettingsCommand;

/**
 * Class DrawingPreviewController.
 *
 * @see visualn_embed.module visualn_embed_form_alter()
 */
class DrawingPreviewController extends ControllerBase {

  public function drawingPreviewResponse(VisualNDrawingInterface $visualn_drawing) {
    $response = new AjaxResponse();

    $preview_build = $this->drawingPreviewBuild($visualn_drawing);

    $entity = $visualn_drawing;
    if (!empty($preview_build)) {
      $content = $preview_build;

/*
      $new_settings = [
        'visualn' => ['context_wrapper' => '.preview-content'],
      ];
      $response->addCommand(new SettingsCommand($new_settings, TRUE));
*/

      $title = $this->t('@label [drawing preview]', ['@label' => $entity->label()]);
      //$response->addCommand(new OpenDialogCommand('#new-drawing-dialog', $title, $content, ['width' => 'auto', 'modal' => TRUE]));
      $response->addCommand(new OpenDialogCommand('#new-drawing-dialog', $title, $content, ['classes' => ['ui-dialog' => 'ui-dialog-visualn'], 'modal' => TRUE]));

      // @todo: update dialog position center since its diminsions could change due to js drawers
      // @todo: add a script that would reset dialog positioning on its dimensions change
    }


    return $response;
  }

  public function drawingPreviewBuild(VisualNDrawingInterface $visualn_drawing) {
    $build = [];
    $entity = $visualn_drawing;
    if (!empty($entity)) {
      $drawing_markup = $entity->buildDrawing();
      // @todo: the class is also used in preview-drawing-dialog.css library
      $drawing_markup['#attached']['drupalSettings']['visualn']['context_wrapper'] = '.preview-content';
      $drawing_markup['#attached']['library'][] = 'visualn_embed/preview-drawing-dialog';

      $drawing_markup['#prefix'] = '<div class="preview-content">';
      $drawing_markup['#suffix'] = '</div>';

      if ($entity->access('update')) {
        $drawing_id = $entity->Id();
        $edit_link = Link::createFromRoute($this->t('edit'), 'visualn_embed.drawing_controller_edit', ['visualn_drawing' => $drawing_id], ['attributes' => ['class' => ['use-ajax']]]);
        $edit_link = [
          '#markup' => $edit_link->toString(),
        ];
      }

/*
      $delete_link = Link::createFromRoute($this->t('delete'), 'visualn_embed.drawing_controller_delete', ['visualn_drawing' => $drawing_id], ['attributes' => ['class' => ['use-ajax']]]);
      $delete_link = [
        '#markup' => $delete_link->toString(),
      ];
*/

      $content = [
        'edit' => $edit_link,
        //'delete' => $delete_link,
        'drawing_markup' => $drawing_markup,
      ];

      /*
      // @todo: inject the service at init
      // check if visualn_snapshots module enabled
      if (\Drupal::service('module_handler')->moduleExists('visualn_snapshots')) {
        // @todo: move into template
        $content['#prefix'] = $content['#prefix'] . '<div><a class="js-create-visualn-drawing-snapshot" href="">' . $this->t('Make snapshot') . '</a></div>';
        $content['#attached']['library'][] = 'visualn_snapshots/html2canvas-script';
      }
      */

      $build = $content;

      // @todo: update dialog position center since its diminsions could change due to js drawers
      // @todo: add a script that would reset dialog positioning on its dimensions change
    }

    return $build;
  }

  public function drawingPreviewBuildTitle(VisualNDrawingInterface $visualn_drawing) {
    $title = '';
    $entity = $visualn_drawing;
    if (!empty($entity)) {
      $title = $this->t('@label [drawing preview]', ['@label' => $entity->label()]);
    }

    return $title;
  }

}
