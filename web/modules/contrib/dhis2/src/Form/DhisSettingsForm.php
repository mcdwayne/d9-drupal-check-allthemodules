<?php

/**
 * @file
 * Contains Drupal\refchecks\Form\RenterForm
 */

namespace Drupal\dhis\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dhis\Services\DhisUserServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DhisSettingsForm extends ConfigFormBase
{
    protected $dhisUserService;

    public function __construct(ConfigFactoryInterface $config_factory, DhisUserServiceInterface $dhisUserService)
    {
        parent::__construct($config_factory);
        $this->dhisUserService = $dhisUserService;

    }
    public static function create(ContainerInterface $container){
        return new static(
            $container->get('config.factory'),
            $container->get('dhis_user')
        );
    }
    /*
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'dhis_settings';
    }

    /*
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return ['dhis.settings'];
    }

    /*
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildForm($form, $form_state);
       // $form['#attached']['library'][] = 'dhis/dhis_settings';
        $config = $this->config('dhis.settings');
        $form['dhis'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('DHIS 2 Settings'),
        );

        $form['dhis']['link'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('DHIS 2 Link'),
            '#default_value' => $config->get('dhis.link'),
            '#attributes' => array('id' => array('dhis-base-url')),
            '#required' => TRUE,
        );

        $form['dhis']['empty_value'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Exclude empty records'),
            '#default_value' => $config->get('dhis.empty_value'),
        );
        $form['dhis']['api_version'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('New Api Version'),
            '#default_value' => $config->get('dhis.api_version'),
        );

        $form['dhis']['username'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('DHIS 2 Username'),
            '#default_value' => $config->get('dhis.username'),
            '#attributes' => array('id' => array('dhis-username')),
            '#required' => TRUE,
        );

        $form['dhis']['password'] = array(
            '#type' => 'password',
            '#title' => $this->t('DHIS 2 Password'),
            '#default_value' => $config->get('dhis.password'),
            '#attributes' => array('id' => array('dhis-password')),
            '#required' => TRUE,
        );
        $form['sync'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('Automatic Sync settings'),
        );
        /*$form['sync']['auto_sync'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Automatic Sync'),
            '#default_value' => $config->get('dhis.auto_sync'),
            '#attributes' => array('class' => array('dhis-auto-sync')),
        );*/
        $form['sync']['auto_sync'] = array(
            //'#attributes' => array('class' => array('dhis-sync-interval')),
            '#type' => 'radios',
            '#title' => t('Sync interval'),
            //'#disabled' => (($config->get('dhis.auto_sync') == 1) ? FALSE: TRUE),
            '#default_value' => $config->get('dhis.auto_sync'),
            '#options' => array(
                '0' => t('Sync off'),
                '1' => t('Monthly'),
                '2' => t('Quaterly'),
            ),
        );


        $form['metadata'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('DHIS 2 Metadata to import'),
        );
        $form['metadata']['metadata_delete'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Delete any existing meta data before next fetch.'),
            '#default_value' => $config->get('dhis.metadata_delete'),
        );
        $form['metadata']['orgUnitGrp'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Organisation Unit Groups'),
            '#default_value' => $config->get('dhis.orgUnitGrp'),
        );
        $form['metadata']['orgUnits'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Organisation Units'),
            '#default_value' => $config->get('dhis.orgUnits'),
        );
        $form['metadata']['dataElements'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Data Elements'),
            '#default_value' => $config->get('dhis.dataElements'),
        );
        $form['metadata']['indicators'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Indicators'),
            '#default_value' => $config->get('dhis.indicators'),
        );
        return $form;
    }
    /*
     * {@inheritdoc}
     */
    public function validateForm (array &$form, FormStateInterface $form_state) {

        $credentials['baseUrl'] = $form_state->getValue('link');
        $credentials['username'] = $form_state->getValue('username');
        $credentials['password'] = $form_state->getValue('password');
        $meResponse = $this->dhisUserService->me($credentials);
        if(!$meResponse){
            //print_r($meResponse);die('invalid url or usename or password');
            $form_state->setErrorByName('link', $this->t('Check for valid base url link.'));
            $form_state->setErrorByName('username', $this->t('Check for valid username.'));
            $form_state->setErrorByName('password', $this->t('Check for valid password.'));
        }
    }

    /*
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $config = $this->config('dhis.settings');
        $config->set('dhis.link', $form_state->getValue('link'));
        $config->set('dhis.empty_value', $form_state->getValue('empty_value'));
        $config->set('dhis.auto_sync', $form_state->getValue('auto_sync'));
        $config->set('dhis.api_version', $form_state->getValue('api_version'));
        $config->set('dhis.username', $form_state->getValue('username'));
        $config->set('dhis.password', $form_state->getValue('password'));
        $config->set('dhis.orgUnitGrp', $form_state->getValue('orgUnitGrp'));
        $config->set('dhis.orgUnits', $form_state->getValue('orgUnits'));
        $config->set('dhis.dataElements', $form_state->getValue('dataElements'));
        $config->set('dhis.indicators', $form_state->getValue('indicators'));
        $config->set('dhis.metadata_delete', $form_state->getValue('metadata_delete'));
        $config->save();
        return parent::submitForm($form, $form_state);
    }
}