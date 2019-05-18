<?php

namespace Drupal\applenews\Normalizer;

use ChapterThree\AppleNewsAPI\Document\Layouts\Layout;
use ChapterThree\AppleNewsAPI\Document;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use ChapterThree\AppleNewsAPI\Document\Components\Component;

/**
 * Applenews content entity normalizer.
 *
 * Takes a content entity, normalizes it into
 *   a ChapterThree\AppleNewsAPI\Document.
 */
class ApplenewsContentEntityNormalizer extends ApplenewsNormalizerBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an ApplenewsTemplateSelection object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    // Only consider this normalizer if we are trying to normalize a content
    // entity into the 'applenews' format.
    return $format === $this->format && $data instanceof ContentEntityInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($data, $format = NULL, array $context = []) {
    // @todo check cache
    $template = $this->entityTypeManager->getStorage('applenews_template')->load($context['template_id']);
    $layout = new Layout($template->columns, $template->width);
    $langcode = $data->language()->getId();
    // If language is not specified , fallback to site default.
    if ($langcode == LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    }
    $layout
      ->setMargin($template->margin)
      ->setGutter($template->gutter);
    $document = new Document($data->uuid(), $data->getTitle(), $langcode, $layout);

    $context['entity'] = $data;
    foreach ($template->getComponents() as $component) {
      $normalized = $this->serializer->normalize($component, $format, $context);
      if (!$normalized) {
        continue;
      }
      elseif ($normalized instanceof Component) {
        $normalized = [$normalized];
      }

      foreach ($normalized as $normalized_component) {
        if ($normalized_component instanceof Component) {
          $document->addComponent($normalized_component);
        }
      }
    }

    // @todo: Load only default and used custom styles.
    $text_styles = $this->entityTypeManager->getStorage('applenews_text_style')->loadMultiple();
    foreach ($text_styles as $id => $text_style) {
      /** @var \Drupal\applenews\Entity\ApplenewsTextStyle $text_style */
      $document->addTextStyle($text_style->id(), $text_style->toObject());
    }
    return $document->jsonSerialize();
  }

}
