<?php

namespace Drupal\site_banner\Form;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Date;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Class SiteBannerConfigurationForm.
 */
class SiteBannerConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'site_banner.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'site_banner_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('site_banner.settings');
    $startDate = NULL;
    $endDate = NULL;

    if ($config->get('site_banner_start_date')) {
      $startDate = new DrupalDateTime ($config->get('site_banner_start_date'));
    }

    if ($config->get('site_banner_end_date')) {
      $endDate = new DrupalDateTime ($config->get('site_banner_end_date'));
    }

    $form['status'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Status'),
      '#description'   => $this->t('Enable/disable banner. (If set this value to disabled then the date fields will be cleared)'),
      '#default_value' => $config->get('status'),
    ];

    $form['show_header'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Header banner'),
      '#description'   => $this->t('Enable/disable banner in the header region'),
      '#default_value' => $config->get('show_header'),
    ];


    $form['show_footer'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Footer banner'),
      '#description'   => $this->t('Enable/disable banner in the footer region'),
      '#default_value' => $config->get('show_footer'),
    ];

    $form['site_banner_text'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Site banner text'),
      '#description'   => $this->t('This Text will be display on the top of the page'),
      '#default_value' => $config->get('site_banner_text'),
    ];

    $form['site_banner_color'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Site banner hex color'),
      '#description'   => $this->t('This color will be used as background color (e.g #FFFFFF)'),
      '#default_value' => $config->get('site_banner_color'),
    ];

    $form['site_banner_start_date'] = [
      '#type'          => 'datetime',
      '#title'         => $this->t('Show banner at specific date'),
      '#description'   => $this->t('Show banner at a specific date'),
      '#default_value' => $startDate,
    ];

    $form['site_banner_end_date'] = [
      '#type'          => 'datetime',
      '#title'         => $this->t('Hide banner at specific date'),
      '#description'   => $this->t('Hide banner at specific date (leave empty if you want to display it permanent)'),
      '#default_value' => $endDate,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->configFactory->getEditable('site_banner.settings');

    $config->set('site_banner_text', $form_state->getValue('site_banner_text'));
    $config->set('status', $form_state->getValue('status'));
    $config->set('show_footer', $form_state->getValue('show_footer'));
    $config->set('show_header', $form_state->getValue('show_header'));
    $config->set('site_banner_color', $form_state->getValue('site_banner_color'));

    if (!$form_state->getValue('status')) {
      $config->set('site_banner_start_date', NULL);
      $config->set('site_banner_end_date', NULL);
      $config->save();

      return;
    }

    if ($form_state->getValue('site_banner_start_date')) {
      $config->set('site_banner_start_date', $form_state->getValue('site_banner_start_date')
        ->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
    }

    if ($form_state->getValue('site_banner_end_date')) {
      $config->set('site_banner_end_date', $form_state->getValue('site_banner_end_date')
        ->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
    }
    $config->save();
  }

}
