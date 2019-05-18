<?php

namespace Drupal\drd\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use mikehaertl\shellcommand\Command as ShellCommand;

/**
 * Defines the Script entity.
 *
 * @ConfigEntityType(
 *   id = "drd_script",
 *   label = @Translation("Script"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\drd\Entity\ListBuilder\Script",
 *
 *     "form" = {
 *       "default" = "Drupal\drd\Entity\Form\Script",
 *       "add" = "Drupal\drd\Entity\Form\Script",
 *       "edit" = "Drupal\drd\Entity\Form\Script",
 *       "delete" = "Drupal\drd\Entity\Form\ScriptDelete"
 *     },
 *   },
 *   config_prefix = "script_code",
 *   admin_permission = "drd.administer script entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/drd/settings/scripts/script/{drd_script}",
 *     "add-form" = "/drd/settings/scripts/add",
 *     "edit-form" = "/drd/settings/scripts/script/{drd_script}/edit",
 *     "delete-form" = "/drd/settings/scripts/script/{drd_script}/delete",
 *     "collection" = "/drd/settings/scripts"
 *   }
 * )
 */
class Script extends ConfigEntityBase implements ScriptInterface {

  private static $selectList;

  /**
   * Get a list of scripts.
   *
   * @param bool $code
   *   If TRUE this return a list of scripts and their code, otherwise a list
   *   for a form API select element will be returned.
   *
   * @return array
   *   List of scripts.
   */
  public static function getSelectList($code = TRUE) {
    $mode = 'script_' . ($code ? 'code' : 'type');
    if (!isset(self::$selectList)) {
      self::$selectList = [
        'script_code' => [
          '' => '-',
        ],
        'script_type' => [],
      ];
      $config = \Drupal::config('drd.' . $mode);
      foreach ($config->getStorage()->listAll('drd.' . $mode) as $key) {
        $script = $config->getStorage()->read($key);
        self::$selectList[$mode][$script['id']] = $script['label'];
      }
    }
    return self::$selectList[$mode];
  }

  /**
   * Script ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Script label.
   *
   * @var string
   */
  protected $label;

  /**
   * Script type id.
   *
   * @var string
   */
  protected $type;

  /**
   * Script code.
   *
   * @var string
   */
  protected $code;

  /**
   * Script type entity.
   *
   * @var ScriptType
   */
  private $scriptType;

  /**
   * Prepared code.
   *
   * @var string
   */
  private $preparedCode;

  /**
   * Output of script execution.
   *
   * @var string
   */
  protected $output = '';

  /**
   * {@inheritdoc}
   */
  public function type() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function code() {
    return $this->code;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array $arguments, $workingDir) {
    $this->scriptType = ScriptType::load($this->type);
    $this->prepareCode();

    $filename = \Drupal::service('file_system')->tempnam('temporary://', 'drdscript') . '.' . $this->scriptType->extension();
    $filecontent = implode(PHP_EOL, [
      '#!' . $this->scriptType->interpreter(),
      $this->scriptType->prefix(),
      $this->preparedCode,
      $this->scriptType->suffix(),
    ]);
    file_put_contents($filename, $filecontent);
    chmod($filename, 0755);
    $command = new ShellCommand($filename);
    // TODO: Get the working dir from calling plugin.
    $command->procCwd = $workingDir;
    $success = $command->execute();
    $this->output = $command->getOutput();
    if (!$success) {
      throw new \Exception($command->getError());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOutput() {
    return $this->output;
  }

  /**
   * Prepare script code for execution.
   */
  private function prepareCode() {
    $this->preparedCode = $this->code;

    // TODO: tokenize the script.
    // Append a line prefix if required.
    $linePrefix = $this->scriptType->linePrefix();
    if (!empty($linePrefix)) {
      $lines = [];
      foreach (explode(PHP_EOL, $this->preparedCode) as $item) {
        $lines[] = implode(' ', [$linePrefix, $item]);
      }
      $this->preparedCode = implode(PHP_EOL, $lines);
    }
  }

}
