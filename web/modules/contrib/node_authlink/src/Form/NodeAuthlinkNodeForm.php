<?php

namespace Drupal\node_authlink\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class NodeAuthlinkNodeForm.
 */
class NodeAuthlinkNodeForm extends FormBase {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * Constructs a new NodeAuthlinkNodeForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory
  ) {
    $this->configFactory = $config_factory;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_authlink_node_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    if (!is_numeric($node)) {
      throw new NotFoundHttpException();
    }

    $config = $this->configFactory->get('node_authlink.settings');
    $config_grants = $config->get('grants');

    $node = Node::load($node);

    $form['disclaimer'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Use the following form to manage anonymous authlinks for performing View, Update or Delete tasks without any further authentication. The links available will depends on the configuration of this content type.') . '</p>',
    ];

    if (isset($config_grants[$node->bundle()])) {

      foreach ($config_grants[$node->bundle()] as $op) {
        if (!$op) {
          continue;
        }

        // If $op is view, load all revisions.
        $has_revisions = FALSE;
        if ($op == 'view') {
          $has_revisions = TRUE;
          $node_storage = \Drupal::entityManager()->getStorage('node');

          $result = $node_storage->getQuery()
            ->allRevisions()
            ->condition($node->getEntityType()->getKey('id'), $node->id())
            ->sort($node->getEntityType()->getKey('revision'), 'DESC')
            ->range(0, 50)
            ->execute();
          if (!empty($result)) {
            $revision_options = [];
            foreach ($result as $vid => $nid) {

              $revision = $node_storage->loadRevision($vid);
              $langcode = $node->language()->getId();
              // Only show revisions that are affected by the language that is being
              // displayed.
              if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {

                // Use revision link to link to revisions that are not active.
                $dateFormatter = \Drupal::service('date.formatter');
                $date = $dateFormatter->format($revision->revision_timestamp->value, 'short');

                if ($revision->isDefaultRevision()) {
                  $revision_options[$vid] = [
                    'text' =>  $this->t('Current revision'),
                    'url' => node_authlink_get_url($node, $op),
                  ];
                }
                else {
                  $revision_options[$vid] = [
                    'text' =>  $date,
                    'url' => node_authlink_get_url($node, $op, $vid),
                  ];
                }
              }
            }
          }
        }

        if ($has_revisions) {
          $form['revisions'] = [
            '#type' => 'select',
            '#title' => $this->t('Revisions'),
            '#options' => [],
          ];
          // @todo: use a table instead.
          foreach ($revision_options as $vid => $revision_option) {
            $form['revisions']['#options'][$vid] = $revision_option['text'];

            $form['link_' . $op . '_' . $vid] = [
              '#type' => 'item',
              '#markup' => "<p><strong>" . $op . "</strong>: " . $revision_option['url'] . "</p>",
              '#states' => [
                'visible' => [
                  '[name="revisions"]' => ['value' => $vid],
                ],
              ],
            ];
          }
        }
        else {
          $url = node_authlink_get_url($node, $op);
          if ($url) {
            // @todo: use a table instead.
            $form['link_'.$op] = [
              '#type' => 'item',
              '#markup' => "<p><strong>$op</strong>: $url</p>",
            ];
          }
        }

      }

      if (node_authlink_load_authkey($node->id())) {
        $form['delete'] = [
          '#type' => 'submit',
          '#value' => $this->t('Delete authlink'),
          '#weight' => 10,
          '#submit' => ['::deleteAuthlink'],
        ];
      }
      else {
        $form['create'] = [
          '#type' => 'submit',
          '#value' => $this->t('Create authlink'),
          '#weight' => 10,
          '#submit' => ['::createAuthlink']
        ];
      }

    }

    return $form;
  }

  /**
   * Create authlink submit callback.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function createAuthlink(array &$form, FormStateInterface $form_state) {
    node_authlink_create($form_state->getBuildInfo()['args'][0]);
  }

  /**
   * Delete authlink submit callback.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function deleteAuthlink(array &$form, FormStateInterface $form_state) {
    node_authlink_delete($form_state->getBuildInfo()['args'][0]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }

  }

  /**
   * Checks that node_authlink was enabled for this content type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param $node
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function access(AccountInterface $account, $node) {
    if (is_numeric($node)) {
      $node = Node::load($node);
      $enable = $this->config('node_authlink.settings')->get('enable');
      if (isset($enable[$node->bundle()]) && $enable[$node->bundle()]) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }
}
