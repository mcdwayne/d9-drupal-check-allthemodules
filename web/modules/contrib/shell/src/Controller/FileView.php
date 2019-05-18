<?php

namespace Drupal\shell\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\shell\ShellExec;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller class for shell file viewer.
 */
class FileView extends ControllerBase {

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
   * Display the file contents in a separate tab of the browser.
   */
  public function view() {
    $rtn = [];

    $rtn['#attached']['library'][] = 'shell/shell.file';

    $filename = $this->currentRequest->query->get("file");
    $cwd = $this->currentRequest->query->get("cwd");

    $fileperms = "(new file)";
    if (file_exists("$cwd/$filename")) {
      $fileperms = $this->shellExec->getFilePermissions("$cwd/$filename");
    }

    $rtn[] = [
      '#markup' => "<div>Viewing $cwd/$filename</div><div><b>Permissions:</b> $fileperms</div>",
    ];

    if (file_exists("$cwd/$filename")) {
      if (!$contents = file_get_contents("$cwd/$filename")) {
        $rtn[] = [
          '#markup' => (string) $this->t("Cannot view file! There is possibly a permission issue."),
        ];
      }
      else {
        $rtn[] = [
          '#markup' => "<pre class='shell-view-file'>$contents</pre>",
        ];
      }
    }

    return $rtn;
  }

}
