<?php

/**
 * @file
 * Contains \Drupal\facebook_boxes\Plugin\Block\FacebookBoxesLikeBlock
 */
namespace Drupal\facebook_boxes\Plugin\Block;

use Drupal\Core\block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Facebook Like block.
 *
 * @Block(
 *  id = "facebook_boxes_like_block",
 *  subject = @Translation("Facebook Like Block"),
 *  admin_label = @Translation("Facebook Like Block")
 * )
 */
class FacebookBoxesLikeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'fb_like_url' => 'http://www.facebook.com/platform',
      'fb_like_width' => 292,
      'fb_like_height' => 300,
      'fb_like_colorscheme' => 'light',
      'fb_like_border' => '',
      'fb_like_toggles' => array('fb_faces', 'fb_header'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['fb_like_url_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Facebook Page URL'),
      '#description' => t('The full URL of your Facebook page, e.g. http://www.facebook.com/platform'),
      '#default_value' => $this->configuration['fb_like_url'],
    );

    $form['fb_like_width_text'] = array(
      '#type' => 'textfield',
      '#size' => 6,
      '#maxlength' => 4,
      '#title' => t('Width'),
      '#description' => t('Width, in pixels, of the Facebook box iframe.'),
      '#default_value' => $this->configuration['fb_like_width'],
    );

    $form['fb_like_height_text'] = array(
      '#type' => 'textfield',
      '#size' => 6,
      '#maxlength' => 5,
      '#title' => t('Height'),
      '#description' => t('Height, in pixels, of the Facebook box iframe.'),
      '#default_value' => $this->configuration['fb_like_height'],
    );

    $form['fb_like_colorscheme_select'] = array(
      '#type' => 'select',
      '#options' => array(
        'light' => t('Light'),
        'dark' => t('Dark'),
      ),
      '#title' => t('Color scheme'),
      '#description' => t('The color scheme used by the plugin.'),
      '#default_value' => $this->configuration['fb_like_colorscheme'],
    );

    $form['fb_like_border_text'] = array(
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 7,
      '#title' => t("Border color"),
      '#description' => t('Color of 1px border around iframe, including leading "#" such as #ff0000.'),
      '#default_value' => $this->configuration['fb_like_border'],
    );

    $form['fb_like_toggles_options'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Configuration'),
      '#options' => array(
        'fb_faces' => 'Show Faces',
        'fb_stream' => 'Show Stream',
        'fb_header' => 'Show FB Header',
      ),
      '#default_value' => $this->configuration['fb_like_toggles'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['fb_like_url']         = $values['fb_like_url_text'];
    $this->configuration['fb_like_width']       = $values['fb_like_width_text'];
    $this->configuration['fb_like_height']      = $values['fb_like_height_text'];
    $this->configuration['fb_like_colorscheme'] = $values['fb_like_colorscheme_select'];
    $this->configuration['fb_like_border']      = $values['fb_like_border_text'];

    $toggles = array();
    foreach ($values['fb_like_toggles_options'] as $key => $val) {
      if ($val) $toggles[] = $key;
    }
    $this->configuration['fb_like_toggles']     = $toggles;
  }

  public function build() {
    $url          = urlencode( $this->configuration['fb_like_url']);
    $width        = $this->configuration['fb_like_width'];
    $height       = $this->configuration['fb_like_height'];
    $colorscheme  = urlencode($this->configuration['fb_like_colorscheme']);
    $border       = urlencode($this->configuration['fb_like_border']);
    $opts         = $this->configuration['fb_like_toggles'];
    return array(
      '#type' => 'markup',
      '#markup' => sprintf('<iframe src="//www.facebook.com/plugins/likebox.php?href=%s&amp;width=%u&amp;height=%u&amp;colorscheme=%s&amp;show_faces=%s&amp;border_color=%s&amp;stream=%s&amp;header=%s" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:%spx; height:%spx;" allowTransparency="true"></iframe>',
        $url,
        $width,
        $height,
        $colorscheme,
        (in_array('fb_faces', $opts)) ? 'true' : 'false',
        $border,
        (in_array('fb_stream', $opts)) ? 'true' : 'false',
        (in_array('fb_header', $opts)) ? 'true' : 'false',
        $width,
        $height
      ),
    );
  }
}
