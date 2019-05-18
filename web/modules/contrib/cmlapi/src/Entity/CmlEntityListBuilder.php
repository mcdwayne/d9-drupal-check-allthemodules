<?php

namespace Drupal\cmlapi\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Cml entity entities.
 *
 * @ingroup cmlapi
 */
class CmlEntityListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id']    = $this->t('ID');
    $header['date']  = $this->t('Date');
    $header['file']  = $this->t('File');
    $header['name']  = $this->t('View');
    $header['login'] = $this->t('Login');
    $header['ip']    = $this->t('Ip');
    $header['type']  = $this->t('Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\cmlapi\Entity\CmlEntity */
    $row['id'] = $entity->id();
    $time = strtotime($entity->field_cml_date->value);
    $row['date'] = format_date($time, 'custom', 'dM H:i:s');
    $files = $entity->field_file->getValue();
    $files_output = [];
    if (!empty($files)) {
      foreach ($files as $file) {
        $files_output[] = file_load($file['target_id'])->getFilename();
      }
    }
    $row['file']  = implode(', ', $files_output);
    $row['name']  = $this->l(
      'cml',
      new Url(
        'entity.cml.edit_form', array(
          'cml' => $entity->id(),
        )
      )
    );
    $row['login'] = $entity->field_cml_login->value;
    $row['ip']    = $entity->field_cml_ip->value;
    $row['type']  = $entity->field_cml_type->value;

    return $row + parent::buildRow($entity);
  }

}
