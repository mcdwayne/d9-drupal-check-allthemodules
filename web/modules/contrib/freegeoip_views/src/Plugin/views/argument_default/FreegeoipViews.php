<?php

namespace Drupal\freegeoip_views\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

/**
 * Freegeoip Parameters Selector.
 *
 * @ViewsArgumentDefault(
 *   id = "freegeoip",
 *   title = @Translation("Freegeoip Values")
 * )
 */
class FreegeoipViews extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

    /**
     * {@inheritdoc}
     */
    protected function defineOptions() {
      $options = parent::defineOptions();
      $options['argument'] = array('default' => '');

      return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function buildOptionsForm(&$form, FormStateInterface $form_state) {
      parent::buildOptionsForm($form, $form_state);
      $form['argument'] = array(
        '#type' => 'select',
        '#title' => $this->t('Select the Freegeoip Value'),
        '#options' => array(
          'ip' => 'ip',
          'country_code' => 'country_code',
          'country_name' => 'country_name',
          'region_code' => 'region_code',
          'region_name' => 'region_name',
          'city' => 'city',
          'zip_code' => 'zip_code',
          'time_zone' => 'time_zone',
          'latitude' => 'latitude',
          'longitude' => 'longitude',
          'metro_code' => 'metro_code',
        ),
        '#default_value' => $this->options['argument'],
      );
    }

    /**
     * {@inheritdoc}
     */
    public function getArgument() {
      if(!empty($_SESSION['freegeoip'])) {
        $argument = $this->options['argument'];
        $freegeoip = json_decode($_SESSION['freegeoip']);
        return $freegeoip->$argument;
      }
      return NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheMaxAge() {
      return Cache::PERMANENT;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheContexts() {
      return [];
    }

  }
