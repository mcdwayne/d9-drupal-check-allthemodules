<?php

/**
 * @file
 * Contains \Drupal\usfedgov_google_analytics\Form\UsfedgovGoogleAnalyticsForm.
 */

namespace Drupal\usfedgov_google_analytics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class UsfedgovGoogleAnalyticsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'usfedgov_google_analytics_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('usfedgov_google_analytics.settings');
    $values = $form_state->getValues();
    $config->set('usfedgov_google_analytics__load_from_cdn', $values['usfedgov_google_analytics__load_from_cdn']);
    $config->set('usfedgov_google_analytics__load_minified', $values['usfedgov_google_analytics__load_minified']);
    $this->configRecurse($config, $values['usfedgov_google_analytics__settings'], 'usfedgov_google_analytics__settings');

    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * recursively go through the set values to set the configuration
   */
  protected function configRecurse($config, $values, $base = '') {
    foreach ($values AS $var => $value) {
      if (!empty($base)) {
        $v = $base . '.' . $var;
      }
      else {
        $v = $var;
      }
      if (!is_array($value)) {
        $config->set($v, $value);
      }
      else {
        $this->configRecurse($config, $value, $v);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['usfedgov_google_analytics.settings'];
  }

  public function buildForm(array $form = [], FormStateInterface $form_state) {
    $settings = usfedgov_google_analytics_settings();

    $form['library'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Library'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['library']['usfedgov_google_analytics__load_from_cdn'] = array(
      '#type'             => 'checkbox',
      '#title'            => $this->t('Load Analytics library from CDN'),
      '#description'      => $this->t('This will use the DAP analytics library from http://analytics.usa.gov hosted by their CloudFlare CDN.'),
      '#default_value'    => (int) \Drupal::config('usfedgov_google_analytics.settings')->get('usfedgov_google_analytics__load_from_cdn'),
    );

    $form['library']['usfedgov_google_analytics__load_minified'] = [
      '#type' => 'radios',
      '#title' => $this->t('Library Version'),
      '#description' => $this->t('Which version of the library should be loaded?'),
      '#default_value' => (int) \Drupal::config('usfedgov_google_analytics.settings')->get('usfedgov_google_analytics__load_minified'),
      '#options' => [
        1 => t('Minified'),
        0 => t('Original'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="usfedgov_google_analytics__load_from_cdn"]' => [
            'unchecked' => TRUE
            ]
          ]
        ],
    ];

    $form['usfedgov_google_analytics__settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
      '#description' => $this->t('More information about the settings can be found in <a href="@analytics-instructions-url">the DAP analytics tool instructions</a> or <a href="@quick-guide-url">the GSA DAP UA code quick guide</a>.', [
        '@analytics-instructions-url' => 'http://www.digitalgov.gov/services/dap/analytics-tool-instructions/',
        '@quick-guide-url' => 'https://www.digitalgov.gov/files/2015/02/GSA-DAP-UA-Code-Quick-Guide-15-01-30-v1-02_mvf.pdf',
      ]),
      '#collapsible' => FALSE,
      '#tree' => TRUE,
    ];

    $form['usfedgov_google_analytics__settings']['agency'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agency'),
      '#description' => $this->t('The main Federal agency which this website is part of. i.e. DHS'),
      '#default_value' => $settings['agency'],
      '#required' => TRUE,
    ];
    $form['usfedgov_google_analytics__settings']['subagency'] = [
      '#type' => 'textfield',
      '#title' => t('Sub-Agency'),
      '#description' => $this->t('The sub-agency, if any, which this website is part of. i.e. FEMA'),
      '#default_value' => $settings['subagency'],
    ];
    $form['usfedgov_google_analytics__settings']['extra'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Extra Settings'),
      '#description' => $this->t('These all come with default settings which will be used if left blank.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['usfedgov_google_analytics__settings']['extra']['sp'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Params'),
      '#description' => $this->t('Default: "@default"', [
        '@default' => 'q|querytext|nasaInclude|k|QT|'
        ]),
      '#default_value' => $settings['extra']['sp'],
    ];
    $form['usfedgov_google_analytics__settings']['extra']['exts'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File Extensions'),
      '#description' => $this->t('Default: "@default"', [
        '@default' => 'doc|docx|xls|xlsx|xlsm|ppt|pptx|exe|zip|pdf|js|txt|csv|dxf|dwgd|rfa|rvt|dwfx|dwg|wmv|jpg|msi|7z|gz|tgz|wma|mov|avi|mp3|mp4|csv|mobi|epub|swf|rar'
        ]),
      '#default_value' => $settings['extra']['exts'],
    ];
    $form['usfedgov_google_analytics__settings']['extra']['yt'] = [
      '#type' => 'radios',
      '#title' => $this->t('Youtube'),
      '#default_value' => (int) $settings['extra']['yt'],
      '#options' => [
        1 => $this->t('true'),
        0 => $this->t('false'),
      ],
    ];
    $form['usfedgov_google_analytics__settings']['extra']['sdor'] = [
      '#type' => 'radios',
      '#title' => $this->t('Subdomain Based'),
      '#default_value' => (int) $settings['extra']['sdor'],
      '#options' => [
        1 => $this->t('true'),
        0 => $this->t('false'),
      ],
    ];
    $form['usfedgov_google_analytics__settings']['extra']['dclink'] = [
      '#type' => 'radios',
      '#title' => $this->t('DoubleClick Link'),
      '#default_value' => (int) $settings['extra']['dclink'],
      '#options' => [
        1 => $this->t('true'),
        0 => $this->t('false'),
      ],
    ];
    $form['usfedgov_google_analytics__settings']['extra']['aip'] = [
      '#type' => 'radios',
      '#title' => $this->t('Anonymize IP'),
      '#default_value' => (int) $settings['extra']['aip'],
      '#options' => [
        1 => $this->t('true'),
        0 => $this->t('false'),
      ],
    ];
    $form['usfedgov_google_analytics__settings']['extra']['pua'] = [
      '#type' => 'textfield',
      '#title' => t('PUA_NAME'),
      '#description' => t('Default: "@default"', [
        '@default' => 'GSA_CP'
        ]),
      '#default_value' => (int) $settings['extra']['pua'],
    ];
    $form['usfedgov_google_analytics__settings']['extra']['enhlink'] = [
      '#type' => 'radios',
      '#title' => $this->t('Enhanced Link'),
      '#default_value' => (int) $settings['extra']['enhlink'],
      '#options' => [
        1 => $this->t('true'),
        0 => $this->t('false'),
      ],
    ];
    $form['usfedgov_google_analytics__settings']['extra']['autotracker'] = [
      '#type' => 'radios',
      '#title' => t('Autotracker'),
      '#default_value' => (int) $settings['extra']['autotracker'],
      '#options' => [
        1 => t('true'),
        0 => t('false'),
      ],
    ];
    $form['usfedgov_google_analytics__settings']['extra']['forcessl'] = [
      '#type' => 'radios',
      '#title' => $this->t('Force SSL'),
      '#default_value' => (int) $settings['extra']['forcessl'],
      '#options' => [
        1 => $this->t('true'),
        0 => $this->t('false'),
      ],
    ];
    $form['usfedgov_google_analytics__settings']['extra']['optout'] = [
      '#type' => 'radios',
      '#title' => $this->t('Opt Out Page'),
      '#default_value' => (int) $settings['extra']['optout'],
      '#options' => [
        1 => $this->t('true'),
        0 => $this->t('false'),
      ],
    ];
    $form['usfedgov_google_analytics__settings']['extra']['fedagencydim'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MAIN_AGENCY_CUSTOM_DIMENSION_SLOT'),
      '#description' => $this->t('Default: "@default"', [
        '@default' => 'dimension1'
        ]),
      '#default_value' => $settings['extra']['fedagencydim'],
    ];
    $form['usfedgov_google_analytics__settings']['extra']['fedsubagencydim'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MAIN_SUBAGENCY_CUSTOM_DIMENSION_SLOT'),
      '#description' => $this->t('Default: "@default"', [
        '@default' => 'dimension2'
        ]),
      '#default_value' => $settings['extra']['fedsubagencydim'],
    ];
    $form['usfedgov_google_analytics__settings']['extra']['fedversiondim'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MAIN_CODEVERSION_CUSTOM_DIMENSION_SLOT'),
      '#description' => $this->t('Default: "@default"', [
        '@default' => 'dimension3'
        ]),
      '#default_value' => $settings['extra']['fedversiondim'],
    ];
    $form['usfedgov_google_analytics__settings']['extra']['palagencydim'] = [
      '#type' => 'textfield',
      '#title' => t('PARALLEL_AGENCY_CUSTOM_DIMENSION_SLOT'),
      '#description' => t('Default: "@default"', [
        '@default' => 'dimension1'
        ]),
      '#default_value' => $settings['extra']['palagencydim'],
    ];
    $form['usfedgov_google_analytics__settings']['extra']['palsubagencydim'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PARALLEL_SUBAGENCY_CUSTOM_DIMENSION_SLOT'),
      '#description' => $this->t('Default: "@default"', [
        '@default' => 'dimension2'
        ]),
      '#default_value' => $settings['extra']['palsubagencydim'],
    ];
    $form['usfedgov_google_analytics__settings']['extra']['palversiondim'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PARALLEL_CODEVERSION_CUSTOM_DIMENSION_SLOT'),
      '#description' => $this->t('Default: "@default"', [
        '@default' => 'dimension3'
        ]),
      '#default_value' => $settings['extra']['palversiondim'],
    ];
    $form['usfedgov_google_analytics__settings']['extra']['maincd'] = [
      '#type' => 'radios',
      '#title' => $this->t('USE_MAIN_CUSTOM_DIMENSIONS'),
      '#default_value' => (int) $settings['extra']['maincd'],
      '#options' => [
        1 => $this->t('true'),
        0 => $this->t('false'),
      ],
    ];
    $form['usfedgov_google_analytics__settings']['extra']['parallelcd'] = [
      '#type' => 'radios',
      '#title' => $this->t('USE_PARALLEL_CUSTOM_DIMENSIONS'),
      '#default_value' => (int) $settings['extra']['parallelcd'],
      '#options' => [
        1 => $this->t('true'),
        0 => $this->t('false'),
      ],
    ];
    $form['usfedgov_google_analytics__settings']['extra']['cto'] = [
      '#type' => 'textfield',
      '#title' => $this->t('COOKIE_TIMEOUT'),
      '#description' => $this->t('Default: "@default"', [
        '@default' => '24'
        ]),
      '#default_value' => $settings['extra']['cto'],
    ];

    return parent::buildForm($form, $form_state);
  }
}
