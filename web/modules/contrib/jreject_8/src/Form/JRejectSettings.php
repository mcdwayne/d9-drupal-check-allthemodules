<?php

/**
 * @file
 *Contains \Drupal\flag_limit\Form\flaglimitForm
 */

namespace Drupal\jreject\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Flag settings form.
 */
class JRejectSettings extends ConfigFormBase
{

  /**
   *
   */
  public function getFormId()
  {
    return 'jreject_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return ['jreject.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = \Drupal::config('jreject.settings');

    $form = array();

    $form['jreject_enable'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('jreject_enable'),
      '#title' => $this->t('Enable jReject'),
    ];

    $form['jreject_header'] = [
      '#type' => 'textfield',
      '#size' => 100,
      '#maxlength' => 256,
      '#default_value' => $config->get('jreject_header'),
      '#title' => $this->t('Modal header message'),
    ];

    $form['jreject_paragraph1'] = [
      '#type' => 'textarea',
      '#cols' => 60,
      '#rows' => 5,
      '#default_value' => $config->get('jreject_paragraph1'),
      '#title' => $this->t('Modal paragraph 1'),
      '#description' => $this->t('You are encouraged to keep this brief.'),
    ];

    $form['jreject_paragraph2'] = [
      '#type' => 'textarea',
      '#cols' => 60,
      '#rows' => 5,
      '#default_value' => $config->get('jreject_paragraph2'),
      '#title' => $this->t('Modal paragraph 2'),
      '#description' => $this->t('You are encouraged to keep this brief.'),
    ];

    $form['jreject_closeMessage'] = [
      '#type' => 'textfield',
      '#size' => 100,
      '#maxlength' => 256,
      '#default_value' => $config->get('jreject_closeMessage'),
      '#title' => $this->t('Modal close message'),
    ];

    $form['jreject_closeLink'] = [
      '#type' => 'textfield',
      '#size' => 50,
      '#maxlength' => 256,
      '#default_value' => $config->get('jreject_closeLink'),
      '#title' => $this->t('Modal close link'),
    ];

    $form['jreject_closeURL'] = [
      '#type' => 'textfield',
      '#size' => 50,
      '#maxlength' => 256,
      '#default_value' => $config->get('jreject_closeURL'),
      '#title' => $this->t('Modal close URL'),
      '#description' => $this->t("If you want to send your users somewhere special when they click the close link, enter the destination here."),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // Retrieve the configuration
    $this->configFactory->getEditable('jreject.settings')
      ->set('jreject_enable', $form_state->getValue('jreject_enable'))
      ->set('jreject_header', $form_state->getValue('jreject_header'))
      ->set('jreject_paragraph1', $form_state->getValue('jreject_paragraph1'))
      ->set('jreject_paragraph2', $form_state->getValue('jreject_paragraph2'))
      ->set('jreject_closeMessage', $form_state->getValue('jreject_closeMessage'))
      ->set('jreject_closeLink', $form_state->getValue('jreject_closeLink'))
      ->set('jreject_closeURL', $form_state->getValue('jreject_closeURL'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  public static function getOptions()
  {
    $config = \Drupal::config('jreject.settings');
    $data = $config->getRawData();

    $out = array();

    $to_translate = array('header', 'paragraph1', 'paragraph2', 'closeMessage', 'closeLink');

    foreach ($data as $key => $opt) {
      $key = substr($key, 8);

      if (in_array($key, $to_translate)) {
        $opt = \Drupal::service('string_translation')->translate($opt);
      }

      if (is_int($opt)) {
        $opt = $opt ? "true" : "false"; //for javascript
      }

      if (!($key == 'closeURL' && trim($opt) == "")) {
        $out[$key] = $opt;
      }
    }

    return $out;
  }
}
