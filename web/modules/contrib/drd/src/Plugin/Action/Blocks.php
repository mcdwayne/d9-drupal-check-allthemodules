<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\drd\Entity\BaseInterface as RemoteEntityInterface;

/**
 * Provides a 'Blocks' action.
 *
 * @Action(
 *  id = "drd_action_blocks",
 *  label = @Translation("List and render blocks"),
 *  type = "drd_domain",
 * )
 */
class Blocks extends BaseEntityRemote {

  /**
   * {@inheritdoc}
   */
  public function executeAction(RemoteEntityInterface $domain) {
    $response = parent::executeAction($domain);
    if (is_array($response)) {
      /* @var \Drupal\drd\Entity\DomainInterface $domain */
      if (empty($this->arguments['module'])) {
        $site_config = \Drupal::configFactory()->getEditable('drd.general');
        $blocks = $site_config->get('remote_blocks');
        foreach ($response as $module => $items) {
          foreach ($items as $delta => $label) {
            $blocks[$module][$delta] = $label;
          }
        }
        $site_config
          ->set('remote_blocks', $blocks)
          ->save();
      }
      else {
        $domain->cacheBlock(
          $this->arguments['module'],
          $this->arguments['delta'],
          $response['data']
        );
      }
      return TRUE;
    }
    return FALSE;
  }

}
