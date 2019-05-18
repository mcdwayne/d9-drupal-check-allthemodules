<?php

namespace Drupal\drupal_git\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\drupal_git\GitRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\drupal_git\checkGitService;

/**
 * Class drupalGitStatusController.
 */
class DrupalGitStatusController extends ControllerBase {

  /**
   * Drupal\drupal_git\checkGitService definition.
   *
   * @var \Drupal\drupal_git\checkGitService
   */
  protected $drupalGitCheckGit;

  /**
   * Constructs a new DefaultController object.
   */
  public function __construct(checkGitService $drupal_git_check_git) {
    $this->drupalGitCheckGit = $drupal_git_check_git;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('drupal_git.check_git')
    );
  }

  /**
   * Drupalgitstatus.
   *
   * @return string
   *   Return Hello string.
   */
  public function drupalGitStatus() {
    $repo = new GitRepository(__DIR__);
    return [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#title' => $this->t("List of files Which are staged, unstaged, and untracked"),
      '#items' => $repo->getStatus(),
      '#attributes' => ['class' => ['drupal-git', 'drupal-git-status']],
      '#wrapper_attributes' => ['class' => 'container'],
      '#attached' => [
        'library' => 'drupal_git/drupal_git_global',
      ],
    ];
  }

}
