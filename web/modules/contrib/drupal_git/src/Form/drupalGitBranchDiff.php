<?php

namespace Drupal\drupal_git\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupal_git\GitRepository;
use Drupal\drupal_git\checkGitService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class drupalGitBranchDiff.
 */
class DrupalGitBranchDiff extends FormBase {

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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drupal_git_branch_diff';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->drupalGitCheckGit->isGitRepo()) {
      $repo = new GitRepository(__DIR__);

      if (!is_null($repo->getBranches())) {
        foreach ($repo->getBranches() as $branch) {
          $branched_options[$branch] = $branch;
        }

        $form['branch_to_compare'] = [
          '#type' => 'select',
          '#title' => $this->t('Source Branch'),
          '#description' => $this->t('Source Branch'),
          '#options' => $branched_options,
          '#size' => 1,
          '#weight' => '0',
        ];

        $form['base_branch'] = [
          '#type' => 'select',
          '#title' => $this->t('Target Branch'),
          '#description' => $this->t('Target Branch'),
          '#options' => $branched_options,
          '#size' => 1,
          '#weight' => '0',
        ];

        $form['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Submit'),
        ];
      }
      else {
        $form['markup'] = [
          '#type' => 'markup',
          '#markup' => $this->t("No branches found."),
          '#attached' => [
            'library' => 'drupal_git/drupal_git_global',
          ],
        ];
      }
    }
    else {
      $messenger      = \Drupal::messenger();
      $messenger->addError(t("fatal: not a git repository (or any of the parent directories): .git"));
      $form['markup'] = [
        '#type' => 'markup',
        '#markup' => $this->t("For more info please check README.txt of drupal git module."),
        '#attached' => [
          'library' => 'drupal_git/drupal_git_global',
        ],
      ];
    }

    if ($form_state->getValue('branch_to_compare') && $form_state->getValue('base_branch')) {
      $branch_to_compare = $form_state->getValue('branch_to_compare');
      $base_branch       = $form_state->getValue('base_branch');
      $repo              = new GitRepository(__DIR__);

      $form['markup_data'] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => $this->t("Result for <b><i>git diff @base_branch...@branch_to_compare </i></b>", ["@base_branch" => $base_branch, "@branch_to_compare" => $branch_to_compare]),
        '#items' => empty($repo->getDiffOfBranch($base_branch, $branch_to_compare)) ? [$this->t("Branches are indenticle.")] : $repo->getDiffOfBranch($base_branch, $branch_to_compare),
        '#attributes' => [
          'class' => 'drupal-git',
        ],
        '#wrapper_attributes' => [
          'class' => 'container',
        ],
      ];
      $form['#attached']   = [
        'library' => 'drupal_git/drupal_git_global',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('branch_to_compare'))) {
      $form_state->setErrorByName('branch_to_compare', $this->t('Please select compare branch'));
    }
    if (empty($form_state->getValue('base_branch'))) {
      $form_state->setErrorByName('base_branch', $this->t('Please select base branch'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

}
