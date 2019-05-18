<?php

namespace Drupal\field_pager\Plugin\Field\FieldFormatter;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_pager\PagerHelper;

/**
 * Plugin implementation of the 'entity reference rendered entity' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_entity_view_pager",
 *   label = @Translation("Rendered entity (Pager)"),
 *   description = @Translation("Display the referenced entities rendered by entity_view()."),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 */
class EntityReferencePager extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    return PagerHelper::mergeDefaultSettings($settings);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    return PagerHelper::mergeSettingsForm($form, $form_state, $this, $elements);

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    return PagerHelper::mergeSettingsSummary($summary, $this);
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {

    $fields = parent::view($items, $langcode);
    $entities = $this->getEntitiesToView($items, $langcode);
    return PagerHelper::mergeView(count($entities), $this, $fields);

  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $view_mode = $this->getSetting('view_mode');
    $elements = [];

    $entities = $this->getEntitiesToView($items, $langcode);
    $index_name = $this->getSetting('index_name');
    $delta = (int) (isset($_GET[$index_name]) ? $_GET[$index_name] : 0);

    if (empty($entities[$delta])) {
      throw new NotFoundHttpException();
    }
    $entity = $entities[$delta];

    // Due to render caching and delayed calls, the viewElements() method
    // will be called later in the rendering process through a '#pre_render'
    // callback, so we need to generate a counter that takes into account
    // all the relevant information about this field and the referenced
    // entity that is being rendered.
    $recursive_render_id = $items->getFieldDefinition()->getTargetEntityTypeId()
      . $items->getFieldDefinition()->getTargetBundle()
      . $items->getName()
      // We include the referencing entity, so we can render default images
      // without hitting recursive protections.
      . $items->getEntity()->id()
      . $entity->getEntityTypeId()
      . $entity->id();

    if (isset(static::$recursiveRenderDepth[$recursive_render_id])) {
      static::$recursiveRenderDepth[$recursive_render_id]++;
    }
    else {
      static::$recursiveRenderDepth[$recursive_render_id] = 1;
    }

    // Protect ourselves from recursive rendering.
    if (static::$recursiveRenderDepth[$recursive_render_id] > static::RECURSIVE_RENDER_LIMIT) {
      $this->loggerFactory->get('entity')->error('Recursive rendering detected when rendering entity %entity_type: %entity_id, using the %field_name field on the %bundle_name bundle. Aborting rendering.', [
        '%entity_type' => $entity->getEntityTypeId(),
        '%entity_id' => $entity->id(),
        '%field_name' => $items->getName(),
        '%bundle_name' => $items->getFieldDefinition()->getTargetBundle(),
      ]);
      return $elements;
    }

    $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    $elements[$delta] = $view_builder->view($entity, $view_mode, $entity->language()->getId());

    // Add a resource attribute to set the mapping property's value to the
    // entity's url. Since we don't know what the markup of the entity will
    // be, we shouldn't rely on it for structured data such as RDFa.
    if (!empty($items[$delta]->_attributes) && !$entity->isNew() && $entity->hasLinkTemplate('canonical')) {
      $items[$delta]->_attributes += ['resource' => $entity->toUrl()->toString()];
    }

    return $elements;
  }

}
