<?php

namespace Drupal\flipping_book\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Plugin implementation of the 'flipping_book_link_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "flipping_book_link_formatter",
 *   label = @Translation("Flipping Book Link"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FlippingBookLinkFormatter extends FormatterBase  {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['url_only']['#access'] = TRUE;

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    $entity = $items->getEntity();

    foreach ($items as $delta => $item) {
      $items[$delta]->title = $entity->getName();
    }

    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      // By default use the full URL as the link text.
      $url = $this->buildUrl($item);
      $link_title = $url->toString();

      // If the title field value is available, use it for the link text.
      if (empty($settings['url_only']) && !empty($item->title)) {
        $link_title = \Drupal::token()->replace($item->title, [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]);
      }

      // Trim the link text to the desired length.
      if (!empty($settings['trim_length'])) {
        $link_title = Unicode::truncate($link_title, $settings['trim_length'], FALSE, TRUE);
      }

      if (!empty($settings['url_only']) && !empty($settings['url_plain'])) {
        $element[$delta] = array(
          '#plain_text' => $link_title,
        );

        if (!empty($item->_attributes)) {
          $content = str_replace('internal:/', '', $item->uri);
          $item->_attributes += array('content' => $content);
        }
      }
      else {
        $element[$delta] = array(
          '#type' => 'link',
          '#title' => $link_title,
          '#options' => $url->getOptions(),
        );
        $element[$delta]['#url'] = $url;

        if (!empty($item->_attributes)) {
          $element[$delta]['#options'] += array ('attributes' => array());
          $element[$delta]['#options']['attributes'] += $item->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and should not be rendered in the field template.
          unset($item->_attributes);
        }
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildUrl(FileItem $item) {
    $flipping_book = $item->getRoot()->getValue();

    /** @var Url $url */
    $url = \Drupal::service('flipping_book')
      ->buildFlippingBookUrl($flipping_book);

    $settings = $this->getSettings();
    $options = $url->getOptions();

    // Add optional 'rel' attribute to link options.
    if (!empty($settings['rel'])) {
      $options['attributes']['rel'] = $settings['rel'];
    }
    // Add optional 'target' attribute to link options.
    if (!empty($settings['target'])) {
      $options['attributes']['target'] = $settings['target'];
    }
    $url->setOptions($options);

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return (
      parent::isApplicable($field_definition) &&
      ($field_definition->getTargetEntityTypeId() == 'flipping_book') &&
      ($field_definition->getName() == 'file')
    );
  }

}
