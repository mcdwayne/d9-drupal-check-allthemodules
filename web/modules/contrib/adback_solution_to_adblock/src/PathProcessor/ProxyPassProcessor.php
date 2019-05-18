<?php

namespace Drupal\adback_solution_to_adblock\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ProxyPassProcessor
 */
class ProxyPassProcessor implements InboundPathProcessorInterface
{
    /**
     * @param         $path
     * @param Request $request
     *
     * @return string
     */
    public function processInbound($path, Request $request)
    {
        $config = \Drupal::config('adback_solution_to_adblock.endpoints');

        $endpoints = [
            $config->get('end_point'),
            $config->get('old_end_point'),
            $config->get('next_end_point'),
        ];

        foreach ($endpoints as $endpoint) {
            if (strpos($path, '/' . $endpoint . '/') === 0) {
                $names = preg_replace('|^\/' . $endpoint . '\/|', '', $path);
                $names = str_replace('/',':', $names);

                return "/$endpoint/$names";
            }
        }

        return $path;
    }

}
