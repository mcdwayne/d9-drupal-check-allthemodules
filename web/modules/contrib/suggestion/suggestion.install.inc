<?php

/**
 * @file
 * Contains suggestion.install.inc.
 */

/**
 * Build the current field spec.
 *
 * @return array
 *   The current database spec.
 */
function suggestion_install_update_8201_spec() {
  return [
    'fields' => [
      'ngram' => [
        'description' => "The suggestion string.",
        'type'        => 'varchar',
        'length'      => 65,
        'binary'      => TRUE,
        'not null'    => TRUE,
      ],
      'src' => [
        'description' => "Suggestion source bitmap: 0 = disabled, 1 = content, 2 = surfer, 4 = priority",
        'type'        => 'int',
        'unsigned'    => TRUE,
        'not null'    => TRUE,
        'default'     => 1,
      ],
      'atoms' => [
        'description' => "The number of words in this suggestion.",
        'type'        => 'int',
        'unsigned'    => TRUE,
        'not null'    => TRUE,
        'default'     => 1,
      ],
      'qty' => [
        'description' => "The number of instances.",
        'type'        => 'int',
        'unsigned'    => TRUE,
        'not null'    => TRUE,
        'default'     => 1,
      ],
      'density' => [
        'description' => "The weighted average.",
        'type'        => 'float',
        'unsigned'    => TRUE,
        'precision'   => 3,
        'not null'    => TRUE,
        'default'     => 1.0,
      ],
      'langcode' => [
        'description' => "The language of suggestion string.",
        'type'        => 'varchar',
        'length'      => 32,
        'binary'      => TRUE,
        'not null'    => TRUE,
        'default'     => '',
      ],
    ],
    'indexes' => [
      'atoms'               => ['atoms'],
      'density'             => ['density'],
      'langcode'            => ['langcode'],
      'qty'                 => ['qty'],
      'src'                 => ['src'],
      'src_lang'            => ['src', 'langcode'],
      'density_ngram_atom'  => ['density', 'ngram', 'atoms'],
      'atom_src_lang_ngram' => ['atoms', 'src', 'langcode', 'ngram'],
    ],
    'primary key' => ['langcode', 'ngram'],
  ];
}
