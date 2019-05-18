<?php

namespace Drupal\cctags\Form;

use Drupal\block\Entity\Block;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoginDisableSettingsForm.
 *
 * @package Drupal\cctags\Form
 */
class CctagsDeleteItemForm extends ConfirmFormBase {

  protected $configFactory;

  /**
   * The ID of the item to delete.
   *
   * @var string
   */
  protected $cctid;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $configFactory) {
    $this->configFactory = $configFactory->getEditable('cctags.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('cctags.settings_form');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cctags_delete_item_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('Cancel');
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $conn = Database::getConnection();

    $block = Block::load('cctagsblock_'. $this->cctid);
    if($block) {
      \Drupal::entityManager()->getStorage('block')->delete(array('cctagsblock_'. $this->cctid => $block));
    }
    $conn->delete('cctags')
      ->condition('cctid', $this->cctid)
      ->execute();

    $form_state->setRedirect('cctags.settings_form');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cctid = NULL) {
    $this->cctid = $cctid;
//    $form = array();
    return parent::buildForm($form, $form_state);
  }

}
