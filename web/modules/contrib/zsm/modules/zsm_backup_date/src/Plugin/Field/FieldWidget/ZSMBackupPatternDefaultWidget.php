<?php

namespace Drupal\zsm_backup_date\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use \Drupal\Core\Render\RendererInterface;
/**
 * Field widget "zsm_backup_pattern_default".
 *
 * @FieldWidget(
 *   id = "zsm_backup_pattern_default",
 *   label = @Translation("ZSM Backup Pattern default"),
 *   field_types = {
 *     "zsm_backup_pattern",
 *   }
 * )
 */
class ZSMBackupPatternDefaultWidget extends WidgetBase implements WidgetInterface {
    /**
     * {@inheritdoc}
     */
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
        // $item is where the current saved values are stored.
        $item =& $items[$delta];
        $element += array(
            '#type' => 'fieldset',
        );
        $element['name'] = array(
            '#title' => t('Pattern Type/Name'),
            '#description' => t('A name or title .'),
            '#type' => 'textfield',
            '#default_value' => isset($item->name) ? $item->name : '',
        );
        $element['location'] = array(
            '#title' => t('Location to evaluate RegEx'),
            '#description' => t('The location for evaluating the pattern. Should be an absolute filepath.'),
            '#type' => 'textfield',
            '#default_value' => isset($item->location) ? $item->location : '',
        );
        $element['pattern'] = array(
            '#title' => t('RegEx Pattern'),
            '#description' => t('The regular expression to evaluate. 511 characters max.'),
            '#type' => 'textfield',
            '#default_value' => isset($item->pattern) ? $item->pattern : '',
        );
        $element['age'] = array(
            '#title' => t('File Time'),
            '#description' => t('Whether to use created or modified time.'),
            '#type' => 'select',
            '#options' => array(
                'created' => t('Created'),
                'modified' => t('Modified'),
            ),
            '#default_value' => isset($item->age) ? $item->age : 'created',
        );
        return $element;
    }
}