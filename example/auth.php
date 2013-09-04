<?
if (php_sapi_name() != 'cli-server') {
	echo 'You must run the auth script via "php -S localhost:8080 auth.php"';
	exit();
}

ini_set('display_errors', 'On');
error_reporting(E_ALL);
session_start();
const REDIRECT_URI = 'http://localhost:8080/callback';

require_once('../vimeo.php');
$config = json_decode(file_get_contents('./config.json'), true);

if (preg_match('%^/callback%', $_SERVER["REQUEST_URI"])) {
	// Callback url, respond to the information sent from vimeo and turn that into a usable access token
	if ($_SESSION['state'] != $_GET['state']) {
		echo 'Something is wrong. Vimeo sent back a different state than this script was expecting. Please let vimeo know that this has happened';
	}

	$lib = new Vimeo($config['client_id'], $config['client_secret']);
	$tokens = $lib->accessToken($_GET['code'], REDIRECT_URI);	
	if ($tokens['status'] == 200) {
		$_SESSION['access_token'] = $tokens['body']->access_token;
		echo 'Successful authentication. Please go to <a href="http://localhost:8080">localhost:8080</a>';
	} else {
		echo "Unsuccessful authentication";
		var_dump($tokens);
	}
} elseif (preg_match('%^/reset%', $_SERVER["REQUEST_URI"])) {
	// Reset url, kill the session and start over
	session_destroy();
	header('Location: http://localhost:8080');
	exit();
} else { 
	// Root url, check if the user has already authenticated or not
	if (empty($_SESSION['access_token'])) {
		echo "This is an unauthenticated request to /users/dashron<br />";
		$lib = new Vimeo($config['client_id'], $config['client_secret']);
		$user = $lib->request('/users/dashron');
		$_SESSION['state'] = base64_encode(openssl_random_pseudo_bytes(30));

		echo 'To authenticate you should click <a href="' 
			. $lib->buildAuthorizationEndpoint(REDIRECT_URI, 'public', $_SESSION['state']) 
			. '">here</a><br />';

		var_dump($user);
	} else {
		echo "This is an authenticated request to /me<br />";
		echo 'To start over click <a href="http://localhost:8080/reset">here</a><br />';
		$lib = new Vimeo($config['client_id'], $config['client_secret'], $_SESSION['access_token']);
		$me = $lib->request('/me');
		var_dump($me);
	}	
}
