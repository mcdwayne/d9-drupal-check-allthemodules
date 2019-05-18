<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Class KeyPairImportForm.
 *
 * Responsible for key importing via user uploaded public key.
 *
 * @package Drupal\aws_cloud\Form\Ec2
 */
class KeyPairImportForm extends AwsCloudContentForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cloud_context = '') {
    /* @var $entity \Drupal\aws_cloud\Entity\Ec2\KeyPair */
    $form = parent::buildForm($form, $form_state);

    $this->awsEc2Service->setCloudContext($cloud_context);

    $entity = $this->entity;

    $form['cloud_context'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Cloud ID'),
      '#maxlength'     => 255,
      '#size'          => 60,
      '#default_value' => !$entity->isNew() ? $entity->getCloudContext() : $cloud_context,
      '#required'      => TRUE,
      '#weight'        => -5,
      '#disabled'      => TRUE,
    ];

    $form['key_pair_name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Key Pair Name'),
      '#maxlength'     => 255,
      '#size'          => 60,
      '#default_value' => $entity->getKeyPairName(),
      '#required'      => TRUE,
      '#weight'        => -5,
    ];

    $form['key_pair_public_key'] = [
      '#type' => 'file',
      '#title' => 'Public Key',
      '#description' => t('Upload your public key'),
      '#weight' => -4,
    ];

    $form['langcode'] = [
      '#title' => t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    ];

    $form['actions'] = $this->actions($form, $form_state, $cloud_context);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Custom validation for file upload.
    $all_files = $this->getRequest()->files->get('files', []);
    if (!empty($all_files['key_pair_public_key'])) {
      $file_upload = $all_files['key_pair_public_key'];
      if ($file_upload->isValid()) {
        $form_state->setValue('key_pair_public_key', $file_upload->getRealPath());
        return;
      }
    }

    $form_state->setErrorByName('key_pair_public_key', $this->t('The file could not be uploaded.'));
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {

    /* @var \Drupal\aws_cloud\Entity\Ec2\KeyPair $entity */
    $entity = $this->entity;

    if ($path = $form_state->getValue('key_pair_public_key')) {
      $handle = fopen($path, "r");
      $key_material = fread($handle, filesize($path));
      fclose($handle);

      $result = $this->awsEc2Service->importKeyPair([
        'KeyName' => $entity->getKeyPairName(),
        'PublicKeyMaterial' => $key_material,
      ]);

      // Following AWS specification and not storing key material.
      if (isset($result['KeyName'])
        && ($entity->setKeyFingerprint($result['KeyFingerprint']))
        && ($entity->save())
      ) {
        $message = $this->t('The @label "@key_pair_name" has been imported.', [
          '@label' => $entity->getEntityType()->getLabel(),
          '@key_pair_name' => $entity->getKeyPairName(),
        ]);
        $this->messenger->addMessage($message);

        $form_state->setRedirect('entity.aws_cloud_key_pair.canonical', [
          'cloud_context' => $entity->getCloudContext(),
          'aws_cloud_key_pair' => $entity->id(),
        ]);

      }
      else {
        $message = $this->t('The @label "@key_pair_name" couldn\'t create.', [
          '@label' => $entity->getEntityType()->getLabel(),
          '@key_pair_name' => $entity->getKeyPairName(),
        ]);
        $this->messenger->addError($message);
        $form_state->setRedirect('view.aws_cloud_key_pairs.page_1', ['cloud_context' => $entity->getCloudContext()]);
      }

    }
  }

}
