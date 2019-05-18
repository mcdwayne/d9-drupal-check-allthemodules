<?php

namespace Drupal\shell;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Manage execution of shell commands.
 */
class ShellExec {

  use StringTranslationTrait;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The current working directory.
   *
   * @var string
   */
  protected $currentDirectory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The app root.
   *
   * @var string
   */
  protected $appRoot;

  /**
   * Constructs a ShellExec object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The CSRF token manager.
   * @param string $app_root
   *   The app root.
   */
  public function __construct(RequestStack $request_stack, AccountProxyInterface $current_user, $app_root) {
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->currentUser = $current_user;
    $this->appRoot = $app_root;
  }

  /**
   * Handles the 'special' command sent by the user.
   *
   * Since there are some commands the shell cannot handle correctly,
   * we will capture them and send the user special output.  This function
   * does this.
   *
   * @param string $cmd
   *   The 'special' command to be processed.
   *
   * @return array
   *   An array of data to be processed by the calling javascript.
   */
  protected function handleSpecialCommand($cmd) {
    // Take out excess spaces.
    $cmd = str_replace('  ', ' ', trim($cmd));
    $cmd = str_replace('  ', ' ', $cmd);

    $temp = explode(' ', $cmd);

    $the_rest = '';

    for ($t = 0; $t < count($temp); $t++) {
      // Skip the first one.
      if ($t == 0) {
        continue;
      }
      $the_rest .= $temp[$t] . ' ';
    }
    $the_rest = trim($the_rest);

    $rtn = [
      'action' => 'text',
      'text' => '',
    ];
    switch (Unicode::strtolower($temp[0])) {
      case 'welcome':
        $rtn['text'] .= $this->t('<div class="user-command">Welcome to Shell.<br /></div>');
        $rtn['text'] .= $this->t('<strong>System:</strong> @phpuname<br />', ['@phpuname' => php_uname()]);
        $rtn['text'] .= $this->t('<strong>PHP:</strong> @phpversion <strong>Web server:</strong> @webserver<br /><br />', ['@phpversion' => phpversion(), '@webserver' => $this->currentRequest->server->get('SERVER_SOFTWARE')]);
        $rtn['text'] .= $this->t('Some commands are interpreted by this emulated shell differently than you might expect. To see a list of commands (and for other general help) type <b><u>shelp</u></b>.<br /><br />');
        break;

      case 'shelp':
        $rtn['text'] .= $this->t('=============================<br />');
        $rtn['text'] .= $this->t('Shell Help<br />');
        $rtn['text'] .= $this->t('=============================<br />');
        $rtn['text'] .= $this->t('<b>man <i>command</i></b> ... Will provide a link to off-site man page.<br />');
        $rtn['text'] .= $this->t('<b>edit, vi, vim, emacs</b> ... Will provide a link to a file editor.<br />');
        $rtn['text'] .= $this->t('<b>clear</b> ... Will wipeout the screen output.<br />');
        $rtn['text'] .= $this->t('<b>reset</b> ... Will restore screen at initial stage, with welcome message and starting directory.<br /><br />');
        $rtn['text'] .= $this->t('Special information:<br />');
        $rtn['text'] .= $this->t('Be aware that Shell is just an emulator.  All commands are actually being sent through PHP to the server.  As such, interactivity is very limited.  This is why you cannot use vi or emacs.  Any other commands or programs which require interactivity also will not work. You are also limited to what commands your server will allow the web user to run.');
        break;

      case 'man':
        $rtn['text'] .= $this->t('<div class="shell-not-supported">This shell emulator cannot correctly view man pages.</div>');
        $rtn['text'] .= $this->t('Please click the following link to load your intended man page in a new window:<br /><a href="http://www.geona.net/search?s=manpages.info&q=@the_rest" target="_blank">http://www.geona.net/search?s=manpages.info&q=@the_rest</a>', ['@the_rest' => $the_rest]);
        break;

      case 'edit':
      case 'vi':
      case 'vim':
      case 'emacs':
        $rtn['text'] .= $this->t('<div class="shell-not-supported">This shell emulator cannot correctly edit files, so you must use a special file editor.</div>');
        $rtn['text'] .= $this->t('Please click the following link to load the file editor in a new window:<br />');
        $rtn['text'] .= Link::createFromRoute($this->t('Click to edit @the_rest', ['@the_rest' => $the_rest]), 'shell.file.edit', [], [
          'query' => [
            'file' => $the_rest,
            'cwd' => $this->getCurrentDirectory(),
          ],
          'attributes' => [
            'target' => 'blank',
          ],
        ])->toString();
        break;

      case 'less':
      case 'more':
        $rtn['text'] .= $this->t('<div class="shell-not-supported">This shell emulator cannot provide the interactivity of less/more, so you must use a special file viewer.</div>');
        $rtn['text'] .= $this->t('Please click the following link to load the file viewer in a new window:<br />');
        $rtn['text'] .= Link::createFromRoute($this->t('Click to view @the_rest', ['@the_rest' => $the_rest]), 'shell.file.view', [], [
          'query' => [
            'file' => $the_rest,
            'cwd' => $this->getCurrentDirectory(),
          ],
          'attributes' => [
            'target' => 'blank',
          ],
        ])->toString();
        break;

      case 'clear':
        $rtn['action'] = 'clear';
        break;

      case 'reset':
        $rtn['action'] = 'clear';
        $rtn['text'] = $this->handleSpecialCommand('welcome')['text'];
        $this->setCurrentDirectory($this->appRoot);
        break;
    }

    return $rtn;
  }

