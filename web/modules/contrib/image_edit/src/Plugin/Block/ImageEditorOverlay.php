<?php

namespace Drupal\image_edit\Plugin\Block;


use Drupal\Core\Block\BlockBase;


/**
 * Provides an overlay for the image-editor to operate in.
 *
 * @Block(
 *   id = "image_edit",
 *   admin_label = @Translation("Image-edit block"),
 *   category = @Translation("Editor"),
 * )
 */
class ImageEditorOverlay extends BlockBase {

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * If a block should not be rendered because it has no content, then this
   * method must also ensure to return no content: it must then only return an
   * empty array, or an empty array with #cache set (with cacheability metadata
   * indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockViewBuilder
   */
  public function build() {
    $blockContent = [];

    if (\Drupal::currentUser()->hasPermission('use image edit')) {
      $blockContent= [
        '#type' => 'item',
        '#markup' => file_get_contents(
          \Drupal::root()  . DIRECTORY_SEPARATOR .
          drupal_get_path('module', 'image_edit') .
          DIRECTORY_SEPARATOR . 'popup_template.html'
        ),
        '#attached' => ['library' => ['image_edit/editor_code']],
        '#allowed_tags' => ['canvas', 'button', 'div', 'h2', 'label', 'input', 'nav', 'p', 'a'],
      ];
    }

    return $blockContent;
  }
}