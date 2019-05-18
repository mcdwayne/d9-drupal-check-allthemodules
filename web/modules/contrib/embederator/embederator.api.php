<?php

/**
 * @file
 * Hooks provided by the Embederator module.
 */

/**
 * Alter server-side url embed.
 */
function hook_embederator_url_alter(&$url, $embederator_type, $entity) {
  if (strpos($url, 'tfaform.net')) {
    $url . '?return=' . urlencode('http://example.com');
  }
}

/**
 * Alter markup prior to render.
 */
function hook_embederator_embed_alter(&$html, $embederator_type, $entity) {
  $html = trim($html);
}
