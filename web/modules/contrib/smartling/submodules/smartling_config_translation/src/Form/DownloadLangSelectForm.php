<?php

/**
 * @file
 * Contains \Drupal\smartling\Form\SendMultipleConfirmForm.
 */

namespace Drupal\smartling_config_translation\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Url;
use Drupal\smartling\Entity\SmartlingSubmission;
use Drupal\smartling\Form\SendMultipleConfirmForm;
use Drupal\smartling\SubmissionStorageInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a confirmation form for sending multiple content entities.
 */
class DownloadLangSelectForm extends SendMultipleConfirmForm {

  /**
   * @inheritdoc
   */
  protected $tempStorageName ='smartling_config_translation_operations_download';


  /**
   * Processes the sending batch.
   *
   * @param array $data
   *   Keyed array of data to send.
   * @param array $context
   *   The batch context.
   */
  public static function processBatch($data, &$context) {
    if (!isset($context['results']['errors'])) {
      $context['results']['errors'] = [];
      $context['results']['count'] = 0;
    }

    $entity_type_manager = \Drupal::entityTypeManager();

    $entity_type_id = $data['entity_type'];
    $entity_id = $data['entity_id'];
    $entity = $entity_type_manager
      ->getStorage($entity_type_id)
      ->load($entity_id);
    if (!$entity) {
      $context['results']['errors'][] = t('Entity @entity_type:@entity_id not found', [
        '@entity_type' => $entity_type_id,
        '@entity_id' => $entity_id,
      ]);
    }
    elseif ($entity_type_manager->hasHandler($entity_type_id, 'smartling')) {

      foreach ($data['locales'] as $language_code) {
        $submission = $entity_type_manager->getStorage('smartling_submission')
          ->loadByProperties([
            'entity_id' => $entity->id(),
            'entity_type' => $entity->getEntityTypeId(),
            'target_language' => $language_code,
          ]);
        $submission = reset($submission);
        /** @var \Drupal\smartling\SmartlingEntityHandler $handler */
        $handler = $entity_type_manager->getHandler($entity_type_id, 'smartling');
        if ($handler->downloadTranslation($submission)) {
          $context['results']['count']++;
        }
        else {
          $context['results']['errors'][] = new FormattableMarkup('Error uploading %name', [
            '%name' => $entity->label(),
          ]);
        }
        $context['message'] = new FormattableMarkup('Processed %name.', [
          '%name' => $entity->label(),
        ]);

      }
    }
    else {
      $context['message'] = new FormattableMarkup('Skipped %name.', [
        '%name' => $entity->label(),
      ]);
    }
  }

  /**
   * Finish batch.
   */
  public static function finishBatch($success, $results, $operations) {
    if ($success) {
      if (!empty($results['errors'])) {
        foreach ($results['errors'] as $error) {
          drupal_set_message($error, 'error');
        }
        drupal_set_message(\Drupal::translation()
          ->translate('Entities were sent with errors.'), 'warning');
      }
      drupal_set_message(\Drupal::translation()
        ->formatPlural($results['count'], 'One entity has been sent successfully.', '@count entities have been sent successfully.'));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $message = \Drupal::translation()
        ->translate('An error occurred while sending.');
      drupal_set_message($message, 'error');
    }
  }
//  /**
//   * {@inheritdoc}
//   */
//  public function submitForm(array &$form, FormStateInterface $form_state) {
//    $current_user_id = $this->currentUser()->id();
//
//    // Clear out the accounts from the temp store.
//    $this->tempStoreFactory->get('smartling_config_operations_download')
//      ->delete($current_user_id);
//    if ($form_state->getValue('confirm')) {
//      // @todo Update to use for all entity types.
//      /** @var \Drupal\node\NodeInterface[] $nodes */
//      $entities = $this->entityIds;
////      $this->entityTypeManager->getStorage('smartling_config_translation')
////        ->loadMultiple($this->entityIds);
////      $locales = $form_state->getValue('locales');
//
//
////      foreach($entities as $entity) {
//      foreach($entities as $config_name => $bundle) {
////        $config_name = $entity->get("name")->value;
////        $bundle = $entity->get("bundle")->value;
//
//       // foreach($locales as $locale) {
//          $srv = \Drupal::getContainer()->get('smartling_config_translation.config_translation');
//          $file = $srv->downloadConfig($bundle . '.' . $config_name . '.en.xml', 'nl-NL');
//          $encoder = \Drupal::getContainer()->get('serializer.encoder.smartling_config_xml');// 'serializer.encoder.smartling_xml');
//          $data = $encoder->decode($file, 'smartling_xml');
//
//
//          $srv->saveConfig($config_name, 'nl', $data);
//        //}
//
//        drupal_set_message('Translation has been successfully downloaded.');
//      }
//    }
//  }
}
