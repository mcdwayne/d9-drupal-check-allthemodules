<?php

namespace Drupal\administration_language_negotiation\Plugin\LanguageNegotiation;

use Drupal\administration_language_negotiation\AdministrationLanguageNegotiationConditionManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language from a administration language negotiation.
 *
 * @LanguageNegotiation(
 *   weight = -4,
 *   name = @Translation("Administration language"),
 *   description = @Translation("A predefined language is set based on predefined customizable paths."),
 *   id = Drupal\administration_language_negotiation\Plugin\LanguageNegotiation\LanguageNegotiationAdministrationLanguage::METHOD_ID,
 *   types = {\Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE},
 *   config_route_name = "administration_language_negotiation.negotiation_administration_language"
 * )
 */
class LanguageNegotiationAdministrationLanguage extends LanguageNegotiationMethodBase implements
    ContainerFactoryPluginInterface
{
    /**
     * The language negotiation method id.
     */
    const METHOD_ID = 'administration-language-negotiation';

    /**
     * The condition manager.
     *
     * @var \Drupal\administration_language_negotiation\AdministrationLanguageNegotiationConditionManager
     */
    protected $conditionManager;

    /**
     * Constructs a RequestPath condition plugin.
     *
     * @param \Drupal\administration_language_negotiation\AdministrationLanguageNegotiationConditionManager $manager
     *   The current path.
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param array $plugin_definition
     *   The plugin implementation definition.
     */
    public function __construct(
        AdministrationLanguageNegotiationConditionManager $manager,
        array $configuration,
        $plugin_id,
        array $plugin_definition
    ) {
        $this->configuration = $configuration;
        $this->conditionManager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $container->get('plugin.manager.administration_language_negotiation_condition'),
            $configuration,
            $plugin_id,
            $plugin_definition
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getLangcode(Request $request = null)
    {
        // Run only for allowed users.
        if ($this->currentUser->hasPermission('use administration language negotiation')) {
            $config = $this->config->get('administration_language_negotiation.negotiation');
            $manager = $this->conditionManager;

            foreach ($manager->getDefinitions() as $def) {
                /** @var \Drupal\Core\Executable\ExecutableInterface $condition_plugin */
                $condition_plugin = $manager->createInstance(
                    $def['id'],
                    $config->get()
                );

                if (!$manager->execute($condition_plugin)) {
                    return $this->currentUser->getPreferredAdminLangcode();
                }
            }
        }

        return false;
    }
}
