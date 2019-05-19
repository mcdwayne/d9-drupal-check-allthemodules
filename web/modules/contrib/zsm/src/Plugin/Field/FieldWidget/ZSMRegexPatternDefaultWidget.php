<?php

namespace Drupal\zsm\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use \Drupal\Core\Render\RendererInterface;
/**
 * Field widget "zsm_regex_pattern_default".
 *
 * @FieldWidget(
 *   id = "zsm_regex_pattern_default",
 *   label = @Translation("ZSM Regex Pattern default"),
 *   field_types = {
 *     "zsm_regex_pattern",
 *   }
 * )
 */
class ZSMRegexPatternDefaultWidget extends WidgetBase implements WidgetInterface {
    /**
     * {@inheritdoc}
     */
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
        // $item is where the current saved values are stored.
        $item =& $items[$delta];
        $element += array(
            '#type' => 'fieldset',
        );
        $element['type'] = array(
            '#title' => t('Type'),
            '#description' => t('The plugin-specific pattern type. See the notes for your plugin.'),
            '#type' => 'textfield',
            '#default_value' => isset($item->type) ? $item->type : '',
        );
        $element['location'] = array(
            '#title' => t('Location to evaluate RegEx'),
            '#description' => t('The plugin-specific location for evaluating the pattern. See the notes for your plugin.'),
            '#type' => 'textfield',
            '#default_value' => isset($item->location) ? $item->location : '',
        );
        $element['pattern'] = array(
            '#title' => t('RegEx Pattern'),
            '#description' => t('The regular expression to evaluate. 511 characters max.'),
            '#type' => 'textfield',
            '#default_value' => isset($item->pattern) ? $item->pattern : '',
        );
        return $element;
    }
}