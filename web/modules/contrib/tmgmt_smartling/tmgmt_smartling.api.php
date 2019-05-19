<?php

/**
 * @file
 * Hooks provided by the TMGMT Smartling module.
 */

use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\JobItemInterface;

/**
 * Alter entity context url.
 *
 * @param string $url
 * @param \Drupal\tmgmt\JobItemInterface $job_item
 */
function hook_tmgmt_smartling_context_url_alter(&$url, JobItemInterface $job_item) {
  // Set NULL to avoid creating context for a content from $job_item.
  $url = NULL;
}

/**
 * Alter translation file name.
 *
 * @param string $name
 * @param JobInterface $job
 */
function hook_tmgmt_smartling_filename_alter(&$name, JobInterface $job) {
  // Example: filename = job's label. If you have only one job item in a job
  // then file name will look like "[node_title]_job_id_[id]". If there are more
  // than 1 job item inside a job then filename will look like
  // "[node_title]_and_[n]_more_job_id_[id]". If you enter your own job label
  // then filename will look like "[your_own_label]_job_id_[id]"
  $name = preg_replace('/[^a-zA-Z0-9_\-\:]/i', '_', $job->label()) . '_job_id_' . $job->id();
}

/**
 * Alter daily bucket job name.
 *
 * @param string $name
 */
function hook_tmgmt_smartling_bucket_job_name(&$name) {
  $name = 'My daily bucket job';
}

/**
 * Alter directives for the file being uploaded.
 *
 * @param array $directives
 */
function hook_tmgmt_smartling_directives_alter(array &$directives) {
  $directives['smartling.force_inline_for_tags'] = 'icon';
}
