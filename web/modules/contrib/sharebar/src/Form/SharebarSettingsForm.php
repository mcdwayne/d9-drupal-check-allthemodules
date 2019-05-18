<?php
/**
 * @file
 * Contains \Drupal\sharebar\Form\SharebarSettingsForm.
 */

namespace Drupal\sharebar\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\Core\Form\ConfigFormBase;


/**
 * Implements an example form.
 */
class SharebarSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'sharebar_settings';
  }

  /**
   * Form builder: Configure the sharebar system.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $extra = NULL) {

    $config = $this->config('sharebar.settings');
  //  echo "<pre>"; print_r($config); echo "</pre>"; die;
   // echo "hi"; echo $config->get('sharebar_bar_pages_enabled'); die;

   _drupal_add_css(drupal_get_path('module', 'sharebar') . '/css/colorpicker.css');
      _drupal_add_js(drupal_get_path('module', 'sharebar') . '/js/colorpicker.js');
      _drupal_add_js('jQuery(document).ready(function($) {
			var ids = ["edit-sharebar-bar-background","edit-sharebar-bar-border"];
			$.each(ids, function() {
				var id = this;
				$("#"+this).ColorPicker({
					onSubmit: function(hsb, hex, rgb, el) {
						$(el).val(hex);
						$(el).ColorPickerHide();
					},
					onBeforeShow: function () {
						$(this).ColorPickerSetColor(this.value);
					},
					onChange: function(hsb, hex, rgb, el) {
						$("#"+id).val(hex);
					}
				});
			});
		});
  ', 'inline');


      $form['buttonsset'] = array(
        '#type' => 'fieldset',
        '#title' => t('Buttons'),
        '#description' => t(''),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );

      $form['buttonsset']['buttons'] = array(
        '#theme' => 'sharebar_buttons_table',
        '#weight' => 0,
      );

      // Add sharebar.
      $form['addsharebar'] = array(
        '#type' => 'fieldset',
        '#weight' => 1,
        '#title' => t('Add sharebar'),
        '#description' => t('The following settings allow you to automatically add the Sharebar to your pages.'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );
      $form['addsharebar']['nodetypes'] = array(
        '#type' => 'fieldset',
        '#title' => t('Content Types'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      );
      $node_types = node_type_get_types();
      $node_names = node_type_get_names();
      if (is_array($node_names) && count($node_names)) {
        foreach ($node_names as $key => $value) {
          $form['addsharebar']['nodetypes']['sharebar_bar_posts_' . $node_types[$key]->type . '_enabled'] = array(
            '#type' => 'checkbox',
            '#title' => t('Automatically add Sharebar to content type @value (only affects content type @value)', array('@value' => $value)),
            '#default_value' => \Drupal::config('sharebar.settings')->get('sharebar_bar_posts_' . $node_types[$key]->type . '_enabled'),
          );
        }
      }

      $form['addsharebar']['sharebar_bar_pages_enabled'] = array(
        '#type' => 'checkbox',
        '#title' => t('Automatically add to all pages except content pages.'),
        '#default_value' => \Drupal::config('sharebar.settings')->get('sharebar_bar_pages_enabled'),
      );

      // Display options.
      $form['displayoptions'] = array(
        '#type' => 'fieldset',
        '#weight' => 2,
        '#title' => t('Display options'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );

      $form['displayoptions']['sharebar_bar_horizontal'] = array(
        '#type' => 'checkbox',
        '#title' => t('Display horizontal Sharebar if the page is resized to less than 1000px?'),
        '#default_value' => \Drupal::config('sharebar.settings')->get('sharebar_bar_horizontal'),
      );

      $form['displayoptions']['sharebar_bar_oncontent'] = array(
        '#type' => 'checkbox',
        '#title' => t('Display sharebar in content'),
        '#default_value' => \Drupal::config('sharebar.settings')->get('sharebar_bar_oncontent'),
      );

      $form['displayoptions']['sharebar_bar_credit'] = array(
        '#type' => 'checkbox',
        '#title' => t('Display credit link back to the Sharebar plugin? If disabled, please consider donating.'),
        '#default_value' => \Drupal::config('sharebar.settings')->get('sharebar_bar_credit'),
      );

      $form['displayoptions']['sharebar_bar_idcontent'] = array(
        '#type' => 'textfield',
        '#title' => t('Custom CSS Container when displayed in content region(only id selector is supported)'),
        '#size' => 10,
        '#default_value' => \Drupal::config('sharebar.settings')->get('sharebar_bar_idcontent'),
        '#states' => array(
          'visible' => array (
            ':input[name="sharebar_bar_oncontent"]' => array('checked' => TRUE),
          ),
        ),
      );

      $form['displayoptions']['sharebar_bar_id'] = array(
        '#type' => 'textfield',
        '#title' => t('Custom CSS Container when not displayed in content region (only id selector is supported)'),
        '#size' => 10,
        '#default_value' => \Drupal::config('sharebar.settings')->get('sharebar_bar_id'),
        '#states' => array(
          'visible' => array (
            ':input[name="sharebar_bar_oncontent"]' => array('checked' => FALSE),
          ),
        ),
      );

      $form['displayoptions']['sharebar_bar_idhorizontal'] = array(
        '#type' => 'textfield',
        '#title' => t('Custom CSS Container when displayed horizontally (only id selector is supported)'),
        '#size' => 10,
        '#default_value' => \Drupal::config('sharebar.settings')->get('sharebar_bar_idhorizontal'),
        '#states' => array(
          'visible' => array (
            ':input[name="sharebar_bar_horizontal"]' => array('checked' => TRUE),
          ),
        ),
      );

      $form['displayoptions']['sharebar_bar_toptoffset'] = array(
        '#type' => 'textfield',
        '#title' => t('Top offset'),
        '#size' => 10,
        '#default_value' => \Drupal::config('sharebar.settings')->get('sharebar_bar_toptoffset'),
      );

      $form['displayoptions']['sharebar_bar_position'] = array(
        '#type' => 'select',
        '#title' => t('Sharebar Position'),
        '#default_value' => \Drupal::config('sharebar.settings')->get('sharebar_bar_position'),
        '#options' => array('left' => 'Left', 'right' => 'Right'),
      );

      $form['displayoptions']['sharebar_bar_leftoffset'] = array(
        '#type' => 'textfield',
        '#title' => t('Left Offset (used when positioned to left)'),
        '#size' => 10,
        '#default_value' => \Drupal::config('sharebar.settings')->get('sharebar_bar_leftoffset'),
      );

      $form['displayoptions']['sharebar_bar_rightoffset'] = array(
        '#type' => 'textfield',
        '#title' => t('Right Offset (used when positioned to right)'),
        '#size' => 10,
        '#default_value' => \Drupal::config('sharebar.settings')->get('sharebar_bar_rightoffset'),
      );

      $form['displayoptions']['sharebar_bar_width'] = array(
        '#type' => 'textfield',
        '#title' => t('Minimum width in pixels required to show vertical Sharebar to the left of post (cannot be blank)'),
        '#size' => 10,
        '#default_value' => \Drupal::config('sharebar.settings')->get('sharebar_bar_width'),
      );

      // Customize.
      $form['customize'] = array(
        '#type' => 'fieldset',
        '#weight' => 3,
        '#title' => t('Customize'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );

      $form['customize']['sharebar_bar_swidth'] = array(
        '#type' => 'textfield',
        '#title' => t('Sharebar Width'),
        '#size' => 10,
        '#default_value' => \Drupal::config('sharebar.settings')->get('sharebar_bar_swidth'),
      );

      $form['customize']['sharebar_bar_twitter_username'] = array(
        '#type' => 'textfield',
        '#title' => t('Twitter Username'),
        '#size' => 10,
        '#default_value' => \Drupal::config('sharebar.settings')->get('sharebar_bar_twitter_username'),
      );

      $form['customize']['sharebar_bar_background'] = array(
        '#type' => 'textfield',
        '#title' => t('Sharebar Background Color'),
        '#size' => 10,
        '#default_value' => \Drupal::config('sharebar.settings')->get('sharebar_bar_background'),
      );

      $form['customize']['sharebar_bar_border'] = array(
        '#type' => 'textfield',
        '#title' => t('Sharebar Border Color'),
        '#size' => 10,
        '#default_value' => \Drupal::config('sharebar.settings')->get('sharebar_bar_border'),
      );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node_types = node_type_get_types();
    $node_names = node_type_get_names();
    if (is_array($node_names) && count($node_names)) {
      foreach ($node_names as $key => $value) {
        $this->config('sharebar.settings')
          ->set('sharebar_bar_posts_' . $node_types[$key]->type . '_enabled', $form_state->getValue('sharebar_bar_posts_' . $node_types[$key]->type . '_enabled'))
          ->save();
      }
    }

    $this->config('sharebar.settings')
      ->set('sharebar_bar_pages_enabled', $form_state->getValue('sharebar_bar_pages_enabled'))
      ->set('sharebar_bar_horizontal', $form_state->getValue('sharebar_bar_horizontal'))
      ->set('sharebar_bar_oncontent', $form_state->getValue('sharebar_bar_oncontent'))
      ->set('sharebar_bar_credit', $form_state->getValue('sharebar_bar_credit'))
      ->set('sharebar_bar_idcontent', $form_state->getValue('sharebar_bar_idcontent'))
      ->set('sharebar_bar_id', $form_state->getValue('sharebar_bar_id'))
      ->set('sharebar_bar_idhorizontal', $form_state->getValue('sharebar_bar_idhorizontal'))
      ->set('sharebar_bar_toptoffset', $form_state->getValue('sharebar_bar_toptoffset'))
      ->set('sharebar_bar_position', $form_state->getValue('sharebar_bar_position'))
      ->set('sharebar_bar_leftoffset', $form_state->getValue('sharebar_bar_leftoffset'))
      ->set('sharebar_bar_rightoffset', $form_state->getValue('sharebar_bar_rightoffset'))
      ->set('sharebar_bar_width', $form_state->getValue('sharebar_bar_width'))
      ->set('sharebar_bar_swidth', $form_state->getValue('sharebar_bar_swidth'))
      ->set('sharebar_bar_twitter_username', $form_state->getValue('sharebar_bar_twitter_username'))
      ->set('sharebar_bar_background', $form_state->getValue('sharebar_bar_background'))
      ->set('sharebar_bar_border', $form_state->getValue('sharebar_bar_border'))
      ->save();

    parent::submitForm($form, $form_state);

  }
}
?>