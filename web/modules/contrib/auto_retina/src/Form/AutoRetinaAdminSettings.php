<?php

/**
 * @file
 * Contains \Drupal\auto_retina\Form\AutoRetinaAdminSettings.
 */

namespace Drupal\auto_retina\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class AutoRetinaAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'auto_retina_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['auto_retina.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['suffix'] = [
      '#type' => 'textfield',
      '#title' => t('Retina filename suffix'),
      '#description' => t('This suffix appears at the end of a filename, before the dot+extension to indicate it is the retina version of an image, e.g. "@2x".  <strong>To allow more than one multiplier, add a space-separated list of suffixes, e.g. "@.75x @1.5x @2x @3x"</strong>'),
      '#default_value' => \Drupal::config('auto_retina.settings')
        ->get('suffix'),
      '#required' => TRUE,
    ];

    if (\Drupal::moduleHandler()->moduleExists('image_style_quality')) {
      $explanation = t('If a given style includes an image style quality effect it will be used as the basis, if not then the <a href=":url">image toolkit setting</a> is used.  ', [
        ':url' => Url::fromRoute('system.image_toolkit_settings', [], [
          'query' => [
            'destination' => \Drupal::destination()->get(),
          ],
        ])->toString(),
      ]);
    }
    else {
      $explanation = t('The basis is the <a href=":url">image toolkit setting</a>. <em>(Consider installing the <a href=":module" target="_blank">Image Style Quality</a> module for more options.)</em>  ', [
        ':url' => Url::fromRoute('system.image_toolkit_settings', [], [
          'query' => [
            'destination' => \Drupal::destination()->get(),
          ],
        ])->toString(),
        ':module' => Url::fromUri('https://www.drupal.org/project/image_style_quality')
          ->toString(),
      ]);
    }

    $form['quality_multiplier'] = [
      '#title' => t('JPEG Quality Multiplier (for magnified images only)'),
      '#type' => 'number',
      '#step' => '.05',
      '#min' => '.05',
      '#max' => '1',
      '#field_suffix' => 'x JPEG percentage',
      '#size' => 4,
      '#description' => t('Affect the JPEG quality used when generating the magnified image(s).  @explanation<strong>If the basis is 80% and you leave this at 1, the retina image will be generated at 80% (no change); however if you set this to .5 then the retina image will be generated at 40%.</strong>  The lower the percentage multiplier, the smaller will be the file size of the retina image, <em>at the expense of quality</em>.', [
        '@explanation' => $explanation,
      ]),
      '#default_value' => \Drupal::config('auto_retina.settings')
        ->get('quality_multiplier'),
    ];


    $form['advanced'] = [
      '#type' => 'details',
      '#title' => t('Advanced'),
      '#open' => FALSE,
    ];

    $form['advanced']['regex'] = [
      '#type' => 'textfield',
      '#title' => t('Retina filename regex'),
      '#description' => t('Enter a regex expression to use for determining if an url is retina.  The token <code>SUFFIX</code> may be used to dynamically populate the setting from above. You may omit start/end delimiters.'),
      '#default_value' => \Drupal::config('auto_retina.settings')
        ->get('regex'),
      '#required' => TRUE,
    ];

    $form['advanced']['js'] = [
      '#type' => 'checkbox',
      '#title' => t('Include the javascript settings <code>drupalSettings.autoRetina</code> on every page?'),
      '#default_value' => \Drupal::config('auto_retina.settings')
        ->get('js'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('auto_retina.settings');
    $config->set('suffix', $form_state->getValue($form['suffix']['#parents']));
    if (isset($form['quality_multiplier'])) {
      $config->set('quality_multiplier', $form_state->getValue($form['quality_multiplier']['#parents']));
    }
    $config->set('regex', $form_state->getValue($form['advanced']['regex']['#parents']));
    $config->set('js', $form_state->getValue($form['advanced']['js']['#parents']));
    $config->save();
    parent::submitForm($form, $form_state);
  }
}
