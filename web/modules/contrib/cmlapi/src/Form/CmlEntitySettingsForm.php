<?php

namespace Drupal\cmlapi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\cmlapi\Service\CmlCleanerInterface;

/**
 * Class CmlEntitySettingsForm.
 *
 * @package Drupal\cmlapi\Form
 *
 * @ingroup cmlapi
 */
class CmlEntitySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cmlapi.cleaner')
    );
  }

  /**
   * SettingsForm constructor.
   *
   * Drupal\cmlapi\Service\CmlCleanerInterface $cleaner
   *   Clerner service.
   */
  public function __construct(CmlCleanerInterface $cleaner) {
    $this->cleanerService = $cleaner;
  }

  /**
   * AJAX Responce.
   */
  public function ajax($otvet) {
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand("#cleaner-results", "<pre>{$otvet}</pre>"));
    return $response;
  }

  /**
   * AJAX Import.
   */
  public function ajaxCleanerCheck(array $form, FormStateInterface $form_state) {
    $otvet = "ajaxCleanerCheck\n";
    $ids = $this->cleanerService->view();
    $otvet .= implode(", ", $ids);
    return $this->ajax($otvet);
  }

  /**
   * AJAX Import.
   */
  public function ajaxCleanerRun(array $form, FormStateInterface $form_state) {
    $otvet = "ajaxCleanerRun\n";
    $ids = $this->cleanerService->clean();
    $otvet .= implode(", ", $ids);
    return $this->ajax($otvet);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'CmlEntity_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cmlapi.mapsettings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cmlapi.mapsettings');
    $form['cleaner'] = [
      '#type' => 'details',
      '#title' => $this->t('Cleaner'),
      '#open' => TRUE,
    ];
    $form['cleaner']['cleaner-cron'] = [
      '#title' => $this->t('Cron Cleaner'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('cleaner-cron'),
    ];
    $form['cleaner']['cleaner-force'] = [
      '#title' => $this->t('Force file delete'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('cleaner-force'),
    ];
    $form['cleaner']['cleaner-expired'] = [
      '#title' => $this->t('Cml expired'),
      '#type' => 'textfield',
      '#default_value' => $config->get('cleaner-expired'),
      '#description' => $this->t('StrToTime()'),
    ];
    $form['cleaner']['cleaner-keep'] = [
      '#title' => $this->t('Keep'),
      '#type' => 'textfield',
      '#default_value' => $config->get('cleaner-keep'),
      '#description' => $this->t('Skip X not empty cmls.'),
    ];

    $form['cleaner']['actions'] = [
      '#type' => 'actions',
      'cleaner-check' => $this->ajaxButton('Check expired CML', '::ajaxCleanerCheck'),
      'cleaner-run' => $this->ajaxButton('Run cleaner', '::ajaxCleanerRun'),
      '#suffix' => '<div id="cleaner-results"></div>',
    ];
    $form['parser'] = [
      '#type' => 'details',
      '#title' => $this->t('Parser'),
    ];
    $form['parser']['main'] = [
      '#type' => 'details',
      '#title' => $this->t('Standart'),
      '#open' => FALSE,
    ];
    $form['parser']['main']['tovar-standart'] = [
      '#title' => $this->t('Product Standart'),
      '#type' => 'textarea',
      '#default_value' => $config->get('tovar-standart'),
      '#rows' => 14,
    ];
    $form['parser']['main']['offers-standart'] = [
      '#title' => $this->t('Offers Standart'),
      '#type' => 'textarea',
      '#default_value' => $config->get('offers-standart'),
      '#rows' => 6,
    ];

    $form['parser']['tovar-dop'] = [
      '#title' => $this->t('Product Dop'),
      '#type' => 'textarea',
      '#default_value' => $config->get('tovar-dop'),
      '#rows' => 10,
    ];
    $form['parser']['offers-dop'] = [
      '#title' => $this->t('Offers Dop'),
      '#type' => 'textarea',
      '#default_value' => $config->get('offers-dop'),
      '#rows' => 5,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cmlapi.mapsettings');
    $config
      ->set('tovar-standart', $form_state->getValue('tovar-standart'))
      ->set('tovar-dop', $form_state->getValue('tovar-dop'))
      ->set('offers-standart', $form_state->getValue('offers-standart'))
      ->set('offers-dop', $form_state->getValue('offers-dop'))
      ->set('cleaner-cron', $form_state->getValue('cleaner-cron'))
      ->set('cleaner-force', $form_state->getValue('cleaner-force'))
      ->set('cleaner-expired', $form_state->getValue('cleaner-expired'))
      ->set('cleaner-keep', $form_state->getValue('cleaner-keep'))
      ->save();
  }

  /**
   * Button template.
   */
  public function ajaxButton($title, $callback) {
    return [
      '#type' => 'submit',
      '#value' => $title,
      '#ajax'   => [
        'callback' => $callback,
        'effect'   => 'fade',
        'progress' => ['type' => 'throbber', 'message' => ""],
      ],
    ];
  }

}
