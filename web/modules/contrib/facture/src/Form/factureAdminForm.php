<?php

namespace Drupal\facture\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements a facture admin form.
 */
class factureAdminForm extends ConfigFormBase {

    protected $entityTypeManager;

    public function __construct(EntityTypeManagerInterface $entityTypeManager) {
        $this->entityTypeManager = $entityTypeManager;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('entity_type.manager')
        );
    }

    /**
     * {@inheritdoc}.
     */
    public function getFormID() {
        return 'facture_admin_form';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return array('facture.config');
    }

    /**
     * {@inheritdoc}.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

//
//        $form['company'] = array(
//            '#type' => 'details',
//            '#title' => t('Entreprise - Configuration'),
//            '#description' => t('Ces informations figurent en entête et pied de page du document.'),
//            '#open' => TRUE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
//        );
//
//        $company_name = $this->config('facture.config')->get('company_name');
//        $form['company']['company_name'] = [
//            '#type' => 'textfield',
//            '#title'   => $this->t('Dénomination'),
//            '#size' => 60,
//            '#maxlength' => 128,
//            '#required' => TRUE,
//            '#default_value' => $company_name,
//        ];
//
//        $company_email = $this->config('facture.config')->get('company_email');
//        $form['company']['company_email'] = array(
//            '#type' => 'email',
//            '#title' => $this->t('Email'),
//            '#pattern' => '^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$',
//            '#default_value' => $company_email,
//        );
//
//        $company_phone = $this->config('facture.config')->get('company_phone');
//        $form['company']['company_phone'] = array(
//            '#type' => 'tel',
//            '#title' => $this
//                ->t('Téléphone'),
//            '#required' => TRUE,
//            '#pattern' => '^(?:0|\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$',
//            '#default_value' => $company_phone,
//        );
//
//        $company_adress = $this->config('facture.config')->get('company_adress');
//        $form['company']['company_adress'] = [
//            '#type' => 'textfield',
//            '#title'   => $this->t('Adresse'),
//            '#size' => 60,
//            '#maxlength' => 128,
//            '#required' => TRUE,
//            '#default_value' => $company_adress,
//        ];
//
//        $company_postcode = $this->config('facture.config')->get('company_postcode');
//        $form['company']['company_postcode'] = [
//            '#type' => 'textfield',
//            '#title'   => $this->t('Code postale'),
//            '#size' => 60,
//            '#maxlength' => 128,
//            '#required' => TRUE,
//            '#pattern' => '[0-9]{5}',
//            '#default_value' => $company_postcode,
//        ];
//
//        $company_town = $this->config('facture.config')->get('company_town');
//        $form['company']['company_town'] = [
//            '#type' => 'textfield',
//            '#title'   => $this->t('Ville'),
//            '#size' => 60,
//            '#maxlength' => 128,
//            '#required' => TRUE,
//            '#default_value' => $company_town,
//        ];
//
//        $company_web = $this->config('facture.config')->get('company_web');
//        $form['company']['company_web'] = [
//            '#type' => 'textfield',
//            '#title'   => $this->t('Site Web'),
//            '#size' => 60,
//            '#maxlength' => 128,
//            '#default_value' => $company_web,
//        ];
//
//        $company_siret = $this->config('facture.config')->get('company_siret');
//        $form['company']['company_siret'] = [
//            '#type' => 'textfield',
//            '#title'   => $this->t('Numéro siret'),
//            '#size' => 60,
//            '#maxlength' => 128,
//            '#required' => TRUE,
//            '#default_value' => $company_siret,
//        ];
//
//        $company_rcs = $this->config('facture.config')->get('company_rcs');
//        $form['company']['company_rcs'] = [
//            '#type' => 'textfield',
//            '#title'   => $this->t('Numéro RCS'),
//            '#description' => t('ou saisissez une mention type "Dispensé d’immatriculation au registre du commerce et des sociétés (RCS) et au répertoire des métiers (RM)."'),
//            '#size' => 100,
//            '#maxlength' => 128,
//            '#required' => TRUE,
//            '#default_value' => $company_rcs,
//        ];
//
//        $company_vat = $this->config('facture.config')->get('company_vat');
//        $form['company']['company_vat'] = [
//            '#type' => 'textfield',
//            '#title'   => $this->t('Numéro de TVA intracommunautaire'),
//            '#description' => t('ou saisissez une mention type "TVA non applicable, art. 293 B du CGI."'),
//            '#size' => 60,
//            '#maxlength' => 128,
//            '#required' => TRUE,
//            '#default_value' => $company_vat,
//        ];
//
//        $company_logo = $this->config('facture.config')->get('company_logo');
//        $form['company']['company_logo'] = array(
//            '#type' => 'managed_file',
//            '#default_value' => $company_logo,
//            '#title' => t('Logo'),
//            '#description' => t('Upload an image'),
//            '#upload_location' => 'public://facture',
////            '#theme' => 'mymodule_thumb_upload',
//            '#upload_validators' => array(
//                'file_validate_is_image' => array(),
//                'file_validate_extensions' => array('jpg jpeg gif png'),
//                'file_validate_image_resolution' => array('600x400','300x100'),
//            ),
//        );

        $form['facture_default'] = array(
            '#type' => 'details',
            '#title' => t('Facture - Configuration'),
            '#description' => t('Les valeurs par defaut servent à préremplir la facture lors de la création. Ces valeurs peuvent être supplanter pour chaque facture.'),
            '#open' => TRUE, // Controls the HTML5 'open' attribute. Defaults to FALSE.

        );

        $facture_devise_default = $this->config('facture.config')->get('facture_devise_default');
        $form['facture_default']['facture_devise_default'] = [
            '#type' => 'textfield',
            '#title'   => $this->t('Devise par défaut'),
            '#size' => 2,
            '#maxlength' => 1,
            '#default_value' => $facture_devise_default,
        ];

        $facture_vat_default = $this->config('facture.config')->get('facture_vat_default');
        $form['facture_default']['facture_vat_default'] = [
            '#type' => 'textfield',
            '#title'   => $this->t('Taux de TVA par défaut'),
            '#suffix' => t('%'),
            '#size' => 4,
            '#maxlength' => 3,
            '#default_value' => $facture_vat_default,
            '#disabled' => TRUE,
        ];

        $facture_limit_date_default = $this->config('facture.config')->get('facture_limit_date_default');
        $form['facture_default']['facture_limit_date_default'] = [
            '#type' => 'textfield',
            '#title'   => $this->t('Date limite de règlement relative par défaut'),
            '#description' => t('Décrire une durée par rapport à la date de la facture. Exemple "+90 days" (90 jours depuis le jour de création du champ) ou "+1 Saturday" (le samedi suivant). Consulter strtotime pour plus de détails.'),
            '#size' => 60,
            '#maxlength' => 128,
            '#default_value' => $facture_limit_date_default,
        ];

        $facture_penality_rate_default = $this->config('facture.config')->get('facture_penality_rate_default');
        $form['facture_default']['facture_penality_rate_default'] = [
            '#type' => 'number',
            '#title'   => $this->t('Taux de pénalité par defaut'),
            '#description' => t('en l\'abscence de paiement.'),
            '#suffix' => t('%'),
            '#size' => 2,
            '#maxlength' => 10,
            '#default_value' => $facture_penality_rate_default,
        ];

        $facture_zerofill = $this->config('facture.config')->get('facture_zerofill');
        $form['facture_default']['facture_zerofill'] = [
            '#type' => 'number',
            '#title'   => $this->t('Numéro de facture zeroFill'),
            '#description' => t('Si vous souhaitez qu\'un numéro de facture s\'affiche sous la forme "0001", renseignez 4. Si vous souhaitez uniquement afficher le numéro de facture "1", laissez vide.'),
            '#size' => 1,
            '#min' => 0,
            '#max' => 9,
            '#maxlength' => 1,
            '#default_value' => $facture_zerofill,
        ];

        $facture_number_prefix = $this->config('facture.config')->get('facture_number_prefix');
        $form['facture_default']['facture_number_prefix'] = [
            '#type' => 'textfield',
            '#title'   => $this->t('Prefixe du numéro de facture'),
            '#description' => t('Si vous souhaitez qu\'un numéro de facture s\'affiche sous la forme "20180001", saisissez "&lt;date&gt;Y&lt;/date&gt;". Remplissez 4 dans le champ zerofill ci-dessus pour des valeurs de zéro supplémentaires. Toutes les valeurs de <a href="http://www.php.net/date">http://www.php.net/date</a> peuvent être entrées ici entre les balises "&lt;date&gt;&lt;/date&gt;".'),
            '#size' => 60,
            '#min' => 0,
            '#default_value' => $facture_number_prefix,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
//        var_dump(\Drupal::service('config.factory')->get("facture.config")->get("company_name"));die;
        $this->config('facture.config')
            ->set('facture_devise_default', $form_state->getValue('facture_devise_default'))
            ->set('facture_vat_default', $form_state->getValue('facture_vat_default'))
            ->set('facture_limit_date_default', $form_state->getValue('facture_limit_date_default'))
            ->set('facture_zerofill', $form_state->getValue('facture_zerofill'))
            ->set('facture_number_prefix', $form_state->getValue('facture_number_prefix'))
            ->set('facture_penality_rate_default', $form_state->getValue('facture_penality_rate_default'))

            ->save();

        $this->entityTypeManager->getViewBuilder('block')->resetCache();

        parent::submitForm($form, $form_state);
    }
}
