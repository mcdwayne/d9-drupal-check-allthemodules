<?php

namespace Drupal\entity_slug\Plugin\Field\FieldWidget;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'slug' widget.
 *
 * @FieldWidget(
 *   id = "slug_default",
 *   module = "entity_slug",
 *   label = @Translation("Slug field widget"),
 *   field_types = {
 *     "slug",
 *   }
 * )
 */
class SlugWidget extends SlugWidgetBase {}
