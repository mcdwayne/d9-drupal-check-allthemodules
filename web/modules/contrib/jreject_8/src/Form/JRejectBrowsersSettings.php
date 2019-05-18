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
class JRejectBrowsersSettings extends ConfigFormBase {

  /**
   *
   */
  public function getFormId() {
    return 'jreject_browsers_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jreject.browsers.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('jreject.browsers.settings');

    $form = array();

    $form['intro'] = [
      '#type' => 'item',
      '#markup' => '<p>Select the browser types and versions you want to display the modal popup for:</p>',
    ];

    $browsers = ["msie" => ["Internet Explorer" => range(5, 10)],
      "firefox" => ["Firefox" => range(2, 24)],
      "chrome" => ["Chrome" => range(1, 30)],
      "safari" => ["Safari" => range(1, 6)],
      "opera" => ["Opera" => range(1, 16)],
    ];

    foreach ($browsers as $machine_name => $details) {
      foreach ($details as $human_name => $versions) {
        $form[$machine_name] = [
          '#type' => 'fieldset',
          '#title' => t('@name', array("@name" => $human_name)),
          '#tree' => TRUE,
          '#collapsible' => TRUE,
        ];
        foreach ($versions as $v) {
          $form[$machine_name][$v] = [
            '#type' => 'checkbox',
            '#default_value' => $config->get($machine_name. '_'. $v) ? $config->get($machine_name. '_'. $v) : 0,
            '#title' => t('@name @version', array("@name" => $human_name, "@version" => $v)),
          ];
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->configFactory->getEditable('jreject.browsers.settings');
    foreach($form_state->getValues() as $machine_name => $element) {
      if(is_array($element)) {
        foreach($element as $version => $value) {
          $config->set($machine_name. '_' . $version, $value);
        }
      }
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

  public static function getRejects() {
    $config = \Drupal::config('jreject.browsers.settings');
    $data = $config->getRawData();

    $out = array();
    foreach ($data as $browser_version => $reject) {
      if ($reject) {
        $tabBrowserVersion = explode('_', $browser_version);
        $out[$tabBrowserVersion[0] . $tabBrowserVersion[1]] = "true";
      }
    }
    return $out;
  }
}
