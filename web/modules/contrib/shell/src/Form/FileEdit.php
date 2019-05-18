<?php

namespace Drupal\shell\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\shell\ShellExec;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * This very basic form lets the user edit a file on their system.
 */
class FileEdit extends FormBase {

  /**
   * The shell command execution service.
   *
   * @var \Drupal\shell\ShellExec
   */
  protected $shellExec;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Constructs a FileEdit object.
   *
   * @param \Drupal\shell\ShellExec $shell_exec
   *   The shell command execution service.
   * @param \Symfony\Component\HttpFoundation\Request $current_request
   *   The current request.
   */
  public function __construct(ShellExec $shell_exec, Request $current_request) {
    $this->shellExec = $shell_exec;
    $this->currentRequest = $current_request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('shell.exec'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shell_file_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // This form will let a user edit a file from the system.
    $filename = $this->currentRequest->query->get('file');
    $cwd = $this->currentRequest->query->get('cwd');
    $contents = '';

    $fileperms = '(new file)';
    if (file_exists("$cwd/$filename")) {
      $fileperms = $this->shellExec->getFilePermissions("$cwd/$filename");
    }

    $form['mark1'] = [
      '#markup' => "<div>Editing $cwd/$filename</div><div><b>Permissions:</b> $fileperms</div>",
    ];

    // @todo Check here to make sure we have the proper permissions.
    $form['filename'] = [
      '#type' => 'hidden',
      '#value' => $filename,
    ];

    $form['cwd'] = [
      '#type' => 'hidden',
      '#value' => $cwd,
    ];

    if (file_exists("$cwd/$filename")) {
      if (!$contents = file_get_contents("$cwd/$filename")) {
        $form['mark2'] = [
          '#markup' => $this->t('Could not load file! Either the web user does not have the correct permissions for this file, or it does not exist.'),
        ];
      }
    }
    else {
      // File does not already exist, so we will be creating it (or trying to)
      $form['mark2'] = [
        '#markup' => $this->t('(Creating a new file).'),
      ];
    }

    $form['file_contents'] = [
      '#type' => 'textarea',
      '#rows' => 20,
      '#title' => $this->t('File Contents'),
      '#default_value' => $contents,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save file',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cwd = $form_state->getValue('cwd');
    $filename = $form_state->getValue('filename');
    $file_contents = $form_state->getValue('file_contents');

    if (!file_put_contents("$cwd/$filename", $file_contents)) {
      drupal_set_message($this->t('File could not be saved. Perhaps the web user does not have the correct permissions to edit this file?'), 'error');
    }
    else {
      drupal_set_message($this->t('File has been updated.'));
    }
  }

}
