<?php

namespace Drupal\wisski_core\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityField as ViewsEntityField;
use Drupal\views\ResultRow; 
use Drupal\wisski_core\Entity\Render\WisskiEntityFieldRenderer;

/**
 * Default implementation of the base field plugin.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("wisski_entityfield")
 */
class EntityField extends ViewsEntityField {
  
  /**
   * {@inheritdoc}
   */ 
  public function query($use_groupby = FALSE) {
    $this->query->addField($this->realField, $this->realField);
    $this->query->addField('_entity', '_entity');
  }

  /**
   * Returns the entity field renderer.
   *
   * @return \Drupal\views\Entity\Render\EntityFieldRenderer
   *   The entity field renderer.
   */
  protected function getEntityFieldRenderer() {
#    dpm("yay!");
    if (!isset($this->entityFieldRenderer)) {
      // This can be invoked during field handler initialization in which case
      // view fields are not set yet.
      if (!empty($this->view->field)) {
        foreach ($this->view->field as $field) {
          // An entity field renderer can handle only a single relationship.
          if ($field->relationship == $this->relationship && isset($field->entityFieldRenderer)) {
            $this->entityFieldRenderer = $field->entityFieldRenderer;
            break;
          }
        }
      }
      if (!isset($this->entityFieldRenderer)) {
        $entity_type = $this->entityManager->getDefinition($this->getEntityType());
        // this is the main code - take the wisski-variant!
        $this->entityFieldRenderer = new WisskiEntityFieldRenderer($this->view, $this->relationship, $this->languageManager, $entity_type, $this->entityManager);
      }
    }
    return $this->entityFieldRenderer;
  }

#  public function render(ResultRow $values) {
#    dpm("yay!!!");
#    dpm($values, "yay!");
#  }

}   

