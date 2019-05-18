<?php
/**
 * @file
 * paramter form
 * Use to customize report parameters form.
 * @author metzlerd
 *
 */
namespace Drupal\forena\FrxPlugin\Renderer;
/**
 * Crosstab Renderer
 *
 * @FrxRenderer(id = "FrxParameterForm_DEV")
 */
class FrxParameterForm extends RendererBase {
  public function render() {
    $output = '';
    $variables = $this->replacedAttributes();
    $variables['template']  = $this->innerXML();
    //@TODO: Develop custom parameters form template.
    //$form = $this->report->parametersForm($variables);
    $this->report->parameters_form = array('#markup' => drupal_render($form));
    return $output;
  }
}