<?php
namespace Drupal\registry_styles\Controller;

use Drupal\isoregistry\Controller\RegistryExceptions;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Description of StylesController
 *
 * @author Balschmiter
 */
class StylesController {
  
  private $defaultFormats = ['none','sld'];
  private $format = null;

  public function showStyle($namespace, $style) {
    $this->format = substr(strrchr($style, "."), 1);    
    
    foreach ($this->defaultFormats as $key => $value) {
      $style = str_replace($value, "", $style);
    }
    if ($this->format == null || in_array(strtolower($this->format),$this->defaultFormats)) {
      $response = $this->changeResponse($namespace, $style);
      return $response;
    } else {
      $response = new RegistryExceptions(t('angegebenes Format nicht unterstützt, unterstützt wird nur SLD und Blank (Ausgabe über die Webseite)'));
      return $response->getDefaultException();
    }
  }
    
  public function changeResponse($namespace, $style) {
    switch ($this->format) {
      case null:
        $response = new StylesNode($namespace, $style);
        return $response->getResponse();
      case 'sld':
        $style = substr($style, 0, -1);
        $response = new StylesSLD($namespace, $style);
        return $response->getResponse();
    }
  }
  
}
