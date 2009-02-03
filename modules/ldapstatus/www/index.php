<?php


$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

if (!$session->isValid('login-admin') ) {
	SimpleSAML_Utilities::redirect('/' . $config->getBaseURL() . 'auth/login-admin.php',
		array('RelayState' => SimpleSAML_Utilities::selfURL())
	);
}


function myErrorHandler($errno, $errstr, $errfile, $errline) {

    switch ($errno) {
    case E_USER_ERROR:
    	echo('<p>PHP_ERROR   : [' . $errno . '] ' . $errstr . '. Fatal error on line ' . $errline . ' in file ' . $errfile);
    	break;

    case E_USER_WARNING:
    	echo('<p>PHP_WARNING : [' . $errno . '] ' . $errstr . '. Warning on line ' . $errline . ' in file ' . $errfile);
    	break;

    case E_USER_NOTICE:
    	echo('<p>PHP_WARNING : [' . $errno . '] ' . $errstr . '. Warning on line ' . $errline . ' in file ' . $errfile);        
    	break;

    default:
    	echo('<p>PHP_UNKNOWN : [' . $errno . '] ' . $errstr . '. Unknown error on line ' . $errline . ' in file ' . $errfile);        
        break;
    }

    /* Don't execute PHP internal error handler */
    return true;
}




$ldapconfig = $config->copyFromBase('loginfeide', 'config-login-feide.php');
$ldapStatusConfig = $config->copyFromBase('ldapstatus', 'module_ldapstatus.php');

$debug = $ldapconfig->getValue('ldapDebug', FALSE);
$orgs = $ldapconfig->getValue('orgldapconfig');

#echo '<pre>'; print_r($orgs); exit;







$results = NULL;

$results = $session->getData('module:ldapstatus', 'results');
if (empty($results)) {
	$results = array();
} elseif (array_key_exists('reset', $_GET) && $_GET['reset'] === '1') {
	$results = array();
}

#echo('<pre>'); print_r($results); exit;


$start = microtime(TRUE);
$previous = microtime(TRUE);

$maxtime = $ldapStatusConfig->getValue('maxExecutionTime', 15); 


if (array_key_exists('orgtest', $_REQUEST)) {
	$old_error_handler = set_error_handler("myErrorHandler");
	
	echo('<html><head><style>
	p {
		font-family: monospace; color: #333;
	}
	
	</style></head><body><h1>Test connection to [' . $_REQUEST['orgtest'] . ']</h1>');
	$tester = new sspmod_ldapstatus_LDAPTester($orgs[$_REQUEST['orgtest']], $debug, TRUE);
	$res = $tester->test();
	echo('<pre>');
	print_r($res);
	echo('</p>');
	echo('</body>');
	exit;
}


// Traverse and execute tests for each entry...
foreach ($orgs AS $orgkey => $orgconfig) {
	if (array_key_exists($orgkey, $results)) continue;

	SimpleSAML_Logger::debug('ldapstatus: Executing test on ' . $orgkey);
	
	$tester = new sspmod_ldapstatus_LDAPTester($orgconfig, $debug);
	$results[$orgkey] = $tester->test();
	
	if ((microtime(TRUE) - $start) > $maxtime) {
		SimpleSAML_Logger::debug('ldapstatus: Completing execution after maxtime [' .(microtime(TRUE) - $start) . ' of maxtime ' . $maxtime . ']');
		break;
	}
}

$session->setData('module:ldapstatus', 'results', $results);

#echo '<pre>'; print_r($results); exit;

$lightCounter = array(0,0,0);

function resultCode($res) {
	global $lightCounter;
	$code = '';
	$columns = array('config', 'ping', 'adminBind', 'ldapSearchBogus', 'configTest', 'ldapSearchTestUser', 'ldapBindTestUser', 'ldapGetAttributesTestUser', 'configMeta');
	foreach ($columns AS $c) {
		if (array_key_exists($c, $res)) {
			if ($res[$c][0]) {
				$code .= '0';
				$lightCounter[0]++;
			} else {
				$code .= '2';
				$lightCounter[2]++;
			}
			
		} else {
			$code .= '1';
			$lightCounter[1]++;
		}
	}
	return $code;
}
	
	
$ressortable = array();
foreach ($results AS $key => $res) {
	$ressortable[$key] = resultCode($res);
}
asort($ressortable);
#echo '<pre>'; print_r($ressortable); exit;


$t = new SimpleSAML_XHTML_Template($config, 'ldapstatus:ldapstatus.php');

$t->data['completeNo'] = count($results);
$t->data['completeOf'] = count($orgs);
$t->data['results'] = $results;
$t->data['orgconfig'] = $orgs;
$t->data['lightCounter'] = $lightCounter;
$t->data['sortedOrgIndex'] = array_keys($ressortable);
$t->show();
exit;

?>
