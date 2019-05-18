<?php

namespace Drupal\webform_openlayers\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'webform_openlayers' element.
 *
 * @WebformElement(
 *   id = "webform_openlayers_composite",
 *   label = @Translation("Webform OpenLayers"),
 *   description = @Translation("Provides a webform element example."),
 *   category = @Translation("spatial elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\webform_openlayers\Element\WebformExampleComposite
 * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class WebformOpenLayers_Composite extends WebformCompositeBase {

	/**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = $this->getDefaultMultipleProperties() + parent::getDefaultProperties();
    $properties['title_display'] = '';
		$properties['map'] = '';
		$properties['geometry'] = '';
		$properties['showBox'] = '';
    $properties['element'] = [];
		$properties['validation_iswithinGeom'] = '';
		$properties['validation_iswithinField'] = '';
    unset($properties['flexbox']);
    return $properties;
  }	
	
    public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    unset($form['composite']);
    $form['form']['geometry'] = array(
        '#type' => 'radios',
        '#title' => $this->t('allowed geometry'),
        '#options' => array(
            'Point' => $this->t('Point'),
            'Linestring' => $this->t('Line'),
            'Polygon' => $this->t('Polygon'),
        ),
    );
		
    $openlayers_map_options = [];
    foreach (openlayers_map_get_info() as $key => $map) {
      $openlayers_map_options[$key] = $this->t($map['label']);
    }		
    $form['form']['map'] = array(
        '#type' => 'select',
        '#title' => $this->t('select map'),
        '#options' => $openlayers_map_options,
    );

    $form['form']['showBox'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Hide WKT Textarea'),
    );

    $form['validation']['validation_iswithinGeom'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Polygon'),
        '#description' => $this->t('Add a Polygon in WKT Format'),
    );

    $form['validation']['validation_iswithinField'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Select Field'),
        '#description' => $this->t('Add a  form field of Polygon/Webform OpenLayers by adding the key'),
    );
    return $form;
  }

	
  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $this->formatTextItemValue($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    $lines = [];
    $lines [] = ($value['value'] ? ' (' . $value['value'] . ')' : '');
    return $lines;
  }
	
	/**
   * {@inheritdoc}
   */
  public function getCompositeElements() {
    return [];
  }
	
  public function initializeCompositeElements(array &$element) {
    $element['#webform_composite_elements'] = [
      'value' => [
        '#title' => $this->t('Geom as WKT'),
        '#type' => 'textarea',
      ]
    ];
  }

}
