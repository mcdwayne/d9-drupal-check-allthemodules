<?php

namespace Drupal\shopify\Command;

use Drupal\Console\Command\ContainerAwareCommand;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ShopifyCommand.
 */
class ShopifyCommand extends ContainerAwareCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('shopify:api')
      ->setDescription($this->trans('View/Create/Update/Delete a Shopify resource.'))
      ->addArgument('method', InputArgument::REQUIRED, $this->trans('Either GET, POST, PUT, or DELETE.'))
      ->addArgument('resource', InputArgument::REQUIRED, $this->trans('Resource, such as "product", "order", etc.'))
      ->addArgument('opts', InputArgument::OPTIONAL, $this->trans('Options to pass to the API request.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $client = shopify_api_client();
    $opts = $input->getArgument('opts');
    parse_str($opts, $opts);

    if (in_array($input->getArgument('method'), ['post', 'put'])) {
      $opts['form_params'] = $opts;
    }

    $response = $client->request($input->getArgument('method'), $input->getArgument('resource'), (array) $opts);
    if ($response instanceof Response) {
      $output->write($response->getBody()->getContents(), TRUE);
    }
  }

}
