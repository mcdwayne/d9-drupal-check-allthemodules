<?Php

namespace Drupal\ipquery;

use Drupal\Core\Site\Settings;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class QueryService.
 */
class QueryService extends BaseService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The HTTP request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The ipquery.settings configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The cached results.
   *
   * @var array
   */
  protected $cached;

  /**
   * QueryService constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP Request.
   */
  public function __construct(Connection $database, RequestStack $request, ConfigFactoryInterface $config_factory) {
    $this->database = $database;
    $this->request = $request;
    $this->config = $config_factory->get('ipquery.settings');
  }

  /**
   * Query the ipquery table.
   *
   * @param string|null $ip
   *   The IP to find.
   */
  public function query($ip = NULL) {
    if (!isset($this->cached[$ip])) {
      // Get the IP to lookup, which is either the passed in IP, the debug IP,
      // or the request IP. Usually it's the request IP.
      $lookup_ip = $ip;
      if (!$lookup_ip) {
        $request = $this->request->getCurrentRequest();
        if ($this->config->get('debug_ip')) {
          $lookup_ip = $request->query->get('ip');
          if (!$lookup_ip) {
            // Allow the developer to put this in settings.php. Note that
            // the config option and settings value must both be set to do this.
            $lookup_ip = Settings::get('ipquery_debug_ip');
          }
        }
        if (!$lookup_ip) {
          $lookup_ip = $request->getClientIp();
          if ($lookup_ip == '::1') {
            $lookup_ip = $this->config->get('localhost') ?: '127.0.0.1';
          }
        }
      }

      // Check if this is an IPv4 or IPv6 query.
      if (filter_var($lookup_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        // Make sure IPv6 is supported.
        if (!$this->isIpv6Supported()) {
          return NULL;
        }

        // Get IPv6 number.
        list($ip_number_left, $ip_number_right) = $this->ipToLong($lookup_ip);

        // Query the database for IP's in this range.
        $data = $this->database
          ->query('SELECT t.* FROM (SELECT * FROM {ipquery6} WHERE ip_low_left = :left AND ip_low_right <= :right ORDER BY ip_low_left, ip_low_right DESC LIMIT 1) AS t WHERE ip_high_left >= :left', [
            ':left' => $ip_number_left,
            ':right' => $ip_number_right,
          ])
          ->fetchAll();
        if (!$data || $data['country'] == '-') {
          $data = $this->database
            ->query('SELECT * FROM {ipquery6} WHERE ip_low_left <= :left AND ip_high_left >= :left ORDER BY ip_low_left, ip_low_right DESC LIMIT 1', [
              ':left' => $ip_number_left,
              ':right' => $ip_number_right,
            ])
            ->fetchAll();
        }
      }
      else {
        // Get IPv4 number.
        $ip_number = ip2long($lookup_ip);

        // Query the database for IP's in this range.
        $data = $this->database
          ->query('SELECT t.* FROM (SELECT * FROM {ipquery} WHERE ip_low <= :ip ORDER BY ip_low DESC LIMIT 1) AS t WHERE ip_high >= :ip', [
            ':ip' => $ip_number,
          ])
          ->fetchAll();
      }

      // Save the results.
      $this->cached[$ip] = count($data) ? $data[0] : [];
    }

    return $this->cached[$ip];
  }

}
