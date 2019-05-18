<?php

namespace Drupal\inline_formatter_field\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Creates the settings form for the inline formatter field settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Array of ace editor modes.
   *
   * @var array
   */
  protected $aceModes = [
    'abap' => 'ABAP',
    'abc' => 'ABC',
    'actionscript' => 'ActionScript',
    'ada' => 'ADA',
    'apache_conf' => 'Apache Conf',
    'asciidoc' => 'AsciiDoc',
    'asl' => 'ASL',
    'assembly_x86' => 'Assembly x86',
    'autohotkey' => 'AutoHotkey / AutoIt',
    'apex' => 'Apex',
    'batchfile' => 'BatchFile',
    'bro' => 'Bro',
    'c_cpp' => 'C and C++',
    'c9search' => 'C9Search',
    'cirru' => 'Cirru',
    'clojure' => 'Clojure',
    'cobol' => 'Cobol',
    'coffee' => 'CoffeeScript',
    'coldfusion' => 'ColdFusion',
    'csharp' => 'C#',
    'csound_document' => 'Csound Document',
    'csound_orchestra' => 'Csound',
    'csound_score' => 'Csound Score',
    'css' => 'CSS',
    'curly' => 'Curly',
    'd' => 'D',
    'dart' => 'Dart',
    'diff' => 'Diff',
    'dockerfile' => 'Dockerfile',
    'dot' => 'Dot',
    'drools' => 'Drools',
    'edifact' => 'Edifact',
    'eiffel' => 'Eiffel',
    'ejs' => 'EJS',
    'elixir' => 'Elixir',
    'elm' => 'Elm',
    'erlang' => 'Erlang',
    'forth' => 'Forth',
    'fortran' => 'Fortran',
    'fsharp' => 'FSharp',
    'fsl' => 'FSL',
    'ftl' => 'FreeMarker',
    'gcode' => 'Gcode',
    'gherkin' => 'Gherkin',
    'gitignore' => 'Gitignore',
    'glsl' => 'Glsl',
    'gobstones' => 'Gobstones',
    'golang' => 'Go',
    'graphqlschema' => 'GraphQLSchema',
    'groovy' => 'Groovy',
    'haml' => 'HAML',
    'handlebars' => 'Handlebars',
    'haskell' => 'Haskell',
    'haskell_cabal' => 'Haskell Cabal',
    'haxe' => 'haXe',
    'hjson' => 'Hjson',
    'html' => 'HTML',
    'html_elixir' => 'HTML (Elixir)',
    'html_ruby' => 'HTML (Ruby)',
    'ini' => 'INI',
    'io' => 'Io',
    'jack' => 'Jack',
    'jade' => 'Jade',
    'java' => 'Java',
    'javascript' => 'JavaScript',
    'json' => 'JSON',
    'jsoniq' => 'JSONiq',
    'jsp' => 'JSP',
    'jssm' => 'JSSM',
    'jsx' => 'JSX',
    'julia' => 'Julia',
    'kotlin' => 'Kotlin',
    'latex' => 'LaTeX',
    'less' => 'LESS',
    'liquid' => 'Liquid',
    'lisp' => 'Lisp',
    'livescript' => 'LiveScript',
    'logiql' => 'LogiQL',
    'lsl' => 'LSL',
    'lua' => 'Lua',
    'luapage' => 'LuaPage',
    'lucene' => 'Lucene',
    'makefile' => 'Makefile',
    'markdown' => 'Markdown',
    'mask' => 'Mask',
    'matlab' => 'MATLAB',
    'maze' => 'Maze',
    'mel' => 'MEL',
    'mixal' => 'MIXAL',
    'mushcode' => 'MUSHCode',
    'mysql' => 'MySQL',
    'nix' => 'Nix',
    'nsis' => 'NSIS',
    'objectivec' => 'Objective-C',
    'ocaml' => 'OCaml',
    'pascal' => 'Pascal',
    'perl' => 'Perl',
    'perl6' => 'Perl 6',
    'pgsql' => 'pgSQL',
    'php_laravel_blade' => 'PHP (Blade Template)',
    'php' => 'PHP',
    'puppet' => 'Puppet',
    'pig' => 'Pig',
    'powershell' => 'Powershell',
    'praat' => 'Praat',
    'prolog' => 'Prolog',
    'properties' => 'Properties',
    'protobuf' => 'Protobuf',
    'python' => 'Python',
    'r' => 'R',
    'razor' => 'Razor',
    'rdoc' => 'RDoc',
    'red' => 'Red',
    'rhtml' => 'RHTML',
    'rst' => 'RST',
    'ruby' => 'Ruby',
    'rust' => 'Rust',
    'sass' => 'SASS',
    'scad' => 'SCAD',
    'scala' => 'Scala',
    'scheme' => 'Scheme',
    'scss' => 'SCSS',
    'sh' => 'SH',
    'sjs' => 'SJS',
    'slim' => 'Slim',
    'smarty' => 'Smarty',
    'snippets' => 'snippets',
    'soy_template' => 'Soy Template',
    'space' => 'Space',
    'sql' => 'SQL',
    'sqlserver' => 'SQLServer',
    'stylus' => 'Stylus',
    'svg' => 'SVG',
    'swift' => 'Swift',
    'tcl' => 'Tcl',
    'terraform' => 'Terraform',
    'tex' => 'Tex',
    'text' => 'Text',
    'textile' => 'Textile',
    'toml' => 'Toml',
    'tsx' => 'TSX',
    'twig' => 'Twig',
    'typescript' => 'Typescript',
    'vala' => 'Vala',
    'vbscript' => 'VBScript',
    'velocity' => 'Velocity',
    'verilog' => 'Verilog',
    'vhdl' => 'VHDL',
    'visualforce' => 'Visualforce',
    'wollok' => 'Wollok',
    'xml' => 'XML',
    'xquery' => 'XQuery',
    'yaml' => 'YAML',
    'django' => 'Django',
  ];

  /**
   * Array of ace editor themes.
   *
   * @var array
   */
  protected $aceThemes = [
    'ambiance' => 'Ambiance',
    'chaos' => 'Chaos',
    'chrome' => 'Chrome',
    'clouds_midnight' => 'Clouds Midnight',
    'clouds' => 'Clouds',
    'cobalt' => 'Cobalt',
    'crimson_editor' => 'Crimson Editor',
    'dawn' => 'Dawn',
    'dracula' => 'Dracula',
    'dreamweaver' => 'Dreamweaver',
    'eclipse' => 'Eclipse',
    'github' => 'GitHub',
    'gob' => 'Gob',
    'gruvbox' => 'Gruvbox',
    'idle_fingers' => 'Idle Fingers',
    'iplastic' => 'IPlastic',
    'katzenmilch' => 'Katzenmilch',
    'kr_theme' => 'krTheme',
    'kuroir' => 'Kuroir',
    'merbivore_soft' => 'Merbivore Soft',
    'merbivore' => 'Merbivore',
    'mono_industrial' => 'Mono Industrial',
    'monokai' => 'Monokai',
    'pastel_on_dark' => 'Pastel on Dark',
    'solarized_dark' => 'Solarized Dark',
    'solarized_light' => 'Solarized Light',
    'sqlserver' => 'SQL Server',
    'terminal' => 'Terminal',
    'textmate' => 'TextMate',
    'tomorrow_night_blue' => 'Tomorrow Night Blue',
    'tomorrow_night_bright' => 'Tomorrow Night Bright',
    'tomorrow_night_eighties' => 'Tomorrow Night Eighties',
    'tomorrow_night' => 'Tomorrow Night',
    'tomorrow' => 'Tomorrow',
    'twilight' => 'Twilight',
    'vibrant_ink' => 'Vibrant Ink',
    'xcode' => 'XCode',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'inline_formatter_field.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'inline_formatter_field_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('inline_formatter_field.settings');
    $ace_default = $config->get('ace_source') ? $config->get('ace_source') : 'cdn';
    $fa_default = $config->get('fa_source') ? $config->get('fa_source') : 'cdn';
    $theme_default = $config->get('ace_theme') ? $config->get('ace_theme') : 'monokai';
    $mode_default = $config->get('ace_mode') ? $config->get('ace_mode') : 'twig';
    $wrap_default = $config->get('ace_wrap') ? $config->get('ace_wrap') : FALSE;
    $print_margin_default = $config->get('ace_print_margin') ? $config->get('ace_print_margin') : FALSE;

    $form['ace_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Ace Editor source'),
      '#description' => $this->t('Whether to get the souce code from a CDN or in the /libraries/ directory'),
      '#options' => [
        'cdn' => 'CDN',
        'lib' => 'Library',
      ],
      '#default_value' => $ace_default,
    ];
    $form['fa_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Font Awesome source'),
      '#description' => $this->t('Whether to get the souce code from a CDN or in the /libraries/ directory'),
      '#options' => [
        'cdn' => 'CDN',
        'lib' => 'Library',
      ],
      '#default_value' => $fa_default,
    ];
    $form['ace_theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Ace Editor Theme'),
      '#description' => $this->t('Select the theme for ace editor to use.'),
      '#options' => $this->aceThemes,
      '#default_value' => $theme_default,
    ];
    $form['ace_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Ace Editor Mode'),
      '#description' => $this->t('Select the mode for ace editor to use.'),
      '#options' => $this->aceModes,
      '#default_value' => $mode_default,
    ];
    $form['ace_wrap'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ace Editor Wrap Mode'),
      '#description' => $this->t('Allow ace editor to wrap.'),
      '#default_value' => $wrap_default,
    ];
    $form['ace_print_margin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ace Editor Print Margin'),
      '#description' => $this->t('Allow ace editor to print margin.'),
      '#default_value' => $print_margin_default,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('inline_formatter_field.settings')
      ->set('ace_source', $form_state->getValue('ace_source'))
      ->set('fa_source', $form_state->getValue('fa_source'))
      ->set('ace_theme', $form_state->getValue('ace_theme'))
      ->set('ace_mode', $form_state->getValue('ace_mode'))
      ->set('ace_wrap', $form_state->getValue('ace_wrap'))
      ->set('ace_print_margin', $form_state->getValue('ace_print_margin'))
      ->save();
  }

}
