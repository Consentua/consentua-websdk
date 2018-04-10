<?php


$_CONF = array();

switch($_SERVER['SERVER_NAME'])
{
	case 'websdk-test.consentua.com':
		$_CONF['api-path'] = "https://test.consentua.com/";
		break;

	case 'websdk-dev.consentua.com':
		$_CONF['api-path'] = "https://consentuadevsvcapi.azurewebsites.net/";
		break;

	case 'websdk.consentua.com':
	default:
		$_CONF['api-path'] = "https://api.consentua.com/";
		break;
}



?>
