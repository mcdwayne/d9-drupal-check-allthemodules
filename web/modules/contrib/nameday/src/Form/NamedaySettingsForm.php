<?php

/**
 * @file
 * Contains \Drupal\nameday\Form\NamedaySettingsForm.
 */

namespace Drupal\nameday\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Configure nameday module.
 */
class NamedaySettingsForm extends ConfigFormBase {

  /**
   * {@inheritDoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nameday_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['nameday.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('nameday.settings');

    $form['show_date'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show date in the name day'),
      '#default_value' => $config->get('show_date'),
      '#description'   => $this->t('If this checked, the date is displayed before the name days and holidays.'),
    ];

    $form['show_holiday'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show holiday in the name day, if any'),
      '#default_value' => $config->get('show_holiday'),
    ];

    $form['date_format'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Date format'),
      '#default_value' => $config->get('date_format'),
      '#options'       => [
        'short'  => $this->t('Short date format'),
        'medium' => $this->t('Medium date format'),
        'long'   => $this->t('Long date format'),
        'custom' => $this->t('Custom date format'),
      ],
      '#description'   => $this->t('If you want to show the date, You can choose the format of it. You can set it up in the <a href="@url">Date and time settings</a>', [
        '@url' => Url::fromUri('internal:/admin/config/regional/date-time')
          ->toString(),
      ]),
    ];

    $form['date_format_custom'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Custom date format'),
      '#default_value' => $config->get('date_format_custom'),
      '#description'   => $this->t('If You choose the custom date format here You can define the date format. See the <a href="@url">PHP manual</a> for available options.', [
        '@url' => 'http://php.net/manual/function.date.php',
      ]),
      '#states'        => [
        'visible' => [
          ':input[name="date_format"]' => [
            'value' => 'custom',
          ],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('nameday.settings');

    $form_state->cleanValues();

    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
