<?php

namespace Drupal\ansible\Controller;

use Asm\Ansible\Ansible;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Controller\ControllerBase;

/**
 * Ansible Coontroller.
 *
 * @package Drupal\ansible\Controller
 */
class AnsibleController extends ControllerBase {

  /**
   * Get entity value.
   *
   * @param int $id
   *   Entity id.
   * @param string $field
   *   Field.
   *
   * @return string
   *   Return field value
   */
  private static function loadConfig($id, $field) {
    $query = \Drupal::entityTypeManager()->getStorage('ansible_entity')->load($id);
    return $query->$field->value;
  }

  /**
   * Load Ansible class.
   *
   * @return string
   *   load ansible Class.
   */
  private static function loadAnsible($id) {
    $deployment = self::loadConfig($id, "playbookdirectory");
    $ansibleplaybook = "/usr/bin/ansible-playbook";
    $ansiblegalaxy = "/usr/bin/ansible-galaxy";
    $ansible = new Ansible(
      $deployment,
      $ansibleplaybook,
      $ansiblegalaxy
    );
    return $ansible;
  }

  /**
   * Execute playbook.
   *
   * @param int $id
   *   Entity id.
   * @param array $extravars
   *   Extravars parameter for ansible-playbook.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   Symfony\Component\HttpFoundation\Response ansible-playbook result.
   */
  public static function exec($id, array $extravars = NULL) {
    $playbook = self::loadConfig($id, "playbook");
    $tags = self::loadConfig($id, "tags");
    if (empty($extravars)) {
      $extravars = self::loadConfig($id, "extravars");
    }
    $inventoryfile = self::loadConfig($id, "inventoryfile");
    $ansible = self::loadAnsible($id)->playbook()
      ->play($playbook)
      ->inventoryFile($inventoryfile);
    if (isset($extravars)) {
      $ansible->extraVars($extravars);
    }
    if (isset($tags)) {
      $ansible->tags($tags);
    }

    return new Response($ansible->execute());
  }

  /**
   * Load playbook page.
   *
   * @param int $id
   *   Entity id.
   *
   * @return array
   *   return page.
   */
  public static function load($id) {
    $page = [
      '#prefix' => '<div id="ansibleContent"><div class="se-pre-con">',
      '#suffix' => '</div></div>',
    ];
    $page['#attached']['library'][] = 'ansible/ajax';
    $page['#attached']['drupalSettings']['js']['ansibleajax']['id'] = $id;
    return $page;
  }

}
