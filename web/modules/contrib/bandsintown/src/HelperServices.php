<?php

namespace Drupal\bandsintown;

/**
 * Provides service with some helper functions for Bandsintown module.
 */
class HelperServices {

  protected $isBandsintown;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->isBandsintown = FALSE;
  }

  /**
   * Simple check whether current attribute belongs to Bandsintown.
   */
  public function isBandsintown($attribute, $check) {
    if (substr($attribute, 0, strlen($check)) === $check) {
      return TRUE;
    }
    return $this->isBandsintown;
  }

  /**
   * Bandsintown settings array.
   */
  public function bandsintownSettings() {
    return array(
      'data_artist' => array(
        'value' => '',
        'desc'  => t('Artist name'),
        'type'  => 'string',
      ),
      'data_force_narrow_layout' => array(
        'value' => FALSE,
        'desc'  => t('Tour Dates Widget force narrow layout'),
        'type'  => 'boolean',
      ),
      'data_display_limit' => array(
        'value' => 3,
        'desc'  => t('Tour Dates Widget display limit'),
        'type'  => 'integer',
      ),
      'data_text_color' => array(
        'value' => '#000000',
        'desc'  => t('Tour Dates Widget text color'),
        'type'  => 'string',
      ),
      'data_link_color' => array(
        'value' => '#000000',
        'desc'  => t('Tour Dates Widget link color'),
        'type'  => 'string',
      ),
      'data_bg_color' => array(
        'value' => 'none',
        'desc'  => t('Tour Dates Widget background color'),
        'type'  => 'string',
      ),
      'data_separator_color' => array(
        'value' => '#e9e9e9',
        'desc'  => t('Tour Dates Widget separator color'),
        'type'  => 'string',
      ),
      'data_width' => array(
        'value' => '100%',
        'desc'  => t('Tour Dates Widget width'),
        'type'  => 'string',
      ),
      'data_bandsintown_footer_link' => array(
        'value' => FALSE,
        'desc'  => t('Tour Dates Widget footer link'),
        'type'  => 'boolean',
      ),
      'data_notify_me' => array(
        'value' => TRUE,
        'desc'  => t('Tour Dates Widget notify me'),
        'type'  => 'boolean',
      ),
      'data_share_links' => array(
        'value' => TRUE,
        'desc'  => t('Tour Dates Widget share links'),
        'type'  => 'boolean',
      ),
      'data_share_url' => array(
        'value' => '',
        'desc'  => t('Tour Dates Widget share url'),
        'type'  => 'string',
      ),
      'data_div_id' => array(
        'value' => NULL,
        'desc'  => t('Tour Dates Widget div id'),
        'type'  => 'string',
      ),
      'data_facebook_page_id' => array(
        'value' => NULL,
        'desc'  => t('Tour Dates Widget Facebook page id'),
        'type'  => 'string',
      ),
      // Tour Widget v2 only
      'data_link_text_color' => array(
        'value' => '#FFFFFF',
        'desc'  => t('Tour Dates Widget link text color'),
        'type'  => 'string',
      ),
      'data_background_color' => array(
        'value' => 'transparent',
        'desc'  => t('Tour Dates Widget background color'),
        'type'  => 'string',
      ),
      'data_popup_background_color' => array(
        'value' => '#FFFFFF',
        'desc'  => t('Tour Dates Widget popup background color'),
        'type'  => 'string',
      ),
      'data_font' => array(
        'value' => 'Helvetica',
        'desc'  => t('Tour Dates Widget font name'),
        'type'  => 'string',
      ),
      'data_widget_width' => array(
        'value' => '100%',
        'desc'  => t('Tour Dates Widget width'),
        'type'  => 'string',
      ),
      'data_display_local_dates' => array(
        'value' => FALSE,
        'desc'  => t('Tour Dates Widget display local dates'),
        'type'  => 'boolean',
      ),
      'data_display_past_dates' => array(
        'value' => TRUE,
        'desc'  => t('Tour Dates Widget display past dates'),
        'type'  => 'boolean',
      ),
      'data_auto_style' => array(
        'value' => FALSE,
        'desc'  => t('Tour Dates Widget auto style'),
        'type'  => 'boolean',
      ),
      // Track button
      'button_size' => array(
        'value' => 'large',
        'desc'  => t('Track button size'),
        'type'  => 'string',
      ),
      'button_display_tracker_count' => array(
        'value' => TRUE,
        'desc'  => t('Track button display tracker count'),
        'type'  => 'boolean',
      ),
      'button_text_color' => array(
        'value' => '#ffffff',
        'desc'  => t('Track button text color'),
        'type'  => 'string',
      ),
      'button_background_color' => array(
        'value' => '#22cb65',
        'desc'  => t('Track button background color'),
        'type'  => 'string',
      ),
      'button_hover_color' => array(
        'value' => '#1dac56',
        'desc'  => t('Track button hover color'),
        'type'  => 'string',
      ),
      'button_height' => array(
        'value' => 32,
        'desc'  => t('Track button height'),
        'type'  => 'integer',
      ),
      'button_width' => array(
        'value' => 165,
        'desc'  => t('Track button width'),
        'type'  => 'integer',
      ),
      'button_scrolling' => array(
        'value' => 'no',
        'desc'  => t('Track button scrolling'),
        'type'  => 'string',
      ),
      'button_frameborder' => array(
        'value' => 0,
        'desc'  => t('Track button frameborder'),
        'type'  => 'integer',
      ),
      'button_style' => array(
        'value' => 'border:none; overflow:hidden;',
        'desc'  => t('Track button style'),
        'type'  => 'string',
      ),
      'button_allowtransparency' => array(
        'value' => TRUE,
        'desc'  => t('Track button allowtransparency'),
        'type'  => 'boolean',
      ),
    );
  }

}
