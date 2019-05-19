<?php

namespace Drupal\trending_images\Plugin\TrendingSocialChannel;

use Drupal\Component\Plugin\PluginInspectionInterface;

interface TrendingImagesInterface extends PluginInspectionInterface {

  // Function for fetching social network latest feed.
  public function getSocialNetworkFeed($amount, $settings, $timestamp);

}
