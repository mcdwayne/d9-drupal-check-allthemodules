<?php
/**
 * Created by PhpStorm.
 * User: aksha
 * Date: 2017-09-28
 * Time: 1:26 PM
 */

namespace Drupal\ae\Form\General;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class UserFieldsForm extends ConfigFormBase {

    protected function getEditableConfigNames() {
        return [
            'ae.userfields'
        ];
    }

    public function getFormId() {
        return 'ae_extra_fields_form';
    }

    function __construct()
    {
        $this->state = \Drupal::state();
        $this->fields = [
            'email' => [
                "name" => "Email",
                "type" => "text"
            ],
            'birthdate' => [
                "name" => "Date Of Birth",
                "type" => "date"
            ],
            'address' => [
                "name" => "Address",
                "type" => "text"
            ],
            'addressline2' => [
                "name" => "Address Line 2",
                "type" => "text"
            ],
            'city' => [
                "name" => "City",
                "type" => "text"
            ],
            'state' => [
                "name" => "State",
                "type" => "text"
            ],
            'homephone' => [
                "name" => "Home Phone",
                "type" => "text"
            ],
            'mobilephone' => [
                "name" => "Mobile Phone",
                "type" => "text"
            ],
            'firstname' => [
                "name" => "First Name",
                "type" => "text"
            ],
            'username' => [
                "name" => "Username",
                "type" => "text"
            ],
            'website' => [
                "name" => "Website",
                "type" => "text"
            ],
            'bio' => [
                "name" => "Bio",
                "type" => "text"
            ],
            'gender' => [
                "name" => "Gender",
                "type" => "text"
            ],
            'surname' => [
                "name" => "Last Name",
                "type" => "text"
            ],
            'postcode' => [
                "name" => "Zipcode",
                "type" => "text"
            ],
            'country' => [
                "name" => "Country",
                "type" => "text"
            ]
        ];
    }

    public function buildForm(array $form, FormStateInterface $form_state) {

        foreach ($this->fields as $field_key=>$field_details) {

            $form['extra_field_' . $field_key] = [
                '#type' => 'fieldset',
                '#title' => $this->t($field_details["name"])
            ];

            $form['extra_field_' . $field_key]['enable_' . $field_key] = [
                '#type' => 'checkbox',
                '#title' => $this->t('Enable')
            ];

            $form['extra_field_' . $field_key]['require_' . $field_key] = [
                '#type' => 'checkbox',
                '#title' => $this->t('Required')
            ];

            $form['extra_field_' . $field_key]['alt_text_' . $field_key] = [
                '#type' => 'textfield',
                '#title' => t('Alternative Text'),
                '#size' => 60,
                '#maxlength' => 60,
            ];

        }

        return parent::buildForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        parent::submitForm($form, $form_state);

        $fields = $this->getFieldsData($form_state);
        $state = \Drupal::state();
        $state->set('fields', $fields);
        ksm($fields);
    }


    private function getFieldsData(FormStateInterface $form_state) {

        $fieldsData = [];
        foreach ($this->fields as $field_key=>$field_details) {
            $data = [
                'field' => $field_key,
                'type' => $field_details["type"],
                'enabled' => $form_state->getValue('enable_' . $field_key),
                'required' => $form_state->getValue('require_' . $field_key),
                'alt_text' => $form_state->getValue('alt_text_' . $field_key)
            ];
            $fieldsData[] = $data;
        }
        return $fieldsData;
    }


}

