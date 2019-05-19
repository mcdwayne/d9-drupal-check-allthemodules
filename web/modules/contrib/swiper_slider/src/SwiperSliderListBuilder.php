<?php

namespace Drupal\swiper_slider;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Swiper slide entities.
 *
 * @ingroup swiper_slider
 */
class SwiperSliderListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Swiper slide ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\swiper_slider\Entity\SwiperSlider */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.swiper_slider.edit_form',
      ['swiper_slider' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
