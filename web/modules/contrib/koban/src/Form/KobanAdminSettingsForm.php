<?php

namespace Drupal\koban\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure Google_Analytics settings for this site.
 */
class KobanAdminSettingsForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'koban_admin_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return ['koban.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $config = $this->config('koban.settings');
        $koban_logo = '/'.drupal_get_path('module', 'koban').'/images/logo-koban.png';

        $form['account'] = [
            '#type' => 'details',
            '#title' => $this->t('Koban Tracking settings'),
            '#open' => TRUE,
        ];

        $form['account']['koban'] = array(
            '#markup' => t('<a href="https://koban.cloud/" target="_blank"><img src="'. $koban_logo .'" alt="Koban CRM" /></a>')
        );

        $form['account']['koban_apikey'] = [
            '#default_value' => $config->get('apikey'),
            '#description' => $this->t('Your Koban API Key'),
            '#required' => TRUE,
            '#title' => $this->t('Koban API Key'),
            '#type' => 'textfield',
        ];

        $form['account']['koban_tracking_enabled'] = [
            '#default_value' => $config->get('tracking_enabled'),
            '#description' => $this->t('Enabled the Koban Tracking on your site'),
            '#title' => $this->t('Enabled the Koban Tracking'),
            '#type' => 'checkbox',
        ];

        $form['tracking_page'] = [
            '#type' => 'vertical_tabs',
            '#title' => $this->t('Tracking Page'),
        ];

        $koban_pages_list = $config->get('visibility.request_path_pages');

        $form['tracking']['page_visibility_settings'] = [
            '#type' => 'details',
            '#title' => $this->t('Pages'),
            '#group' => 'tracking_page',
        ];

        if ($config->get('visibility.request_path_mode') == 2) {
            $form['tracking']['page_visibility_settings'] = [];
            $form['tracking']['page_visibility_settings']['koban_visibility_request_path_mode'] = ['#type' => 'value', '#value' => 2];
            $form['tracking']['page_visibility_settings']['koban_visibility_request_path_pages'] = ['#type' => 'value', '#value' => $koban_pages_list];
        }
        else {
            $options = [
                t('Every page except the listed pages'),
                t('The listed pages only'),
            ];
            $description = t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", ['%blog' => '/blog', '%blog-wildcard' => '/blog/*', '%front' => '<front>']);

            $title = t('Pages');
            $form['tracking']['page_visibility_settings']['koban_visibility_request_path_mode'] = [
                '#type' => 'radios',
                '#title' => $this->t('Add tracking to specific pages'),
                '#options' => $options,
                '#default_value' => $config->get('visibility.request_path_mode'),
            ];
            $form['tracking']['page_visibility_settings']['koban_visibility_request_path_pages'] = [
                '#type' => 'textarea',
                '#title' => $title,
                '#title_display' => 'invisible',
                '#default_value' => !empty($koban_pages_list) ? $koban_pages_list : '',
                '#description' => $description,
                '#rows' => 10,
            ];
        }

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = $this->config('koban.settings');
        $config
            ->set('apikey', $form_state->getValue('koban_apikey'))
            ->set('tracking_enabled', $form_state->getValue('koban_tracking_enabled'))
            ->set('visibility.request_path_mode', $form_state->getValue('koban_visibility_request_path_mode'))
            ->set('visibility.request_path_pages', $form_state->getValue('koban_visibility_request_path_pages'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}
