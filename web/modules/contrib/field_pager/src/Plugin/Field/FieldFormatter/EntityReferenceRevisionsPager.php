<?php

namespace Drupal\field_pager\Plugin\Field\FieldFormatter;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_reference_revisions\Plugin\Field\FieldFormatter\EntityReferenceRevisionsEntityFormatter;
use Drupal\field_pager\PagerHelper;

/**
 * Plugin implementation of the 'entity reference rendered entity' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_revisions_entity_view_pager",
 *   label = @Translation("Rendered entity (Pager)"),
 *   description = @Translation("Display the referenced entities rendered by entity_view()."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class EntityReferenceRevisionsPager extends EntityReferenceRevisionsEntityFormatter {

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

    // To filter correct index.
    $entities = $this->getEntitiesToView($items, $langcode);
    $index_name = $this->getSetting('index_name');
    $delta = (int) (isset($_GET[$index_name]) ? $_GET[$index_name] : 0);
    if (empty($entities[$delta])) {
      throw new NotFoundHttpException();
    }
    $entity = $entities[$delta];

    // Protect ourselves from recursive rendering.
    static $depth = 0;
    $depth++;
    if ($depth > 20) {
      $this->loggerFactory->get('entity')->error('Recursive rendering detected when rendering entity @entity_type @entity_id. Aborting rendering.', [
        '@entity_type' => $entity->getEntityTypeId(),
        '@entity_id' => $entity->id(),
      ]);
      return $elements;
    }
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId());
    $elements[$delta] = $view_builder->view($entity, $view_mode, $entity->language()->getId());

    // Add a resource attribute to set the mapping property's value to the
    // entity's url. Since we don't know what the markup of the entity will
    // be, we shouldn't rely on it for structured data such as RDFa.
    if (!empty($items[$delta]->_attributes) && !$entity->isNew() && $entity->hasLinkTemplate('canonical')) {
      $items[$delta]->_attributes += ['resource' => $entity->toUrl()->toString()];
    }
    $depth = 0;

    return $elements;
  }

}
