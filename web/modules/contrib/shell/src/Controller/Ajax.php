<?php

namespace Drupal\shell\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\shell\ShellExec;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller class for Ajax shell commands.
 */
class Ajax extends ControllerBase {

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
   * The CSRF token manager.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs an Ajax object.
   *
   * @param \Drupal\shell\ShellExec $shell_exec
   *   The shell command execution service.
   * @param \Symfony\Component\HttpFoundation\Request $current_request
   *   The current request.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The CSRF token manager.
   */
  public function __construct(ShellExec $shell_exec, Request $current_request, CsrfTokenGenerator $csrf_token, AccountProxyInterface $current_user) {
    $this->shellExec = $shell_exec;
    $this->currentRequest = $current_request;
    $this->csrfToken = $csrf_token;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('shell.exec'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('csrf_token'),
      $container->get('current_user')
    );
  }

  /**
   * Processes a command.
   *
   * Accessed by our javascript through ajax.  This will take a command from
   * the user and send it back to their screen.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON object suitable for returning to the calling javascript.
   */
  public function sendCommand() {
    // Let's confirm that the shell_token given to us is valid
    // (to prevent CSRF attacks).
    $request_token = trim($this->currentRequest->request->get('form_token'));
    $expected_token = 'form_token_placeholder_' . Crypt::hashBase64('shell_display_form');
    if (!$this->csrfToken->validate($request_token, $expected_token)) {
      $json = [
        'action' => 'text',
        'text' => (string) $this->t('Error - security token does not match expected value.'),
        'input_field' => '',
        'shell_cwd' => '',
      ];
      return new JsonResponse($json);
    }
    $command = trim($this->currentRequest->request->get('command'));

    // Because some servers cannot accept commands like 'cd ..' or 'wget' in
    // the POST, we have encoded the command in javascript.  We will now decode
    // it so we may use it.
    $command = base64_decode(urldecode($command));
    $this->shellExec->setCurrentDirectory(base64_decode(urldecode(($this->currentRequest->request->get('shell_cwd')))));

    $pressed_tab = trim($this->currentRequest->request->get('pressed_tab'));

    if ($pressed_tab == 'yes') {
      // Since the user pressed tab, we don't actually want to run
      // any command.  Rather, we want to modify what's in the input field.
      $result = $this->handleTabPress($command);
      $input_field = $result['input_field'];
      $text = $result['text'];
      $json = [
        'action' => 'text',
        'text' => $text,
        'input_field' => $input_field,
        'shell_cwd' => $this->shellExec->getCurrentDirectory(),
      ];
    }
    else {
      $response = $this->shellExec->handleCommand($command);
      $json = [
        'action' => $response['action'],
        'text' => $response['text'],
        'input_field' => '',
        'shell_cwd' => $this->shellExec->getCurrentDirectory(),
      ];
    }

    // Send it back to the browser.
    return new JsonResponse($json);
  }

  /**
   * Processes a TAB key press autocomplete.
   *
   * The user has pressed tab in the current directory, so we are going
   * to try to auto-complete the rest of the filename on the user's behalf.
   *
   * @param string $cmd
   *   The command to be autocomplelted.
   *
   * @return array
   *   A return array with relevant information.
   */
  protected function handleTabPress($cmd) {
    $rtn = ['input_field' => '', 'text' => '', 'shell_cwd' => ''];

    $text = '';
    // Take out excess spaces...
    $cmd = str_replace('  ', ' ', trim($cmd));
    $cmd = str_replace('  ', ' ', $cmd);

    $temp = explode(' ', $cmd);

    $the_last = trim($temp[count($temp) - 1]);
    $original_the_last = $the_last;

    $dir = $this->shellExec->getCurrentDirectory();

    // Here is where it gets interesting.  If the user specified a path
    // (we detect a '/' in $the_last, then this is what our $dir should
    // be, and $the_last should be the ending part of that.
    // For example, if I typed:
    // cd /www/public_html/mysi [TAB]
    // then $dir should = '/www/public_html'
    // and $the_last should = 'mysi'.
    if (strstr($the_last, "/")) {
      $temp = explode("/", $the_last);
      $the_last = $temp[count($temp) - 1];
      $new_dir = '';
      for ($c = 0; $c < count($temp) - 1; $c++) {
        $new_dir .= $temp[$c] . "/";
      }
      if (Unicode::substr($original_the_last, 0, 1) != "/") {
        // Does not begin with a slash, so the directory or file is relative to
        // our current one.
        $dir = "$dir/$new_dir";
      }
      else {
        // It DOES begin with a slash!  So, it is relative to the
        // base, so let's just use it what they typed.
        $dir = $new_dir;
      }
    }

    // Issue an 'ls' command to get a list of files in this directory.
    $files = $this->shellExec->getLsOfDirectory($dir);
    // Okay, now, based on what the user has partially typed, try to
    // find which filename matches the most.
    $part_length = Unicode::strlen($the_last);
    $matches = [];
    foreach ($files as $c => $filename) {
      if (Unicode::substr($filename, 0, $part_length) == $the_last) {
        $matches[] = [
          'full' => $filename,
          'last_part' => Unicode::substr($filename, $part_length, Unicode::strlen($filename)),
        ];
      }
    }

    if (count($matches) == 1) {
      // We found a match!
      $rtn['input_field'] = $cmd . $matches[0]['last_part'];
    }
    else {
      // Also fill into the inputfield the parts that match of
      // the multiple selections.
      if (Unicode::strlen($the_last) > 0) {
        $start_chars = $this->getStartingCharacters($matches);
        $rtn['input_field'] = $cmd . Unicode::substr($start_chars, Unicode::strlen($the_last));
      }

      // List the possible matches for the user.
      foreach ($matches as $c => $value) {
        $text .= " <span class='shell-filename'>" . Html::escape($value['full']) . "</span>";
      }
    }

    if ($text != '') {
      $rtn['text'] .= "<div class='shell-list-from-tab'>$text</div>";
      $rtn['text'] .= $this->t('<div class="shell-directory-listing">@un%@cwd&gt;</div>', ['@un' => $this->currentUser->getAccountName(), '@cwd' => $this->shellExec->getCurrentDirectory()]);
    }

    return $rtn;
  }

  /**
   * Returns the letters in common between all the matches.
   *
   * This is used when the user presses the tab key.
   *
   * @param array $matches
   *   An array of matches.
   *
   * @return string
   *   The letters in common between the matches.
   */
  protected function getStartingCharacters(array $matches) {
    $rtn = '';
    $longest_in_common = [];

    $test = str_split($matches[0]['full']);
    for ($t = 1; $t < count($matches); $t++) {
      $other_test = str_split($matches[$t]['full']);
      $longest = 0;
      for ($x = 0; $x < count($test); $x++) {
        if ($test[$x] == $other_test[$x]) {
          $longest = $x;
        }
        else {
          break;
        }
      }
      $longest_in_common[] = $longest;
    }

    // Now, we need the smallest longest in common value!
    sort($longest_in_common);
    $shortest = $longest_in_common[0];

    for ($t = 0; $t <= $shortest; $t++) {
      $rtn .= $test[$t];
    }

    return $rtn;
  }

}
