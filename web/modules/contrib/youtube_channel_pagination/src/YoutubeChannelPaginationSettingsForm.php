<?php

namespace Drupal\youtubechannelpagination;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * provides Configure settings.
 */
class YoutubeChannelPaginationSettingsForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'youtubechannelpagination_admin_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'youtubechannelpagination.settings'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $config = $this->config('youtubechannelpagination.settings');


        if (empty($config->get('youtubechannelpagination_api_key'))) {
            $youtubechannelpagination_api_key = "";
        } else {
            $youtubechannelpagination_api_key = $config->get('youtubechannelpagination_api_key');
        }

        if (empty($config->get('youtubechannelpagination_id'))) {
            $youtubechannelpagination_id = '';
        } else {
            $youtubechannelpagination_id = $config->get('youtubechannelpagination_id');
        }

        if (empty($config->get('youtubechannelpagination_video_limit'))) {
            $youtubechannelpagination_video_limit = 5;
        } else {
            $youtubechannelpagination_video_limit = $config->get('youtubechannelpagination_video_limit');
        }
        if (empty($config->get('youtubechannelpagination_video_grid_for_row'))) {
            $youtubechannelpagination_video_grid_for_row = 4;
        } else {
            $youtubechannelpagination_video_grid_for_row = $config->get('youtubechannelpagination_video_grid_for_row');
        }

        if (empty($config->get('youtubechannelpagination_video_width'))) {
            $youtubechannelpagination_video_width = 200;
        } else {
            $youtubechannelpagination_video_width = $config->get('youtubechannelpagination_video_width');
        }

        if (empty($config->get('youtubechannelpagination_video_height'))) {
            $youtubechannelpagination_video_height = 150;
        } else {
            $youtubechannelpagination_video_height = $config->get('youtubechannelpagination_video_height');
        }

        $form['youtubechannelpagination'] = array(
            '#type' => 'fieldset',
            '#title' => t('Youtube channel pagination settings'),
            '#collapsible' => FALSE,
        );

        $form['youtubechannelpagination']['youtubechannelpagination_api_key'] = array(
            '#type' => 'textfield',
            '#title' => t('Youtube Google API Key'),
            '#size' => 40,
            '#default_value' => $youtubechannelpagination_api_key,
            '#required' => TRUE,
            '#description' => t('Your YouTube Google API key from your developer' . 'console. See the README.txt for more details.'),
        );

        $form['youtubechannelpagination']['youtubechannelpagination_id'] = array(
            '#type' => 'textfield',
            '#title' => t('Youtube Channel ID'),
            '#size' => 40,
            '#default_value' => $youtubechannelpagination_id,
            '#required' => TRUE,
            '#description' => t('The youtube channel ID you want to get the videos.'),
        );

        $form['youtubechannelpagination']['youtubechannelpagination_video_limit'] = array(
            '#type' => 'textfield',
            '#title' => t('Youtube Channel video limit Per Page'),
            '#size' => 40,
            '#default_value' => $youtubechannelpagination_video_limit,
            '#required' => TRUE,
            '#description' => t('Number of videos to be shown from youtube channel (max 50).'),
        );

        $form['youtubechannelpagination']['youtubechannelpagination_video_grid_for_row'] = array(
            '#type' => 'textfield',
            '#title' => t('Resposive video grid per row'),
            '#size' => 40,
            '#default_value' => $youtubechannelpagination_video_grid_for_row,
            '#required' => TRUE,
            '#description' => t('Number of videos to be shown on each row (max 12).'),
        );
        $form['youtubechannelpagination']['youtubechannelpagination_video_width'] = array(
            '#type' => 'textfield',
            '#title' => t('Youtube Channel video width'),
            '#size' => 40,
            '#default_value' => $youtubechannelpagination_video_width,
            '#required' => TRUE,
            '#description' => t('Max width to youtube video. In px'),
        );

        $form['youtubechannelpagination']['youtubechannelpagination_video_height'] = array(
            '#type' => 'textfield',
            '#title' => t('Youtube Channel video height'),
            '#size' => 40,
            '#default_value' => $youtubechannelpagination_video_height,
            '#required' => TRUE,
            '#description' => t('Max height to youtube video. In px'),
        );

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->config('youtubechannelpagination.settings')->set('youtubechannelpagination_api_key', $form_state->getValue('youtubechannelpagination_api_key'))
                ->set('youtubechannelpagination_id', $form_state->getValue('youtubechannelpagination_id'))
                ->set('youtubechannelpagination_video_limit', $form_state->getValue('youtubechannelpagination_video_limit'))
                ->set('youtubechannelpagination_video_grid_for_row', $form_state->getValue('youtubechannelpagination_video_grid_for_row'))
                ->set('youtubechannelpagination_video_width', $form_state->getValue('youtubechannelpagination_video_width'))
                ->set('youtubechannelpagination_video_height', $form_state->getValue('youtubechannelpagination_video_height'))->save();
        parent::submitForm($form, $form_state);
    }

}
