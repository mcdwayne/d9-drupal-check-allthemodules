<?php

namespace Drupal\celum_connect\Form;

/**
 * @file
 * Contains \Drupal\google_map_field\Form\GmapFieldSettingsForm.
 */
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Administration settings form.
 */
class CelumConnectSettingsForm extends ConfigFormBase {

    /**
     * Implements \Drupal\Core\Form\FormInterface::getFormID().
     */
    public function getFormId() {
        return 'celum_connect';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'celum_connect.settings',
        ];
    }

    /**
     * Implements \Drupal\Core\Form\FormInterface::buildForm().
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('celum_connect.settings');
        $settings = $config->get();
        $licenseKey = '';
        $api_key = '';
        $locale = '';
        $rootNode = '';
        $dlfu = '';
        $dlfi = '';
        $dlfd = '';
        $dlfv = '';
        $dlfa = '';
        $dlft = '';
        $dlfs = '';
        $usage_link = 'drupal linked';
        $usage_download = 'drupal downloaded';
        $asset_picker_version = '';

        if (isset($settings['celum_connect_apikey']) && trim($settings['celum_connect_apikey']) != '') {
            $licenseKey = $settings['celum_connect_licenseKey'];
            $api_key = $settings['celum_connect_apikey'];
            $locale = $settings['celum_connect_locale'];
            $rootNode = $settings['celum_connect_rootNode'];
            $dlfu = $settings['celum_connect_dlfu'];
            $dlfi = $settings['celum_connect_dlfi'];
            $dlfd = $settings['celum_connect_dlfd'];
            $dlfv = $settings['celum_connect_dlfv'];
            $dlfa = $settings['celum_connect_dlfa'];
            $dlft = $settings['celum_connect_dlft'];
            $dlfs = $settings['celum_connect_dlfs'];
            $asset_picker_version = $settings['celum_connect_asset_picker_version'];
            $usage_link = $settings['celum_connect_usage_link'];
            $usage_download = $settings['celum_connect_usage_download'];
        }


        $form['celum_connect_licenseKey'] = [
            '#type' => 'textfield',
            '#title' => $this->t('License Key'),
            '#default_value' => $licenseKey,
            '#required' => FALSE,
            '#size' => 80
        ];

        $form['celum_connect_apikey'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Celum API Key'),
            '#description' => $this->t('Celum Cora Api Key'),
            '#default_value' => $api_key,
            '#required' => FALSE,
            '#size' => 80
        ];

        $form['celum_connect_locale'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Locale'),
            '#default_value' => $locale,
            '#required' => FALSE,
            '#size' => 5
        ];

        $form['celum_connect_rootNode'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Root node'),
            '#default_value' => $rootNode,
            '#required' => FALSE,
            '#size' => 8
        ];

        $form['celum_connect_usage_link'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Usage (download)'),
            '#default_value' => $usage_download,
            '#required' => FALSE,
            '#size' => 50
        ];

        $form['celum_connect_usage_download'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Usage (link)'),
            '#default_value' => $usage_link,
            '#required' => FALSE,
            '#size' => 50
        ];

        $form['celum_connect_asset_picker_version'] = array(
            '#type' => 'select',
            '#title' => $this->t('Asset Picker version'),
            '#options' => array(
                0 => $this->t('2.0'),
                1 => $this->t('2.1'),
                2 => $this->t('2.2'),
                3 => $this->t('2.3'),
                4 => $this->t('2.4'),
                5 => $this->t('2.5'),
                6 => $this->t('2.5.1'),
                7 => $this->t('2.5.2'),
            ),
            '#default_value' => $asset_picker_version
        );

        //Downloadformats

        $form['celum_connect_dlfu'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Downloadformat unknown'),
            '#default_value' => $dlfu,
            '#required' => FALSE,
            '#size' => 5
        ];

        $form['celum_connect_dlfi'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Downloadformat image'),
            '#default_value' => $dlfi,
            '#required' => FALSE,
            '#size' => 5
        ];

        $form['celum_connect_dlfd'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Downloadformat document'),
            '#default_value' => $dlfd,
            '#required' => FALSE,
            '#size' => 5
        ];

        $form['celum_connect_dlfv'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Downloadformat video'),
            '#default_value' => $dlfv,
            '#required' => FALSE,
            '#size' => 5
        ];

        $form['celum_connect_dlfa'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Downloadformat audio'),
            '#default_value' => $dlfa,
            '#required' => FALSE,
            '#size' => 5
        ];

        $form['celum_connect_dlft'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Downloadformat text'),
            '#default_value' => $dlft,
            '#required' => FALSE,
            '#size' => 5
        ];

        $form['celum_connect_dlfs'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Supported Downloadformats'),
            '#default_value' => $dlfs,
            '#required' => FALSE,
            '#size' => 50
        ];


        return parent::buildForm($form, $form_state);
    }

    /**
     * Implements \Drupal\Core\Form\FormInterface:submitForm()
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = \Drupal::service('config.factory')->getEditable('celum_connect.settings');
        $config->set('celum_connect_licenseKey', $form_state->getValue('celum_connect_licenseKey'))
            ->set('celum_connect_apikey', $form_state->getValue('celum_connect_apikey'))
            ->set('celum_connect_locale', $form_state->getValue('celum_connect_locale'))
            ->set('celum_connect_rootNode', $form_state->getValue('celum_connect_rootNode'))
            ->set('celum_connect_dlfu', $form_state->getValue('celum_connect_dlfu'))
            ->set('celum_connect_dlfi', $form_state->getValue('celum_connect_dlfi'))
            ->set('celum_connect_dlfd', $form_state->getValue('celum_connect_dlfd'))
            ->set('celum_connect_dlfv', $form_state->getValue('celum_connect_dlfv'))
            ->set('celum_connect_dlfa', $form_state->getValue('celum_connect_dlfa'))
            ->set('celum_connect_dlft', $form_state->getValue('celum_connect_dlft'))
            ->set('celum_connect_dlfs', $form_state->getValue('celum_connect_dlfs'))
            ->set('celum_connect_usage_download', $form_state->getValue('celum_connect_usage_download'))
            ->set('celum_connect_usage_link', $form_state->getValue('celum_connect_usage_link'))
            ->set('celum_connect_asset_picker_version', $form_state->getValue('celum_connect_asset_picker_version'))
            ->save();
        $this->createConfigFile($form_state);
        parent::submitForm($form, $form_state);
    }

    function createConfigFile($form_state){
        $versions = array();
        $versions[0] = '2.0';
        $versions[1] = '2.1';
        $versions[2] = '2.2';
        $versions[3] = '2.3';
        $versions[4] = '2.4';
        $versions[5] = '2.5';
        $versions[6] = '2.5.1';
        $versions[7] = '2.5.2';

        list($url, $t) = explode("_", $this->decrypt($form_state->getValue('celum_connect_licenseKey')));

        $script="Custom.AssetPickerConfig = {
            endPoint: '".$url."/cora',
            apiKey: '".$form_state->getValue('celum_connect_apikey')."',
            locale: '".$form_state->getValue('celum_connect_locale')."',
            searchScope: {
              rootNodes: [".$form_state->getValue('celum_connect_rootNode')."]
            },
            requiredAssetData: ['fileInformation','versionInformation','binaries'],
            downloadFormats: {
              defaults: {
                unknown: ".$form_state->getValue('celum_connect_dlfu').",
                image: ".$form_state->getValue('celum_connect_dlfi').",
                document:".$form_state->getValue('celum_connect_dlfd').",
                video: ".$form_state->getValue('celum_connect_dlfv').",
                audio: ".$form_state->getValue('celum_connect_dlfa').",
                text: ".$form_state->getValue('celum_connect_dlft')."
                },
                supported: [".$form_state->getValue('celum_connect_dlfs')."],
                additionalDownloadFormats: [".$form_state->getValue('celum_connect_dlfs')."]
            },
            nrOfAllowedDownloadFormats: 99,
            forceDownloadSelection: true,
            keepSelectionOnExport: true
        };";
        $file = drupal_get_path('module', 'celum_connect').'/assetPicker/assetPicker_'.$versions[$form_state->getValue('celum_connect_asset_picker_version')].'/config.js';
        file_put_contents($file, $script);
    }


    function decrypt($sData){
        $secretKey = "ZbMchtd9DivzjPDi5QIio1iVERFnNZiSE33QKY3Gw9rYfCNLFiKloJQt3zi4";
        $sResult = '';
        $sData   = $this->decode_base64($sData);
        for($i=0;$i<strlen($sData);$i++){
            $sChar    = substr($sData, $i, 1);
            $sKeyChar = substr($secretKey, ($i % strlen($secretKey)) - 1, 1);
            $sChar    = chr(ord($sChar) - ord($sKeyChar));
            $sResult .= $sChar;
        }
        return $sResult;
    }

    function decode_base64($sData){
        $sBase64 = strtr($sData, '-_', '+/');
        return base64_decode($sBase64.'==');
    }

}
