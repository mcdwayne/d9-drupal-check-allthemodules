<?php

namespace Drupal\image_styles_mapping\Plugin\ImageStylesMapping;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\image_styles_mapping\Plugin\ImageStylesMappingPluginBase;
use Drupal\Core\Link;

/**
 * Image Plugin.
 *
 * @ImageStylesMapping(
 *   id = "image_styles",
 *   label = @Translation("Image styles"),
 *   description = @Translation("Adds image styles support."),
 * )
 */
class Image extends ImageStylesMappingPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return ['image'];
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->displayImageStyleLink();
  }

  /**
   * {@inheritdoc}
   */
  public function getRowData(array $field_settings) {
    $image_styles = [];

    foreach ($this->getImageStyles() as $image_style_name => $image_style_label) {
      // Use recursive search because the structure of the field_formatter is
      // unknown.
      $search_result = FALSE;
      $this->recursiveSearch($image_style_name, $field_settings, $search_result);
      if ($search_result) {
        $image_styles[] = $this->displayImageStyleLink($image_style_label, $image_style_name);
      }
    }

    // Case empty.
    if (empty($image_styles)) {
      $image_styles[] = $this->t('No image style used');
    }

    $image_styles = implode(', ', $image_styles);
    // Use FormattableMarkup object to avoid link in plain text.
    $image_styles = new FormattableMarkup($image_styles, []);
    return $image_styles;
  }

  /**
   * Helper function to get the image styles.
   *
   * @return array
   *   An array of image style label keyed with its name.
   */
  public function getImageStyles() {
    $image_styles = &drupal_static(__FUNCTION__);

    if (!isset($image_styles)) {
      /** @var \Drupal\image\Entity\ImageStyle[] $image_styles_entities */
      $image_styles_entities = $this->entityTypeManager->getStorage('image_style')->loadMultiple();

      $image_styles = [];
      foreach ($image_styles_entities as $image_styles_entity) {
        // Get the info we seek from the image style entity.
        $image_styles[$image_styles_entity->get('name')] = $image_styles_entity->get('label');
      }
    }

    return $image_styles;
  }

  /**
   * Helper function.
   *
   * Display a link to image style edit page if user has permission.
   *
   * If no argument is given, display a link to the image styles list.
   *
   * @param string $image_style_label
   *   The label of the image style.
   * @param string $image_style_name
   *   The name of the image style.
   *
   * @return string
   *   A link to the image style if user has access.
   *   The image style's label otherwise.
   */
  public function displayImageStyleLink($image_style_label = '', $image_style_name = '') {
    // Prepare link.
    if ($image_style_label != '' && $image_style_name != '') {
      $url = Url::fromRoute('entity.image_style.edit_form', ['image_style' => $image_style_name]);
      $link_text = $image_style_label;
    }
    else {
      $url = Url::fromRoute('entity.image_style.collection');
      $link_text = $this->t('Image styles (not sortable)');
    }

    // Use the routing system to check access.
    if ($url->renderAccess($url->toRenderArray())) {
      $link = Link::fromTextAndUrl($link_text, $url)->toRenderable();
      return render($link);
    }
    else {
      return $image_style_label;
    }
  }

}
