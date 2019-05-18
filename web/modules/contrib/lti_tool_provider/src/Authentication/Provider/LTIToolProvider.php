<?php

namespace Drupal\lti_tool_provider\Authentication\Provider;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\lti_tool_provider\Entity\Consumer;
use Drupal\user\Entity\User;
use Exception;
use OAuthProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Oauth authentication provider for LTI Tool Provider.
 */
class LTIToolProvider implements AuthenticationProviderInterface
{

    /**
     * The configuration factory.
     *
     * @var ConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * The entity type manager.
     *
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * A logger instance.
     *
     * @var LoggerChannelFactory
     */
    protected $loggerFactory;

    /**
     * The module handler.
     *
     * @var ModuleHandlerInterface
     */
    protected $moduleHandler;

    /**
     * The private temp store for storing LTI context info.
     *
     * @var PrivateTempStore
     */
    protected $tempStore;

    /**
     * The consumer entity matching the LTI request.
     *
     * @var Consumer
     */
    protected $consumerEntity;

    /**
     * The LTI context, i.e. the request parameters.
     *
     * @var array
     */
    protected $context;

    /**
     * Constructs a HTTP basic authentication provider object.
     *
     * @param ConfigFactoryInterface $config_factory
     *   The configuration factory.
     * @param EntityTypeManagerInterface $entity_type_manager
     *   The entity manager.
     * @param LoggerChannelFactory $logger_factory
     *   A logger instance.
     * @param ModuleHandlerInterface $module_handler
     *   The module handler.
     * @param PrivateTempStoreFactory $tempStoreFactory
     *   The temp store factory.
     */
    public function __construct(
        ConfigFactoryInterface $config_factory,
        EntityTypeManagerInterface $entity_type_manager,
        LoggerChannelFactory $logger_factory,
        ModuleHandlerInterface $module_handler,
        PrivateTempStoreFactory $tempStoreFactory
    ) {
        $this->configFactory = $config_factory;
        $this->entityTypeManager = $entity_type_manager;
        $this->loggerFactory = $logger_factory->get('lti_tool_provider');
        $this->moduleHandler = $module_handler;
        $this->tempStore = $tempStoreFactory->get('lti_tool_provider');
    }

