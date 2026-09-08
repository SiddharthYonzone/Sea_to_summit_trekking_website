<?php
require_once 'config.php';

$clientId = get_setting('google_client_id', '');
if ($clientId === '') {
    die('Google login is not configured yet. An admin needs to add a Google Client ID under Admin &rarr; Edit Website &rarr; Social Login. <a href="account-login.php">Back to login</a>');
}

// CSRF protection + where to send the user back to afterwards
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_redirect'] = $_GET['redirect'] ?? 'account.php';

$redirectUri = site_base_url() . 'oauth-google-callback.php';

$params = http_build_query([
    'client_id'     => $clientId,
    'redirect_uri'  => $redirectUri,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'access_type'   => 'online',
    'prompt'        => 'select_account',
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
exit;
