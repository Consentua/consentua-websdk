<?php


$_CONF = array();

switch($_SERVER['SERVER_NAME'])
{
	case 'websdk-test.consentua.com':
	case '127.0.0.1':
		$_CONF['api-path'] = "https://test.consentua.com/";
		break;

	case 'websdk-dev.consentua.com':
	case '127.0.0.2':
		$_CONF['api-path'] = "https://consentuadevsvcapi.azurewebsites.net/";
		break;

	case 'websdk.consentua.com':
	case '127.0.0.3':
	default:
		$_CONF['api-path'] = "https://api.consentua.com/";
		break;
}



?>
