<?php

namespace Drupal\urban_airship_web_push_notifications\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;

/**
 * Configure Airship Web Notifications Opt-In Form.
 */
class OptInPrompt extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['urban_airship_web_push_notifications.configuration'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uawn_unzip_sdk_bundle_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('urban_airship_web_push_notifications.configuration');
    $form = parent::buildForm($form, $form_state);
    $form['info'] = [
      '#markup' => $this->t('<p>For additional details on creating or using a custom opt-in, please see the <a href="https://docs.urbanairship.com/platform/web/#registration-ui" target="_blank">Airship documentation</a>.</p>'),
    ];
    $form['hide_optin_prompt'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Do not use custom Opt-In Prompt'),
      '#default_value' => $config->get('hide_optin_prompt'),
      '#description'   => $this->t('If box is checked, no custom prompt will be shown. Instead, users will only see the required default browser prompt for notification permission.'),
    ];
    $form['optin_prompt_type'] = [
      '#type'          => 'radios',
      '#title'         => $this->t('Prompt Type'),
      '#description'   => $this->t('Select prompt type.'),
      '#default_value' => ($optin_prompt_type = $config->get('optin_prompt_type')) ? $optin_prompt_type : 'modal',
      '#options'       => [
        'modal'  => $this->t('Modal'),
        'banner' => $this->t('Banner'),
        'button' => $this->t('Button'),
      ],
      '#states' => [
        'invisible' => [
          ':input[name="hide_optin_prompt"]' => ['checked' => TRUE]
        ],
      ],
    ];
    $form['#attached']['library'][] = 'urban_airship_web_push_notifications/opt_in.prompt_css';
    $form['#attached']['library'][] = 'urban_airship_web_push_notifications/opt_in.prompt_example';
    $module_handler = \Drupal::service('module_handler');
    $path = $module_handler->getModule('urban_airship_web_push_notifications')->getPath();
    $optin_prompt_bell_icon_path = '/' . $path . '/assets/img/bell.svg';
    $form['optin_prompt_example'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="uawn-opt-in-prompt-example">
        <h4>' . $this->t('Opt-in Prompt Example') . '</h4>
        <div class="uawn-opt-in-prompt prompt-modal example">
          <div class="optin-message">' . $this->t('Want to stay up to date with the latest breaking news alerts?') . '</div>
          <div class="optin-buttons-wrapper">
            <span id="uawn-opt-in-allow" class="prompt-button btn-prompt-allow">' . $this->t('Ok') . '</span>
            <span id="uawn-opt-in-dismiss" class="prompt-button btn-prompt-dismiss">' . $this->t('Not Now') . '</span>
          </div>
        </div>
        <div class="uawn-opt-in-prompt prompt-banner example">
          <div class="prompt-banner-wrapper">
            <div class="optin-message">' . $this->t('Want to stay up to date with the latest breaking news alerts?') . '</div>
            <div class="optin-buttons-wrapper">
              <span id="uawn-opt-in-dismiss" class="prompt-button btn-prompt-dismiss">' . $this->t('Not Now') . '</span>
              <span id="uawn-opt-in-allow" class="prompt-button btn-prompt-allow">' . $this->t('Ok') . '</span>
            </div>
          </div>
        </div>
        <div class="uawn-opt-in-prompt prompt-button example">
          <div class="optin-buttons-wrapper">
            <span id="uawn-opt-in-allow" class="prompt-button btn-prompt-allow"><img src="' . $optin_prompt_bell_icon_path . '" width="30"></span>
          </div>
          <div class="uawn-button-snippet">
            <p><div class="descr optional">' . $this->t('Optionally, use this HTML snippet to place a button in your page templates:') . '</div>
              <div class="descr">' . $this->t('Use this HTML snippet to place the button in your page templates:') . '</div></p>
            <div class="uawn-button-html-code">&lt;div class="uawn-button">&lt;/div></div>
          </div>
        </div>
      </div>'
    ];
    $form['optin_prompt'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Opt-in Prompt'),
      '#description'   => $this->t('Customize the text for your opt-in prompt. The prompt displays to your website visitors and should give them some context as to what to expect from your notifications. For example, "Want to stay up to date with the latest breaking news alerts?"'),
      '#default_value' => ($optin_prompt = $config->get('optin_prompt')) ? $optin_prompt : 'Want to stay up to date with the latest breaking news alerts?',
      '#attributes'    => [
        'placeholder' => $this->t('Notification message')
      ],
      '#states' => [
        'invisible' => [
          [
            ':input[name="hide_optin_prompt"]' => ['checked' => TRUE],
          ],
          [
            ':input[name="optin_prompt_type"]' => ['value' => 'button'],
          ]
        ],
      ],
    ];
    $form['optin_allow_label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Allow Label'),
      '#description'   => $this->t('Label of the button on the prompt used for allowing notifications. Examples include: Allow or Yes'),
      '#default_value' => ($optin_allow_label = $config->get('optin_allow_label')) ? $optin_allow_label : 'Yes',
      '#size'          => 20,
      '#attributes'    => [
        'placeholder' => $this->t('Yes')
      ],
      '#states' => [
        'invisible' => [
          [
            ':input[name="hide_optin_prompt"]' => ['checked' => TRUE],
          ],
          [
            ':input[name="optin_prompt_type"]' => ['value' => 'button'],
          ]
        ],
      ],
    ];
    $form['optin_dismiss_label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Dismiss Label'),
      '#description'   => $this->t('Label of the button used for dismissing the prompt. Examples include: Not Now, Maybe Later, or No Thanks'),
      '#default_value' => ($optin_dismiss_label = $config->get('optin_dismiss_label')) ? $optin_dismiss_label : 'Not Now',
      '#size'          => 20,
      '#attributes'    => [
        'placeholder' => $this->t('Not Now')
      ],
      '#states' => [
        'invisible' => [
          [
            ':input[name="hide_optin_prompt"]' => ['checked' => TRUE],
          ],
          [
            ':input[name="optin_prompt_type"]' => ['value' => 'button'],
          ]
        ],
      ],
    ];
    $form['optin_banner_position'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Banner Position'),
      '#description'   => $this->t('Customize where on the page to display the banner opt-in prompt, such as the top of the page or bottom.'),
      '#default_value' => ($optin_banner_position = $config->get('optin_banner_position')) ? $optin_banner_position : 'top',
      '#options'       => [
        'top'    => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
      ],
      '#states' => [
        'invisible' => [
          [
            ':input[name="hide_optin_prompt"]' => ['checked' => TRUE]
          ],
          [
            ':input[name="optin_prompt_type"]' => ['value' => 'modal'],
          ],
          [
            ':input[name="optin_prompt_type"]' => ['value' => 'button'],
          ]
        ],
      ],
    ];
    $form['optin_position'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Position'),
      '#description'   => $this->t('Customize where on the page to display the modal opt-in prompt, such as the middle of the page or the bottom left corner.'),
      '#default_value' => ($optin_position = $config->get('optin_position')) ? $optin_position : 'bottom-left',
      '#options'       => [
        'top-left'      => $this->t('Top Left'),
        'top-center'    => $this->t('Top Center'),
        'top-right'     => $this->t('Top Right'),
        'middle'        => $this->t('Middle'),
        'bottom-left'   => $this->t('Bottom Left'),
        'bottom-center' => $this->t('Bottom Center'),
        'bottom-right'  => $this->t('Bottom Right'),
      ],
      '#states' => [
        'invisible' => [
          [
            ':input[name="hide_optin_prompt"]' => ['checked' => TRUE]
          ],
          [
            ':input[name="optin_prompt_type"]' => ['value' => 'banner'],
          ],
          [
            ':input[name="optin_prompt_type"]' => ['value' => 'button'],
          ]
        ],
      ],
    ];
    $form['prompt_notifications'] = [
      '#type'          => 'radios',
      '#title'         => $this->t('Prompt for Notifications'),
      '#default_value' => ($prompt_notifications = $config->get('prompt_notifications')) ? $prompt_notifications : 'on_page_load',
      '#options'       => [
        'on_page_load'  => $this->t('Display opt-in prompt on page load'),
        'on_page_views' => $this->t('Display opt-in prompt after specified number of page views'),
      ],
      '#states' => [
        'visible' => [
          [
            ':input[name="hide_optin_prompt"]' => ['checked' => TRUE]
          ],
          [
            ':input[name="optin_prompt_type"]' => ['value' => 'modal'],
          ],
          [
            ':input[name="optin_prompt_type"]' => ['value' => 'banner'],
          ]
        ],
      ],
    ];
    $form['page_views'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Number of Page Views'),
      '#default_value' => ($page_views = $config->get('page_views')) ? $page_views : 1,
      '#description'   => $this->t('Numeric value to set page views'),
      '#states' => [
        'visible' => [
          [
            ':input[name="prompt_notifications"]' => ['value' => 'on_page_views'],
          ],
        ],
      ],
    ];
    $form['temporarily_disable'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Temporarily Disable Opt-In Prompt'),
      '#default_value' => ($temporarily_disable = $config->get('temporarily_disable')) ? $temporarily_disable : 10,
      '#description'   => $this->t('Temporarily disable the opt-in prompt for a specified number of page views when a user dismisses the prompt without allowing notifications.'),
      '#states' => [
        'visible' => [
          [
            ':input[name="hide_optin_prompt"]' => ['checked' => TRUE]
          ],
          'or',
          [
            [
              ':input[name="optin_prompt_type"]' => ['value' => 'modal'],
            ],
            [
              ':input[name="optin_prompt_type"]' => ['value' => 'banner'],
            ]
          ]
        ],
      ],
    ];
    $form['optin_prompt_button_example'] = [
      '#type' => 'inline_template',
      '#template' => '<div id="button-html-snippet" class="uawn-opt-in-prompt-example">
        <div class="uawn-opt-in-prompt prompt-button example">
          <div class="optin-buttons-wrapper">
            <span id="uawn-opt-in-allow" class="prompt-button btn-prompt-allow"><img src="' . $optin_prompt_bell_icon_path . '" width="30"></span>
          </div>
          <div class="uawn-button-snippet">
            <p>' . $this->t('Optionally, use this HTML snippet to place a button in your page templates. This button may be used in combination with the default browser prompt.') . '</p>
            <div class="uawn-button-html-code">&lt;div class="uawn-button">&lt;/div></div>
          </div>
        </div>
      </div>'
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('urban_airship_web_push_notifications.configuration');
    $form_state->cleanValues();
    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
    Cache::invalidateTags(['urban_airship_optin_prompt']);
    parent::submitForm($form, $form_state);
  }

}
