<?php

namespace Drupal\entity_counter\Element;

use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for a single counter value.
 *
 * Properties:
 * - #entity_counter: The entity counter ID.
 * - #renderer_plugin: (Optional) The entity counter renderer plugin ID,
 *     defaults 'plain'.
 * - #renderer_settings: (Optional) The entity counter renderer plugin settings
 *     array.
 * - #wrapper_tag: (Optional) The wrapper HTML tag, defaults div.
 *
 * Usage example:
 * @code
 * $form['counter'] = [
 *   '#type' => 'entity_counter',
 *   '#entity_counter' => 'my_entity_counter',
 *   '#renderer_plugin' => 'plain'
 *   '#wrapper_tag' => 'span',
 * ];
 * @endcode
 *
 * @FormElement("entity_counter")
 */
class EntityCounter extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#counter_value' => NULL,
      '#entity_counter' => NULL,
      '#renderer_plugin' => 'plain',
      '#renderer_settings' => [],
      '#wrapper_tag' => 'div',
      '#pre_render' => [
        [$class, 'preRenderEntityCounter'],
      ],
      '#theme' => 'entity_counter',
      '#theme_wrappers' => ['entity_counter_wrapper'],
    ];
  }

  /**
   * Prepares a #type 'entity_counter' render element.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *
   * @return array
   *   The $element with prepared variables ready for the twig.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function preRenderEntityCounter(array $element) {
    /** @var \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter */
    $entity_counter = \Drupal::entityTypeManager()->getStorage('entity_counter')->load($element['#entity_counter']);

    // Generate the ID of the element.
    if (!isset($element['#id'])) {
      $id_parts = [
        'entity-counter',
        HtmlUtility::cleanCssIdentifier($element['#entity_counter']),
        'wrapper',
      ];
      $element['#id'] = HtmlUtility::getUniqueId(implode('-', $id_parts));
    }

    // Get and add the format to the entity counter value.
    if ($entity_counter) {
      try {
        $renderer_manager = \Drupal::getContainer()->get('plugin.manager.entity_counter.renderer');

        /** @var \Drupal\entity_counter\Plugin\EntityCounterRendererInterface $renderer */
        $renderer = $renderer_manager->createInstance($element['#renderer_plugin'], $element['#renderer_settings']);
        $renderer->setEntityCounter($entity_counter);
        $renderer->render($element);
      }
      catch (\Exception $exception) {
        watchdog_exception('entity_counter', $exception);
      }
    }

    return $element;
  }

}
