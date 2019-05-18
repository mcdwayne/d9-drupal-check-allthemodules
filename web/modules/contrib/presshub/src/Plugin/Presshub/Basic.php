<?php

namespace Drupal\presshub\Plugin\Presshub;

use Drupal\presshub\PresshubBase;
use Presshub\Template;
use Presshub\Template\Component;

/**
 * Presshub template for Article content type.
 *
 * @Presshub(
 *   id = "basic",
 *   name = @Translation("Basic"),
 *   entity_types = {
 *     "article",
 *   }
 * )
 */
class Basic extends PresshubBase {

  /**
   * {@inheritdoc}
   */
  public function isPublishable($entity) {
    if ($entity->bundle() == 'article') {
      $value = $entity->get('field_distribution_partner')->getValue();
      if (!empty($value['0']['value']) && $value['0']['value'] == 'AppleNews') {
        return TRUE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isPreview($entity) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setServiceParams($entity) {
    $config = \Drupal::config('presshub.settings');
    if ($entity->bundle() == 'article') {
      $service = $entity->get('field_presshub_service')->getValue();
      switch ($service) {
        case 'AppleNews':
          return [
            'AppleNews' => [
              'sections' => [],
            ]
          ];
          break;
        case 'GoogleAmp':
          return [
            'GoogleAmp' => [
              'content_id' => $entity->id(),
              'signature'  => $config->get('amp_signature'),
            ]
          ];
          break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function template($entity) {

    // Create Presshub template.
    $template = Template::create();
    $template->setTitle('Your Article Title');
    $template->setSubTitle('Your Article Subtitle');
    $template->setCanonicalURL( 'http://example.com/your-article-url.html' );
    $template->setThumbnail( 'https://example.com/article-thumbnail.jpg' );
    $template->setKeywords(['Keyword1', 'Keyword2', 'Keyword3']);
    $template->setTemplate( 'basic' )
      ->addComponent(
        Component::create()
          ->setMap('category')
          ->setValue('News')
          ->setProps()
      )
      ->addComponent(
        Component::create()
          ->setMap('byline')
          ->setValue('By Author Name')
          ->setProps()
      )
      ->addComponent(
        Component::create()
          ->setMap('featured_image')
          ->setValue('URL')
          ->setProps([
            'Caption'      => 'Image caption',
            'Photographer' => 'Photo by Name',
          ])
      )
      ->addComponent(
        Component::create()
          ->setMap('body')
          ->setValue('<p>HTML content</p>')
          ->setProps()
      );

    return $template;

  }

}
