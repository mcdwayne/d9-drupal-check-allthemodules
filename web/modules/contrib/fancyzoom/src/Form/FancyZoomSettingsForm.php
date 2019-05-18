<?php

/**
 * @file
 * Administrative class form for the fancyzoom module.
 * Contains \Drupal\fancyzoom\Form\FancyZoomSettingsForm.
 */

namespace Drupal\fancyzoom\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * General configuration form for controlling the fancyzoom behaviour..
 */
class FancyZoomSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'fancyzoom_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fancyzoom.settings');

    $fancyzoom_scripts = array(
      'jquery-min' => t('JQuery Minified'),
      'jquery' => t('JQuery'),
      'standard-min' => t('Standard Minified'),
      'standard' => t('Standard')
    );

    $form = array(
      'info' => array(
        '#markup' => '<div class="form-item">'
          . '<label>' . t('Test FancyZoom') . ':</label> '
          . _l('<img src="/core/misc/druplicon.png" width="32" />',
              'core/misc/druplicon.png',
              array('html' => 1, 'attributes' => array('title' => t('It Works!')))
            )
          . '</div>'
      ),
      'zoomTime' => array(
        '#title' => t('Zoom Time'),
        '#type' => 'textfield',
        '#default_value' => _fancyzoom_var('zoomTime'),
        '#required' => 1,
        '#maxlength' => 3,
        '#description' => t('Milliseconds between frames of zoom animation.')
      ),
      'zoomSteps' => array(
        '#title' => t('Zoom Steps'),
        '#type' => 'textfield',
        '#default_value' => _fancyzoom_var('zoomSteps'),
        '#required' => 1,
        '#maxlength' => 3,
        '#description' => t('Number of zoom animation frames.')
      ),
      'minBorder' => array(
        '#title' => t('Minimum Border'),
        '#type' => 'textfield',
        '#default_value' => _fancyzoom_var('minBorder'),
        '#required' => 1,
        '#maxlength' => 3,
        '#description' => t('Minimum padding between a zoomed image and the window edges.')
      ),
      'includeFade' => array(
        '#title' => t('Use Fade Effect'),
        '#type' => 'checkbox',
        '#return_value' => 1,
        '#default_value' => _fancyzoom_var('includeFade'),
        '#description' => t('Fade the image in / out as it zooms.')
      ),
      'includeCaption' => array(
        '#title' => t('Show Caption'),
        '#type' => 'checkbox',
        '#return_value' => 1,
        '#default_value' => _fancyzoom_var('includeCaption'),
        '#description' => t('The link\'s title can appear below the zoomed image as a caption.')
      ),
      'showClosebox' => array(
        '#title' => t('Show Close Box'),
        '#type' => 'checkbox',
        '#return_value' => 1,
        '#default_value' => _fancyzoom_var('showClosebox'),
        '#description' => t('The Close Box is drawn over the top-left corner of the image.')
      ),
      'shadowColor' => array(
        '#title' => t('Shadow Color'),
        '#type' => 'textfield',
        '#default_value' => _fancyzoom_var('shadowColor'),
      ),
      'scriptType' => array(
        '#type' => 'radios',
        '#title' => t('Script To Use'),
        '#options' => $fancyzoom_scripts,
        '#default_value' => _fancyzoom_var('scriptType'),
        '#description' => t("Always use the jQuery Minified script unless you're having conflicts.")
      ),
      'zoomImagesURI' => array(
        '#title' => t('Zoom Images Path (!path...)', array('!path' => "http://$_SERVER[HTTP_HOST]" . base_path())),
        '#type' => 'textfield',
        '#default_value' => _fancyzoom_var('zoomImagesURI'),
        '#required' => 0,
        '#maxlength' => 300,
        '#description' => t('Location of the zoom images. Leave blank for default images. <em>$t = theme folder, $m = module folder</em>')
      )
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('fancyzoom.settings');
    $v = $form_state->getCompleteForm();
    // print_r($v);
    $config
      ->set('zoomTime', $v['zoomTime']['#value'])
      ->set('zoomSteps', $v['zoomSteps']['#value'])
      ->set('minBorder', $v['minBorder']['#value'])
      ->set('includeFade', $v['includeFade']['#value'])
      ->set('includeCaption', $v['includeCaption']['#value'])
      ->set('showClosebox', $v['showClosebox']['#value'])
      ->set('shadowColor', $v['shadowColor']['#value'])
      ->set('scriptType', $v['scriptType']['#value'])
      ->set('zoomImagesURI', $v['zoomImagesURI']['#value'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
