<?php

namespace Drupal\tideways;

use Drupal\Core\StringTranslation\StringTranslationTrait;

class Install {

  use StringTranslationTrait;

  public function requirements($phase) {

    $requirements = [];

    switch($phase) {
      case 'runtime':
        $loaded = extension_loaded('tideways');

        $description = '';

        if ($loaded) {
          $options = [
            'version' => phpversion('tideways'),
            'isStarted' => \Tideways\Profiler::isStarted() ? 'yes' : 'no',
            'isProfiling' => \Tideways\Profiler::isProfiling() ? 'yes' : 'no',
            'isTracing' => \Tideways\Profiler::isTracing() ? 'yes' : 'no',
            'connection' => ini_get('tideways.connection'),
            'upd_connection' => ini_get('tideways.udp_connection'),
            'api_key' => ini_get('tideways.api_key') ?: 'none',
            'sample_rate' => ini_get('tideways.sample_rate'),
            'collect' => ini_get('tideways.collect'),
            'monitor' => ini_get('tideways.monitor'),
            'auto_start' => ini_get('tideways.auto_start'),
            'framework' => ini_get('tideways.framework') ?: 'none'
            ];
          foreach ($options as $key => &$op) {
            $op = "$key: $op";
          }
          $description = implode(' | ', $options);
        }
        else {
          $description = $this->t('The tideways PHP extension is not available.');
        }

        $requirements['tideways_extension'] = [
          'title' => $this->t('Tideways integration'),
          'severity' => $loaded ? REQUIREMENT_OK : REQUIREMENT_ERROR,
          'description' => $description,
        ];

        break;
    }

    return $requirements;

  }

}