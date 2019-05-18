<?php

namespace Drupal\adback_solution_to_adblock\Controller;

use Drupal\adback_solution_to_adblock\ApiSdk\AdbackSolutionToAdblockGeneric;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Class AdbackController
 */
class AdbackController implements ContainerInjectionInterface
{
    public static function create(ContainerInterface $container)
    {
        return new static();
    }

    /**
     * @return array
     */
    public function statistics()
    {
        $adback = AdbackSolutionToAdblockGeneric::getInstance();
        if (!$adback->isConnected()) {
            return $this->displayLoginPage();
        }

        $path = __DIR__ . '/../templates/statistics.html.twig';
        $template = file_get_contents($path);
        $token = $adback->getToken()->access_token;
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

        return [
            'statistics' => [
                '#type' => 'inline_template',
                '#template' => $template,
                '#context' => [
                    'locale' => $language,
                    'access_token' => $token,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function message()
    {
        $adback = AdbackSolutionToAdblockGeneric::getInstance();
        if (!$adback->isConnected()) {
            return $this->displayLoginPage();
        }

        $path = __DIR__ . '/../templates/message.html.twig';
        $template = file_get_contents($path);
        $token = $adback->getToken()->access_token;
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

        return [
            'message' => [
                '#type' => 'inline_template',
                '#template' => $template,
                '#context' => [
                    'locale' => $language,
                    'access_token' => $token,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function placement()
    {
        $adback = AdbackSolutionToAdblockGeneric::getInstance();
        if (!$adback->isConnected()) {
            return $this->displayLoginPage();
        }

        $path = __DIR__ . '/../templates/placement.html.twig';
        $template = file_get_contents($path);
        $token = $adback->getToken()->access_token;
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

        return [
            'message' => [
                '#type' => 'inline_template',
                '#template' => $template,
                '#context' => [
                    'locale' => $language,
                    'access_token' => $token,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function settings()
    {
        $adback = AdbackSolutionToAdblockGeneric::getInstance();
        if (!$adback->isConnected()) {
            return $this->displayLoginPage();
        }

        $mail = \Drupal::config('system.site')->get('mail');
        $path = __DIR__ . '/../templates/settings.html.twig';
        $template = file_get_contents($path);
        $token = $adback->getToken()->access_token;
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

        return [
            'settings' => [
                '#type' => 'inline_template',
                '#template' => $template,
                '#context' => [
                    'access_token' => $token,
                    'email' => $mail,
                    'locale' => $language,
                ],
                '#attached' => array(
                    'library' => array(
                        'adback_solution_to_adblock/adback_solution_to_adblock.ab-admin',
                    ),
                ),
            ],
        ];
    }

    /**
     * @return RedirectResponse
     */
    public function tokenSave()
    {
        if (array_key_exists('access_token', $_GET)) {
            $accessToken = $_GET['access_token'];
            $adback = AdbackSolutionToAdblockGeneric::getInstance();

            $adback->saveToken([
                'access_token' => $accessToken,
                'refresh_token' => '',
            ]);
            drupal_set_message(t('The AdBack token has been successfully saved.'), 'status');
        }

        return new RedirectResponse(Url::fromRoute('adback_solution_to_adblock.statistics')->toString());
    }

    /**
     * @return RedirectResponse
     */
    public function logout()
    {
        $adback = AdbackSolutionToAdblockGeneric::getInstance();
        $adback->logout();

        return new RedirectResponse(Url::fromRoute('adback_solution_to_adblock.statistics')->toString());
    }

    /**
     * @return array
     */
    protected function displayLoginPage()
    {
        global $base_url;
        $mail = \Drupal::config('system.site')->get('mail');
        $path = __DIR__ . '/../templates/login.html.twig';
        $template = file_get_contents($path);

        return [
            'login' => [
                '#type' => 'inline_template',
                '#template' => $template,
                '#context' => [
                    'email' => $mail,
                    'base_url' => $base_url,
                ],
                '#attached' => [
                    'library' => [
                        'adback_solution_to_adblock/adback_solution_to_adblock.ab-admin',
                    ],
                ],
            ]
        ];
    }
}
