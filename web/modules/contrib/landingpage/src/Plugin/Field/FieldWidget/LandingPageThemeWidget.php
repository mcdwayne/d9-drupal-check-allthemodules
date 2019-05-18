<?php

namespace Drupal\landingpage\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\InfoParser;

/**
 * Plugin implementation of the 'landing_page_theme_widget' widget.
 *
 * @FieldWidget(
 *   id = "landing_page_theme_widget",
 *   label = @Translation("LandingPage Theme widget"),
 *   field_types = {
 *     "landing_page_theme_field_type"
 *   }
 * )
 */
class LandingPageThemeWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $default_value = isset($items[$delta]->value) ? $items[$delta]->value : '';

    $options = array();
    $themes = system_list('theme');
    $parser = new InfoParser();
    foreach ($themes as $key => $theme) {
      $info = $parser->parse(drupal_get_path('theme', $key) . '/' . $key . '.info.yml');
      if ($info['landingpage'] == '1.x') {
        $options[$key] = $info['name'];

        // start of update service list of 'landingpage_skin' entities
        $classes = $info['landingpage classes'];

        foreach ($classes as $class => $value) {
          
          $entity = \Drupal::entityTypeManager()->getStorage('landingpage_skin')->load($class);

          if (empty($entity)) {
            \Drupal::entityTypeManager()->getStorage('landingpage_skin')
              ->create(array('id' => $class, 'label' => $value))
              ->save();
          }
        }

        // end of update service list of 'landingpage_skin' entities
      }
    }
    $element = array(
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#options' => $options,
      '#default_value' => $default_value,
      '#multiple' => FALSE,
    );

    return array('value' => $element);
  }

}
