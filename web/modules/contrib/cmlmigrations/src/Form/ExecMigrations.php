<?php

namespace Drupal\cmlmigrations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\cmlmigrations\Service\ExecServiceInterface;

/**
 * Implements ExecMigrations.
 */
class ExecMigrations extends FormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cmlmigrations.exec')
    );
  }

  /**
   * Form constructor.
   *
   * Drupal\cmlmigrations\Service\ExecServiceInterface $exec
   *   Exec service.
   */
  public function __construct(ExecServiceInterface $exec) {
    $this->execService = $exec;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cmlmigrations_exec';
  }

  /**
   * AJAX Responce.
   */
  public function ajax($otvet) {
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand("#exec-results", "<pre>{$otvet}</pre>"));
    return $response;
  }

  /**
   * AJAX Import.
   */
  public function ajaxImport(array $form, FormStateInterface $form_state) {
    $otvet = "ajaxImport\n";
    $otvet .= $this->execService->exec(FALSE, FALSE);
    return $this->ajax($otvet);
  }

  /**
   * AJAX Import.
   */
  public function ajaxImportNohup(array $form, FormStateInterface $form_state) {
    $otvet = "ajaxImportNohup\n";
    $otvet .= $this->execService->exec(TRUE, FALSE);
    return $this->ajax($otvet);
  }

  /**
   * AJAX Update.
   */
  public function ajaxUpdate(array $form, FormStateInterface $form_state) {
    $otvet = "ajaxUpdate\n";
    $otvet .= $this->execService->exec(FALSE, TRUE);
    return $this->ajax($otvet);
  }

  /**
   * AJAX Update.
   */
  public function ajaxUpdateNohup(array $form, FormStateInterface $form_state) {
    $otvet = "ajaxUpdateNohup\n";
    $otvet .= $this->execService->exec(TRUE, TRUE);
    return $this->ajax($otvet);
  }

  /**
   * AJAX Test Exec.
   */
  public function ajaxTestExec(array $form, FormStateInterface $form_state) {
    $otvet = "ajaxTestExec\n\n";
    $otvet .= $this->execService->execTest();
    return $this->ajax($otvet);
  }

  /**
   * AJAX Test Drush.
   */
  public function ajaxTestDrush(array $form, FormStateInterface $form_state) {
    $otvet = "Test Drush:\n\n";
    $otvet .= $this->execService->drushTest();
    return $this->ajax($otvet);
  }

  /**
   * AJAX Test Nohup.
   */
  public function ajaxTestNohup(array $form, FormStateInterface $form_state) {
    $otvet = "Test Nohup:\n\n";
    $otvet .= $this->execService->nohupTest();
    return $this->ajax($otvet);
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

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $extra = NULL) {
    $form_state->setCached(FALSE);

    $form['info'] = [
      '#markup' => '<h3>Запустить:</h3>',
    ];

    // Exec.
    $form['run'] = [
      '#type' => 'details',
      '#title' => $this->t('Run migrations'),
      '#open' => TRUE,
      'actions' => [
        '#type' => 'actions',
        'import' => $this->ajaxButton('Import', '::ajaxImport'),
        'import-nohup' => $this->ajaxButton('Import Nohup', '::ajaxImportNohup'),
        'update' => $this->ajaxButton('Update', '::ajaxUpdate'),
        'update-nohup' => $this->ajaxButton('Update Nohup', '::ajaxUpdateNohup'),
      ],
    ];
    // Test.
    $form['test'] = [
      '#type' => 'details',
      '#title' => $this->t('Test Environment'),
      '#open' => FALSE,
      'actions' => [
        '#type' => 'actions',
        'test-exec' => $this->ajaxButton('Test Exec', '::ajaxTestExec'),
        'test-drush' => $this->ajaxButton('Test Drush', '::ajaxTestDrush'),
        'test-nohup' => $this->ajaxButton('Test Nohup', '::ajaxTestNohup'),
      ],
    ];
    $form['#suffix'] = '<div id="exec-results"></div>';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
