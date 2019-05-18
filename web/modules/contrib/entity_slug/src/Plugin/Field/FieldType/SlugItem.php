<?php

namespace Drupal\entity_slug\Plugin\Field\FieldType;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\entity_slug\Plugin\Slugifier\SlugifierInterface;
use Drupal\entity_slug\SlugifierManager;

/**
 * Provides a field type of slug.
 *
 * @FieldType(
 *   id = "slug",
 *   label = @Translation("Slug"),
 *   category = @Translation("Slug"),
 *   module = "entity_slug",
 *   description = @Translation("Provides a Slug field type for generating URL-friendly identifiers."),
 *   default_widget = "slug_default",
 *   default_formatter = "slug_default",
 * )
 */
class SlugItem extends SlugItemBase {}
