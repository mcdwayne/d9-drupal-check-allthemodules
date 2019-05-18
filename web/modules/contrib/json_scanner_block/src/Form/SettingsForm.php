<?php

namespace Drupal\json_scanner_block\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Asset\LibraryDiscovery;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\json_scanner_block\BaseClass\JsonScannerBase;
use Drupal\json_scanner_block\DbStorage\DbActions;

/**
 * Defines a form that configures json_scanner_block settings.
 */
class SettingsForm extends ConfigFormBase {

    /**
     * Drupal LibraryDiscovery service container.
     *
     * @var Drupal\Core\Asset\LibraryDiscovery
     */
    protected $libraryDiscovery;
    protected $scanner_data;
    protected $table_name = 'json_scanner_block';

    /**
     * {@inheritdoc}
     */
    public function __construct(LibraryDiscovery $library_discovery, JsonScannerBase $scanner_data) {
        $this->libraryDiscovery = $library_discovery;
        $this->scanner_data = $scanner_data;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {

        return new static(
                $container->get('library.discovery'), $container->get('json_scanner_base.data')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'json_scanner_block_admin_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'json_scanner_block.settings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        // Get current settings.
        $fontawesome_config = $this->config('json_scanner_block.settings');
        $form['name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Title'),
            '#required' => TRUE,
            '#size' => 80,
            '#description' => $this->t('Specify a title so that you can identify.'),
        ];

        $form['auth'] = [
            '#type' => 'select',
            '#title' => $this->t('Auth Type'),
            '#options' => [
                '0' => $this->t('UnAuth'),
                '1' => $this->t('Auth'),
            ],
            '#default_value' => empty($fontawesome_config->get('tag')) ? 'i' : $fontawesome_config->get('tag'),
            '#description' => $this->t('Select Authentication type \'Auth\' if the URL which you want to access json data needs authentication.'),
        ];

        $form['auth_user'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Authenticate User'),
            '#required' => FALSE,
            '#size' => 80,
            '#description' => $this->t('Only fill the value when \'Auth Type\' Field setting is \'Auth\''),
        ];

        $form['auth_pass'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Authenticate Password'),
            '#required' => FALSE,
            '#size' => 80,
            '#description' => $this->t('Only fill the value when \'Auth Type\' Field setting is \'Auth\''),
        ];

        $form['auth_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('URL for Authentication'),
            '#required' => FALSE,
            '#size' => 80,
            '#description' => $this->t('Only fill the value when \'Auth Type\' Field setting is \'Auth\''),
        ];

        $form['json_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('JSON data Location URL'),
            '#required' => TRUE,
            '#size' => 80,
            '#description' => $this->t('Enter a source URL for the external / local (absolute path) JSON file you wish to use. This URL should point to the JSON file.'),
        ];

        $form['avail_for_twig'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Make it Available for Twig.'),
            '#description' => $this->t('Check it if you want to use available variables in twig template.'),
        );

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        $values = $form_state->getValues();

        // Validate URL.
        if (!empty($values['json_url']) && !UrlHelper::isValid($values['json_url'], TRUE)) {
            $form_state->setErrorByName('json_url', $this->t('Invalid json url.'));
        }

        if (!empty($values['auth_url']) && !UrlHelper::isValid($values['auth_url'], TRUE)) {
            $form_state->setErrorByName('auth_url', $this->t('Invalid auth url.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $values = $form_state->getValues();

        // Clear the library cache so we use the updated information.
        //$this->libraryDiscovery->clearCachedDefinitions();
        //$jsonScanner = new JsonScannerBase;
        //$scanned_data = $jsonScanner->getApiData($values['json_url']);
        
        //load through service $this->scanner_data->getApiData($values['json_url'])
        $scanned_data = $this->scanner_data->getApiData($values['json_url']);
        $arrayJson = $this->scanner_data->json2Array($scanned_data);

        // Save the submitted entry.
        $entry = [
            'name' => preg_replace('/\s+/', '', strtolower($values['name'])),
            'is_auth' => $values['auth'],
            'auth_user' => $values['auth_user'],
            'auth_pswd' => $values['auth_pass'],
            'json_url' => $values['json_url'],
            'json_data' => $scanned_data,
            'auth_url' => $values['auth_url'],
            'avail_for_twig' => $values['avail_for_twig'],
        ];

        DbActions::insert($entry, $this->table_name);
        
        $form_state->setRedirect('json_scanner_block.list_data');
        return;

        //drupal_set_message($this->scanner_data->json2Array($scanned_data), 'status');
    }

}
