<?php

namespace Drupal\applenews\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Creates all the default Apple News plugins that contain an image.
 */
class ApplenewsDefaultComponentImageTypeDeriver extends DeriverBase implements ApplenewsDefaultDeriverInterface {

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
      'figure' => [
        'component_class' => 'ChapterThree\AppleNewsAPI\Document\Components\Figure',
        'label' => 'Figure',
        'description' => 'Renders an image that is considered a figure. Figures are graphical depictions of information that adds important context to the article.',
      ],
      'logo' => [
        'component_class' => 'ChapterThree\AppleNewsAPI\Document\Components\Logo',
        'label' => 'Logo',
        'description' => 'Describes an image of a company logo or brand.',
      ],
      'photo' => [
        'component_class' => 'ChapterThree\AppleNewsAPI\Document\Components\Photo',
        'label' => 'Photo',
        'description' => 'This component renders a photograph.',
      ],
      'portrait' => [
        'component_class' => 'ChapterThree\AppleNewsAPI\Document\Components\Portrait',
        'label' => 'Portrait',
        'description' => 'A photograph of a person.',
      ],
    ];
  }

}
