<?php
/**
 * Created by PhpStorm.
 * User: aksha
 * Date: 2017-10-06
 * Time: 3:59 PM
 */

namespace Drupal\ae\Form\General;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SocialsForm extends ConfigFormBase {

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

        $networks = $this->getSocials();

        $opts = $this->state->get('socials')["socials"];
        if(isset($opts)) {
            $default_values = array_keys(array_filter($opts));
        }
        else {
            $default_values = [];
        }

        # the drupal checkboxes form field definition
        $form['socials'] = array(
            '#title' => t('Social Networks'),
            '#type' => 'checkboxes',
            '#description' => t('Select the Social Networks for AE.'),
            '#options' => $networks,
            '#default_value' => $default_values
        );

        return parent::buildForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        parent::submitForm($form, $form_state);

        $form_state->cleanValues();

        $this->state->set('socials', $form_state->getValues());
        ksm($this->state->get('socials')["socials"]);
    }

    private function getSocials() {
        $urls = $this->state->get('config')['Urls'];
        $networks = [];
        foreach($urls as $url) {
            $networks[$url['Name']] = t($url['Name']);
        }

        return $networks;
    }


}

