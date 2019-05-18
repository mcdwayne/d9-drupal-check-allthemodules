<?php

namespace Drupal\applenews\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Class ApplenewsDefaultComponentNestedDeriver.
 *
 * @package Drupal\applenews\Derivative
 */
class ApplenewsDefaultComponentNestedDeriver extends DeriverBase implements ApplenewsDefaultDeriverInterface {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    foreach ($this->getComponentClasses() as $id => $info) {
      $this->derivatives[$id] = $info + $base_plugin_definition;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getComponentClasses() {
    return [
      'container' => [
        'component_class' => 'ChapterThree\AppleNewsAPI\Document\Components\Container',
        'label' => 'Container',
        'description' => 'A container component has child components that are rendered relative to the parent component.',
      ],
      'section' => [
        'component_class' => 'ChapterThree\AppleNewsAPI\Document\Components\Section',
        'label' => 'Section',
        'description' => 'A section is a full-width Container with child components. Section components are intended to organize an article at the top level.',
      ],
      'chapter' => [
        'component_class' => 'ChapterThree\AppleNewsAPI\Document\Components\Chapter',
        'label' => 'Chapter',
        'description' => 'A section is a full-width Container with child components. Same as a Section, but can be used for semantic precision.',
      ],
      'aside' => [
        'component_class' => 'ChapterThree\AppleNewsAPI\Document\Components\Aside',
        'label' => 'Aside',
        'description' => 'An aside component contains text that News personalization will ignore. Intended for content not directly related to the article.',
      ],
      'header' => [
        'component_class' => 'ChapterThree\AppleNewsAPI\Document\Components\Header',
        'label' => 'Header',
        'description' => 'A header (top area) can be defined for an article, or for a Section or Chapter component.',
      ],
    ];
  }

}
