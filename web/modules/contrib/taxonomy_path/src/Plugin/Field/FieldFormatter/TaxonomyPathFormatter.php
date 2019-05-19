<?php

/**
 * @file
 * Contains \Drupal\taxonomy_path\Plugin\Field\FieldFormatter\TaxonomyPathFormatter.
 */

namespace Drupal\taxonomy_path\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Url;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Render\BubbleableMetadata;


/**
 * Plugin implementation of the 'taxonomy_path_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "taxonomy_path_formatter",
 *   label = @Translation("Taxonomy path formatter"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class TaxonomyPathFormatter extends EntityReferenceFormatterBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'link' => TRUE,
      'path' => NULL,
      'path_case' => 'none',
      'transform_dash' => NULL,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['link'] = array(
      '#title' => t('Link label to the referenced entity'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('link'),
    );
    $elements['path'] = array(
      '#title' => t('Custom path for the referenced taxonomy'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('path'),
    );

    $elements['path_case'] = array(
      '#type' => 'select',
      '#title' => $this->t('Case in path'),
      '#description' => $this->t('When printing url paths, how to transform the case of the filter value.'),
      '#options' => array(
        'none' => $this->t('No transform'),
        'upper' => $this->t('Upper case'),
        'lower' => $this->t('Lower case'),
        'ucfirst' => $this->t('Capitalize first letter'),
        'ucwords' => $this->t('Capitalize each word'),
      ),
      '#default_value' => $this->getSetting('path_case')
    );

    $elements['transform_dash'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Transform spaces to dashes in URL'),
      '#default_value' => $this->options['transform_dash'],
      '#group' => 'options][more',
    );

    $elements['token_tree'] = array(
      '#theme' => 'token_tree',
      '#token_types' => ['term'],
      '#click_insert' => FALSE,
      '#show_restricted' => TRUE,
    );


    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = $this->getSetting('link') ? t('Link to the referenced entity') : t('No link');
    $summary[] = $this->getSetting('path') ? t('Path: ') . $this->getSettings('path')['path'] : NULL;
    $summary[] = $this->getSetting('path_case') ? t('Case transformation: ') . $this->getSetting('path_case') : NULL;
    $summary[] = $this->getSetting('transform_dash') ? t('Spaces to dashes: ') . t('yes') : t('Spaces to dashes: ') . ('no');
    return $summary;
  }


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $output_as_link = $this->getSetting('link');
    $path_raw = 'base://' . $this->getSetting('path');

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $label = $entity->label();
      // If the link is to be displayed and the entity has a uri, display a
      // link.
      if ($output_as_link && !$entity->isNew()) {
        try {
          $uri = $entity->urlInfo();
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

        // Token substitution here.
        $token = \Drupal::service('token');
        $bubbleable_metadata = new BubbleableMetadata();
        $path = $token->replace($path_raw, ['term' => $entity], [], $bubbleable_metadata);

        if (!empty($this->getSetting('transform_dash'))) {
          $path = strtr($path, ' ', '-');
        }
        $path = $this->caseTransform($path, $this->getSetting('path_case'));


        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $label,
          '#url' => Url::fromUri($path),
          '#options' => $uri->getOptions(),
        ];

        if (!empty($items[$delta]->_attributes)) {
          $elements[$delta]['#options'] += array('attributes' => array());
          $elements[$delta]['#options']['attributes'] += $items[$delta]->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and shouldn't be rendered in the field template.
          unset($items[$delta]->_attributes);
        }
      }
      else {
        $elements[$delta] = array('#plain_text' => $label);
      }
      $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
    }
    return $elements;
  }

  /**
   * Transform a string by a certain method.
   *
   * @param $string
   *    The input you want to transform.
   * @param $option
   *    How do you want to transform it, possible values:
   *      - upper: Uppercase the string.
   *      - lower: lowercase the string.
   *      - ucfirst: Make the first char uppercase.
   *      - ucwords: Make each word in the string uppercase.
   *
   * @return string
   *    The transformed string.
   */
  protected function caseTransform($string, $option) {
    switch ($option) {
      default:
        return $string;
      case 'upper':
        return Unicode::strtoupper($string);
      case 'lower':
        return Unicode::strtolower($string);
      case 'ucfirst':
        return Unicode::ucfirst($string);
      case 'ucwords':
        return Unicode::ucwords($string);
    }
  }

}
