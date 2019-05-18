<?php

namespace Drupal\entity_language_fallback\Plugin\search_api\processor;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\search_api\Item\Item;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Excludes unpublished nodes from node indexes.
 *
 * @SearchApiProcessor(
 *   id = "fallback_language",
 *   label = @Translation("Fallback language"),
 *   description = @Translation("Index content that has translations in fallback languages"),
 *   stages = {
 *     "alter_items" = 0,
 *   },
 * )
 */
class FallbackLanguage extends ProcessorPluginBase {

  /**
   * Languages
   *
   * @var \Drupal\language\ConfigurableLanguageInterface[]
   */
  protected $languages;

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    // Annoyingly, this doc comment is needed for PHPStorm. See
    // http://youtrack.jetbrains.com/issue/WI-23586
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item_id => $item) {
      $object = $item->getOriginalObject();
      $entity = $object->getValue();
      if (!($entity instanceof ContentEntityInterface) || !$entity->isTranslatable()) {
        continue;
      }
      // Only add missing translations to the source language item.
      $entity_lang = $entity->language()->getId();
      if (!$entity->hasField('content_translation_source')
        || !in_array($entity->content_translation_source->value, [$entity_lang, LanguageInterface::LANGCODE_NOT_SPECIFIED])) {
        continue;
      }
      foreach ($this->languages as $langcode => $language) {
        if ($entity->hasTranslation($langcode)) {
          continue;
        }
        $fallback_chain = $language->getThirdPartySetting('entity_language_fallback', 'fallback_langcodes', []);
        $fallback_found = FALSE;
        foreach ($fallback_chain as $candidate) {
          if ($entity->hasTranslation($candidate)) {
            $fallback_found = TRUE;
            break;
          }
        }
        if ($fallback_found) {
          $entity = $entity->getTranslation($candidate);
          $new_key = 'entity:' . $entity->getEntityType()->id() . '/' . $entity->id() . ':' . $candidate;
          $new_item = new Item($item->getIndex(), $new_key, $item->getDatasource());
          $object->setValue($entity);
          $new_item->setOriginalObject($object);
          $new_item->setLanguage($langcode);
          $items[$new_key . $langcode] = $new_item;
        }
      }
    }
  }

  /**
   * Set internal language list.
   */
  protected function setLanguageList(LanguageManagerInterface $languageManager) {
    $this->languages = $languageManager->getLanguages();
    foreach ($this->languages as $key => &$language) {
      $language = ConfigurableLanguage::load($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->setLanguageList($container->get('language_manager'));
    return $processor;
  }

}
