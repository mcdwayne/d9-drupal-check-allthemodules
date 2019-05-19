<?php

namespace Drupal\twig_extender_extras\Plugin\TwigPlugin;

use Drupal\Core\Field\Plugin\DataType\FieldItem;
use Drupal\twig_extender\Plugin\Twig\TwigPluginBase;

/**
 * The plugin for render a url string of a link field object.
 *
 * @TwigPlugin(
 *   id = "twig_extender_url_value",
 *   label = @Translation("Get the raw url value from Field"),
 *   type = "filter",
 *   name = "url_value",
 *   function = "getUrlValue"
 * )
 */
class UrlValue extends TwigPluginBase {

  /**
   * Get a url value from a link field.
   *
   * @param \Drupal\Core\Field\Plugin\DataType\FieldItem $field
   *   Field item from type link.
   *
   * @throws \Exception
   *
   * @return \Drupal\Core\Url
   *   Url object.
   */
  public function getUrlValue(FieldItem $field) {
    try {
      $field_type = $field->getFieldDefinition()->getType();
      if ($field_type == 'link') {
        $uri = $field->first()->getUrl();
        return $uri;
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('twig_extender_extras')->error($e->getMessage());
      throw $e;
    }
  }

}
