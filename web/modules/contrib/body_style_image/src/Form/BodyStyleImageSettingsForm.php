<?php

namespace Drupal\body_style_image\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\image\Entity\ImageStyle;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Displays the Ajax links API settings form.
 */
class BodyStyleImageSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'body_style_image_adminsettings';
  }

  /**
   * {@inheritdoc}
   */
   
  public function getEditableConfigNames() {
    return ['body_style_image.adminsettings'];
  }
   
  public function buildForm(array $form, FormStateInterface $form_state) {
	$config = $this->config('body_style_image.adminsettings');
	$content_types = node_type_get_types();
	$styles_options = array();
    $styles_options[''] = '- None -';
	$image_styles = ImageStyle::loadMultiple();
	if(!empty($image_styles)) {
	  foreach ($image_styles as $style) {
		$styles_options[$style->id()] = $style->label();
	  }
	}
    $form['body_style_image'] = array(
      '#type' => 'fieldset',
      '#title' => t('Image Style Settings'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#description' => t('Image style will be applied for Body Section images of Selected Content type.'),
    );
    foreach ($content_types as $content_type) {
	  $content_type_name = $content_type->label();
	  $content_type_id = $content_type->id();
      $form['body_style_image']['body_style_image_content_type_' . $content_type_id] = array(
        '#type' => 'checkbox',
        '#title' => SafeMarkup::checkPlain($content_type_name),
        '#options' => $content_type_name,
        '#default_value' => $config->get('body_style_image_content_type_' . $content_type_id),
      );
      $form['body_style_image']['body_style_image_style_' . $content_type_id] = array(
        '#type' => 'select',
        '#title' => SafeMarkup::checkPlain('Select Image Style for ' . $content_type_name),
        '#options' =>  $styles_options, 
		'#default_value' => $config->get('body_style_image_style_' . $content_type_id),
      );
    } 
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();	
	$config = $this->config('body_style_image.adminsettings');
	$content_types = node_type_get_types();
	foreach($content_types as $ctype) {
	  $ctype_id = $ctype->id();
	  $config->set('body_style_image_content_type_' . $ctype_id, $values['body_style_image_content_type_' . $ctype_id]);
	  $config->set('body_style_image_style_' . $ctype_id, $values['body_style_image_style_' . $ctype_id]);
	}
    $config->save(); 
    return parent::submitForm($form, $form_state);
  }
  
}
