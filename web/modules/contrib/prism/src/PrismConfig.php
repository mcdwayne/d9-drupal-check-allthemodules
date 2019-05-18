<?php

/**
 * @file
 * Contains Drupal\prism\PrismConfig.
 */

namespace Drupal\prism;

/**
 * Class PrismConfigForm.
 *
 * @package Drupal\prism\Form
 */
class PrismConfig {

  /**
   * Returns languages used by prism.js to highlight code.
   */
  public static function getLanguages() {
    $languages = array(
      'markup' => 'Markup',
      'css' => 'CSS',
      'clike' => 'C-like',
      'javascript' => 'JavaScript',
      'abap' => 'ABAP',
      'actionscript' => 'ActionScript',
      'apacheconf' => 'Apache Configuration',
      'apl' => 'APL',
      'applescript' => 'AppleScript',
      'aspnet' => 'ASP.NET (C#)',
      'autoit' => 'AutoIt',
      'autohotkey' => 'AutoHotkey',
      'bash' => 'Bash',
      'basic' => 'BASIC',
      'batch' => 'Batch',
      'bison' => 'Bison',
      'brainfuck' => 'Brainfuck',
      'c' => 'C',
      'csharp' => 'C#',
      'cpp' => 'C++',
      'coffeescript' => 'CoffeeScript',
      'crystal' => 'Crystal',
      'css-extras' => 'CSS Extras',
      'd' => 'D',
      'dart' => 'Dart',
      'diff' => 'Diff',
      'docker' => 'Docker',
      'eiffel' => 'Eiffel',
      'elixir' => 'Elixir',
      'erlang' => 'Erlang',
      'fsharp' => 'F#',
      'fortran' => 'Fortran',
      'gherkin' => 'Gherkin',
      'git' => 'Git',
      'glsl' => 'GLSL',
      'go' => 'Go',
      'groovy' => 'Groovy',
      'haml' => 'Haml',
      'handlebars' => 'Handlebars',
      'haskell' => 'Haskell',
      'http' => 'HTTP',
      'inform7' => 'Inform 7 ',
      'ini' => 'Ini',
      'j' => 'J',
      'jade' => 'Jade',
      'java' => 'Java',
      'julia' => 'Julia',
      'keyman' => 'Keyman',
      'latex' => 'LaTeX',
      'less' => 'Less',
      'lolcode' => 'LOLCODE',
      'makefile' => 'Makefile',
      'markdown' => 'Markdown',
      'matlab' => 'MATLAB',
      'mel' => 'MEL',
      'mizar' => 'Mizar',
      'monkey' => 'Monkey',
      'nasm' => 'NASM',
      'nginx' => 'nginx',
      'nim' => 'Nim',
      'nix' => 'Nix',
      'nsis' => 'NSIS',
      'objectivec' => 'Objective-C',
      'ocaml' => 'OCaml',
      'pascal' => 'Pascal',
      'perl' => 'Perl',
      'php' => 'PHP',
      'php-extras' => 'PHP Extras',
      'powershell' => 'PowerShell',
      'processing' => 'Processing',
      'prolog' => 'Prolog',
      'pure' => 'Pure',
      'python' => 'Python',
      'q' => 'Q',
      'qore' => 'Qore',
      'r' => 'R',
      'jsx' => 'React JSX',
      'rest' => 'reST (reStructuredText)',
      'rip' => 'Rip',
      'ruby' => 'Ruby',
      'rust' => 'Rust',
      'sas' => 'SAS',
      'sass' => 'Sass (Sass)',
      'scss' => 'Sass (Scss)',
      'scala' => 'Scala',
      'scheme' => 'Scheme',
      'smalltalk' => 'Smalltalk',
      'smarty' => 'Smarty',
      'sql' => 'SQL',
      'stylus' => 'Stylus',
      'swift' => 'Swift',
      'tcl' => 'Tcl',
      'textile' => 'Textile',
      'twig' => 'Twig',
      'typescript' => 'TypeScript',
      'verilog' => 'Verilog',
      'vhdl' => 'VHDL',
      'vim' => 'vim',
      'wiki' => 'Wiki markup',
      'yaml' => 'YAML',
    );
    return $languages;
  }
}
