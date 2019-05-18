<?php

namespace Drupal\akismet\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\akismet\Entity\FormInterface;
use Drupal\akismet\Utility\AkismetUtilities;

/**
 * Provides a listing of akismet_form entities.
 *
 * @package Drupal\akismet\Controller
 *
 * @ingroup akismet
 */
class FormListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    AkismetUtilities::getAdminAPIKeyStatus();
    AkismetUtilities::displayAkismetTestModeWarning();

    $header['label'] = $this->t('Form');
    $header['protection_mode'] = $this->t('Protection mode');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $akismet_form = $entity->initialize();
    $row['label'] = $entity->label() . ' (' . $entity->id() . ')';
    if (isset($akismet_form['orphan'])) {
      $row['protection_mode'] = t('- orphan -');
    }
    else {
      $row['protection_mode'] = t('Textual analysis (@discard)', [
        '@discard' => $entity->discard ? t('discard') : t('retain'),
      ]);
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritDoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $akismet_form = $entity->initialize();
    $operations = parent::getDefaultOperations($entity);
    if (!empty($akismet_form['orphan'])) {
      \Drupal::messenger()->addMessage(t("%module module's %form_id form no longer exists.", [
        '%form_id' => $entity->id(),
        '%module' => $entity->module,
      ]), 'warning');
      unset($operations['edit']);
    }
    $operations['delete']['title'] = t('Unprotect');
    return $operations;
  }

}
