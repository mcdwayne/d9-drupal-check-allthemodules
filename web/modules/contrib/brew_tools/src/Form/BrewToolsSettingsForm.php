<?php

/**
 * @file
 * 
 * Contains \Drupal\brew_tools\Form\BrewToolsSettingsForm
 */

namespace Drupal\brew_tools\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form to configure maintenance settings for this module.
 */
class BrewToolsSettingsForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormid() {
        return 'brew_tools_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        //Create a $form API array.
        $config = $this->config("brew_tools.settings");
        $form['hop_util'] = array(
          '#type' => 'fieldset',
          '#title' => t('Hop Utilistation settings'),
          '#weight' => 5,
          '#collapsible' => TRUE,
          '#collapsed' => FALSE,
        );
        $form['hop_util']['brew_tools_utilization_time_curve'] = array(
          '#type' => 'textfield',
          '#title' => t('Hop Utilisation'),
          '#description' => t('Utilization vs. time curve'),
          '#default_value' => $config->get('utilization_time_curve'),
          '#required' => TRUE,
          '#weight' => 1,
        );
        $form['hop_util']['brew_tools_max_utilization'] = array(
          '#type' => 'textfield',
          '#title' => t('Max setting'),
          '#description' => t('the maximum utilization'),
          '#default_value' => $config->get('max_utilization'),
          '#required' => TRUE,
          '#weight' => 1,
        );
        $form['conversion'] = array(
          '#type' => 'fieldset',
          '#title' => t('Unit settings'),
          '#weight' => 5,
          '#collapsible' => TRUE,
          '#collapsed' => FALSE,
        );

        $form['conversion']['calc_plato_grav'] = array(
          '#type' => 'radios',
          '#options' => array('p' => t('Plato'), 'g' => t('Gravity')),
          '#title' => t('Plato Gravity setting'),
          '#description' => t('Plato Gravity Default settings'),
          '#default_value' => $config->get('calc_plato_grav'),
          '#required' => TRUE,
          '#weight' => 1,
        );
        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->config('brew_tools.settings')
            ->set('utilization_time_curve', $form_state['values']['utilization_time_curve'])
            ->set('max_utilization', $form_state['values']['max_utilization'])
            ->set('calc_plato_grav', $form_state['values']['calc_plato_grav'])
            ->save();
        parent::submitForm($form, $form_state);
    }

    protected function getEditableConfigNames() {
        
    }

}