  /**
   * Handles the command sent by the user.
   *
   * Actually handles the command sent by the user by passing it to the server
   * and capturing the output. This code is drawn partially from bzrundi,
   * and the phpterm project found here: http://phpterm.sourceforge.net/
   *
   * @param string $cmd
   *   The command to be processed.
   *
   * @return array
   *   An array of data to be processed by the calling javascript.
   */
  public function handleCommand($cmd) {
    // Make sure current directory is set.
    $this->getCurrentDirectory();

    $cmd = trim($cmd);
    // Since we cannot handle certain commands, we should make sure
    // the user isn't trying to use those commands here.
    // Special commands (which we can't handle):
    $special_cmd = [
      'welcome',
      'shelp',
      'man',
      'edit',
      'vi',
      'vim',
      'emacs',
      'less',
      'more',
      'clear',
      'reset',
    ];
    $temp = explode(' ', Unicode::strtolower($cmd));
    if (in_array($temp[0], $special_cmd)) {
      $rtn = $this->handleSpecialCommand($cmd);
    }
    else {
      $rtn = [
        'action' => 'text',
        'text' => '',
      ];

      // Process the command normally.
      $aliases = [
        'la' => 'ls -la',
        'll' => 'ls -lvhF',
        'dir' => 'ls',
      ];

      if (preg_match('/^[[:blank:]]*cd[[:blank:]]*$/', @$cmd)) {
        // A 'cd' command with no parameters, set Shell current directory to
        // Drupal app root.
        $this->setCurrentDirectory($this->appRoot);
      }
      elseif (preg_match('/^[[:blank:]]*cd[[:blank:]]+([^;]+)$/', @$cmd, $regs)) {
        // The current command is 'cd', which we have to handle as an internal
        // shell command.
        ($regs[1][0] == '/') ? $new_dir = $regs[1] : $new_dir = $this->getCurrentDirectory() . '/' . $regs[1];

        // Cosmetics.
        while (strpos($new_dir, '/./') !== FALSE) {
          $new_dir = str_replace('/./', '/', $new_dir);
        }
        while (strpos($new_dir, '//') !== FALSE) {
          $new_dir = str_replace('//', '/', $new_dir);
        }
        while (preg_match('|/\.\.(?!\.)|', $new_dir)) {
          $new_dir = preg_replace('|/?[^/]+/\.\.(?!\.)|', '', $new_dir);
        }

        if (is_dir($new_dir)) {
          $this->setCurrentDirectory($new_dir);
        }
        else {
          $rtn['text'] .= "Could not change to: $new_dir\n";
        }
      }
      else {
        // The command is not a 'cd' command, so we execute it after
        // changing the directory and save the output.
        $cwd = getcwd();
        chdir($this->getCurrentDirectory());

        // Alias expansion.
        $length = strcspn(@$cmd, " \t");
        $token = Unicode::substr(@$cmd, 0, $length);
        if (isset($aliases[$token])) {
          $cmd = $aliases[$token] . Unicode::substr($cmd, $length);
        }

        $p = proc_open(@$cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $io);

        // Read output sent to stdout.
        while (!feof($io[1])) {
          $rtn['text'] .= htmlspecialchars(fgets($io[1]), ENT_COMPAT, 'UTF-8');
        }
        // Read output sent to stderr.
        while (!feof($io[2])) {
          $rtn['text'] .= htmlspecialchars(fgets($io[2]), ENT_COMPAT, 'UTF-8');
        }

        fclose($io[1]);
        fclose($io[2]);
        proc_close($p);

        // Restore the current working directory.
        chdir($cwd);
      }
      $rtn['text'] = "<pre class='shell'>{$rtn['text']}</pre>";
    }

    $rtn['text'] .= $this->t('<div class="shell-directory-listing">@un%@cwd&gt;</div>', [
      '@un' => $this->currentUser->getAccountName(),
      '@cwd' => $this->getCurrentDirectory(),
    ]);
    return $rtn;
  }

