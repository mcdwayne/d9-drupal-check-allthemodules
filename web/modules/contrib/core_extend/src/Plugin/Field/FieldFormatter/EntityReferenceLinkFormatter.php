<?php

namespace Drupal\core_extend\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;

/**
 * Plugin implementation of the 'entity reference label' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_link",
 *   label = @Translation("Link"),
 *   description = @Translation("Display a linked label of the referenced entities."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceLinkFormatter extends EntityReferenceFormatterBase {

  use RedirectDestinationTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'link_template' => ['default' => 'canonical'],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $entity_type_id = $this->fieldDefinition->getItemDefinition()->getSetting('target_type');
    $link_template_ids = array_keys(\Drupal::entityTypeManager()->getDefinition($entity_type_id)->getLinkTemplates());

    $elements['link_template'] = [
      '#type' => 'select',
      '#title' => $this->t('Link to use'),
      '#options' => array_combine($link_template_ids, $link_template_ids),
      '#default_value' => $this->getSetting('link_template'),
    ];
    $elements['destination'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include destination'),
      '#description' => $this->t('Enforce a <code>destination</code> parameter in the link to return the user to the original view upon completing the link action. Most operations include a destination by default and this setting is no longer needed.'),
      '#default_value' => $this->getSetting('destination'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $link_template = $this->getSetting('link_template');

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $label = $entity->label();
      $output_as_link = TRUE;
      // If the link is to be displayed and the entity has a uri, display a
      // link.
      if ($link_template && !$entity->isNew()) {
        try {
          $options = [];
          if ($this->getSetting('destination')) {
            $options = $this->getDestinationArray();
          }
          $uri = $entity->toUrl($link_template, $options);
        }
        catch (UndefinedLinkTemplateException $e) {
          // This exception is thrown by \Drupal\Core\Entity\Entity::urlInfo()
          // and it means that the entity type doesn't have a link template nor
          // a valid "uri_callback", so don't bother trying to output a link for
          // the rest of the referenced entities.
          $output_as_link = FALSE;
        }
      }

      if ($output_as_link && isset($uri) && !$entity->isNew()) {
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $label,
          '#url' => $uri,
          '#options' => $uri->getOptions(),
        ];

        if (!empty($items[$delta]->_attributes)) {
          $elements[$delta]['#options'] += ['attributes' => []];
          $elements[$delta]['#options']['attributes'] += $items[$delta]->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and shouldn't be rendered in the field template.
          unset($items[$delta]->_attributes);
        }
      }
      else {
        $elements[$delta] = ['#plain_text' => $label];
      }
      $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
    }

    return $elements;
  }

}
