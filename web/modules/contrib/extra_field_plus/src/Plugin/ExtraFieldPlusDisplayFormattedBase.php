<?php

namespace Drupal\extra_field_plus\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Element;
use Drupal\extra_field\Plugin\ExtraFieldDisplayFormattedInterface;

/**
 * Base class for Extra Field Plus Display plugins with field wrapper output.
 */
abstract class ExtraFieldPlusDisplayFormattedBase extends ExtraFieldPlusDisplayBase implements ExtraFieldPlusDisplayInterface, ExtraFieldDisplayFormattedInterface {

  /**
   * Flag to indicate that the extra field has no content.
   *
   * @var bool
   */
  protected $isEmpty = FALSE;

  /**
   * The langcode of the field values.
   *
   * @var string
   */
  protected $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;

  /**
   * {@inheritdoc}
   */
  public function view(ContentEntityInterface $entity) {
    $elements = $this->viewElements($entity);

    if (!empty($elements) && !$this->isEmpty()) {
      // Construct a render array for the extra field elements.
      // @see \Drupal\Core\Field\FormatterBase::view
      $build = [
        '#theme' => 'field',
        '#title' => $this->getLabel(),
        '#label_display' => $this->getLabelDisplay(),
        '#view_mode' => '_custom',
        '#language' => $this->getLangcode(),
        '#field_name' => $this->getFieldName(),
        '#field_type' => $this->getFieldType(),
        '#field_translatable' => $this->isTranslatable(),
        '#entity_type' => $entity->getEntityTypeId(),
        '#bundle' => $entity->bundle(),
        '#object' => $entity,
        '#formatter' => $this->getPluginId(),
      ];

      if ($children = Element::children($elements, TRUE)) {
        $build['#is_multiple'] = TRUE;

        // Without #children the field will not show up.
        $build['#children'] = '';

        foreach ($children as $key) {
          // Only keys in "#items" property are required in
          // template_preprocess_field().
          $build['#items'][$key] = new \stdClass();
          $build[$key] = $elements[$key];
        }
      }
      else {
        $build['#is_multiple'] = FALSE;
        // Only keys in "#items" property are required in
        // template_preprocess_field().
        $build['#items'][] = new \stdClass();
        $build[] = $elements;
      }
    }
    else {
      $build = $elements;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelDisplay() {
    return 'hidden';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldType() {
    return 'extra_field';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName() {
    return 'extra_field_' . $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->isEmpty;
  }

  /**
   * {@inheritdoc}
   */
  public function setLangcode($langcode) {
    $this->langcode = $langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode() {
    return $this->langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    return FALSE;
  }

}
