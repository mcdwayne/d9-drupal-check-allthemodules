<?php

namespace Drupal\openinbound\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openinbound\Controller\OI;

/**
 * Configure inbound form admin settings for this site.
 */
class OpenInboundSettingsForm extends ConfigFormBase
{


    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'inbound_admin_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return ['inbound.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('openinbound.settings');


        /*
        if ($config->get('settings.openinbound_tracking_id') != ''
            && $config->get('settings.openinbound_api_key') != ''
        ) {

            $form['stats'] = [
                '#type' => 'details',
                '#title' => $this->t('Inbound Statistics'),
                '#open' => TRUE,
                '#tree' => TRUE,
            ];

            $oi = new OI($config->get('settings.openinbound_tracking_id'), $config->get('settings.openinbound_api_key'));
            $stats = $oi->getStats();
            $markup = '';
            foreach ($stats as $key => $stat) {
                $markup .= $key . ': ' . $stat . '<br>';
            }
            $form['stats']['stats_overview'] = [
                '#markup' => $markup
            ];
        }
        */

        $form['page'] = [
            '#type' => 'details',
            '#title' => $this->t('Inbound default settings'),
            '#open' => TRUE,
            '#tree' => TRUE,
        ];

        $form['page']['openinbound_api_key'] = [
            '#type' => 'textfield',
            '#title' => $this->t('API key'),
            '#description' => $this->t('API key to gather additional information about ip data.'),
            '#required' => TRUE,
            '#default_value' => $config->get('settings.openinbound_api_key'),
        ];

        $form['page']['openinbound_tracking_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Tracking ID'),
            '#description' => $this->t('Tracking ID is used to identify your website. Do not change this value until you know what you are doing.'),
            '#required' => TRUE,
            '#default_value' => $config->get('settings.openinbound_tracking_id'),
        ];


        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $settings = $form_state->getValue('page');
        $config = \Drupal::service('config.factory')->getEditable('openinbound.settings');
        $config->set('settings', $settings);
        $config->save();

        parent::submitForm($form, $form_state);
    }

    /**
     * Wrapper for FileItem::validateExtensions.
     */
    public static function validateExtensions($element, FormStateInterface $form_state)
    {
        FileItem::validateExtensions($element, $form_state);
    }

    /**
     * Wrapper for FileItem::validateMaxFilesize.
     */
    public static function validateMaxFilesize($element, FormStateInterface $form_state)
    {
        FileItem::validateMaxFilesize($element, $form_state);
    }

}
