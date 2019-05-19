<?php

namespace Drupal\wisski_salz\Plugin\wisski_salz\Engine;

use EasyRdf_Sparql_Client;
use EasyRdf_Namespace;
use EasyRdf_Http;
use EasyRdf_Format;
use EasyRdf_Graph;
use EasyRdf_Exception;
use EasyRdf_Utils;
use EasyRdf_Sparql_Result;

/**
* This is a subclass of EasyRdf_Sparql_client that overrides
* the communication with the sparql endpoint.
* This is necessary for Sesame as the Sesame restful interface does not fully
* support the sparql 1.1 specification
*/
class WissKI_Sparql_Client extends EasyRdf_Sparql_Client {

  /**
  * Internal function to make an HTTP request to SPARQL endpoint
  * copy from original EasyRdf_Client with Sesame-specific overrides
  * this is NOT the function to be called from outside @see requestSPARQL
  *
  * @ignore
  */
  protected function request($type, $query) {

    // Check for undefined prefixes
    $prefixes = '';
    // @TODO: Check - this should not happen every time I query something, this is very 
    // inefficient. Just check it in case of updates!
    foreach (EasyRdf_Namespace::namespaces() as $prefix => $uri) {
      if (strpos($query, "$prefix:") !== false and strpos($query, "PREFIX $prefix:") === false) {
        $prefixes .=  "PREFIX $prefix: <$uri>\n";
      }
    }
    
    $client = EasyRdf_Http::getDefaultHttpClient();
    $client->resetParameters();
    $client->setConfig(array(
        'maxredirects'    => 5,
        'useragent'       => 'EasyRdf_Http_Client',
        //we change the timeout from 10 secs since some of our requests will necessarily take much longer
        'timeout'         => 600,
    ));

    // Tell the server which response formats we can parse
    $accept = EasyRdf_Format::getHttpAcceptHeader(
    array(
      'application/sparql-results+json' => 1.0,
      'application/sparql-results+xml' => 0.8
    )
    );

		$client->setHeaders('Accept', $accept);

		if ($type == 'update') {

      // this is where Sesame differs
      // it does not accept POST directly as described in
      // https://www.w3.org/TR/2013/REC-sparql11-protocol-20130321/#query-operation
			$client->setMethod('POST');
			$client->setUri($this->getUpdateUri());
			$encodedQuery = 'update='.urlencode($prefixes . $query);
			$client->setRawData($encodedQuery);
			$client->setHeaders('Content-Type', 'application/x-www-form-urlencoded;charset=utf-8');
	
		} elseif ($type == 'query') {
				// Use GET if the query is less than 2kB
				// 2046 = 2kB minus 1 for '?' and 1 for NULL-terminated string on server
				$encodedQuery = 'query='.rawurlencode($prefixes . $query);
#				drupal_set_message(json_encode($query, JSON_UNESCAPED_SLASHES));        
        /*  we do not use GET as it leads to corrupted non-ASCII chars the way
            it is programmed atm.
            we just always use POST as an interim patch until we know the exact
            problem.
            Obsolete: we found a the trick by applying json encoding first, 
            see below
            Obsolete: we encode non-ASCII chars in the escapeSparqlLiteral()
            function now. Such chars should only occur in the literals...
        */
        if (strlen($encodedQuery) + strlen($this->getQueryUri()) <= 2046) {
						$client->setMethod('GET');
#						drupal_set_message("war: " . $query);

						// json_encode should help in case of get!
/*						$query = substr(json_encode($query, JSON_UNESCAPED_SLASHES), 1, -1);
						// however it messes up some chars (this list may not be complete!)
            $messed_up_chars = array(
              '\t' => "\t",
              '\n' => "\n",
              '\r' => "\r",
              '\"' => '"',
            );
						$query = strtr($query, $messed_up_chars);

						// now we have to encode it to url
						$encodedQuery = 'query='.rawurlencode($prefixes . $query);
*/
						$client->setUri($this->getQueryUri().'?'. $encodedQuery);

				} else {
						// Fall back to POST instead (which is un-cacheable)
						$client->setMethod('POST');
						$client->setUri($this->getQueryUri());
						$client->setRawData($encodedQuery);
						$client->setHeaders('Content-Type', 'application/x-www-form-urlencoded;charset=utf-8');
        }
		}
		$response = $client->request();
		//if ($type === 'update') dpm($response,$encodedQuery);
		if ($response->getStatus() == 204) {
			// No content
			return $response;
		} elseif ($response->isSuccessful()) {
			list($type, $params) = EasyRdf_Utils::parseMimeType(
				$response->getHeader('Content-Type')
			);
			if (strpos($type, 'application/sparql-results') === 0) {
				return new EasyRdf_Sparql_Result($response->getBody(), $type);
			} else {
				return new EasyRdf_Graph($this->getQueryUri(), $response->getBody(), $type);
			}
		} else {
			$message = __METHOD__.' (line: '.__LINE__.') failed request '. htmlentities($query). "\n\r---\n\r" . $response->getBody();
			//ddebug_backtrace();
			if (WISSKI_DEVEL) \Drupal::logger('wisski_sparql_client '.$type.' failed')->error('{message}',array('message'=>$message));
			throw new EasyRdf_Exception(
				"HTTP request for SPARQL query failed: ".$response->getBody()
			);
		}
		
	}



}


