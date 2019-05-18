<?php

namespace Drupal\hide_submit\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class HideSubmitSettings.
 *
 * @package Drupal\hide_submit\Form
 */
class HideSubmitSettings extends ConfigFormBase {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->getEditable('hide_submit.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    // Load the service required to construct this class.
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hide_submit_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hide_submit.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['hide_submit_method'] = [
      '#type' => 'select',
      '#options' => [
        'disable' => $this->t('Disable the submit buttons.'),
        'hide' => $this->t('Hide the submit buttons.'),
        'indicator' => $this->t('Built-in loading indicator.'),
      ],
      '#default_value' => $this->config->get('hide_submit_method'),
      '#title' => $this->t('Blocking method'),
      '#description' => $this->t('Choose the blocking method.'),
    ];

    $form['hide_submit_reset_time'] = [
      '#type' => 'number',
      '#title' => $this->t('Reset buttons after some time (ms).'),
      '#description' => $this->t('Enter a value in milliseconds after which all buttons will be enabled. To disable this enter 0.'),
      '#default_value' => $this->config->get('hide_submit_reset_time'),
      '#required' => TRUE,
    ];

    $form['hide_submit_disable'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Disabling settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['hide_submit_disable']['hide_submit_abtext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Append to buttons'),
      '#description' => $this->t('This text will be appended to each of the submit buttons.'),
      '#default_value' => $this->config->get('hide_submit_abtext'),
    ];

    $form['hide_submit_disable']['hide_submit_atext'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Add next to buttons'),
      '#description' => $this->t('This text will be added next to the submit buttons.'),
      '#default_value' => $this->config->get('hide_submit_atext'),
    ];

    $form['hide_submit_hide'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Hiding settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['hide_submit_hide']['hide_submit_hide_fx'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use fade effects?'),
      '#default_value' => $this->config->get('hide_submit_hide_fx'),
      '#description' => $this->t('Enabling a fade in / out effect.'),
    ];

    $form['hide_submit_hide']['hide_submit_hide_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Processing text'),
      '#default_value' => $this->config->get('hide_submit_hide_text'),
      '#description' => $this->t('This text will be shown to the user instead of the submit buttons.'),
    ];

    $form['hide_submit_indicator'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Indicator settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#description' => $this->t('Choose the spinner style as defined by the
      <a href="@library" target="_blank" rel="noopener">ladda.js jQuery library
      </a>. Examples of these styles can be found on the <a href="@examples"
      target="_blank" rel="noopener">Ladda example page</a>.', [
        '@library' => '//github.com/hakimel/Ladda',
        '@examples' => '//lab.hakim.se/ladda/',
      ]),
    ];

    $form['hide_submit_indicator']['hide_submit_indicator_style'] = [
      '#type' => 'select',
      '#options' => [
        'expand-left' => $this->t('expand-left'),
        'expand-right' => $this->t('expand-right'),
        'expand-up' => $this->t('expand-up'),
        'expand-down' => $this->t('expand-down'),
        'contract' => $this->t('contract'),
        'contract-overlay' => $this->t('contract-overlay'),
        'zoom-in' => $this->t('zoom-in'),
        'zoom-out' => $this->t('zoom-out'),
        'slide-left' => $this->t('slide-left'),
        'slide-right' => $this->t('slide-right'),
        'slide-up' => $this->t('slide-up'),
        'slide-down' => $this->t('slide-down'),
      ],
      '#default_value' => $this->config->get('hide_submit_indicator_style'),
      '#title' => $this->t('Built-In Loading Indicator Style'),
    ];

    $form['hide_submit_indicator']['hide_submit_spinner_color'] = [
      '#type' => 'select',
      '#options' => [
        '#000' => $this->t('Black'),
        '#A9A9A9' => $this->t('Dark Grey'),
        '#808080' => $this->t('Grey'),
        '#D3D3D3' => $this->t('Light Grey'),
        '#fff' => $this->t('White'),
      ],
      '#default_value' => $this->config->get('hide_submit_spinner_color'),
      '#title' => $this->t('Built-In Loading Indicator Spinner Color'),
    ];

    $form['hide_submit_indicator']['hide_submit_spinner_lines'] = [
      '#type' => 'number',
      '#title' => $this->t('The number of lines for the spinner'),
      '#default_value' => $this->config->get('hide_submit_spinner_lines'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('hide_submit.settings');
    $config->set('hide_submit_method', $form_state->getValue('hide_submit_method'));
    $config->set('hide_submit_reset_time', $form_state->getValue('hide_submit_reset_time'));
    $config->set('hide_submit_abtext', $form_state->getValue('hide_submit_abtext'));
    $config->set('hide_submit_atext', $form_state->getValue('hide_submit_atext'));
    $config->set('hide_submit_hide_fx', $form_state->getValue('hide_submit_hide_fx'));
    $config->set('hide_submit_indicator_style', $form_state->getValue('hide_submit_indicator_style'));
    $config->set('hide_submit_spinner_color', $form_state->getValue('hide_submit_spinner_color'));
    $config->set('hide_submit_spinner_lines', $form_state->getValue('hide_submit_spinner_lines'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
