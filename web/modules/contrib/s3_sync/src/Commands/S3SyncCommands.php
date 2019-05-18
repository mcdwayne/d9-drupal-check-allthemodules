<?php

namespace Drupal\s3_sync\Commands;

use Drush\Commands\DrushCommands;

class S3SyncCommands extends DrushCommands {

    const SETTINGS = 's3_sync.settings';

    /**
     * Initializes the configuration for s3_sync.
     *
     * @command s3_sync:init
     * @aliases ss-init
     * @options bucket-name Name of S3 bucket you'll use
     * @options aws-region The AWS Region your S3 bucket is in
     * @options mode Either SINGLE or MULTI. See README for more details.
     * @options access-key AWS Access Key of IAM User
     * @options secret-key AWS Secret Key of IAM User
     * @usage s3_sync:init --bucket-name=<bucket-name> --aws-region=<aws-region> --mode=<SINGLE or MULTI>
     *   Set the configuration s3_sync needs to work properly.
     */
    public function init($options = ['bucket-name' => TRUE, 'aws-region' => TRUE, 'mode' => TRUE, 'access-key' => TRUE, 'secret-key' => TRUE]) {
        if ($options['bucket-name'] === TRUE || $options['aws-region'] === TRUE || $options['mode'] === TRUE) {
            $errorMessage = 'Missing options: ';
            if ($options['bucket-name'] === TRUE) $errorMessage .= 'bucket name, ';
            if ($options['aws-region'] === TRUE) $errorMessage .= 'aws region, ';
            if ($options['mode'] === TRUE) $errorMessage .= 'sync mode, ';
            if ($options['access_key'] === TRUE) $errorMessage .= 'access key, ';
            if ($options['secret_key'] === TRUE) $errorMessage .= 'secret key, ';
            $errorMessage .= '(Consider using drush ss-get if you aren\'t settings all values.)';
            \Drupal::messenger()->addError($errorMessage);
        }
        else if ($options['mode'] != 'SINGLE' && $options['mode'] != 'MULTI') {
            \Drupal::messenger()->addError('Invalid mode ' . $options['mode']);
        }
        else {
            if ($options['mode'] == 'SINGLE') $mode = 'single_instance';
            if ($options['mode'] == 'MULTI') $mode = 'multi_instance';
            \Drupal::configFactory()->getEditable(static::SETTINGS)
                ->set('bucket_name', $options['bucket-name'])
                ->set('aws_region', $options['aws-region'])
                ->set('mode', $mode)
                ->set('access_key', $options['access-key'])
                ->set('secret_key', $options['secret-key'])
                ->save();
        }
    }

    /**
     * Sets a specific configuration item for the s3_sync module.
     *
     * @command s3_sync:set
     * @aliases ss-set
     * @param string $configItem The configuration you want to change. Can be bucket_name, aws_region, mode, access_key, or secret_key.
     * @param string $value The value you want to change the config item to.
     * @usage drush s3_sync:set <config-item> <config-value>
     *   Sets a specific config item to a specified value.
     *
     */

    public function set($configItem, $value) {
        if ($configItem === TRUE || $value === TRUE) {
            $errorMessage = 'Missing following values: ';
            if ($configItem === TRUE) $errorMessage .= 'config item, ';
            if ($value === TRUE) $errorMessage .= 'value, ';
            \Drupal::messenger()->addError($errorMessage);
        }
        else if ($configItem != 'bucket_name' && $configItem != 'aws_region' && $configItem != 'mode' && $configItem != 'access_key' && $configItem != 'secret_key') {
            \Drupal::messenger()->addError('Invalid config item: ' . $configItem . ' (Valid config items are: bucket_name, aws_region, mode, access_key, or secret_key.)');
        }
        else {
            if ($configItem == 'mode') {
                if($value != 'SINGLE' && $value != 'MULTI') {
                    \Drupal::messenger()->addError('Invalid mode: ' . $value);
                }
                else {
                    if ($value == 'SINGLE') $value = 'single_instance';
                    else if ($value == 'MULTI') $value = 'multi_instance';
                }
            }
            \Drupal::configFactory()->getEditable(static::SETTINGS)
                ->set($configItem, $value)
                ->save();
        }
    }

    /**
     * Gets a specified configuration item, or all configuration.
     *
     * @command s3_sync:get
     * @aliases ss-get
     * @param string $configItem The configuration item to get.
     * @usage drush s3_sync:get <config-item>
     *   Get a specified config item, or all for all configuration.
     */

    public function get($configItem) {
        if (!$configItem) {
            \Drupal::messenger()->addError('Please specify config item you want to get');
        }
        else if ($configItem != 'bucket_name' && $configItem != 'aws_region' && $configItem != 'mode' && $configItem != 'all') {
            \Drupal::messenger()->addError('Invalid configuration item: ' . $configItem);
        }
        else {
            $config = \Drupal::config(static::SETTINGS);
            if ($configItem == 'all') {
                $this->output()->writeln('S3 Bucket Name: ' . $config->get('bucket_name'));
                $this->output()->writeln('AWS Region: ' . $config->get('aws_region'));
                $this->output()->writeln('Mode: ' . $config->get('mode'));
                $this->output()->writeln('AWS Access Key: ' . $config->get('access_key'));
                $this->output()->writeln('AWS Secret Key: ' . ($config->get('secret_key') ? '******' : 'Not set'));
            }
            else {
                $this->output()->writeln($config->get($configItem));
            }
        }
    }

    /**
     * Used to do the actual syncing of the files.
     *
     * @command s3_sync:sync
     * @aliases ss-sync
     * @usage s3_sync:sync
     *   Syncs public files with S3 bucket from configuration.
     */
    public function sync() {
        syncFiles();
    }
}