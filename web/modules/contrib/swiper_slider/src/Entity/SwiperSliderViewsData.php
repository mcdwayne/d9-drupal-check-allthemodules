<?php

namespace Drupal\swiper_slider\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Swiper slide entities.
 */
class SwiperSliderViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
