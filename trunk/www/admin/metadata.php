<?php

require_once('../_include.php');

/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();


/* Check if valid local session exists.. */
if (!isset($session) || !$session->isValid('login-admin') ) {
	SimpleSAML_Utilities::redirect('/' . $config->getBaseURL() . 'auth/login-admin.php',
		array('RelayState' => SimpleSAML_Utilities::selfURL())
	);
}


try {

	$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

	$et = new SimpleSAML_XHTML_Template($config, 'admin-metadatalist.php');


	if ($config->getValue('enable.saml20-sp') === true) {
		$results = array();	
		
		$metalist = $metadata->getList('saml20-sp-hosted');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'host'),
				array('request.signing','certificate','privatekey', 'privatekey_pass', 'NameIDFormat', 'ForceAuthn', 'AuthnContextClassRef', 'SPNameQualifier', 'attributemap', 'attributealter', 'attributes', 'metadata.sign.enable', 'metadata.sign.privatekey', 'metadata.sign.privatekey_pass', 'metadata.sign.certificate', 'idpdisco.url')
			);
		}
		$et->data['metadata.saml20-sp-hosted'] = $results;
		
		$results = array();	
		$metalist = $metadata->getList('saml20-idp-remote');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'SingleSignOnService', 'SingleLogoutService', 'certFingerprint'),
				array('name', 'description', 'base64attributes', 'certificate', 'hint.cidr', 'saml2.relaxvalidation', 'SingleLogoutServiceResponse', 'request.signing', 'attributemap', 'attributealter', 'sharedkey', 'assertion.encryption')
			);
		}
		$et->data['metadata.saml20-idp-remote'] = $results;
		
	}
	
	if ($config->getValue('enable.saml20-idp') === true) {
		$results = array();	
		$metalist = $metadata->getList('saml20-idp-hosted');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'host', 'privatekey', 'certificate', 'auth'),
				array('requireconsent','request.signing', 'privatekey_pass', 'authority', 'attributemap', 'attributealter', 'userid.attribute', 'metadata.sign.enable', 'metadata.sign.privatekey', 'metadata.sign.privatekey_pass', 'metadata.sign.certificate')
			);
		}
		$et->data['metadata.saml20-idp-hosted'] = $results;
		
		$results = array();	
		$metalist = $metadata->getList('saml20-sp-remote');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'AssertionConsumerService'),
				array('SingleLogoutService', 'NameIDFormat', 'SPNameQualifier', 'base64attributes', 'simplesaml.nameidattribute', 'attributemap', 'attributealter', 'simplesaml.attributes', 'attributes', 'name', 'description','request.signing','certificate', 'ForceAuthn', 'sharedkey', 'assertion.encryption', 'userid.attribute')
			);
		}
		$et->data['metadata.saml20-sp-remote'] = $results;
		
	}




	if ($config->getValue('enable.shib13-sp') === true) {
		$results = array();	

		$metalist = $metadata->getList('shib13-sp-hosted');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'host'),
				array('NameIDFormat', 'ForceAuthn', 'metadata.sign.enable', 'metadata.sign.privatekey', 'metadata.sign.privatekey_pass', 'metadata.sign.certificate', 'idpdisco.url')
			);
		}
		$et->data['metadata.shib13-sp-hosted'] = $results;

		$results = array();	
		$metalist = $metadata->getList('shib13-idp-remote');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'SingleSignOnService', 'certFingerprint'),
				array('name', 'description', 'base64attributes')
			);
		}
		$et->data['metadata.shib13-idp-remote'] = $results;
		
	}
	
	if ($config->getValue('enable.shib13-idp') === true) {
		$results = array();	
		$metalist = $metadata->getList('shib13-idp-hosted');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'host', 'privatekey', 'certificate', 'auth'),
				array('requireconsent', 'authority', 'privatekey_pass')
			);
		}
		$et->data['metadata.shib13-idp-hosted'] = $results;
		
		$results = array();	
		$metalist = $metadata->getList('shib13-sp-remote');
		foreach ($metalist AS $entityid => $mentry) {
			$results[$entityid] = SimpleSAML_Utilities::checkAssocArrayRules($mentry,
				array('entityid', 'AssertionConsumerService'),
				array('base64attributes', 'audience', 'attributemap', 'attributealter', 'simplesaml.attributes', 'attributes', 'name', 'description', 'metadata.sign.enable', 'metadata.sign.privatekey', 'metadata.sign.privatekey_pass', 'metadata.sign.certificate')
			);
		}
		$et->data['metadata.shib13-sp-remote'] = $results;
		
	}

	$et->data['header'] = 'Metadata overview';
	$et->data['icon'] = 'bino.png';

	
	$et->show();
	
} catch(Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);

}

?>