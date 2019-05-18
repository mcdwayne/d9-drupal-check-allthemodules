<?php

namespace Drupal\icons\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\icons\Entity\IconSetInterface;

/**
 * Provides a render element for an icon.
 *
 * Properties:
 * - #attributes: (array, optional) HTML attributes to apply to the tag. The
 *   attributes are escaped, see \Drupal\Core\Template\Attribute.
 * - #icon_set:.
 * - #icon_name:
 *
 * Usage example:
 * @code
 * $build['icon'] = [
 *   '#type' => 'icon',
 *   '#icon_set' => 'icon_set_config_entity_id',
 *   '#icon_name' => 'identifier_for_icon',
 * ];
 * @endcode
 *
 * @RenderElement("icon")
 */
class Icon extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#theme' => 'icon',
      '#attributes' => array(),
      '#pre_render' => array(
        array($class, 'preRenderIcon'),
      ),
    );
  }

  /**
   * Pre-render callback: Renders a link into #theme icon.
   *
   * @param array $element
   *   - #icon_set: The id of the icon provider configuration entity.
   *   - #icon_name: The name of the icon to render.
   *
   * @return array
   *   The passed-in element containing a rendered link in '#markup'.
   */
  public static function preRenderIcon(array $element) {
    if (isset($element['#icon_id']) && !empty($element['#icon_id'])) {

      $value = explode(':', $element['#icon_id']);
      if (count($value) > 1) {
        list($icon_set, $icon_name) = $value;
        $element['#icon_set'] = $icon_set;
        $element['#icon_name'] = $icon_name;
      }
    }

    if (isset($element['#icon_set']) && !$element['#icon_set'] instanceof IconSetInterface) {
      /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
      $entityTypeManager = \Drupal::service('entity_type.manager');
      $iconSetStorage = $entityTypeManager->getStorage('icon_set');

      /** @var IconSetInterface $icon_set */
      $icon_set = $iconSetStorage->load($element['#icon_set']);

      $element['#icon_set'] = $icon_set;
    }

    if (isset($element['#icon_set'])) {
      $iconSetPlugin = $element['#icon_set']->getPlugin();
      $iconSetPlugin->build($element, $element['#icon_set'], $element['#icon_name']);
    }

    return $element;
  }

}
