<?php

namespace Drupal\gareview\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Block\BlockPluginInterface;

/**
 * Provides a 'CustomBlock' block.
 *
 * @Block(
 *  id = "google_review",
 *  admin_label = @Translation("Google Review"),
 * )
 */
class GoogleReview extends BlockBase implements BlockPluginInterface {

    /**
     * {@inheritdoc}
     */
    public function blockForm($form, FormStateInterface $form_state) {

        $options = array(
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5',
        );
        $form = parent::blockForm($form, $form_state);
        $config = $this->getConfiguration();
        $form['google_site_place_id'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Please enter valid place id.'),
            '#description' => $this->t('Find placeID: <a href="https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder">https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder</a>'),
            '#default_value' => isset($config['google_site_place_id']) ? $config['google_site_place_id'] : '',
        );
        $form['review_link_title'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Please enter review link title'),
            '#default_value' => isset($config['review_link_title']) ? $config['review_link_title'] : '',
        );
        $form['google_map_api_key'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Please enter map api key'),
            '#description' => $this->t('Obtain a free Google javascript API Key at <a href="https://developers.google.com/maps/documentation/javascript/get-api-key">https://developers.google.com/maps/documentation/javascript/get-api-key</a>'),
            '#default_value' => isset($config['google_map_api_key']) ? $config['google_map_api_key'] : '',
        );
        $form['min_rating'] = array(
            '#type' => 'select',
            '#title' => $this->t('Please select min_rating'),
            '#options' => $options,
            '#default_value' => isset($config['min_rating']) ? $config['min_rating'] : '',
        );
        $form['max_rows'] = array(
            '#type' => 'select',
            '#title' => $this->t('Please select max_rows'),
            '#options' => $options,
            '#default_value' => isset($config['max_rows']) ? $config['max_rows'] : '',
        );
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function blockSubmit($form, FormStateInterface $form_state) {
        $this->setConfigurationValue('google_site_place_id', $form_state->getValue('google_site_place_id'));
        $this->setConfigurationValue('review_link_title', $form_state->getValue('review_link_title'));
        $this->setConfigurationValue('google_map_api_key', $form_state->getValue('google_map_api_key'));
        $this->setConfigurationValue('min_rating', $form_state->getValue('min_rating'));
        $this->setConfigurationValue('max_rows', $form_state->getValue('max_rows'));
    }

    public function build() {
        $config = $this->getConfiguration();
        $apiurl = "https://search.google.com/local/writereview?placeid={$config['google_site_place_id']}";
        return array(
            '#markup' => $this->t('<div class="write Review"><a target="_blank" href="' . $apiurl . '">' . $config['review_link_title'] . '</a></div><div id="google-reviews"></div>'),
            '#attached' => array('library' => array('gareview/gareview'), 'drupalSettings' => $config),
        );
    }

}
