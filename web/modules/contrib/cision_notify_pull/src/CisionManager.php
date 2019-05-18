<?php

namespace Drupal\cision_notify_pull;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class CisionManager.
 *
 * @package Drupal\cision_notify_pull
 */
class CisionManager implements CisionManagerInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Forum settings config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs the cision manager service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, EntityFieldManagerInterface $entity_field_manager,ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->entityFieldManager = $entity_field_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function processCisionFeeds($elements) {

    foreach ($elements as $element) {
      $release = $this->getDetailpage($element->value->__toString());
      $item = $this->processReleaseToArray($release);
      // Validate Cision ID field is set at administration.
      if (!empty($item['Id'])) {
        $this->saveItem($item);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteCisionFeeds($elements) {
    foreach ($elements as $element) {
      $url = 'http://publish.ne.cision.com/Release/GetDetail/' . $element->value->__toString();
      $release = $this->getDetailpage($url);
      $item = $this->processReleaseToArray($release);

      // Validate Cision ID field is set at administration.
      if (!empty($item['Id'])) {
        $this->deleteItem($item);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getDetailpage($url) {
    $release = simplexml_load_file($url);
    return $release;
  }

  /**
   * Convert simplexmlElement into array.
   *
   * @param mixed $release
   *   Release {@inheritdoc}.
   *
   * @return mixed
   *   Mixed {@inheritdoc}.
   */
  protected function processReleaseToArray($release) {

    $item = [];
    $item['Id'] = $release->attributes()->Id->__toString();
    $item['InformationType'] = $release->attributes()->InformationType->__toString();
    $item['LanguageVersions'] = $release->LanguageVersions->attributes()->CommonId->__toString();
    $item['PublishDateUtc'] = strtotime($release->attributes()->PublishDateUtc->__toString());
    $item['LastChangeDateUtc'] = strtotime($release->attributes()->LastChangeDateUtc->__toString());
    $item['LanguageCode'] = $release->attributes()->LanguageCode->__toString();
    $item['CountryCode'] = $release->attributes()->CountryCode->__toString();
    $item['Title'] = $release->Title->__toString();
    $item['HtmlTitle'] = $release->HtmlTitle->__toString();
    $item['Intro'] = $release->Intro->__toString();
    $item['HtmlIntro'] = $release->HtmlIntro->__toString();
    $item['Header'] = $release->Header->__toString();
    $item['HtmlHeader'] = $release->HtmlHeader->__toString();
    $item['Body'] = $release->Body->__toString();
    $item['HtmlBody'] = $release->HtmlBody->__toString();

    $item['Complete'] = $release->Complete->__toString();
    $item['SeOrganizationNumber'] = $release->SeOrganizationNumber->__toString();
    $item['CustomerReference'] = $release->CustomerReference->__toString();

    $item['Quotes'] = [];
    foreach ($release->Quotes->Quote as $quote) {
      $item['Quotes'][] = [
        'author' => $quote->attributes()->Author->__toString(),
        'quote' => $quote->__toString(),
      ];
    }

    $item['QuickFacts'] = [];
    foreach ($release->QuickFacts->QuickFact as $quickFact) {
      $item['QuickFacts'][] = $quickFact->__toString();
    }

    $item['ExternalLinks'] = [];
    foreach ($release->ExternalLinks->ExternalLink as $externalLink) {
      $item['ExternalLinks'][] = [
        'title' => $externalLink->attributes()->Title->__toString(),
        'uri' => $externalLink->attributes()->Url->__toString(),
      ];
    }
    $item['SocialMediaPitch'] = $release->SocialMediaPitch->__toString();

    $item['Keywords'] = [];
    foreach ($release->Keywords->Keyword as $keyword) {
      $item['Keywords'][] = $keyword->Value->__toString();
    }

    $item['Images'] = [];
    foreach ($release->Images->Image as $image) {
      $item['Images'][] = [
        'FileName' => $image->attributes()->FileName->__toString(),
        'Title' => $image->Title->__toString(),
        'Description' => $image->Description->__toString(),
        'HighQualityUrl' => $image->HighQualityUrl->__toString(),
      ];
    }
    $item['Files'] = [];
    foreach ($release->Files->File as $file) {
      $item['Files'][] = [
        'FileName' => $file->FileName->__toString(),
        'Title' => $file->Title->__toString(),
        'Description' => $file->Description->__toString(),
        'Url' => $file->Url->__toString(),
      ];
    }
    $item['CompanyInformation'] = $release->CompanyInformation->__toString();
    $item['HtmlCompanyInformation'] = $release->HtmlCompanyInformation->__toString();
    $item['Contact'] = $release->Contact->__toString();
    $item['HtmlContact'] = $release->HtmlContact->__toString();
    $item['IsRegulatory'] = $release->IsRegulatory->__toString();

    return $item;
  }

  /**
   * Create/Update item.
   *
   * @param mixed $item
   *   Item {@inheritdoc}.
   */
  protected function saveItem($item) {

    $config = $this->configFactory->get('cision_notify_pull.settings');
    $selected_type = $config->get('allowed_type');
    $target_mapping = $config->get('target_mapping');
    $field_definations = $this->entityFieldManager->getFieldDefinitions('node', $selected_type);
    $unique_field = array_search('Id', $target_mapping);

    $storageNode = $this->entityTypeManager->getStorage('node');
    $exists_node = $storageNode->loadByProperties([$unique_field => $item['Id']]);
    $exists_node = reset($exists_node);

    // If the node is ever created.
    if (empty($exists_node)) {
      $node = Node::create([
        'type' => $selected_type,
        'status' => 1,
      ]);
    }
    else {
      $langcode = $item['LanguageCode'];
      if (!$exists_node->hasTranslation($langcode)) {
        $node = $exists_node->addTranslation($langcode);
      }
      else {
        $node = $exists_node->getTranslation($langcode);
      }
    }
    $node->uid = 1;

    foreach ($target_mapping as $target_id => $source_id) {

      $field_type = $field_definations[$target_id]->getType();
      switch ($field_type) {
        case 'entity_reference':
          $reference = $this->processEntityReference($field_definations[$target_id], $item);
          $node->set($target_id, $reference);
          break;

        case 'file':
          $files = $this->processFiles($field_definations[$target_id], $item);
          $node->set($target_id, $files);
          break;

        case 'image':
          $files = $this->processImages($field_definations[$target_id], $item);
          $node->set($target_id, $files);
          break;

        default:
            $node->set($target_id, $item[$source_id]);
          break;
      }
    }
    // Allow modules to alter the cision feed node before save.
    $this->moduleHandler->alter('cision_feed_node',$node);
    $node->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function processEntityReference($field, $item) {

    $settings = $field->getItemDefinition()->getSettings();

    $target_bundles = $settings['handler_settings']['target_bundles'];
    reset($target_bundles);
    $target_bundle = key($target_bundles);

    switch ($settings['target_type']) {
      case 'taxonomy_term':
        $terms = [];
        foreach ($item['Keywords'] as $keyword) {
          $term = $this->entityTypeManager
            ->getStorage($settings['target_type'])
            ->loadByProperties(['name' => $keyword]);

          if (!$term) {
            $term = $this->entityTypeManager
              ->getStorage($settings['target_type'])->create([
                'parent' => [],
                'name' => $keyword,
                'vid' => $target_bundle,
              ])->save();
            $term = $this->entityTypeManager
              ->getStorage('taxonomy_term')
              ->loadByProperties(['name' => $keyword]);
          }
          $terms[]['target_id'] = reset(array_keys($term));
        }
        break;
    }
    return $terms;
  }

  /**
   * {@inheritdoc}
   */
  protected function processFiles($field, $item) {

    $files = [];
    foreach ($item['Files'] as $item) {
      $data = file_get_contents($item['Url']);
      $file = file_save_data($data, 'public://' . $item['FileName']);
      $files[] = [
        'target_id' => $file->id(),
        'title' => $item['Title'],
      ];
    }
    return $files;
  }

  /**
   * {@inheritdoc}
   */
  protected function processImages($field, $item) {

    $images = [];
    foreach ($item['Images'] as $image) {
      $data = file_get_contents($image['HighQualityUrl']);
      $image_file = file_save_data($data, 'public://' . $image['FileName']);
      $images[] = [
        'target_id' => $image_file->id(),
        'alt' => $image['Description'],
        'title' => $image['Title'],
      ];
    }
    return $images;
  }

  /**
   * Delete item.
   *
   * @param mixed $item
   *   Item {@inheritdoc}.
   */
  protected function deleteItem($item) {
    $other_langs = [];
    $config = $this->configFactory->get('cision_notify_pull.settings');
    $selected_type = $config->get('allowed_type');
    $target_mapping = $config->get('target_mapping');
    $unique_field = array_search('Id', $target_mapping);

    $storageNode = $this->entityTypeManager->getStorage('node');

    $exists = $storageNode->loadByProperties(['type' => $selected_type, $unique_field => $item['Id']]);
    $exists = reset($exists);

    if ($exists) {
      $node = $exists->toArray();
      if ($node['langcode'][0]['value'] != $item['LanguageCode']) {
        if ($exists->isDefaultTranslation() && $exists->hasTranslation($item['LanguageCode'])) {
          $exists->removeTranslation($item['LanguageCode']);
          $exists->save();
        }
      }
      else {
        if ($exists->isDefaultTranslation()) {
          // If this is default language we can't remove translation.
          $languages = $exists->getTranslationLanguages();
          foreach ($languages as $langcode => $lang) {
            if ($node['langcode'][0]['value'] != $langcode) {
              $other_lang_node = $exists->getTranslation($langcode);
              $other_langs[$langcode] = $other_lang_node->{$unique_field}->value;
            }
          }
          // Delete the existing node.
          if ($exists->hasTranslation($item['LanguageCode'])) {
            $exists->delete();
            if (count($other_langs)) {
              // Recreate other translation.
              foreach ($other_langs as $other_lang) {
                $url = 'http://publish.ne.cision.com/Release/GetReleaseDetail?releaseId=' . $other_lang;
                $release = $this->getDetailpage($url);
                $item = $this->processReleaseToArray($release);
                $this->saveItem($item);
              }
            }
          }
        }
        else {
          if ($exists->hasTranslation($item['LanguageCode'])) {
            $exists->removeTranslation($item['LanguageCode']);
            $exists->save();
          }
        }
      }
    }
  }

}
