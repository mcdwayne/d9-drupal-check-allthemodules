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
use Drupal\smartling\SubmissionStorageInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\smartling\Form\SendMultipleConfirmForm;

/**
 * Provides a confirmation form for sending multiple content entities.
 */
class UploadLangSelectForm extends SendMultipleConfirmForm {
  /**
   * @inheritdoc
   */
  protected $tempStorageName ='smartling_config_translation_operations_send';


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
//      $entities = $this->entityTypeManager->getStorage('smartling_config_translation')
//        ->loadMultiple($this->entityIds);
//      $locales = $form_state->getValue('locales');
//
//
//      foreach($entities as $entity) {
//        $config_name = $entity->get("name")->value;
//        $bundle = $entity->get("bundle")->value;
//
//
//        $srv = \Drupal::getContainer()->get('smartling_config_translation.config_translation');
//        $str = $srv->getConfigSourceData([$config_name]);
//        if (empty($str)) {
//          $entity = entity_load($bundle, $config_name);
//          $str = [$config_name => $srv->getSourceData($entity)];
//        }
//        //$strings[] = $str;
//        $encoder = \Drupal::getContainer()->get('serializer.encoder.smartling_config_xml');// 'serializer.encoder.smartling_xml');
//        $xml = $encoder->encode($str, 'smartling_xml');
//
//        //$config = \Drupal::config($config_name);
//        $srv->uploadConfig($xml, $config_name . '.xml', $locales);
//
//        drupal_set_message('Config has been successfully uploaded for translation');
//      }
//    }
//  }
}
