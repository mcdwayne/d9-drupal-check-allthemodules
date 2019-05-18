<?php
/**
 * @file DocumentBase.inc
 * Given a report, render the appropriate output given the document format.
 * @author davidmetzler
 *
 */

namespace Drupal\forena\FrxPlugin\Document;
use Drupal\forena\AppService;
use Drupal\forena\FrxAPI;
use Drupal\forena\FrxPlugin\AjaxCommand\AjaxCommandInterface;
use Drupal\forena\Skin;

class DocumentBase implements DocumentInterface {
  use FrxAPI;
  public $format = '';
  public $buffer = TRUE;
  protected $filename = '';
  public $parameters_form = [];
  // Reort Write buffer.
  protected $write_buffer;
  // Contains CSS and Java script libraries
  public $libraries;
  // Page or report title
  public $title;
  // Ajax Commands
  protected $commands;
  // Skin definition
  protected $skin;
  // Skin assocatiated with the report
  protected $skin_name;

  public $file_name;
  public $content_type='';
  public $charset = 'UTF-8';
  public $headers;
  public function clear() {
    $this->write_buffer = '';
    $this->pre_commands = [];
    $this->parameters_form = [];
    $this->commands = [];
  }

  /**
   * Default implementation to put in content type based headers.
   */
  public function header() {
    $this->write_buffer = '';
    if ($this->content_type) {
      $this->headers = [];
      $this->headers['Content-Type'] = $this->content_type . ' ;charset='
        . $this->charset;
      $this->headers['Cache-Control'] = '';
      $this->headers['Pragma'] = '';
      //$file_name = basename($_GET['q']);
      if ($this->file_name) {
        $this->headers['Content-Disposition:'] = 'attachment; filename="'
          . $this->file_name . '"';
      }

    }
  }

  /**
   * @param string $skin_name
   *   name/path of the skin to load.
   */
  public function setSkin($skin_name) {
    $this->skin_name = $skin_name;
    $this->skin = Skin::instance($skin_name);
  }

  public function setFilename($filename) {
    $this->file_name = $filename;
  }

  // All document objects must implement this method.
  public function flush() {
    return $this->write_buffer;
  }

  public function write($buffer) {
    if ($this->buffer) {
      $this->write_buffer .= $buffer;
    }
  }

  /**
   * Wrapper function for check output to default the right type.
   * @param string $output
   */
  public function check_markup($output) {
    return check_markup($output, \Drupal::config('forena.settings')->get('input_format'));
  }

  /**
   * Perform character set conversion
   * @param $data
   * @param string $default_encoding
   * @return string
   */
  public function convertCharset($data, $default_encoding='UTF-8') {

    if (isset($_SERVER['HTTP_ACCEPT_CHARSET'])) {
      $parts = @explode(';', $_SERVER['HTTP_ACCEPT_CHARSET']);
      $parts = @explode(',', $parts[0]);
      $to_encoding=@$parts[0];
    }
    else {
      $to_encoding = $default_encoding;
    }
    if ($to_encoding!='UTF-8')  {
      $this->charset = $to_encoding;
      $data = mb_convert_encoding($data, $to_encoding, 'UTF-8');
    }
    return $data;
  }

  /**
   * @param array $ajax
   *   The Array of coommand objects
   * @param string $event
   *   'pre' implies pre replacement firing of events. 
   */
  public function addAjaxCommand($ajax, $event) {
    $command = $ajax['command'];

    $plugins = AppService::instance()->getAjaxPlugins();
    if (isset($plugins[$command])) {
      $class = $plugins[$command];
      /** @var AjaxCommandInterface $command */
      $command = new $class();
      $ajax_command = $command->commandFromSettings($ajax);
      if ($ajax_command)  $this->commands[$event][] = $ajax_command;
    }
  }

  /**
   * @return array
   *   Array of ajax commands that are built.
   */
  public function getAjaxCommands() {
    return $this->commands;
  }
  

  /**
   * No default footer.
   */
  public function footer() {

  }

}
