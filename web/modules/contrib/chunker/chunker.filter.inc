<?php

/**
 * @file
 * Filter hooks for chunker.
 *
 * Using chunker as a filter seems to be sub-optimal, so demoting this usage
 * into its own optional file for now. Include if ever needed.
 */

/**
 * Implements hook_filter_info().
 */
function chunker_filter_info() {
  $filters['chunker'] = [
    'title'             => t('Split content into divisions at headings (Chunker)'),
    'process callback'  => 'chunker_filter',
    'settings callback' => 'chunker_filter_settings',
    'default settings'  => [
      'start_level'      => 2,
      'permalinks'       => TRUE,
      'permalink_string' => '#',
    ],
    'tips callback'     => 'chunker_filter_tips',
    'weight'            => -10,
  ];
  return $filters;
}

/**
 * Advertises our function as a text filter.
 *
 * Note that text filters alone do not seem to have the ability to add
 * js libraries, so the effects may be limited to markup only.
 *
 * Implements callback_filter_settings().
 *
 * @inheritdoc
 */
function chunker_filter_settings(array $form, array &$form_state, $filter, $format, $defaults) {
  $filter->settings += $defaults;

  $settings['start_level'] = [
    '#type'          => 'textfield',
    '#title'         => t('Header level to start from'),
    '#default_value' => $filter->settings['start_level'],
    '#maxlength'     => 1,
    '#description'   => t('Header level to perform the chunking on. Numeric, so 2 for H2.'),
  ];

  $settings['permalinks'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Show permalinks'),
    '#default_value' => $filter->settings['permalinks'],
    '#length'        => 10,
    '#description'   => t('Add small anchor target links [#] to each section for people to link directly to.'),
  ];

  return $settings;
}

/**
 * Provides help for the HTML filter.
 *
 * Implements callback_filter_tips().
 *
 * @see filter_filter_info()
 *
 * @inheritdoc
 */
function chunker_filter_tips($filter, $format, $long = FALSE) {
  $start_level = $filter->settings['start_level'];
  $output = t('Break markup into chunks at every header @start_level.', ['@start_level' => $start_level]);
  return $output;
}

/**
 * Provide an HTML text filter that performs the chunker markup modification.
 *
 * Implements callback_filter_process().
 *
 * @inheritdoc
 */
function chunker_filter($text, $filter) {
  $settings = $filter->settings;
  return chunker_chunk_text($text, $settings);
}
