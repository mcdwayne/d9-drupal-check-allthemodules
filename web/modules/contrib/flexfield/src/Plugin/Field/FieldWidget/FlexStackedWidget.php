<?php

namespace Drupal\flexfield\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\flexfield\Plugin\FlexFieldTypeManager;
use Drupal\flexfield\Plugin\FlexFieldTypeManagerInterface;
use Drupal\flexfield\Plugin\Field\FieldWidget\FlexWidgetBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'flex_stacked' widget.
 *
 * @FieldWidget(
 *   id = "flex_stacked",
 *   label = @Translation("Stacked"),
 *   weight = 2,
 *   field_types = {
 *     "flex"
 *   }
 * )
 */
class FlexStackedWidget extends FlexWidgetBase {

  protected $flexFieldManager = null;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    foreach ($this->getFlexFieldItems() as $name => $item) {
      $element[$name] = $item->widget($items, $delta, $element, $form, $form_state);
    }

    return $element;
  }

}