  /**
   * Gets a list of files in a directory.
   *
   * Like it says on the tin, this will perform an 'ls' command on the
   * supplied directory, so we can get a list of files.  This is
   * being used by the ::handleTabPress method.
   *
   * @param string $dir
   *   A directory to scan.
   *
   * @return array
   *   An array of files in the directory.
   */
  public function getLsOfDirectory($dir) {
    $rtn = [];
    $str = '';
    $p = proc_open(@"ls $dir", [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $io);

    // Read output sent to stdout.
    while (!feof($io[1])) {
      $str .= htmlspecialchars(fgets($io[1]), ENT_COMPAT, 'UTF-8');
    }
    // Read output sent to stderr.
    while (!feof($io[2])) {
      $str .= htmlspecialchars(fgets($io[2]), ENT_COMPAT, 'UTF-8');
    }

    fclose($io[1]);
    fclose($io[2]);
    proc_close($p);

    $temp = explode("\n", $str);
    foreach ($temp as $t) {
      $t = trim($t);
      if ($t != '') {
        // If this file is a directory, add a / to the end.
        $is_directory = (is_dir("$dir/$t")) ? "/" : '';
        $rtn[] = $t . $is_directory;
      }
    }

    return $rtn;
  }

  /**
   * Returns a UNIX-like string summarizing file permissions.
   *
   * Handy function from php.net which will give you the file permissions
   * for a particular file.  It will look like it does in linux.
   * Ex: -rw-r--r--
   *
   * @param string $file
   *   A file path.
   *
   * @return string
   *   A UNIX-like string summarizing file permissions.
   */
  public function getFilePermissions($file) {
    $perms = fileperms($file);

    if (($perms & 0xC000) == 0xC000) {
      // Socket.
      $info = 's';
    }
    elseif (($perms & 0xA000) == 0xA000) {
      // Symbolic Link.
      $info = 'l';
    }
    elseif (($perms & 0x8000) == 0x8000) {
      // Regular.
      $info = '-';
    }
    elseif (($perms & 0x6000) == 0x6000) {
      // Block special.
      $info = 'b';
    }
    elseif (($perms & 0x4000) == 0x4000) {
      // Directory.
      $info = 'd';
    }
    elseif (($perms & 0x2000) == 0x2000) {
      // Character special.
      $info = 'c';
    }
    elseif (($perms & 0x1000) == 0x1000) {
      // FIFO pipe.
      $info = 'p';
    }
    else {
      // Unknown.
      $info = 'u';
    }

    // Owner.
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));

    // Group.
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));

    // World.
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));

    return $info;
  }

  /**
   * Get the current directory Shell is positioned at.
   *
   * On first call, it is initialized to Drupal root directory.
   *
   * @return string
   *   The current directory Shell is positioned at.
   */
  public function getCurrentDirectory() {
    if ($this->currentDirectory === NULL) {
      $this->setCurrentDirectory($this->appRoot);
    }
    return $this->currentDirectory;
  }

  /**
   * Set the current directory for Shell.
   *
   * @param string $path
   *   A local path.
   *
   * @return $this
   */
  public function setCurrentDirectory($path) {
    $this->currentDirectory = $path;
    return $this;
  }

}
