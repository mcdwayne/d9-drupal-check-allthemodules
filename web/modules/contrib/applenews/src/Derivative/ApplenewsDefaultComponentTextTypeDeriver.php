<?php

namespace Drupal\applenews\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Creates all the default Apple News plugins that contain text.
 */
class ApplenewsDefaultComponentTextTypeDeriver extends DeriverBase implements ApplenewsDefaultDeriverInterface {

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
      'author' => [
        'component_class' => 'ChapterThree\AppleNewsAPI\Document\Components\Author',
        'label' => 'Author',
        'description' => 'The name of one of the authors of the article.',
      ],
      'body' => [
        'component_class' => 'ChapterThree\AppleNewsAPI\Document\Components\Body',
        'label' => 'Body',
        'description' => 'A chunk of text.',
      ],
      'byline' => [
        'component_class' => 'ChapterThree\AppleNewsAPI\Document\Components\Byline',
        'label' => 'Byline',
        'description' => 'A byline describes one or more contributors to the article, and usually includes the word "by" or "from" as well as the contributors\' names.',
      ],
      'caption' => [
        'component_class' => 'ChapterThree\AppleNewsAPI\Document\Components\Caption',
        'label' => 'Caption',
        'description' => 'The text of a caption for another component in the document.',
      ],
      'intro' => [
        'component_class' => 'ChapterThree\AppleNewsAPI\Document\Components\Intro',
        'label' => 'Intro',
        'description' => 'Text that serves as the introduction of the article.',
      ],
      'pull_quote' => [
        'component_class' => 'ChapterThree\AppleNewsAPI\Document\Components\Pullquote',
        'label' => 'Pull Quote',
        'description' => 'A pullquote is usually an excerpt from the body text. It generally duplicates that text in a format that increases its visibility.',
      ],
      'quote' => [
        'component_class' => 'ChapterThree\AppleNewsAPI\Document\Components\Quote',
        'label' => 'Quote',
        'description' => 'The text of a quotation. Unlike a Pull Quote, a quote is usually not duplicated content.',
      ],
      'title' => [
        'component_class' => 'ChapterThree\AppleNewsAPI\Document\Components\Title',
        'label' => 'Title',
        'description' => 'The article\'s title or headline. ',
      ],
    ];
  }

}
