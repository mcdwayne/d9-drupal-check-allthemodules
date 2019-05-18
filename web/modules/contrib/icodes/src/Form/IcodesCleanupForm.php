<?php

namespace Drupal\icodes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\field\FieldConfigInterface;

/*
 * Icodes settings form.
 */

class IcodesCleanupForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'icodes_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return array('icodes.settings');
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('icodes.settings');

        $api_url = Url::fromUri('http://www.icodes.co.uk/webservices/index.php',
                array('attributes' => array('target' => '_blank')));

        $webservice_link = Url::fromUri('http://www.icodes.co.uk/webservices/webservices.php',
                array('attributes' => array('target' => '_blank')));

        $icodes_high_res_link = Url::fromUri('http://www.vclogos.co.uk',
                array('attributes' => array('target' => '_blank')));

        //state of new offers,published or not.
        $default = $config->get('icodes_cleanup_enable');

        $form['icodes_cleanup_enable'] = array(
            '#type' => 'checkbox',
            '#title' => t('Enable Cleanup Tasks'),
            '#description' => t('Should the cleanup cron task run.'),
            '#default_value' => ($default !== null) ? $default : true,
        );


        $form['icodes_cleanup_max_active'] = array(
            '#type' => 'select',
            '#options' => array(
                '25' => '25 active codes and offers per merchant',
                '50' => '50 active codes and offers per merchant',
                '100' => '100 active codes and offers per merchant',
                '150' => '150 active codes and offers per merchant',
                '200' => '200 active codes and offers per merchant',
                '300' => '300 active codes and offers per merchant',
                '400' => '400 active codes and offers per merchant',
                '500' => '500 active codes and offers per merchant',
            ),
            '#title' => t('How many active codes to keep per perchant'),
            '#required' => TRUE,
            '#default_value' => $config->get('icodes_cleanup_max_active'),
            '#description' => t('Once the number is hit the older ones will be deleted.'),
        );

        $form['icodes_cleanup_max_expired'] = array(
            '#type' => 'select',
            '#options' => array(
                '25' => '25 expired codes and offers per merchant',
                '50' => '50 expired codes and offers per merchant',
                '100' => '100 expired codes and offers per merchant',
                '150' => '150 expired codes and offers per merchant',
                '200' => '200 expired codes and offers per merchant',
                '300' => '300 expired codes and offers per merchant',
                '400' => '400 expired codes and offers per merchant',
                '500' => '500 expired codes and offers per merchant',
            ),
            '#title' => t('How many expired codes to keep per perchant'),
            '#required' => TRUE,
            '#default_value' => $config->get('icodes_cleanup_max_expired'),
            '#description' => t('Once the number is hit the older ones will be deleted.'),
        );

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $config = $this->config('icodes.settings');
        $config
            ->set('icodes_cleanup_enable',
                $form_state->getValue('icodes_cleanup_enable'))
            ->set('icodes_cleanup_max_active',
                $form_state->getValue('icodes_cleanup_max_active'))
            ->set('icodes_cleanup_max_expired',
                $form_state->getValue('icodes_cleanup_max_expired'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}