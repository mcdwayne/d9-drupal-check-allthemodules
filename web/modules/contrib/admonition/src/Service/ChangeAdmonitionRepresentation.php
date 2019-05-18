<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 8/15/16
 * Time: 9:58 AM
 */

namespace Drupal\admonition\Service;

//use QueryPath\QueryPath;
use Drupal\Core\Controller\ControllerBase;
//use Symfony\Component\HttpKernel\Controller;
use Drupal\Core\Template\TwigEnvironment;

class ChangeAdmonitionRepresentation  implements ChunkChangeRepresentationInterface {

  /**
   * {@inheritdoc}
   */
  public function storageToDisplay($content_in) {
    $t = new TwigEnvironment();
//    $t = \Drupal::service('twig');
    return 'DOOOGGGGZZZ! ' . $content_in;

    $qp = html5qp($content_in);
    $admonitions = $qp->find('[data-chunk-type="admonition"]');
    foreach ($admonitions as $admonition) {
      $type = $admonition->attr('data-admonition-type');
      $alignment = $admonition->attr('data-admonition-alignment');
      $width = $admonition->attr('data-admonition-width');
      $content = $admonition->innerHTML5();

      $t = new TwigEnvironment();
      $html = $t->render('number.html.twig', array('number' => 666));
// renders app/Resources/views/lucky/number.html.twig
//      return $this->render('lucky/number.html.twig', array('name' => $name));
//      $html = '<div>';
      $admonition->html5($html);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function displayToStorage($content_in) {
    // TODO: Implement displayToStorage() method.
  }

}