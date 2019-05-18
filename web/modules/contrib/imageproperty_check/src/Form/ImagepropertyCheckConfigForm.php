<?php

/**
 * @file
 * Contains \Drupal\time_spent\Form\TimeSpentConfigForm.
 */

namespace Drupal\imageproperty_check\Form;
use Drupal\Core\Form\ConfigFormBase;
//use Drupal\node\Entity\NodeType;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

class ImagepropertyCheckConfigForm extends ConfigFormBase {
  public function getFormId() {
    return 'imageproperty_check_config_form';
  }
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
  $form['html'] = array(
    '#type' => 'markup',
    '#markup' => t('Enter the maximum size (in <strong>Kilobytes</strong>) an image style could have. Default is 100KB.'),
  );
  $list_image_style = image_style_options();
  unset($list_image_style['']);
  foreach ($list_image_style as $key => $value) {
    $form['imageproperty_check_type_' . $key] = array(
      '#type' => 'textfield',
      '#title' => $key,
      '#default_value' => $this->config('imageproperty_check.settings')->get('imageproperty_check_type_'.$key),
      '#size' => 50,
    );
  }
   return parent::buildForm($form, $form_state);
  }


public function submitForm(array &$form, FormStateInterface $form_state) {
  $userInputValues = $form_state->getUserInput();
  $config = $this->configFactory->get('imageproperty_check.settings');
  foreach ($userInputValues as $image_style_variable => $value) {
    if (strpos($image_style_variable, 'imageproperty_check_type_') !== false) {
      $config->set($image_style_variable , $value);

    }
  }
  $config->save();
  parent::submitForm($form, $form_state);
  }
}
