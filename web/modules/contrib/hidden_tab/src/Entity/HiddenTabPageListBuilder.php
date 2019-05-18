<?php

namespace Drupal\hidden_tab\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\hidden_tab\Entity\Helper\ConfigListBuilderBase;
use Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager;
use Drupal\hidden_tab\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of hidden_tab_pages entities.
 *
 * Also adds the 'layout edit' operation to the default operations.
 *
 * @see \Drupal\hidden_tab\Entity\HiddenTabPageInterface
 */
class HiddenTabPageListBuilder extends ConfigListBuilderBase {

  /**
   * To find list of templates, and show it's label on entity list.
   *
   * @var \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager
   */
  protected $templateMan;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type,
                              EntityStorageInterface $storage,
                              AccountProxyInterface $current_user,
                              HiddenTabTemplatePluginManager $template_man) {
    parent::__construct($entity_type, $storage, $current_user);
    $this->templateMan = $template_man;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container,
                                        EntityTypeInterface $entity_type) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('current_user'),
      $container->get('plugin.manager.hidden_tab_template')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['tab_uri'] = $this->t('Tab Uri');
    $header['secret_uri'] = $this->t('Secret Uri');
    $header['target_entity_type'] = $this->t('Type');
    $header['target_entity_bundle'] = $this->t('Bundle');
    $header['template'] = $this->t('Template');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  protected function unsafeBuildRow(EntityInterface $entity): array {
    /** @var \Drupal\hidden_tab\Entity\HiddenTabPageInterface $entity */
    /** @var \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplateInterface $plugin */

    $row = parent::configRowsBuilder($entity, [
      'id',
      'tab_uri',
      'secret_uri',
      'target_entity_type',
      'target_entity_bundle',
    ]);
    try {
      if ($entity->inlineTemplate()) {
        $row['template'] = $this->t('Inline');
      }
      elseif ($this->templateMan->exists($entity->template())) {
        $row['template'] = $this->templateMan->labelOfPlugin($entity->template());
      }
      else {
        $row['template'] = $this->t('Missing: @missing', [
          '@missing' => $entity->template(),
        ]);
      }
    }
    catch (\Throwable $error0) {
      Utility::renderLog($error0, 'hidden_tab_page', 'template');
      $row['template'] = Utility::WARNING;
    }
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $op = parent::getOperations($entity);
    // Layouts is a VERY dangerous page. It gives access to EVERYTHING.
    if ($this->current_user
      ->hasPermission(Utility::ADMIN_PERMISSION)) {
      $layout = [
        'layout' => [
          'title' => $this->t('Layout'),
          'weight' => 1,
          'url' => Url::fromRoute('entity.hidden_tab_page.layout_form',
            ['hidden_tab_page' => $entity->id()]),
        ],
      ];
      $op = $layout + $op;
    }
    return $op;
  }

}
