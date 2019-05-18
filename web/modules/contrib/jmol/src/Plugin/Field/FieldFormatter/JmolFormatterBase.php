<?php

namespace Drupal\jmol\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Base class for Jmol formatters.
 */
abstract class JmolFormatterBase extends EntityReferenceFormatterBase {

  /**
   * Provide the basic info array for the specific formatters to amend.
   */
  public function baseInfo() {
    return [
      'width' => $this->getSetting('size'),
      'height' => $this->getSetting('size'),
      'color' => "0xC0C0C0",
    // 'disableJ2SLoadMonitor' => TRUE,.
      'disableInitialConsole' => TRUE,
      'serverURL' => "http://chemapps.stolaf.edu/jmol/jsmol/php/jsmol.php",
      'use' => "HTML5",
      'readyFunction' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'size' => 400,
      'view_styles' => [
        'wireframe' => TRUE,
        'sticks' => TRUE,
        'balls' => TRUE,
        'backbone' => TRUE,
        'trace' => TRUE,
        'ribbon' => TRUE,
      ],
      'default_style' => 'wireframe',
      'script' => FALSE,
      'filename' => FALSE,
      'alignment' => 'left',
      'units' => 'angstroms',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $style_options = [
      'wireframe' => $this->t('Wireframe'),
      'sticks' => $this->t('Sticks'),
      'balls' => $this->t('Balls (spacefill)'),
      'backbone' => $this->t('Backbone (C-alpha)'),
      'trace' => $this->t('Trace'),
      'ribbon' => $this->t('Ribbon'),
    ];
    $form['size'] = [
      '#title' => $this->t('Applet size'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#description' => $this->t('The size of the applet in pixels.'),
    ];
    $form['view_styles'] = [
      '#title' => $this->t('Exposed view styles'),
      '#required' => TRUE,
      '#type' => 'checkboxes',
      '#default_value' => $this->getSetting('view_styles'),
      '#options' => $style_options,

    ];
    $form['default_style'] = [
      '#title' => $this->t('Default view style'),
      '#type' => 'select',
      '#description' => $this->t('Make sure that you select a style that is also exposed above. If not, the default style will be the first exposed style in the list.'),
      '#required' => TRUE,
      '#options' => $style_options,
      '#default_value' => $this->getSetting('default_style'),
    ];
    $form['script'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('script'),
      '#title' => $this->t('Expose custom Jmol script textbox'),
    ];
    $form['filename'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('filename'),
      '#title' => $this->t('Display the filename above the applet'),
    ];
    $form['alignment'] = [
      '#title' => $this->t('Applet alignment'),
      '#type' => 'radios',
      '#default_value' => $this->getSetting('alignment'),
      '#options' => [
        'left'   => $this->t('Left'),
        'center' => $this->t('Center'),
        'right'  => $this->t('Right'),
      ],
    ];
    $form['units'] = array(
      '#title' => $this->t('Measurements units'),
      '#type' => 'radios',
      '#default_value' => $this->getSetting('units'),
      '#required' => TRUE,
      '#options' => [
        'angstroms'  => $this->t('Angstroms'),
        'nanometers' => $this->t('Nanometers'),
      ],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $view_style_settings = $this->getSetting('view_styles');
    $checked_styles = [];
    // Remove unchecked (value = FALSE) styles from $settings['viewstyle'].
    foreach ($view_style_settings as $key => $value) {
      if ($value) {
        $checked_styles[$key] = $key;
      }
    }
    // Build the summary.
    $summary[] = $this->t('Size: @px px<br />', array('@px' => $this->getSetting('size')));
    $summary[] = $this->t('Exposed view styles: @buttons<br />', array('@buttons' => implode(',', $checked_styles)));
    $summary[] = $this->t('Default style: @defaultstyle<br />', array('@defaultstyle' => $this->getSetting('default_style')));
    if ($this->getSetting['script']) {
      $summary = t('Custom Jmol script textbox exposed<br />');
    }
    if ($this->getSetting['filename']) {
      $summary .= t('Display filename above the applet<br />');
    }
    $summary[] = $this->t('Alignment: @alignment<br />', array('@alignment' => $this->getSetting('alignment')));
    $summary[] = $this->t('Units: @units', array('@units' => $this->getSetting('units')));
    return $summary;
  }

}
