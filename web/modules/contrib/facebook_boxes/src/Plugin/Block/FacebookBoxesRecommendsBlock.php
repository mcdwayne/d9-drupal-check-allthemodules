<?php

/**
 * @file
 * Contains \Drupal\facebook_boxes\Plugin\Block\FacebookBoxesRecommendsBlock
 */
namespace Drupal\facebook_boxes\Plugin\Block;

use Drupal\Core\block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Facebook Recommends block.
 *
 * @Block(
 *  id = "facebook_boxes_recommends_block",
 *  subject = @Translation("Facebook Recommends Block"),
 *  admin_label = @Translation("Facebook Recommends Block")
 * )
 */

class FacebookBoxesRecommendsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    global $base_root;
    return array(
      'fb_rec_domain'       => parse_url($base_root, PHP_URL_HOST),
      'fb_rec_width'        => 292,
      'fb_rec_height'       => 300,
      'fb_rec_colorscheme'  => 'light',
      'fb_rec_border'       => '',
      'fb_rec_toggles'      => array('fb_blank', 'fb_header'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    global $base_root;
    /**
     * recommendations box documentation is at https://developers.facebook.com/docs/reference/plugins/recommendations/
     * It includes options that aren't included here, such as app id, actions, and font
     */
    $form['fb_rec_domain_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Site Domain'),
      '#description' => t('The domain to track activity on, e.g. ' . parse_url($base_root, PHP_URL_HOST)),
      '#default_value' => $this->configuration['fb_rec_domain'],
    );

    $form['fb_rec_width_text'] = array(
      '#type' => 'textfield',
      '#size' => 6,
      '#maxlength' => 4,
      '#title' => t('Width'),
      '#description' => t('Width, in pixels, of the Facebook box iframe.'),
      '#default_value' => $this->configuration['fb_rec_width'],
    );

    $form['fb_rec_height_text'] = array(
      '#type' => 'textfield',
      '#size' => 6,
      '#maxlength' => 5,
      '#title' => t('Height'),
      '#description' => t('Height, in pixels, of the Facebook box iframe.'),
      '#default_value' => $this->configuration['fb_rec_height'],
    );

    $form['fb_rec_colorscheme_select'] = array(
      '#type' => 'select',
      '#options' => array(
        'light' => t('Light'),
        'dark' => t('Dark'),
      ),
      '#title' => t('Color scheme'),
      '#description' => t('The color scheme used by the plugin.'),
      '#default_value' => $this->configuration['fb_rec_colorscheme'],
    );

    $form['fb_rec_border_text'] = array(
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 7,
      '#title' => t("Border color"),
      '#description' => t('Color of 1px border around iframe, including leading "#" such as #ff0000.'),
      '#default_value' => $this->configuration['fb_rec_border'],
    );

    $form['fb_rec_toggles_checks'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Configuration'),
      '#options' => array(
        'fb_blank' => 'Open links in new window/tab',
        'fb_header' => 'Show Facebook Header',
      ),
      '#default_value' => $this->configuration['fb_rec_toggles'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['fb_rec_domain']       = $values['fb_rec_domain_text'];
    $this->configuration['fb_rec_width']        = $values['fb_rec_width_text'];
    $this->configuration['fb_rec_height']       = $values['fb_rec_height_text'];
    $this->configuration['fb_rec_colorscheme']  = $values['fb_rec_colorscheme_select'];
    $this->configuration['fb_rec_border']       = $values['fb_rec_border_text'];

    $toggles = array();
    foreach ($values['fb_rec_toggles_checks'] as $key => $val) {
      if ($val) $toggles[] = $key;
    }
    $this->configuration['fb_rec_toggles']     = $toggles;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $height = $this->configuration['fb_rec_height'];
    $width  = $this->configuration['fb_rec_width'];
    $opts   = $this->configuration['fb_rec_toggles'];

    return array(
      '#type' => 'markup',
      '#markup' =>  sprintf('<iframe src="//www.facebook.com/plugins/recommendations.php?site=%s&amp;action&amp;width=%u&amp;height=%u&amp;colorscheme=%s&amp;&amp;border_color=%s&amp;header=%s&amp;font&amp;linktarget=%s" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:%upx; height:%upx;" allowTransparency="true"></iframe>',
        urlencode($this->configuration['fb_rec_domain']),
        $width,
        $height,
        urlencode($this->configuration['fb_rec_colorscheme']),
        urlencode($this->configuration['fb_rec_border']),
        (in_array('fb_header', $opts)) ? 'true' : 'false',
        (in_array('fb_target', $opts)) ? '_blank' : '_top',
        $width,
        $height
      ),
    );
  }
}
