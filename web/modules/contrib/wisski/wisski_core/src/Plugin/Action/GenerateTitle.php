<?php

/**
 * @file
 * Contains \Drupal\wisski_core\Plugin\Action\GenerateTitle.
 */

namespace Drupal\wisski_core\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Annotation\Action;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;


/**
* Generates the title for the given WissKI entities.
*
* @Action(
*   id = "wisski_generate_title",
*   label = @Translation("Generate Title"),
*   type = "wisski_individual"
* )
*/
class GenerateTitle extends ActionBase {
  
  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // TODO: review access permissions
    return $object->access('view', $account, $return_as_object);
  }

  
  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    if (!empty($object)) {
      wisski_core_generate_title($object, NULL, TRUE);
    }
  }

}

