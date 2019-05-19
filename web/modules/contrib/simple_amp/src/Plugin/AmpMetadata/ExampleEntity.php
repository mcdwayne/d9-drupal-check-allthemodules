<?php

namespace Drupal\simple_amp\Plugin\AmpMetadata;

use Drupal\simple_amp\AmpMetadataBase;
use Drupal\simple_amp\Metadata\Metadata;
use Drupal\simple_amp\Metadata\Author;
use Drupal\simple_amp\Metadata\Publisher;
use Drupal\simple_amp\Metadata\Image;

/**
 * Example AMP metadata component.
 *
 * @AmpMetadata(
 *   id = "default",
 *   entity_types = {
 *     "example_article"
 *   }
 * )
 */
class ExampleEntity extends AmpMetadataBase {

  /**
   * {@inheritdoc}
   */
  public function getMetadata($entity) {
    $metadata = new Metadata();
    $author = (new Author())
      ->setName('Test Author');
    $logo = (new Image())
      ->setUrl('http://url-to-image')
      ->setWidth(400)
      ->setHeight(300);
    $publisher = (new Publisher())
      ->setName('MyWebsite.com')
      ->setLogo($logo);
    $image = (new Image())
      ->setUrl('http://url-to-image')
      ->setWidth(400)
      ->setHeight(300);
    $metadata
      ->setDatePublished($entity->getCreatedTime())
      ->setDateModified($entity->getChangedTime())
      ->setDescription('test')
      ->setAuthor($author)
      ->setPublisher($publisher)
      ->setImage($image);
    return $metadata->build();
  }

}
