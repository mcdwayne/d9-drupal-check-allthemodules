<?php

/**
 * @file
 * Contains \Drupal\facebook_boxes\Plugin\Block\FacebookBoxesFollowBlock
 */

namespace Drupal\facebook_boxes\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Facebook Follow block.
 *
 * @Block(
 *  id = "facebook_boxes_follow_block",
 *  subject = @Translation("Facebook Follow Block"),
 *  admin_label = @Translation("Facebook Follow Block")
 * )
 */
class FacebookBoxesFollowBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'fb_follow_url' => 'http://www.facebook.com/mediacurrent',
      'fb_follow_layout' => 'standard',
      'fb_follow_showface' => 1,
      'fb_follow_color' => 'light',
      'fb_follow_font' => 'arial',
      'fb_follow_width' => 450,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['fb_follow_url_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Facebook Page URL'),
      '#description' => t('The full URL of your Facebook page, e.g. http://www.facebook.com/newsignature'),
      '#default_value' => $this->configuration['fb_follow_url'],
    );
    $form['fb_follow_layout_select'] = array(
      '#type' => 'select',
      '#title' => t('Layout'),
      '#options' => array(
        'standard' => 'standard',
        'box_count' => 'box_count',
        'button_count' => 'button_count',
        'button' => 'button',
        //standard, box_count, button_count, button
      ),
      '#default_value' => $this->configuration['fb_follow_layout'],
    );
    $form['fb_follow_showface_checkbox'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show faces'),
      '#description' => t('Specifies whether to display profile photos below the button (standard layout only)'),
      '#return_value' => 1,
      '#default_value' => $this->configuration['fb_follow_showface'],
    );
    $form['fb_follow_colorscheme_select'] = array(
      '#type' => 'select',
      '#title' => t('Color Scheme'),
      '#options' => array('light' => 'light', 'dark' => 'dark'),
      '#default_value' => $this->configuration['fb_follow_color'],
    );
    $form['fb_follow_font_select'] = array(
      '#type' => 'select',
      '#title' => t('Font'),
      '#options' => array(
        'arial' => 'arial',
        'lucida grande' => 'lucida grande',
        'segoe ui' => 'segoe ui',
        'tahoma' => 'tahoma',
        'trebuchet ms' => 'trebuchet ms',
        'verdana' => 'verdana'
      ),
      '#default_value' => $this->configuration['fb_follow_font'],
    );
    $form['fb_follow_width_text'] = array(
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 7,
      '#title' => t('Width'),
      '#description' => t('Width of the plugin'),
      '#default_value' => $this->configuration['fb_follow_width'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['fb_follow_url']       = $values['fb_follow_url_text'];
    $this->configuration['fb_follow_layout']    = $values['fb_follow_layout_select'];
    $this->configuration['fb_follow_showface']  = $values['fb_follow_showface_checkbox'];
    $this->configuration['fb_follow_color']     = $values['fb_follow_colorscheme_select'];
    $this->configuration['fb_follow_font']      = $values['fb_follow_font_select'];
    $this->configuration['fb_follow_width']     = $values['fb_follow_width_text'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $url = $this->configuration['fb_follow_url'];
    $layout = $this->configuration['fb_follow_layout'];
    $showfaces = ($this->configuration['fb_follow_showface']) ? 'TRUE' : 'FALSE';
    $width = preg_replace('/[^\d\s]/', '', $this->configuration['fb_follow_width']);

    $out = array();
    $out[] = '<iframe src="http://www.facebook.com/plugins/follow.php?href=' . $url;
    $out[] = '&amp;layout=' . $layout;
    if($layout == 'standard') {
      $out[] = '&amp;show_faces=' . $showfaces;
    }
    $out[] = '&amp;colorscheme=' . $this->configuration['fb_follow_color'];
    $out[] = '&amp;font=' . $this->configuration['fb_follow_font'];
    $out[] = '&amp;width=' . $width;
    $out[] = '&amp;height=80" scrolling="no" frameborder="0" ';
    $out[] = 'style="border:none; overflow:hidden; width:'. $width;
    $out[] = ';height:80px; "allowTransparency="true"></iframe>';

    return array(
      '#type' => 'markup',
      '#markup' =>  implode('', $out),
    );
  }
}
