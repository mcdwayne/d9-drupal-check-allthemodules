<?php

namespace Drupal\type_style\TwigExtension;

use Drupal\Core\Entity\EntityInterface;

/**
 * A Twig extension to render type styles.
 */
class TypeStyleExtension extends \Twig_Extension {

  /**
   * Generates a list of all Twig functions that this extension defines.
   *
   * @return array
   *   A key/value array that defines custom Twig functions. The key denotes the
   *   function name used in the tag, e.g.:
   *   @code
   *   {{ testfunc() }}
   *   @endcode
   *
   *   The value is a standard PHP callback that defines what the function does.
   */
  public function getFunctions() {
    return [
      'type_style' => new \Twig_Function_Function(['Drupal\type_style\TwigExtension\TypeStyleExtension', 'getTypeStyle']),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   *
   * @return string
   *   A unique identifier for this Twig extension.
   */
  public function getName() {
    return 'type_style.type_style_extension';
  }

  /**
   * Helper function to grab a style for a given entity.
   *
   * @param string|\Drupal\core\Entity\EntityInterface $type
   *   The target entity type, or the entity object if available.
   * @param mixed|string $id
   *   The target entity ID, or a style if an object is passed in $type.
   * @param string $style
   *   The style name, or a default if an object is passed in $type.
   * @param string $default
   *   A default value in case the style is not set. Defaults to empty string.
   *
   * @return string
   *   The style if set, or the default. This value is safe to use
   *
   * @see \Drupal\system\Tests\Theme\TwigExtensionTest::testTwigExtensionFunction()
   */
  public static function getTypeStyle($type, $id, $style = NULL, $default = '') {
    if ($type instanceof EntityInterface) {
      return type_style_get_style($type, $id, $style);
    }
    else if (($storage = \Drupal::entityTypeManager()->getStorage($type)) && $style) {
      if ($entity = $storage->load($id)) {
        return type_style_get_style($entity, $style, $default);
      }
    }
    return $default;
  }

}
