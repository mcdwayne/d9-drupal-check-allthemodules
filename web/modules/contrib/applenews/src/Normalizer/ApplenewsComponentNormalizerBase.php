<?php

namespace Drupal\applenews\Normalizer;

use ChapterThree\AppleNewsAPI\Document\Layouts\ComponentLayout;
use ChapterThree\AppleNewsAPI\Document\Margin;
use Drupal\applenews\Plugin\ApplenewsComponentTypeManager;

/**
 * Class ApplenewsComponentNormalizerBase.
 *
 * Component normalizers should be extended from this class.
 */
abstract class ApplenewsComponentNormalizerBase extends ApplenewsNormalizerBase {

  /**
   * The component type of the plugin.
   *
   * This is used in ::supportsNormalization().
   *
   * @var string
   *
   * @see \Drupal\applenews\Annotation\ApplenewsComponentType
   */
  protected $componentType;

  /**
   * Component type manager.
   *
   * @var \Drupal\applenews\Plugin\ApplenewsComponentTypeManager
   */
  protected $applenewsComponentTypeManager;

  /**
   * Constructs a normalizer object.
   *
   * @param \Drupal\applenews\Plugin\ApplenewsComponentTypeManager $component_type_manager
   *   Component type manager.
   */
  public function __construct(ApplenewsComponentTypeManager $component_type_manager) {
    $this->applenewsComponentTypeManager = $component_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    // Only consider this normalizer if we are trying to normalize a content
    // entity into the 'applenews' format and the component is of type "text".
    if ($format === $this->format && is_array($data) && isset($data['id'])) {
      $component = $this->applenewsComponentTypeManager->createInstance($data['id']);
      return $component->getComponentType() == $this->componentType;
    }

    return FALSE;
  }

  /**
   * Get the class name needed to instantiate an Apple News component.
   *
   * @param string $plugin_id
   *   Plugin ID.
   *
   * @return string
   *   The fully-qualified name of the underlying Component class to use.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getComponentClass($plugin_id) {
    $component = $this->applenewsComponentTypeManager->createInstance($plugin_id);
    return $component->getComponentClass();
  }

  /**
   * Get the Component layout values.
   *
   * @param array $component_layout
   *   An array of component layout.
   *
   * @return \ChapterThree\AppleNewsAPI\Document\Layouts\ComponentLayout
   *   Layout object.
   */
  protected function getComponentLayout(array $component_layout) {
    $layout = new ComponentLayout();
    $layout->setColumnSpan($component_layout['column_span']);
    $layout->setColumnStart($component_layout['column_start']);
    $layout->setMargin(new Margin($component_layout['margin_top'], $component_layout['margin_bottom']));
    $layout->setIgnoreDocumentGutter($component_layout['ignore_gutter']);
    $layout->setIgnoreDocumentMargin($component_layout['ignore_margin']);
    $layout->setMinimumHeight($component_layout['minimum_height'] . $component_layout['minimum_height_unit']);

    if (isset($component_layout['maximum_width'])) {
      $layout->setMaximumContentWidth($component_layout['maximum_width'] . $component_layout['maximum_width_unit']);
    }

    return $layout;
  }

}
