<?php
namespace Drupal\jplayer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Class JplayerSettingsForm.
 *
 * @package Drupal\jplayer\Form
 */
class JplayerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jplayer_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('jplayer.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jplayer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['timeformat'] = [
      '#type' => 'fieldset',
      '#title' => t('Time Format'),
      '#weight' => 1,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['timeformat']['jplayer_showHour'] = [
      '#title' => t('Display hours'),
      '#type' => 'select',
      '#options' => [
        FALSE => t('No'),
        TRUE => t('Yes'),
      ],
      '#default_value' => \Drupal::config('jplayer.settings')->get('jplayer_showHour'),
    ];

    $form['timeformat']['jplayer_showMin'] = [
      '#title' => t('Display minutes'),
      '#type' => 'select',
      '#options' => [
        FALSE => t('No'),
        TRUE => t('Yes'),
      ],
      '#default_value' => \Drupal::config('jplayer.settings')->get('jplayer_showMin'),
    ];

    $form['timeformat']['jplayer_showSec'] = [
      '#title' => t('Display seconds'),
      '#type' => 'select',
      '#options' => [
        FALSE => t('No'),
        TRUE => t('Yes'),
      ],
      '#default_value' => \Drupal::config('jplayer.settings')->get('jplayer_showSec'),
    ];

    $form['timeformat']['jplayer_padHour'] = [
      '#title' => t('Zero-pad the hours'),
      '#type' => 'select',
      '#options' => [
        FALSE => t('No'),
        TRUE => t('Yes'),
      ],
      '#default_value' => \Drupal::config('jplayer.settings')->get('jplayer_padHour'),
    ];

    $form['timeformat']['jplayer_padMin'] = [
      '#title' => t('Zero-pad the minutes'),
      '#type' => 'select',
      '#options' => [
        FALSE => t('No'),
        TRUE => t('Yes'),
      ],
      '#default_value' => \Drupal::config('jplayer.settings')->get('jplayer_padMin'),
    ];

    $form['timeformat']['jplayer_padSec'] = [
      '#title' => t('Zero-pad the seconds'),
      '#type' => 'select',
      '#options' => [
        FALSE => t('No'),
        TRUE => t('Yes'),
      ],
      '#default_value' => \Drupal::config('jplayer.settings')->get('jplayer_padSec'),
    ];

    $form['timeformat']['jplayer_sepHour'] = [
      '#title' => t('Hours seperator'),
      '#type' => 'textfield',
      '#maxlength' => 32,
      '#default_value' => \Drupal::config('jplayer.settings')->get('jplayer_sepHour'),
    ];

    $form['timeformat']['jplayer_sepMin'] = [
      '#title' => t('Minutes seperator'),
      '#type' => 'textfield',
      '#maxlength' => 32,
      '#default_value' => \Drupal::config('jplayer.settings')->get('jplayer_sepMin'),
    ];

    $form['timeformat']['jplayer_sepSec'] = [
      '#title' => t('Seconds seperator'),
      '#type' => 'textfield',
      '#maxlength' => 32,
      '#default_value' => \Drupal::config('jplayer.settings')->get('jplayer_sepSec'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
