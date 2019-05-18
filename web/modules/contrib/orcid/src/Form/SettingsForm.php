<?php

namespace Drupal\orcid\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


class SettingsForm extends ConfigFormBase {

    protected $configFactory;
    protected $entityFieldManager;
    public function __construct(ConfigFactoryInterface $config_factory, $entityFieldManager) {
        parent::__construct($config_factory);
        $this->entityFieldManager = $entityFieldManager;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('config.factory'),
            $container->get('entity_field.manager')
        );

    }

    public function getFormId() {
        return 'orcid_admin_settings';
    }

    protected function getEditableConfigNames() {
        return [
            'orcid.settings',
        ];
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('orcid.settings');
        $form['client_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Client ID'),
            '#default_value' => $config->get('client_id'),
            '#description' => t('The client id value <client-id> from ORCID client application registration')
        ];
        $form['client_secret'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Client secret'),
            '#default_value' => $config->get('client_secret'),
            '#description' => $this->t('The client secret value <client-secret> from ORCID client application registration'),
        ];
        $fields = $this->entityFieldManager->getFieldDefinitions('user', 'user');
        $user_fields = array();
        foreach ($fields as $key => $field) {
            if (($field->getType() == 'string') && strpos($key, 'field_') === 0) {
                $user_fields[$key] = $this->t($field->getLabel());
            }
        }
        $form['name_field'] = [
            '#type' => 'select',
            '#options' => $user_fields,
            '#empty_option' => $this->t('- Select -'),
            '#title' => $this->t('User field for ORCID ID'),
            '#default_value' => $config->get('name_field'),
            '#description' => $this->t('This field will be used to store the ORCID ID'),
        ];
        $form['access_token'] = [
            '#type' => 'select',
            '#options' => $user_fields,
            '#empty_option' => $this->t('- Select -'),
            '#title' => $this->t('User field for ORCID Access Token'),
            '#default_value' => $config->get('access_token'),
            '#description' => $this->t('This field will be used to store the ORCID Access Token'),
        ];
        $form['refresh_token'] = [
            '#type' => 'select',
            '#options' => $user_fields,
            '#empty_option' => $this->t('- Select -'),
            '#title' => $this->t('User field for ORCID Refresh Token'),
            '#default_value' => $config->get('refresh_token'),
            '#description' => $this->t('This field will be used to store the ORCID Refresh Token'),
        ];
        $form['scope'] = [
            '#type' => 'select',
            '#options' => $user_fields,
            '#empty_option' => $this->t('- Select -'),
            '#title' => $this->t('User field for ORCID Scope'),
            '#default_value' => $config->get('scope'),
            '#description' => $this->t('This field will be used to store the ORCID scope'),
        ];
        $form['expiry'] = [
            '#type' => 'select',
            '#options' => $user_fields,
            '#empty_option' => $this->t('- Select -'),
            '#title' => $this->t('User field for ORCID Expires'),
            '#default_value' => $config->get('expiry'),
            '#description' => $this->t('This field will be used to store the ORCID Expires In'),
        ];

        $form['allow_new'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Allow creation of new user?'),
            '#description' => $this->t('User will be created from ORCID Credentials.'),
            '#default_value' => $config->get('allow_new'),
        ];
        $form['activate'] = array(
            '#type' => 'checkbox',
            '#title' => t('Requires administrative approval?'),
            '#description' => t('Account will be created in inactive state.   Must be activated by site administrator'),
            '#default_value' => $config->get('activate'),
            '#states' => array(
                'invisible' => array(
                    ':input[name="allow_new"]' => array('checked' => FALSE),
                ),
            ),
        );
        $form['sandbox'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Sandbox'),
            '#default_value' => $config->get('sandbox'),
        ];
        return parent::buildForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $values = $form_state->getValues();
        $this->config('orcid.settings')
            ->set('client_id', $values['client_id'])
            ->set('client_secret', $values['client_secret'])
            ->set('sandbox', $values['sandbox'])
            ->set('allow_new', $values['allow_new'])
            ->set('name_field', $values['name_field'])
            ->set('activate', $values['activate'])
            ->set('access_token', $values['access_token'])
            ->set('refresh_token', $values['refresh_token'])
            ->set('scope', $values['scope'])
            ->set('expiry', $values['expiry'])
            ->save();
    }
}
