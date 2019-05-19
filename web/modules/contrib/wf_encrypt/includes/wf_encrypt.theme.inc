<?php

/**
* @file
* Preprocessors and helper functions for theming.
*/

/**
 * Prepares variables for webform submission HTML template.
 *
 * Default template: webform-submission-html.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform_submission: A webform submission.
 */
function wf_encrypt_preprocess_webform_submission_html(array &$variables) {
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];

  /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');

  $webform = $webform_submission->getWebform();
  $data = $webform_submission->getData();
  $elements = $webform->getElementsInitialized();
  $data_original = $view_builder->buildElements($elements, $data);
  $config = \Drupal::service('config.factory')->get('wf.encrypt')->get('element.settings');

  foreach ($data_original as $key => &$element) {
    if (isset($config[$key]['encrypt']) && $config[$key]['encrypt']) {
      $user = \Drupal::currentUser();
      if ($user->hasPermission('view encrypted values')) {
        $encryption_profile = \Drupal\encrypt\Entity\EncryptionProfile::load($config[$key]['encrypt_profile']);
        $element['#value']['#markup'] = Drupal::service('encryption')->decrypt($element['#value']['#markup'], $encryption_profile);
      }
      else {
        $element['#value']['#markup'] = t('[Value Encrypted]');
      }
    }
  }

  $variables['data'] = $data_original;
}

/**
 * Prepares variables for webform submission plain text template.
 *
 * Default template: webform-submission-text.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform_submission: A webform submission.
 */
function wf_encrypt_preprocess_webform_submission_text(array &$variables) {
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];

  $variables['sid'] = $webform_submission->id();
  $variables['uuid'] = $webform_submission->uuid();
  $variables['is_draft'] = $webform_submission->isDraft() ? t('Yes') : t('No');
  $variables['current_page'] = $webform_submission->getCurrentPage();
  $variables['remote_addr'] = $webform_submission->getRemoteAddr();
  $variables['submitted_by'] = $webform_submission->getOwner()->label();
  $variables['webform'] = $webform_submission->getWebform()->label();
  $variables['created'] = \Drupal\webform\Utility\WebformDateHelper::format($webform_submission->getCreatedTime());
  $variables['completed'] = \Drupal\webform\Utility\WebformDateHelper::format($webform_submission->getCompletedTime());
  $variables['changed'] = \Drupal\webform\Utility\WebformDateHelper::format($webform_submission->getChangedTime());

  // @see \Drupal\Core\Field\Plugin\Field\FieldFormatter\LanguageFormatter::viewValue()
  $languages = \Drupal::languageManager()->getNativeLanguages();
  $langcode = $webform_submission->get('langcode')->value;
  $variables['language'] = isset($languages[$langcode]) ? $languages[$langcode]->getName() : $langcode;

  if ($source_url = $webform_submission->getSourceUrl()) {
    $variables['uri'] = $source_url->toString();
  }

  if ($source_entity = $webform_submission->getSourceEntity()) {
    $variables['submitted_to'] = $source_entity->label();
  }

  /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');

  $webform = $webform_submission->getWebform();
  $data = $webform_submission->getData();
  $elements = $webform->getElementsInitialized();

  $data_original = $view_builder->buildElements($elements, $data, [], 'text');
  $config = \Drupal::service('config.factory')->get('wf.encrypt')->get('element.settings');

  foreach ($data_original as $key => &$element) {
    if (isset($config[$key]['encrypt']) && $config[$key]['encrypt']) {
      $user = \Drupal::currentUser();
      if ($user->hasPermission('view encrypted values')) {
        $encryption_profile = \Drupal\encrypt\Entity\EncryptionProfile::load($config[$key]['encrypt_profile']);
        $element['#value']['#markup'] = Drupal::service('encryption')->decrypt($element['#value']['#markup'], $encryption_profile);
      }
      else {
        $element['#value']['#markup'] = t('[Value Encrypted]');
      }
    }
  }

  $variables['data'] = $data_original;
}

/**
 * Prepares variables for webform submission YAML template.
 *
 * Default template: webform-submission-yaml.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform_submission: A webform submission.
 */
function wf_encrypt_preprocess_webform_submission_yaml(array &$variables) {
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];

  $data = $webform_submission->toArray(TRUE);
  $webform = \Drupal\webform\Entity\Webform::load($data['webform_id']);
  $elements = $webform->getElementsInitializedAndFlattened();
  $config = \Drupal::service('config.factory')->get('wf.encrypt')->get('element.settings');

  foreach ($elements as $key => $element) {
    if (isset($config[$key]['encrypt']) && $config[$key]['encrypt']) {
      $user = \Drupal::currentUser();
      if ($user->hasPermission('view encrypted values')) {
        $encryption_profile = \Drupal\encrypt\Entity\EncryptionProfile::load($config[$key]['encrypt_profile']);
        $data['data'][$key] = Drupal::service('encryption')->decrypt($data['data'][$key], $encryption_profile);
      }
      else {
        $data['data'][$key] = t('[Value Encrypted]');
      }
    }
  }

  $yaml = \Drupal\Component\Serialization\Yaml::encode($data);
  $yaml = \Drupal\webform\Utility\WebformTidy::tidy($yaml);
  $variables['yaml'] = [
    '#markup' => $yaml,
    '#allowed_tags' => \Drupal\Component\Utility\Xss::getAdminTagList(),
  ];;
}

/**
 * Prepares variables for webform submission table template.
 *
 * Default template: webform-submission-table.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform_submission: A webform submission.
 */
function wf_encrypt_preprocess_webform_submission_table(array &$variables) {
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];

  /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');

  $webform = $webform_submission->getWebform();
  $data = $webform_submission->getData();
  $elements = $webform->getElementsFlattenedAndHasValue();
  $config = \Drupal::service('config.factory')->get('wf.encrypt')->get('element.settings');

  foreach ($elements as $key => $element) {
    if (isset($config[$key]['encrypt']) && $config[$key]['encrypt']) {
      $user = \Drupal::currentUser();
      if ($user->hasPermission('view encrypted values')) {
        $encryption_profile = \Drupal\encrypt\Entity\EncryptionProfile::load($config[$key]['encrypt_profile']);
        $data[$key] = Drupal::service('encryption')->decrypt($data[$key], $encryption_profile);
      }
      else {
        $data[$key] = t('[Value Encrypted]');
      }
    }
  }

  $variables['table'] = $view_builder->buildTable($elements, $data);
}
