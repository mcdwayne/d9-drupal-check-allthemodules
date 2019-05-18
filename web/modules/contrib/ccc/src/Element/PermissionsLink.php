<?php

namespace Drupal\ccc\Element;

use Drupal\Core\Render\Element\Link;

/**
 * Provides a CCC-branded permissions link.
 *
 * Properties:
 * - #title: The text of the link to generate (defaults to 'Get permissions').
 * - #link_style: Either 'button' or 'icon'. Defaults to 'icon'.
 *
 * See \Drupal\Core\Render\Element\Link for additional properties.
 *
 * Usage Example:
 * @code
 * $build['permissions_link'] = [
 *   '#type' => 'ccc_permissions_link',
 *   '#url' => Url::fromUri('', ['query' => []],
 * ];
 * @endcode
 *
 * @RenderElement("ccc_permissions_link")
 */
class PermissionsLink extends Link {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    $info = parent::getInfo();
    return [
      '#title' => $this->t('Get permissions'),
      '#link_style' => 'icon',
      '#pre_render' => [
        [$class, 'preRenderPermissionsLink'],
        [$class, 'preRenderLink'],
      ],
    ] + $info;
  }

  /**
   * Pre-render callback: Alters the render array before link gets turned into #markup.
   *
   * Doing so during pre_render gives modules a chance to alter the link parts.
   *
   * @param array $element
   *   A structured array whose keys form the arguments to
   *   \Drupal\Core\Utility\LinkGeneratorInterface::generate():
   *   - #title: The link text.
   *   - #url: The URL info pointing to a CCC permissions URL.
   *   - #link_style: The style of link, either 'icon' or 'button'. Defaults to 'icon'.
   *   - #options: (optional) An array of options to pass to the link generator.
   *
   * @return array
   *   The passed-in element containing the permissions link default values.
   */
  public static function preRenderPermissionsLink(array $element) {
    // Add default link attributes.
    $element['#options']['attributes']['target'] = '_blank';
    $element['#options']['attributes']['class'][] = 'ccc-permissions-link';
    if (!empty($element['#link_style'])) {
      $element['#options']['attributes']['class'][] = 'ccc-permissions-link--' . $element['#link_style'];
    }

    // Attach library.
    $element['#attached']['library'][] = 'ccc/ccc_permissions_link';

    return $element;
  }

}
