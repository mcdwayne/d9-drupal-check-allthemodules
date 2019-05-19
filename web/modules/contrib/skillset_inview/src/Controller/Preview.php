<?php

namespace Drupal\skillset_inview\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Preview controller for the skillset_inview module.
 */
class Preview extends ControllerBase {

  /**
   * Embed the basic block on an admin page for preview.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @return array
   *   returns render array
   */
  public function skillBlock() {
    $db = \Drupal::service('database');
    $query = $db->select('skillset_inview', 's')
      ->fields('s')
      ->execute()->fetchAll();
    $total_skills = count($query);
    if ($total_skills == 0) {
      drupal_set_message($this->t('Preview offline.  No skills exist at this time, please add below&hellip;'), 'warning');
      return new RedirectResponse(Url::fromRoute('skillset_inview.add_form')->toString());
    }

    $renderer = \Drupal::service('renderer');
    $block_manager = \Drupal::service('plugin.manager.block');
    $block_plugin = $block_manager->createInstance('skillset_inview_zero', []);
    $block_build = $block_plugin->build();
    $page['block_preview'] = [
      '#type' => 'markup',
      '#markup' => $renderer->render($block_build),
      '#attached' => [
        'library' => [
          'skillset_inview/admin',
        ],
      ],
    ];

    if (\Drupal::currentUser()->hasPermission('administer blocks')) {
      $block_admin_link = [
        '#title' => $this->t('Block Structure'),
        '#attributes' => [
          'title' => "goto 'Block Structure' page",
        ],
        '#type' => 'link',
        '#url' => Url::fromRoute('block.admin_display'),
      ];
      $page['help'] = [
      '#type' => 'markup',
      '#markup' => '<footer class="preview-footer-note"><mark>' . $this->t('When your content is ready, place the <strong>Skillset Inview</strong> on the @blockStructure page.', [
        '@blockStructure' => $renderer->render($block_admin_link),
      ]) . '</mark></footer>',

    ];

    }
    return $page;
  }

}