    /**
     * {@inheritdoc}
     *
     * @see https://www.imsglobal.org/wiki/step-1-lti-launch-request
     */
    public function applies(Request $request)
    {
        $lti_message_type = $request->request->get('lti_message_type');
        $lti_version = $request->request->get('lti_version');
        $oauth_consumer_key = $request->request->get('oauth_consumer_key');
        $resource_link_id = $request->request->get('resource_link_id');

        if (!$request->isMethod('POST')) {
            return false;
        }

        if ($lti_message_type !== 'basic-lti-launch-request') {
            return false;
        }

        if (!in_array($lti_version, ['LTI-1p0', 'LTI-1p2'])) {
            return false;
        }

        if (empty($oauth_consumer_key)) {
            return false;
        }

        if (empty($resource_link_id)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(Request $request)
    {
        try {
            $this->context = $request->request->all();
            $this->moduleHandler->alter('lti_tool_provider_launch', $this->context);

            $this->validateOauthRequest();
            $user = $this->provisionUser();

            $this->moduleHandler->invokeAll('lti_tool_provider_authenticated', [$user, $this->context]);
            $this->userLoginFinalize($user);

            $this->context['consumer_id'] = $this->consumerEntity->id();
            $this->context['consumer_label'] = $this->consumerEntity->label();
            $this->tempStore->set('context', $this->context);

            return $user;
        }
        catch (Exception $e) {
            $this->loggerFactory->warning($e->getMessage());
            $this->sendLtiError($e->getMessage());

            return null;
        }
    }

    /**
     * Validate the OAuth request.
     */
    private function validateOauthRequest()
    {
        $provider = new OAuthProvider(["oauth_signature_method" => OAUTH_SIG_METHOD_HMACSHA1]);
        $provider->consumerHandler([$this, 'consumerHandler']);
        $provider->timestampNonceHandler([$this, 'timestampNonceHandler']);
        $provider->isRequestTokenEndpoint(false);
        $provider->is2LeggedEndpoint(true);
        $provider->checkOAuthRequest();
    }

    /**
     * Looks up the consumer entity that matches the consumer key.
     *
     * @param $provider
     * @return int
     *   - OAUTH_OK if validated.
     *   - OAUTH_CONSUMER_KEY_UNKNOWN if not.
     */
    public function consumerHandler($provider)
    {
        try {
            $ids = $this->entityTypeManager->getStorage('lti_tool_provider_consumer')
                ->getQuery()
                ->condition('consumer_key', $provider->consumer_key, '=')
                ->execute();

            if (!count($ids)) {
                return OAUTH_CONSUMER_KEY_UNKNOWN;
            }

            $this->consumerEntity = $this->entityTypeManager->getStorage('lti_tool_provider_consumer')->load(key($ids));
            $provider->consumer_secret = $this->consumerEntity->get('consumer_secret')->getValue()[0]['value'];
        }
        catch (InvalidPluginDefinitionException $e) {
            return OAUTH_CONSUMER_KEY_UNKNOWN;
        }
        catch (PluginNotFoundException $e) {
            return OAUTH_CONSUMER_KEY_UNKNOWN;
        }

        return OAUTH_OK;
    }

    /**
     * Validate nonce.
     *
     * @param $provider
     * @return int
     *   - OAUTH_OK if validated.
     *   - OAUTH_BAD_TIMESTAMP if timestamp too old.
     *   - OAUTH_BAD_NONCE if nonce has been used.
     */
    public function timestampNonceHandler($provider)
    {
        // Verify timestamp has been set.
        if (!isset($provider->timestamp)) {
            return OAUTH_BAD_TIMESTAMP;
        }

        // Verify nonce timestamp is not older than now - nonce interval.
        if ($provider->timestamp < (time() - LTI_TOOL_PROVIDER_NONCE_INTERVAL)) {
            return OAUTH_BAD_TIMESTAMP;
        }

        // Verify nonce timestamp is not newer than now + nonce interval.
        if ($provider->timestamp > (time() + LTI_TOOL_PROVIDER_NONCE_INTERVAL)) {
            return OAUTH_BAD_TIMESTAMP;
        }

        // Verify nonce and consumer_key has been set.
        if (!isset($provider->nonce) || !isset($provider->consumer_key)) {
            return OAUTH_BAD_NONCE;
        }

        try {
            $storage = $this->entityTypeManager->getStorage('lti_tool_provider_nonce');

            // Verify that current nonce is not a duplicate.
            $nonce_exists = $storage->getQuery()->condition('nonce', $provider->nonce, '=')->execute();
            if (count($nonce_exists)) {
                return OAUTH_BAD_NONCE;
            }

            // Store nonce in database.
            $storage->create(
                [
                    'nonce' => $provider->nonce,
                    'consumer_key' => $provider->consumer_key,
                    'timestamp' => $provider->timestamp,
                ]
            )->save();
        }
        catch (Exception $e) {
            $this->loggerFactory->warning($e->getMessage());

            return OAUTH_BAD_NONCE;
        }

        return OAUTH_OK;
    }

    /**
     * Get the user that matches the LTI request context info.
     *
     * @return User
     *   Returns a user corresponding to the LTI request.
     * @throws Exception
     */
    protected function provisionUser()
    {
        try {
            $name = 'ltiuser';
            $mail = 'ltiuser@invalid';

            $name_param = $this->consumerEntity->get('name')->getValue()[0]['value'];
            if (isset($this->context[$name_param]) && !empty($this->context[$name_param])) {
                $name = $this->context[$name_param];
            }

            $mail_param = $this->consumerEntity->get('mail')->getValue()[0]['value'];
            if (isset($this->context[$mail_param]) && !empty($this->context[$mail_param])) {
                $mail = $this->context[$mail_param];
            }

            if ($users = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $name, 'status' => 1])) {
                $user = reset($users);
            }
            elseif ($users = $this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $mail, 'status' => 1])) {
                $user = reset($users);
            }
            else {
                $user = User::create();
                $user->setUsername($name);
                $user->setEmail($mail);
                $user->setPassword(user_password());
                $user->enforceIsNew();
                $user->activate();

                $this->moduleHandler->invokeAll('lti_tool_provider_create_user', [$user, $this->context]);

                $user->save();
            }

            return $user;
        }
        catch (Exception $e) {
            $this->loggerFactory->warning($e->getMessage());
        }

        throw new Exception('Unable to provision user.');
    }

    /**
     * Finalizes the user login.
     *
     * @param User $user
     *   The user.
     */
    protected function userLoginFinalize(User $user)
    {
        user_login_finalize($user);
    }

    /**
     * Send an error back to the LMS.
     *
     * @param string $message
     *   The error message to send.
     */
    protected function sendLtiError($message)
    {
        if (isset($this->context['launch_presentation_return_url']) && !empty($this->context['launch_presentation_return_url'])) {
            $url = Url::fromUri($this->context['launch_presentation_return_url'])
                ->setOption(
                    'query',
                    [
                        'lti_errormsg' => $message,
                    ]
                )
                ->setAbsolute(true)
                ->toString();

            $response = new RedirectResponse($url);
            $response->send();
        }
    }
}
