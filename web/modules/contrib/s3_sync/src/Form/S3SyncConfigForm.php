<?php

namespace Drupal\s3_sync\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class S3SyncConfigForm extends ConfigFormBase {

    private $awsRegions = [
        'us-east-1' => 'US East (Ohio)',
        'us-east-2' => 'US East (N. Virginia)',
        'us-west-1' => 'US West (N. California)',
        'us-west-2' => 'US West (Oregon)',
        'ap-south-1' => 'Asia Pacific (Mumbai)',
        'ap-northeast-1' => 'Asia Pacific (Tokyo)',
        'ap-northeast-2' => 'Asia Pacific (Seoul)',
        'ap-northeast-3' => 'Asia Pacific (Osaka-Local)',
        'ap-southeast-1' => 'Asia Pacific (Singapore)',
        'ap-southeast-2' => 'Asia Pacific (Sydney)',
        'ca-central-1' => 'Canada (Central)',
        'cn-north-1' => 'China (Beijing)',
        'cn-northwest-1' => 'China (Ningxia)',
        'eu-central-1' => 'EU (Frankfurt)',
        'eu-west-1' => 'EU (Ireland)',
        'eu-west-2' => 'EU (London)',
        'eu-west-3' => 'EU (Paris)',
        'eu-north-1' => 'EU (Stockholm)',
        'sa-east-1' => 'South America (Sao Paulo)',
        'us-gov-east-1' => 'AWS GovCloud (US-East)',
        'us-gov-west-1' => 'AWS GovCloud (US)'
    ];

    const SETTINGS = 's3_sync.settings';

    /**
     * {@inheritdoc}
     */

    protected function getEditableConfigNames() {
        return [
            static::SETTINGS,
        ];
    }

    /**
     * {@inheritdoc}
     */

    public function getFormId() {
        return 's3_sync_config';
    }

    /**
     * {@inheritdoc}
     */

    public function buildForm(array $form, FormStateInterface $formState = null) {

        $config = $this->config(static::SETTINGS);

        $form['mode_options'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Sync Options')
        ];

        $form['mode_options']['mode'] = [
            '#type' => 'radios',
            '#title' => '',
            '#default_value' => $config->get('mode'),
            '#options' => [
                'single_instance' => $this->t('<b>Single Instance Mode</b><br><small>(Forces S3 bucket to always be the same as the local public files directory. No manual removal of orphan files in S3 necessary.)</small>'),
                'multi_instance' => $this->t('<b>Multi Instance Mode</b><br><small>(Useful if load balancing between multiple instances of this site. Copies everything to the S3 bucket without deleting anything, and also copies back from the S3 bucket if new files were added from another instance.)</small>')
            ]
        ];

        if ($config->get('mode') == 'single_instance' || $config->get('mode') == 'multi_instance') {
            $form['mode_options']['sync_now'] = [
                '#type' => 'submit',
                '#value' => $this->t('Sync Files Now'),
                '#submit' => ['syncFiles']
            ];
        }

        $form['bucket_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Bucket Name'),
            '#description' => $this->t('Name of the S3 Bucket to use'),
            '#default_value' => $config->get('bucket_name')
        ];

        $form['aws_region'] = [
            '#type' => 'select',
            '#options' => $this->awsRegions,
            '#title' => $this->t('AWS Region'),
            '#description' => $this->t('Region where the S3 bucket is located.'),
            '#default_value' => $config->get('aws_region')
        ];

        $form['access_key'] = [
            '#type' => 'textfield',
            '#title' => $this->t('AWS Access Key'),
            '#description' => $this->t('Access Key that comes with IAM user'),
            '#default_value' => $config->get('access_key')
        ];

        $form['secret_key'] = [
            '#type' => 'password',
            '#title' => $this->t('AWS Secret Key'),
            '#description' => $this->t('Secret Key that comes with IAM user.'),
            '#default_value' => $config->get('secret_key')
        ];

        return parent::buildForm($form, $formState);
    }

    /**
     * {@inheritdoc}
     */

    public function submitForm(array &$form, FormStateInterface $formState) {
        $this->configFactory->getEditable(static::SETTINGS)
            ->set('bucket_name', $formState->getValue('bucket_name'))
            ->set('aws_region', $formState->getValue('aws_region'))
            ->set('mode', $formState->getValue('mode'))
            ->set('access_key', $formState->getValue('access_key'))
            ->set('secret_key', $formState->getValue('secret_key'))
            ->save();

        parent::submitForm($form, $formState);
    }


}