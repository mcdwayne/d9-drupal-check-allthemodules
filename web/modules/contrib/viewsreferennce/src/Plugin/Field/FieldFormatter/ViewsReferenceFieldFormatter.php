<?php

namespace Drupal\viewsreference\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;


/**
 *
 * @FieldFormatter(
 *   id = "viewsreference_formatter",
 *   label = @Translation("Views Reference"),
 *   field_types = {"viewsreference"}
 * )
 */
class ViewsReferenceFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();

    $options['render_view'] = TRUE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    // We may decide on alternatives to rendering the view so get settings established
    $form['render_view'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Render View'),
      '#default_value' => $this->getSetting('render_view'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $settings = $this->getSettings();

    $summary[] = t('Render View: @view', array('@view' => $settings['render_view'] ? 'TRUE' : 'FALSE'));
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $view_name = $item->getValue()['target_id'];
      $display_id = $item->getValue()['display_id'];
      $view = views_embed_view($view_name, $display_id);

      if ($this->getSetting('render_view')) {

        $elements[$delta] = array(
          '#markup' => render($view),
          // todo what cache shall we use?
          '#cache' => array(
//              'tags' => $user->getCacheTags(),
          ),
        );

        }
      }


    return $elements;
  }

  /**
   * {@inheritdoc}
   */
//  public static function isApplicable(FieldDefinitionInterface $field_definition) {
//    return $field_definition->getTargetEntityTypeId() === 'user' && $field_definition->getName() === 'name';
//  }

}
