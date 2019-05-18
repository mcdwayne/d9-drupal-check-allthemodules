<?php
/**
 * Created by PhpStorm.
 * User: aksha
 * Date: 2017-10-06
 * Time: 5:23 PM
 */

namespace Drupal\ae\Form\Performance;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class PerformanceForm extends ConfigFormBase {

    protected function getEditableConfigNames() {
        return [
            'ae.generalSettings'
        ];
    }

    public function getFormId() {
        return 'ae_general_settings_form';
    }

    function __construct()
    {
        $this->state = \Drupal::state();
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $options = array(
          'not_create_user' => t('Do not create local user'),
          'not_sign_in' => t('Do not sign in local user')
        );

        $opts = $this->state->get('performance_options')["options"];
        if(isset($opts)) {
            $default_values = array_keys(array_filter($opts));
        }
        else {
            $default_values = [];
        }

        # the drupal checkboxes form field definition
        $form['options'] = array(
            '#title' => t('Options'),
            '#type' => 'checkboxes',
            '#options' => $options,
            '#default_value' => $default_values
        );

        return parent::buildForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        parent::submitForm($form, $form_state);

        $form_state->cleanValues();

        $this->state->set('performance_options', $form_state->getValues());

        ksm($this->state->get('performance_options'));
    }


}
