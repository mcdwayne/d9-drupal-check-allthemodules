<?php

function flexi_magazine_form_system_theme_settings_alter(&$form, &$form_state) {

  $form['flexi_magazine_settings'] = [
    '#type' => 'fieldset',
    '#title' => t('Flexi Magazine Theme Settings'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  ];
  $form['flexi_magazine_settings']['banner_settings'] = [
    '#type' => 'details',
    '#title' => t('Banner Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#group' => 'tabs',
  ];
  $form['flexi_magazine_settings']['banner_settings']['banner_image'] = [
    '#type'          => 'managed_file',
    '#title'         => t('Home Page Banner'),
    '#default_value' => theme_get_setting('banner_image', 'flexi_magazine'),
    '#description'   => t('upload home page banner'),
    '#required' => FALSE,
  ];
  $form['flexi_magazine_settings']['banner_settings']['banner_description'] = [
    '#type' => 'textfield',
    '#title' => t('Banner description'),
    '#default_value' => theme_get_setting('banner_description', 'flexi_magazine'),
  ];
  $form['flexi_magazine_settings']['footer_settings'] = [
    '#type' => 'details',
    '#title' => t('Footer Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#group' => 'tabs',
  ];
  $form['flexi_magazine_settings']['footer_settings']['footer_image'] = [
    '#type'          => 'managed_file',
    '#title'         => t('Footer Logo'),
    '#default_value' => theme_get_setting('footer_image', 'flexi_magazine'),
    '#description'   => t('Upload footer logo.'),
    '#required' => FALSE,
  ];
  $form['flexi_magazine_settings']['footer_settings']['footer_copyright'] = [
    '#type' => 'textfield',
    '#title' => t('Copyright'),
    '#default_value' => theme_get_setting('footer_copyright', 'flexi_magazine'),
  ];

  $form['flexi_magazine_settings']['social_settings'] = [
    '#type' => 'details',
    '#title' => t('Social Media Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#group' => 'tabs',
  ];

  $form['flexi_magazine_settings']['social_settings']['face_book'] = [
    '#type' => 'textfield',
    '#title' => t('Facebook'),
    '#default_value' => theme_get_setting('face_book', 'flexi_magazine'),
  ];
  $form['flexi_magazine_settings']['social_settings']['twitter'] = [
    '#type' => 'textfield',
    '#title' => t('Twitter'),
    '#default_value' => theme_get_setting('twitter', 'flexi_magazine'),
  ];
  $form['flexi_magazine_settings']['social_settings']['linkedin'] = [
    '#type' => 'textfield',
    '#title' => t('Linked in'),
    '#default_value' => theme_get_setting('linkedin', 'flexi_magazine'),
  ];
  $form['flexi_magazine_settings']['social_settings']['instagram'] = [
    '#type' => 'textfield',
    '#title' => t('Instagram'),
    '#default_value' => theme_get_setting('instagram', 'flexi_magazine'),
  ];
}

