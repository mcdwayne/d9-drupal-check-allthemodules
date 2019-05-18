<?php

namespace Drupal\plus\Annotation;

use Doctrine\Common\Annotations\AnnotationException;
use Drupal\Component\Annotation\AnnotationInterface;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Template annotation object.
 *
 * Plugin Namespace: "Plugin/Theme/Template".
 *
 * @see \Drupal\plus\Plugin\Theme\Template\TemplateInterface
 * @see \Drupal\plus\Plugin\Theme\Template\ProviderPluginManager
 * @see plugin_api
 *
 * @Annotation
 *
 * @ingroup plugins_template
 */
class Template extends Plugin {

  /**
   * A list of acceptable placements.
   *
   * @var array
   *
   * @see \Drupal\plus\Annotation\Template::$placement
   */
  public static $PLACEMENTS = [
    'append',
    'prepend',
    'replace_append',
    'replace_prepend',
  ];

  /**
   * The plugin ID.
   *
   * When an annotation is given no key, 'value' is assumed by Doctrine.
   *
   * @var string
   */
  public $value;

  /**
   * Flag that determines how to add the plugin to a callback array.
   *
   * @var string
   *
   * @see \Drupal\plus\Annotation\Template::$PLACEMENTS
   * @see \Drupal\plus\Plugin\Theme\ThemeBase::addCallback()
   */
  public $placement = 'append';

  /**
   * A callback to replace.
   *
   * @var string
   */
  public $replace = FALSE;

  /**
   * The default variables for this template.
   *
   * @var array
   */
  public $variables = [];

  /**
   * Used for render element items only.
   *
   * The name of the renderable element or element tree to pass to the template.
   * This name is used as the name of the variable that holds the renderable
   * element or tree in the preprocess stage.
   *
   * @var string
   */
  public $renderElement = '';

  /**
   * {@inheritdoc}
   */
  public function get() {
    $definition = parent::get();
    $parent_properties = array_keys($definition);
    $parent_properties[] = 'value';

    // Merge in the defined properties.
    $reflection = new \ReflectionClass($this);
    foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
      $name = $property->getName();
      if (in_array($name, $parent_properties)) {
        continue;
      }
      $value = $property->getValue($this);
      if ($value instanceof AnnotationInterface) {
        $value = $value->get();
      }
      if ($name === 'placement' && !in_array($value, static::$PLACEMENTS)) {
        throw new AnnotationException("Invalid placement type: $value");
      }
      $definition[$name] = $value;
    }

    return $definition;
  }

}
