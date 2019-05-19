<?php

/**
 * @file
 * Builds placeholder replacement tokens for webforms and submissions.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function webform_digests_token_info() {

  $types = [];
  $tokens = [];

  /****************************************************************************/
  // Webform Digest.
  /****************************************************************************/

  $types['webform_digest'] = [
    'name' => t('Webform Digests'),
    'description' => t('Tokens related to webform digests.'),
    'needs-data' => 'webform_digest',
  ];

  $webform_digest = [];
  $webform_digest['id'] = [
    'name' => t('Webform Digest ID'),
    'description' => t('The ID of the digest.'),
  ];
  $webform_digest['label'] = [
    'name' => t('Digest Label'),
    'description' => t('The label of the digest.'),
  ];
  $webform_digest['subject'] = [
    'name' => t('Digest Subject'),
    'description' => t('The subject of the digest.'),
  ];
  $webform_digest['submissions'] = [
    'name' => t('Digest Submissions Summary'),
    'description' => t('The summary of submissions for the digest.'),
  ];
  $webform_digest['submissions_count'] = [
    'name' => t('Digest Submissions Count'),
    'description' => t('The count of submissions for the digest.'),
  ];

  $tokens['webform_digest'] = $webform_digest;

  return ['types' => $types, 'tokens' => $tokens];
}

/**
 * Implements hook_tokens().
 */
function webform_digests_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {

  $replacements = [];

  /** @var \Drupal\webform_digests\Entity\WebformDigestInterface $webform_digest */
  if ($type == 'webform_digest' && !empty($data['webform_digest'])) {
    $webform_digest = $data['webform_digest'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'id':
          $replacements[$original] = $webform_digest->id();
          break;

        case 'label':
          $replacements[$original] = $webform_digest->label();
          break;

        case 'subject':
          $replacements[$original] = $webform_digest->getSubject();
          break;

        case 'submissions':
          $submission_summary = [
            '#theme' => 'item_list',
            '#items' => $webform_digest->getSubmissionsSummary(),
          ];
          $replacements[$original] = \Drupal::service('renderer')->renderPlain($submission_summary);
          break;

        case 'submissions_count':
          $replacements[$original] = $webform_digest->getSubmissionsCount();
          break;
      }
    }
  }

  return $replacements;

}
