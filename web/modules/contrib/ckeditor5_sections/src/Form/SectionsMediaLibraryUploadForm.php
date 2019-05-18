<?php

namespace Drupal\ckeditor5_sections\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_library\Form\MediaLibraryUploadForm;
use Drupal\media\MediaInterface;

/**
 * Alter media upload form.
 */
class SectionsMediaLibraryUploadForm extends MediaLibraryUploadForm {

  /**
   * {@inheritdoc}
   */
  public function updateWidget(array &$form, FormStateInterface $form_state) {
    if ($form_state->getErrors()) {
      return $form;
    }
    $widget_id = $this->getRequest()->query->get('media_library_widget_id');
    if (!$widget_id || !is_string($widget_id)) {
      throw new BadRequestHttpException('The "media_library_widget_id" query parameter is required and must be a string.');
    }
    $return_type = $this->getRequest()->query->get('return_type');
    $mids = array_map(function (MediaInterface $media) use ($return_type) {
      if ($return_type == 'uuid') {
        return $media->uuid();
      }
      return $media->id();
    }, $this->media);
    // Pass the selection to the field widget based on the current widget ID.
    return (new AjaxResponse())
      ->addCommand(new InvokeCommand("[data-media-library-widget-value=\"$widget_id\"]", 'val', [implode(',', $mids)]))
      ->addCommand(new InvokeCommand("[data-media-library-widget-update=\"$widget_id\"]", 'trigger', ['mousedown']))
      ->addCommand(new CloseDialogCommand());
  }

}
