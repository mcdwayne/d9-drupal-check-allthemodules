<?php

namespace Drupal\asciidoc\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to use asciidoc syntax.
 *
 * @Filter(
 *   id = "asccidoc_simple",
 *   title = @Translation("Simple AsciiDoc filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   description = @Translation("Allows users to use AsciiDoc syntax."),
 *   settings = {
 *     "command" = "asciidoc"
 *   },
 * )
 */
class Asciidoc extends FilterBase {

  /**
   * Return asciidoc formatted text.
   *
   * @param string $text
   *   Source text.
   *
   * @return string
   *   Filtered text.
   */
  private function getAsciidoc($text) {
    if (empty($text)) {
      return '';
    }

    $command = $this->settings['command'];

    // We use basically asciidoc defaults: --doctype article --backend xhtml11.
    $command = sprintf('echo %s | %s --no-header-footer -o - -', escapeshellarg($text), $command);
    exec($command, $lines, $ret);

    if ($ret == 0) {
      $output = implode("\n", $lines);
    }
    else {
      $output = $text;
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult($this->getAsciidoc($text));
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('You can use <a href="@user_guide">AsciiDoc syntax</a> to format and style the text. For a quick reference see the <a href="@cheatsheet">cheatsheet</a>.', ['@user_guide' => 'http://www.methods.co.nz/asciidoc/userguide.html', '@cheatsheet' => 'http://powerman.name/doc/asciidoc']);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $commands = $this->listCommands();

    $description = $this->t('You need to install <a href="@url_asciidoc">AsciiDoc</a> or <a href="@url_asciidoctor">Asciidoctor</a> in order to use the AsciiDoc filter.', [
      '@url_asciidoc' => 'http://www.methods.co.nz/asciidoc/index.html',
      '@url_asciidoctor' => 'http://asciidoctor.org',
    ]);

    if (count($commands) > 0) {
      $form['command'] = [
        '#type' => 'select',
        '#title' => $this->t('Command'),
        '#options' => $commands,
        '#default_value' => $this->settings['command'],
        '#description' => $description,
      ];
    }
    else {
      $form['command'] = [
        '#markup' => $description,
      ];
    }

    return $form;
  }

  /**
   * Returns all available commands.
   *
   * @return array
   *   All commands for AsciiDoc.
   */
  private function listCommands() {
    $commands = [
      'asciidoc' => 'AsciiDoc',
      'asciidoctor' => 'Asciidoctor',
    ];

    $available_commands = [];
    foreach ($commands as $command => $command_label) {
      exec(sprintf('%s --version', $command), $output, $ret);
      if ($ret == 0) {
        $available_commands[$command] = $command_label;
      }
    }

    return $available_commands;
  }

}
