<?php
/**
 * @package    miniOrange
 * @subpackage Plugins
 * @license    GNU/GPLv3
 * @copyright  Copyright 2015 miniOrange. All Rights Reserved.
 *
 *
 * This file is part of miniOrange Joomla SAML IDP plugin.
 *
 * miniOrange Joomla SAML IDP plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * miniOrange Joomla IDP plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with miniOrange SAML plugin.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Drupal\miniorange_saml_idp; 
use DOMElement;
use DOMDocument;
use DOMNode;
use DOMXPath;
class Utilities {

    public static function AddSupportForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state){
        $RegisteredEmailandPhone = Utilities::getEmailandPhone();
        $form['markup_idp_attr_header_top_support'] = array(
            '#markup' => '<div id="Support_Section" class="mo_saml_table_layout_support_1">',
        );

        $form['markup_support_1'] = array(
            '#markup' => '<h3>Support</h3></h3><div>Need any help? Just send us a query so we can help you.<br /><br /></div>',
        );

        $form['miniorange_saml_email_address_support'] = array(
            '#type' => 'textfield',
            '#attributes' => array('placeholder' => 'Enter your Email'),
            '#default_value' => $RegisteredEmailandPhone['email'],
        );

        $form['miniorange_saml_phone_number_support'] = array(
            '#type' => 'textfield',
            '#attributes' => array('placeholder' => 'Enter your Phone Number'),
            '#default_value' => $RegisteredEmailandPhone['phone'],
        );

        $form['miniorange_saml_support_query_support'] = array(
            '#type' => 'textarea',
            '#clos' => '10',
            '#rows' => '5',
            '#attributes' => array('placeholder' => 'Write your query here'),
        );

        $form['miniorange_saml_support_submit_click'] = array(
            '#type' => 'submit',
            '#value' => t('Submit Query'),
            '#submit' => array('::saved_support'),
        );

        $form['miniorange_saml_support_note'] = array(
            '#markup' => '<div><br/>If you want custom features in the plugin, just drop an email to <a href="mailto:info@miniorange.com">info@miniorange.com</a></div>'
        );

        $form['miniorange_saml_support_div_cust'] = array(

            '#markup' => '</div></div>'
        );
    }

    public static function getEmailandPhone() {
        $Registered_email = \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_customer_admin_email');
        $Registered_phone = \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_customer_admin_phone');
        $array = array(
            'email' => isset($Registered_email) ? $Registered_email : '',
            'phone' => isset($Registered_phone) ? $Registered_phone : '',
        );
        return $array;
    }

    public static function send_support_query($email, $phone, $query)
    {
        if(empty($email)||empty($query)){
            drupal_set_message(t('The <b><u>Email</u></b> and <b><u>Query</u></b> fields are mandatory.'), 'error');
            return;
        } elseif(!valid_email_address($email)) {
            drupal_set_message(t('The email address <b><i>' . $email . '</i></b> is not valid.'), 'error');
            return;
        }
        $support = new MiniorangeSAMLIdpSupport($email, $phone, $query);
        $support_response = $support->sendSupportQuery();
        if ($support_response) {
            drupal_set_message(t('Support query successfully sent'));
        } else {
            drupal_set_message(t('Error sending support query'), 'error');
        }
    }

    public static function isCustomerRegistered()
    {
        if (   \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_customer_admin_email') == NULL
            || \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_customer_id') == NULL
            || \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_customer_admin_token') == NULL
            || \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_customer_api_key') == NULL) {
            return false;
        }else {
            return true;
        }
    }

    public static function isCurlInstalled() {
      if (in_array('curl', get_loaded_extensions())) {
        return 1;
      }
      else {
        return 0;
      }
    }
	
	public static function generateID() {
		return '_' . self::stringToHex(self::generateRandomBytes(21));
	}
	
	public static function stringToHex($bytes) {
		$ret = '';
		for($i = 0; $i < strlen($bytes); $i++) {
			$ret .= sprintf('%02x', ord($bytes[$i]));
		}
		return $ret;
	}
	
	public static function generateRandomBytes($length, $fallback = TRUE) {
		assert('is_int($length)');
        return openssl_random_pseudo_bytes($length);
	}
	
	public static function createAuthnRequest($acsUrl, $issuer, $force_authn = 'false') {
		$requestXmlStr = '<?xml version="1.0" encoding="UTF-8"?>' .
						'<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" ID="' . self::generateID() . 
						'" Version="2.0" IssueInstant="' . self::generateTimestamp() . '"';
		if( $force_authn == 'true') {
			$requestXmlStr .= ' ForceAuthn="true"';
		}
		$requestXmlStr .= ' ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" AssertionConsumerServiceURL="' . $acsUrl . 
						'" ><saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">' . $issuer . '</saml:Issuer></samlp:AuthnRequest>';
		$deflatedStr = gzdeflate($requestXmlStr);
		$base64EncodedStr = base64_encode($deflatedStr);
		$urlEncoded = urlencode($base64EncodedStr);
		return $urlEncoded;
	}
	
	public static function generateTimestamp($instant = NULL) {
		if($instant === NULL) {
			$instant = time();
		}
		return gmdate('Y-m-d\TH:i:s\Z', $instant);
	}
	
	public static function xpQuery(DOMNode $node, $query){
        assert('is_string($query)');
        static $xpCache = NULL;

        if ($node instanceof DOMDocument) {
            $doc = $node;
        }else {
            $doc = $node->ownerDocument;
        }

        if ($xpCache === NULL || !$xpCache->document->isSameNode($doc)) {
            $xpCache = new DOMXPath($doc);
            $xpCache->registerNamespace('soap-env', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xpCache->registerNamespace('saml_protocol', 'urn:oasis:names:tc:SAML:2.0:protocol');
            $xpCache->registerNamespace('saml_assertion', 'urn:oasis:names:tc:SAML:2.0:assertion');
            $xpCache->registerNamespace('saml_metadata', 'urn:oasis:names:tc:SAML:2.0:metadata');
            $xpCache->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
            $xpCache->registerNamespace('xenc', 'http://www.w3.org/2001/04/xmlenc#');
        }

        $results = $xpCache->query($query, $node);
        $ret = array();
        for ($i = 0; $i < $results->length; $i++) {
            $ret[$i] = $results->item($i);
        }
		return $ret;
    }
	
	public static function parseNameId(DOMElement $xml)
    {
        $ret = array('Value' => trim($xml->textContent));

        foreach (array('NameQualifier', 'SPNameQualifier', 'Format') as $attr) {
            if ($xml->hasAttribute($attr)) {
                $ret[$attr] = $xml->getAttribute($attr);
            }
        }
        return $ret;
    }
	
	public static function xsDateTimeToTimestamp($time)
    {
        $matches = array();

        // We use a very strict regex to parse the timestamp.
        $regex = '/^(\\d\\d\\d\\d)-(\\d\\d)-(\\d\\d)T(\\d\\d):(\\d\\d):(\\d\\d)(?:\\.\\d+)?Z$/D';
        if (preg_match($regex, $time, $matches) == 0) {
            echo sprintf("nvalid SAML2 timestamp passed to xsDateTimeToTimestamp: ".$time);
            exit;
        }

        // Extract the different components of the time from the  matches in the regex.
        // intval will ignore leading zeroes in the string.
        $year   = intval($matches[1]);
        $month  = intval($matches[2]);
        $day    = intval($matches[3]);
        $hour   = intval($matches[4]);
        $minute = intval($matches[5]);
        $second = intval($matches[6]);

        // We use gmmktime because the timestamp will always be given
        //in UTC.
        $ts = gmmktime($hour, $minute, $second, $month, $day, $year);
        return $ts;
    }
	
	public static function extractStrings(DOMElement $parent, $namespaceURI, $localName)
    {
        assert('is_string($namespaceURI)');
        assert('is_string($localName)');

        $ret = array();
        for ($node = $parent->firstChild; $node !== NULL; $node = $node->nextSibling) {
            if ($node->namespaceURI !== $namespaceURI || $node->localName !== $localName) {
                continue;
            }
            $ret[] = trim($node->textContent);
        }
        return $ret;
    }
	
	public static function validateElement(DOMElement $root)
    {
        /* Create an XML security object. */
        $objXMLSecDSig = new XMLSecurityDSig();

        /* Both SAML messages and SAML assertions use the 'ID' attribute. */
        $objXMLSecDSig->idKeys[] = 'ID';

        /* Locate the XMLDSig Signature element to be used. */
        $signatureElement = self::xpQuery($root, './ds:Signature');

        if (count($signatureElement) === 0) {
            /* We don't have a signature element to validate. */
            return FALSE;
        }elseif (count($signatureElement) > 1) {
        	echo sprintf("XMLSec: more than one signature element in root.");
        	exit;
        }
       
        $signatureElement = $signatureElement[0];
        $objXMLSecDSig->sigNode = $signatureElement;
		
        /* Canonicalize the XMLDSig SignedInfo element in the message. */
        $objXMLSecDSig->canonicalizeSignedInfo();
		
       /* Validate referenced xml nodes. */
        if (!$objXMLSecDSig->validateReference()) { 
        	echo sprintf("XMLsec: digest validation failed");
        	exit;
        }
		
		/* Check that $root is one of the signed nodes. */
        $rootSigned = FALSE;
        /** @var DOMNode $signedNode */
        foreach ($objXMLSecDSig->getValidatedNodes() as $signedNode) {
            if ($signedNode->isSameNode($root)) {
                $rootSigned = TRUE;
                break;
            } elseif ($root->parentNode instanceof DOMDocument && $signedNode->isSameNode($root->ownerDocument)) {
                /* $root is the root element of a signed document. */
                $rootSigned = TRUE;
                break;
            }
        }
		
		if (!$rootSigned) {
			echo sprintf("XMLSec: The root element is not signed.");
			exit;
        }

        /* Now we extract all available X509 certificates in the signature element. */
        $certificates = array();
        foreach (self::xpQuery($signatureElement, './ds:KeyInfo/ds:X509Data/ds:X509Certificate') as $certNode) {
            $certData = trim($certNode->textContent);
            $certData = str_replace(array("\r", "\n", "\t", ' '), '', $certData);
            $certificates[] = $certData;
        }
	
        $ret = array(
            'Signature' => $objXMLSecDSig,
            'Certificates' => $certificates,
            );

        return $ret;
    }

	public static function validateSignature(array $info, XMLSecurityKey $key)
    {
        assert('array_key_exists("Signature", $info)');

        /** @var XMLSecurityDSig $objXMLSecDSig */
        $objXMLSecDSig = $info['Signature'];

        $sigMethod = self::xpQuery($objXMLSecDSig->sigNode, './ds:SignedInfo/ds:SignatureMethod');
        if (empty($sigMethod)) {
            echo sprintf('Missing SignatureMethod element');
            exit();
        }
        $sigMethod = $sigMethod[0];
        if (!$sigMethod->hasAttribute('Algorithm')) {
            echo sprintf('Missing Algorithm-attribute on SignatureMethod element.');
            exit;
        }
        $algo = $sigMethod->getAttribute('Algorithm');

        if ($key->type === XMLSecurityKey::RSA_SHA1 && $algo !== $key->type) {
            $key = self::castKey($key, $algo);
        }
		
        /* Check the signature. */
        if (! $objXMLSecDSig->verify($key)) {
        	echo sprintf('Unable to validate Sgnature');
        	exit;
        }
    }
	
    public static function castKey(XMLSecurityKey $key, $algorithm, $type = 'public')
    {
    	assert('is_string($algorithm)');
    	assert('$type === "public" || $type === "private"');
    
    	// do nothing if algorithm is already the type of the key
    	if ($key->type === $algorithm) {
    		return $key;
    	}
    
    	$keyInfo = openssl_pkey_get_details($key->key);
    	if ($keyInfo === FALSE) {
    		echo sprintf('Unable to get key details from XMLSecurityKey.');
    		exit;
    	}
    	if (!isset($keyInfo['key'])) {
    		echo sprintf('Missing key in public key details.');
    		exit;
    	}
    
    	$newKey = new XMLSecurityKey($algorithm, array('type'=>$type));
    	$newKey->loadKey($keyInfo['key']);
    
    	return $newKey;
    }
    
	public static function processResponse($currentURL, $certFingerprint, $signatureData,
		SAML2_Response $response) {
		assert('is_string($currentURL)');
		assert('is_string($certFingerprint)');
		
		
		/* Validate Response-element destination. */
		$msgDestination = $response->getDestination();
		if ($msgDestination !== NULL && $msgDestination !== $currentURL) {
			echo sprintf('Destination in response doesn\'t match the current URL. Destination is "' .
				$msgDestination . '", current URL is "' . $currentURL . '".');
			exit;
		}
		
		$responseSigned = self::checkSign($certFingerprint, $signatureData);
		
		/* Returning boolean $responseSigned */
		return $responseSigned;
	}

    public static function processRequest($certFingerprint, $signatureData) {
        assert('is_string($certFingerprint)');
        
        $responseSigned = self::checkSign($certFingerprint, $signatureData);
        
        /* Returning boolean $responseSigned */
        return $responseSigned;
    }
	
	public static function checkSign($certFingerprint, $signatureData) {
		$certificates = $signatureData['Certificates'];	

		if (count($certificates) === 0) {
			return FALSE;
		} 

		$fpArray = array();
		$fpArray[] = $certFingerprint;
		$pemCert = self::findCertificate($fpArray, $certificates);
		
		$lastException = NULL;
		
		$key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'public'));
		$key->loadKey($pemCert);
				
		try {
			/*
			 * Make sure that we have a valid signature
			 */
			assert('$key->type === XMLSecurityKey::RSA_SHA1');
			self::validateSignature($signatureData, $key);			
			return TRUE;
		} catch (Exception $e) {
			$lastException = $e;
		}
		
		
		/* We were unable to validate the signature with any of our keys. */
		if ($lastException !== NULL) {
			throw $lastException;
		} else {
			return FALSE;
		}
	
	}
	
	public static function validateIssuerAndAudience($samlResponse, $spEntityId, $issuerToValidateAgainst) {
		$issuer = current($samlResponse->getAssertions())->getIssuer();
		$audience = current(current($samlResponse->getAssertions())->getValidAudiences());
		if(strcmp($issuerToValidateAgainst, $issuer) === 0) {
			if(strcmp($audience, $spEntityId) === 0) {
				return TRUE;
			} else {
				echo sprintf('Invalid audience');
				exit;
			}
		} else {
			echo sprintf('Issuer cannot be verified.');
			exit;
		}
	}
	
	private static function findCertificate(array $certFingerprints, array $certificates) {

		$candidates = array();
		
		foreach ($certificates as $cert) {
			$fp = strtolower(sha1(base64_decode($cert)));
			if (!in_array($fp, $certFingerprints, TRUE)) {
				$candidates[] = $fp;
				continue;
			}

			/* We have found a matching fingerprint. */
			$pem = "-----BEGIN CERTIFICATE-----\n" .
				chunk_split($cert, 64) .
				"-----END CERTIFICATE-----\n";
			
			return $pem;
		}

		echo sprintf('Unable to find a certificate matching the configured fingerprint.');
		exit;
	}
	
	    /**
     * Decrypt an encrypted element.
     *
     * This is an internal helper function.
     *
     * @param  DOMElement     $encryptedData The encrypted data.
     * @param  XMLSecurityKey $inputKey      The decryption key.
     * @param  array          &$blacklist    Blacklisted decryption algorithms.
     * @return DOMElement     The decrypted element.
     * @throws Exception
     */
    private static function doDecryptElement(DOMElement $encryptedData, XMLSecurityKey $inputKey, array &$blacklist)
    {	
        $enc = new XMLSecEnc();
        $enc->setNode($encryptedData);
		
        $enc->type = $encryptedData->getAttribute("Type");
        $symmetricKey = $enc->locateKey($encryptedData);
        if (!$symmetricKey) {
        	echo sprintf('Could not locate key algorithm in encrypted data.');
        	exit;     
        }
		
        $symmetricKeyInfo = $enc->locateKeyInfo($symmetricKey);
        if (!$symmetricKeyInfo) {
			echo sprintf('Could not locate <dsig:KeyInfo> for the encrypted key.');
			exit;
        }
        $inputKeyAlgo = $inputKey->getAlgorith();
        if ($symmetricKeyInfo->isEncrypted) {
            $symKeyInfoAlgo = $symmetricKeyInfo->getAlgorith();
            if (in_array($symKeyInfoAlgo, $blacklist, TRUE)) {
                echo sprintf('Algorithm disabled: ' . var_export($symKeyInfoAlgo, TRUE));
                exit;
            }
            if ($symKeyInfoAlgo === XMLSecurityKey::RSA_OAEP_MGF1P && $inputKeyAlgo === XMLSecurityKey::RSA_1_5) {
                /*
                 * The RSA key formats are equal, so loading an RSA_1_5 key
                 * into an RSA_OAEP_MGF1P key can be done without problems.
                 * We therefore pretend that the input key is an
                 * RSA_OAEP_MGF1P key.
                 */
                $inputKeyAlgo = XMLSecurityKey::RSA_OAEP_MGF1P;
            }
            /* Make sure that the input key format is the same as the one used to encrypt the key. */
            if ($inputKeyAlgo !== $symKeyInfoAlgo) {
                echo sprintf( 'Algorithm mismatch between input key and key used to encrypt ' .
                    ' the symmetric key for the message. Key was: ' .
                    var_export($inputKeyAlgo, TRUE) . '; message was: ' .
                    var_export($symKeyInfoAlgo, TRUE));
                exit;
            }
            /** @var XMLSecEnc $encKey */
            $encKey = $symmetricKeyInfo->encryptedCtx;
            $symmetricKeyInfo->key = $inputKey->key;
            $keySize = $symmetricKey->getSymmetricKeySize();
            if ($keySize === NULL) {
                /* To protect against "key oracle" attacks, we need to be able to create a
                 * symmetric key, and for that we need to know the key size.
                 */
				echo sprintf('Unknown key size for encryption algorithm: ' . var_export($symmetricKey->type, TRUE));
				exit;
            }
            try {
                $key = $encKey->decryptKey($symmetricKeyInfo);
                if (strlen($key) != $keySize) {
                	echo sprintf('Unexpected key size (' . strlen($key) * 8 . 'bits) for encryption algorithm: ' .
                        var_export($symmetricKey->type, TRUE));
                	exit;
                }
            } catch (Exception $e) {
                /* We failed to decrypt this key. Log it, and substitute a "random" key. */
                
                /* Create a replacement key, so that it looks like we fail in the same way as if the key was correctly padded. */
                /* We base the symmetric key on the encrypted key and private key, so that we always behave the
                 * same way for a given input key.
                 */
                $encryptedKey = $encKey->getCipherValue();
                $pkey = openssl_pkey_get_details($symmetricKeyInfo->key);
                $pkey = sha1(serialize($pkey), TRUE);
                $key = sha1($encryptedKey . $pkey, TRUE);
                /* Make sure that the key has the correct length. */
                if (strlen($key) > $keySize) {
                    $key = substr($key, 0, $keySize);
                } elseif (strlen($key) < $keySize) {
                    $key = str_pad($key, $keySize);
                }
            }
            $symmetricKey->loadkey($key);
        } else {
            $symKeyAlgo = $symmetricKey->getAlgorith();
            /* Make sure that the input key has the correct format. */
            if ($inputKeyAlgo !== $symKeyAlgo) {
            	echo sprintf( 'Algorithm mismatch between input key and key in message. ' .
                    'Key was: ' . var_export($inputKeyAlgo, TRUE) . '; message was: ' .
                    var_export($symKeyAlgo, TRUE));
            	exit;
            }
            $symmetricKey = $inputKey;
        }
        $algorithm = $symmetricKey->getAlgorith();
        if (in_array($algorithm, $blacklist, TRUE)) {
            echo sprintf('Algorithm disabled: ' . var_export($algorithm, TRUE));
            exit;
        }
        /** @var string $decrypted */
        $decrypted = $enc->decryptNode($symmetricKey, FALSE);
        /*
         * This is a workaround for the case where only a subset of the XML
         * tree was serialized for encryption. In that case, we may miss the
         * namespaces needed to parse the XML.
         */
        $xml = '<root xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" '.
                     'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' .
            $decrypted .
            '</root>';
        $newDoc = new DOMDocument();
        if (!@$newDoc->loadXML($xml)) {
        	echo sprintf('Failed to parse decrypted XML. Maybe the wrong sharedkey was used?');
        	throw new Exception('Failed to parse decrypted XML. Maybe the wrong sharedkey was used?');
        }
        $decryptedElement = $newDoc->firstChild->firstChild;
        if ($decryptedElement === NULL) {
        	echo sprintf('Missing encrypted element.');
        	throw new Exception('Missing encrypted element.');
        }

        if (!($decryptedElement instanceof DOMElement)) {
        	echo sprintf('Decrypted element was not actually a DOMElement.');
        }

        return $decryptedElement;
    }
    /**
     * Decrypt an encrypted element.
     *
     * @param  DOMElement     $encryptedData The encrypted data.
     * @param  XMLSecurityKey $inputKey      The decryption key.
     * @param  array          $blacklist     Blacklisted decryption algorithms.
     * @return DOMElement     The decrypted element.
     * @throws Exception
     */
    public static function decryptElement(DOMElement $encryptedData, XMLSecurityKey $inputKey, array $blacklist = array(), XMLSecurityKey $alternateKey = NULL)
    {	
        try {
        	echo "trying primary";
            return self::doDecryptElement($encryptedData, $inputKey, $blacklist);
        } catch (Exception $e) {
        	//Try with alternate key
        	try {
        		echo "trying secondary";
        		return self::doDecryptElement($encryptedData, $alternateKey, $blacklist);
        	} catch(Exception $t) {
        		
        	}
        	/*
        	 * Something went wrong during decryption, but for security
        	 * reasons we cannot tell the user what failed.
        	 */
        	echo sprintf('Failed to decrypt XML element.');
        	exit;
        }
    }

    /**
     * Parse a boolean attribute.
     *
     * @param  \DOMElement $node          The element we should fetch the attribute from.
     * @param  string     $attributeName The name of the attribute.
     * @param  mixed      $default       The value that should be returned if the attribute doesn't exist.
     * @return bool|mixed The value of the attribute, or $default if the attribute doesn't exist.
     * @throws \Exception
     */
    public static function parseBoolean(DOMElement $node, $attributeName, $default = null)
    {
        assert('is_string($attributeName)');
        if (!$node->hasAttribute($attributeName)) {
            return $default;
        }
        $value = $node->getAttribute($attributeName);
        switch (strtolower($value)) {
            case '0':
            case 'false':
                return FALSE;
            case '1':
            case 'true':
                return TRUE;
            default:
                throw new Exception('Invalid value of boolean attribute ' . var_export($attributeName, TRUE) . ': ' . var_export($value, TRUE));
        }
    }
	
	 /**
     * Generates the metadata of the SP based on the settings
     *
     * @param string    $sp            The SP data
     * @param string    $authnsign     authnRequestsSigned attribute
     * @param string    $wsign         wantAssertionsSigned attribute 
     * @param DateTime  $validUntil    Metadata's valid time
     * @param Timestamp $cacheDuration Duration of the cache in seconds
     * @param array     $contacts      Contacts info
     * @param array     $organization  Organization ingo
     *
     * @return string SAML Metadata XML
     */
    public static function metadata_builder($siteUrl)
    {
		$xml = new DOMDocument();
		$url = plugins_url().'/miniorange-saml-20-single-sign-on/sp-metadata.xml';
		
		$xml->load($url);
		
		$xpath = new DOMXPath($xml);
		$elements = $xpath->query('//md:EntityDescriptor[@entityID="http://{path-to-your-site}/wp-content/plugins/miniorange-saml-20-single-sign-on/"]');
		
		 if ($elements->length >= 1) {
		    $element = $elements->item(0);
		    $element->setAttribute('entityID', $siteUrl.'/wp-content/plugins/miniorange-saml-20-single-sign-on/');
		}
		
		$elements = $xpath->query('//md:AssertionConsumerService[@Location="http://{path-to-your-site}"]');
		if ($elements->length >= 1) {
		    $element = $elements->item(0);
		    $element->setAttribute('Location', $siteUrl.'/');
		}
		 
		//re-save
		$xml->save(plugins_url()."/miniorange-saml-20-single-sign-on/sp-metadata.xml");
    }
	
	public static function get_mapped_groups($saml_params, $saml_groups)
	{
			$groups = array();

		if (!empty($saml_groups)) {
			$saml_mapped_groups = array();
			$i=1;
			while ($i < 10) {
				$saml_mapped_groups_value = $saml_params->get('group'.$i.'_map');
				
				$saml_mapped_groups[$i] = explode(';', $saml_mapped_groups_value);
				$i++;
			}
		}

		foreach ($saml_groups as $saml_group) {
			if (!empty($saml_group)) {
				$i = 0;
				$found = false;
				
				while ($i < 9 && !$found) {
					if (!empty($saml_mapped_groups[$i]) && in_array($saml_group, $saml_mapped_groups[$i])) {
						$groups[] = $saml_params->get('group'.$i);
						$found = TRUE;
					}
					$i++;
				}
			}
		}
		
		return array_unique($groups);
	}


	public static function getEncryptionAlgorithm($method){
		switch($method){
			case 'http://www.w3.org/2001/04/xmlenc#tripledes-cbc':
				return XMLSecurityKey::TRIPLEDES_CBC;
				break;
			
			case 'http://www.w3.org/2001/04/xmlenc#aes128-cbc':
				return XMLSecurityKey::AES128_CBC;
				
			case 'http://www.w3.org/2001/04/xmlenc#aes192-cbc':
				return XMLSecurityKey::AES192_CBC;
				break;
			
			case 'http://www.w3.org/2001/04/xmlenc#aes256-cbc':
				return XMLSecurityKey::AES256_CBC;
				break;
				
			case 'http://www.w3.org/2001/04/xmlenc#rsa-1_5':
				return XMLSecurityKey::RSA_1_5;
				break;
			
			case 'http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p':
				return XMLSecurityKey::RSA_OAEP_MGF1P;
				break;
				
			case 'http://www.w3.org/2000/09/xmldsig#dsa-sha1':
				return XMLSecurityKey::DSA_SHA1;
				break;

			case 'http://www.w3.org/2000/09/xmldsig#rsa-sha1':
				return XMLSecurityKey::RSA_SHA1;
				break;
			
			case 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256':
				return XMLSecurityKey::RSA_SHA256;
				break;
				
			case 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha384':
				return XMLSecurityKey::RSA_SHA384;
				break;
			
			case 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha512':
				return XMLSecurityKey::RSA_SHA512;
				break;
			
			default:
				echo sprintf('Invalid Encryption Method: '.$method);
				exit;
				break;
		}
	}
	
	public static function sanitize_certificate( $certificate ) {
		$certificate = preg_replace("/[\r\n]+/", "", $certificate);
		$certificate = str_replace( "-", "", $certificate );
		$certificate = str_replace( "BEGIN CERTIFICATE", "", $certificate );
		$certificate = str_replace( "END CERTIFICATE", "", $certificate );
		$certificate = str_replace( " ", "", $certificate );
		$certificate = chunk_split($certificate, 64, "\r\n");
		$certificate = "-----BEGIN CERTIFICATE-----\r\n" . $certificate . "-----END CERTIFICATE-----";
		return $certificate;
	}
	
	public static function desanitize_certificate( $certificate ) {
		$certificate = preg_replace("/[\r\n]+/", "", $certificate);
		$certificate = str_replace( "-----BEGIN CERTIFICATE-----", "", $certificate );
		$certificate = str_replace( "-----END CERTIFICATE-----", "", $certificate );
		$certificate = str_replace( " ", "", $certificate );
		return $certificate;
	}
}
?>