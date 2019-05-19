<?php

/**
 * @file
 * Batch Operations.
 */

/**
 * Process to load the node body.
 */
function updateExternalLinksProcess_1($nid, $num_operations, &$context) {
  if (empty($context['sandbox'])) {
    $context['sandbox'] = array();
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['current_node'] = 0;
    // Save node count for the termination message.
    $context['sandbox']['max'] = $num_operations;
  }
  $node = node_load($nid);
  if (!empty($node->get('body')->value)) {
    $data = updateExternalLinksUrl($node->get('body')->value);
    if (!is_null($data)) {
      $node->get('body')->value = $data;
      $context['results'][] = $nid;
      $node->save();
      $context['sandbox']['current_node'] = $node->nid;
    }
    $context['sandbox']['progress']++;
  }
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = ($context['sandbox']['progress'] >= $context['sandbox']['max']);
  }
}

/**
 * Process to update links pointing to external sites.
 *
 * @return string
 *   Node body content with updated external links to be opened in new tab.
 */
function updateExternalLinksUrl($body_data) {
  global $base_url;
  $doc = new DOMDocument();
  $content_html = mb_convert_encoding($body_data, 'HTML-ENTITIES', "UTF-8");
  $doc->loadHTML($content_html);
  $parse_base_url = parse_url($base_url);
  $site_url = explode($parse_base_url['scheme'] . '://', $base_url);
  $parsed_base_url = parse_url($base_url);
  foreach ($doc->getElementsByTagName('a') as $link) {
    $url_path = '';
    $url = $link->getAttribute('href');
    $url_target = $link->getAttribute('target');
    if (!empty($url) && empty($url_target)) {
      $parsed_url = parse_url($url);
      if (!empty($parsed_url['path'])) {
        $url_path = explode('www.', $parsed_url['path']);
        if (!empty($url_path[1])) {
          $url_link = $url_path[1];
        }
      }
      if (((!empty($url_link)) && ($url_link != $site_url[1])) || ((!empty($parsed_url['host'])) && ($parsed_url['host'] != $parsed_base_url['host']))) {
        $link->setAttribute('target', '_blank');
        $updated_url[] = $link->getAttribute('href');
      }
    }
    unset($url_link);
    unset($url);
  }
  if (!empty($updated_url)) {
    $doc->encoding = 'UTF-8';
    $content_save = $doc->saveHTML();
    $content = preg_replace('~<(?:!DOCTYPE|/?(?:html|head|body))[^>]*>\s*~i', '', $content_save);
    return $content;
  }
  else {
    return NULL;
  }
}

/**
 * Batch process finish function for updating external urls.
 */
function updateExternalLinksFinished($success, $results, $operations) {
  if ($success) {
    if (empty($results)) {
      drupal_set_message(t('We could not find any external url on your site which is not opening in new tab.'));
    }
    else {
      drupal_set_message(t('Your site is ready with all external links to be open in new tab. Enjoy!!'));
    }
  }
  else {
    // An error occurred.
    // $operations contains the operations that remained unprocessed.
    $error_operation = reset($operations);
    $operation_array = array(
      '@operation' => $error_operation[0],
      '@args' => print_r($error_operation[0], TRUE),
    );
    drupal_set_message(t('An error occurred while processing @operation with arguments : @args', $operation_array));
  }
  return TRUE;
}
