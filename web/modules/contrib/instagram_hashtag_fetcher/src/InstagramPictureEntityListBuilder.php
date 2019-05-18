<?php

namespace Drupal\instagram_hashtag_fetcher;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Instagram Picture Entity entities.
 *
 * @ingroup instagram_pictures
 */
class InstagramPictureEntityListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Instagram ID');
    $header['instagram_link'] = $this->t('Instagram Link');
    $header['thumbnail'] = $this->t('Thumbnail');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\instagram_pictures\Entity\InstagramPictureEntity */
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.instagram_picture_entity.edit_form',
      ['instagram_picture_entity' => $entity->id()]
    );
    $row['instagram_link'] = new \Drupal\Component\Render\FormattableMarkup('<a href=":link" target="_blank">Go To Instagram Post</a>', [':link' => $entity->field_instagram_media_link[0]->value]);
    $row['thumbnail'] = new \Drupal\Component\Render\FormattableMarkup('<img src=":img" style="width:50px;height:50px;">', [':img' => \Drupal\image\Entity\ImageStyle::load('thumbnail')->buildUrl($entity->field_instagram_picture[0]->entity->uri->value)]);
    return $row + parent::buildRow($entity);
  }

}
