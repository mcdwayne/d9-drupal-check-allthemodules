<?php
/**
 * Created by PhpStorm.
 * User: aksha
 * Date: 2017-10-06
 * Time: 5:23 PM
 */

namespace Drupal\ae\Form\Widget;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class BasicForm extends ConfigFormBase {

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
            'show_ep_button' => t('Show Email/Password as Button')
        );

        $opts = $this->state->get('basic_options');
        if(isset($opts["options"])) {
            $default_values = array_keys(array_filter($opts)["options"]);
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

        // Style URL
        $form['style_url'] = array(
            '#type' => 'textfield',
            '#title' => t('Stylesheet URL'),
            '#size' => 60,
            '#maxlength' => 60,
            '#default_value' => $opts['style_url']
        );

        $language_codes = array(
            'English' => 'en_US',
            'Albanian  ' => 'sq_AL',
            'Arabic ' => 'ar_MA',
            'Basque ' => 'eu_ES',
            'Belgium (French) ' => 'be_FR',
            'Belgium (Netherland) ' => 'be_NL',
            'Bulgarian ' => 'bg_BG',
            'Catalan ' => 'ca_ES',
            'Chilean Spanish ' => 'es_CL',
            'Chinese ' => 'zh_CN',
            'Czech ' => 'cs_CZ',
            'Danish ' => 'da_DK',
            'Dutch (Netherland) ' => 'nl_NL',
            'Finnish ' => 'fi_FI',
            'French ' => 'fr_FR',
            'German ' => 'de_DE',
            'Greek ' => 'gr_EL',
            'Hebrew ' => 'he_IL',
            'Hindi ' => 'hi_IN',
            'Hungarian ' => 'hu_HU',
            'Indonesian ' => 'id_ID',
            'Italian ' => 'it_IT',
            'Japanese ' => 'ja_JP',
            'Norwegian ' => 'no_NO',
            'Persian (Farsi) ' => 'fa_IR',
            'Polish ' => 'pl_PL',
            'Portuguese (Brazil) ' => 'pt_BR',
            'Portuguese (Portugal) ' => 'pt_PT',
            'Romanian ' => 'ro_RO',
            'Russian ' => 'ru_RU',
            'Serbian ' => 'sr_RS',
            'Slovak ' => 'sk_SK',
            'Spanish ' => 'es_ES',
            'Swedish ' => 'sv_SE',
            'Taiwanese ' => 'zh_TW',
            'Thai ' => 'th_TH',
            'Turkish ' => 'tr_TR',
            'Ukrainian ' => 'ua_UA',
            'Vietnamese ' => 'vi_VN'
        );

        // Language
        $form['form_validation_language'] = array(
            '#type' => 'select',
            '#title' => t('Form Validation Language'),
            '#options' => array_flip($language_codes),
            '#default_value' => $opts['form_validation_language']
        );


        return parent::buildForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        parent::submitForm($form, $form_state);

        $form_state->cleanValues();

        $this->state->set('basic_options', $form_state->getValues());

        ksm($this->state->get('basic_options'));

    }


}
