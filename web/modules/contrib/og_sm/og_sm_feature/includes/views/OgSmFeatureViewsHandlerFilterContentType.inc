<?php

/**
 * @file
 * Views filter handler.
 */

/**
 * Views handler to filter content by being a Site content type.
 *
 * This extends OgSmContentViewsHandlerFilterContentType to exclude content
 * types which are not enabled because of feature dependencies.
 */
class OgSmFeatureViewsHandlerFilterContentType extends OgSmContentViewsHandlerFilterContentType {

  /**
   * {@inheritdoc}
   */
  // @codingStandardsIgnoreStart
  public function get_value_options() {
    parent::get_value_options();
    if (empty($this->value_options) || !$this->site) {
      return;
    }

    foreach ($this->value_options as $type => $info) {
      $is_enabled = og_sm_feature_site_content_type_is_enabled($this->site, $type);
      if (!$is_enabled) {
        unset($this->value_options[$type]);
      }
    }

  }
  // @codingStandardsIgnoreEnd

}
