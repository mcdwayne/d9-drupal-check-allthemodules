<?php /**
 * @file
 * Contains \Drupal\download\Plugin\Field\FieldWidget\DownloadLinkWidget.
 */

namespace Drupal\download\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;

/**
 * @FieldWidget(
 *  id = "download_link_widget",
 *  label = @Translation("Field selector"),
 *  field_types = {"download_link"}
 * )
 */
class DownloadLinkWidget extends WidgetBase implements WidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = (!$items[$delta]->isEmpty()) ? $items[$delta]->get('download_fields')->getValue() : array();
    $default_label = ($items[$delta]->get('download_label')->getValue() != '') ? $items[$delta]->get('download_label')->getValue() : '';
    $default_value = is_array($value) ? $value : unserialize($value);

    $widget = $element;
    $widget['#delta'] = $delta;

    $entity = $items->getEntity();
    $options = array();
    foreach($entity->getFields() as $field_name => $field) {
      if ($field instanceof FileFieldItemList) {
        $options[$field_name] = $field->getFieldDefinition()->getLabel();
      }
    }

    $element += array(
      '#type' => 'fieldset',
      'download_label' => array(
        '#title' => t('Download label'),
        '#type' => 'textfield',
        '#default_value' => $default_label,
      ),
      'download_fields' => array(
        '#title' => t('Download Fields'),
        '#type' => 'checkboxes',
        '#options' => $options,
        '#default_value' => $default_value,
      )
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Serialize download fields
    foreach($values as $delta => $fields) {
      $values[$delta]['download_fields'] = serialize($fields['download_fields']);
    }

    return $values;
  }

}
