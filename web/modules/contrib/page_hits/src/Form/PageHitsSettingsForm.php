<?php

namespace Drupal\page_hits\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure page_hits settings for this site.
 */
class PageHitsSettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\page_hits\Form\PageHitsSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_hits_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['page_hits.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('page_hits.settings');

    $form['clear_page_hits'] = [
      '#type' => 'details',
      '#title' => $this->t('Clear Page Hits'),
      '#open' => TRUE,
    ];

    $form['clear_page_hits']['clear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear all page hits'),
      '#submit' => ['::submitPageHitsClear'],
    ];

    $form['content'] = [
      '#type' => 'details',
      '#title' => $this->t('Page Hits settings'),
      '#open' => TRUE,
    ];
    $form['content']['increment_page_count_for_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Increment page count for admin users'),
      '#default_value' => $config->get('increment_page_count_for_admin'),
    ];
    $form['content']['show_user_ip_address'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show IP address of user'),
      '#default_value' => $config->get('show_user_ip_address'),
    ];
    $form['content']['show_unique_page_visits'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show total number of unique page visits'),
      '#default_value' => $config->get('show_unique_page_visits'),
    ];
    $form['content']['show_total_page_count'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show total number of page visits'),
      '#default_value' => $config->get('show_total_page_count'),
    ];
    $form['content']['show_page_count_of_logged_in_user'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show total number of page visits by logged in user'),
      '#default_value' => $config->get('show_page_count_of_logged_in_user'),
    ];
    $form['content']['show_total_page_count_of_week'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show total number of page visits of the week'),
      '#default_value' => $config->get('show_total_page_count_of_week'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('page_hits.settings')
      ->set('increment_page_count_for_admin', $form_state->getValue('increment_page_count_for_admin'))
      ->set('show_user_ip_address', $form_state->getValue('show_user_ip_address'))
      ->set('show_unique_page_visits', $form_state->getValue('show_unique_page_visits'))
      ->set('show_total_page_count', $form_state->getValue('show_total_page_count'))
      ->set('show_page_count_of_logged_in_user', $form_state->getValue('show_page_count_of_logged_in_user'))
      ->set('show_total_page_count_of_week', $form_state->getValue('show_total_page_count_of_week'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitPageHitsClear(array &$form, FormStateInterface $form_state) {
    page_hits_flush_all();
    drupal_set_message($this->t('Page Hits cleared.'));
  }

}
