<?php

namespace Drupal\entity_gallery\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Field handler to provide simple renderer that allows linking to a entity
 * gallery. Definition terms:
 * - link_to_entity_gallery default: Should this field have the checkbox "link
 *   to entity gallery" enabled by default.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_gallery")
 */
class EntityGallery extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // Don't add the additional fields to groupby
    if (!empty($this->options['link_to_entity_gallery'])) {
      $this->additional_fields['egid'] = array('table' => 'entity_gallery_field_data', 'field' => 'egid');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_entity_gallery'] = array('default' => isset($this->definition['link_to_entity_gallery default']) ? $this->definition['link_to_entity_gallery default'] : FALSE);
    return $options;
  }

  /**
   * Provide link to entity gallery option
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_entity_gallery'] = array(
      '#title' => $this->t('Link this field to the original piece of content'),
      '#description' => $this->t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_entity_gallery']),
    );

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Prepares link to the entity gallery.
   *
   * @param string $data
   *   The XSS safe string for the link text.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($data, ResultRow $values) {
    if (!empty($this->options['link_to_entity_gallery']) && !empty($this->additional_fields['egid'])) {
      if ($data !== NULL && $data !== '') {
        $this->options['alter']['make_link'] = TRUE;
        $this->options['alter']['url'] = Url::fromRoute('entity.entity_gallery.canonical', ['entity_gallery' => $this->getValue($values, 'egid')]);
        if (isset($this->aliases['langcode'])) {
          $languages = \Drupal::languageManager()->getLanguages();
          $langcode = $this->getValue($values, 'langcode');
          if (isset($languages[$langcode])) {
            $this->options['alter']['language'] = $languages[$langcode];
          }
          else {
            unset($this->options['alter']['language']);
          }
        }
      }
      else {
        $this->options['alter']['make_link'] = FALSE;
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->renderLink($this->sanitizeValue($value), $values);
  }

}
