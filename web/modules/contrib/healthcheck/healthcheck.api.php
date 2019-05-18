<?php

/**
 * Alter the information of Healthcheck plugin tags.
 *
 * The $tags parameter is keyed by the tag's "machine name" -- the name used in
 * the 'tags` item in the @Healthcheck annotation. Here, you can add further
 * information:
 *
 * @code
 * $tags['performance']['label'] = 'Performance & Caching';
 *
 * $tags['performance']['description'] = t('Checks the performance and caching configuration of the site.');
 * @endcode
 *
 * @param $tags
 *   An array of Healthcheck plugin tags, keyed by machine name.
 */
function hook_healthcheck_tags_alter(&$tags) {
}

/**
 * Alter the findings labels, messages, and other data.
 *
 * @param $findings
 *   An array of Healthcheck findings data, keyed by finding key.
 */
function hook_healthcheck_findings_alter(&$findings) {
}
