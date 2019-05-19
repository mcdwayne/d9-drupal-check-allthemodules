<?php

namespace Drupal\stacks\Form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form\InlineFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generic entity inline form handler.
 */
abstract class WidgetExtendEntityInlineForm implements InlineFormInterface {

  /**
   * Custom validation for Photo Caption on Widget Extend forms.
   *
   * @param $entity_form
   *   The entity form.
   * @param $form_state
   *   The form state of the parent form.
   */
  public static function validatePhotoCaption(&$entity_form, FormStateInterface $form_state) {
    $photo_caption = $form_state->getValue('field_list_add_content');
    if (strlen($photo_caption[0]['field_extend_description'][0]['value']) >= 255)
      $form_state->setError(
        $entity_form['field_extend_description'],
        t('The photo caption text must be 255 long or less.')
      );
  }
}
