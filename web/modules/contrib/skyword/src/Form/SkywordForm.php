<?php
/**
 * Created by PhpStorm.
 * User: bmcintyre
 * Date: 7/27/18
 * Time: 9:15 AM
 */

namespace Drupal\skyword\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webprofiler\Form\ConfigForm;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

class SkywordForm extends ConfigFormBase {
    public function getFormId() {
        return 'skyword_form';
    }

    public function getEditableConfigNames() { }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('skyword.skyword_config');
        $form['api_key'] = [
            '#type' => 'textfield',
            '#title' => $this->t('API Key'),
            '#description' => $this->t('API Key given to you by Skyword.'),
            '#default_value' => $config->get('apiKey'),
        ];
        return parent::buildForm($form, $form_state);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        return parent::validateForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        parent::submitForm($form, $form_state);

        $config = \Drupal::configFactory()->getEditable('skyword.skyword_config');
        $config->set('apiKey', $form_state->getValue('api_key'));
        $config->save();
    }
}