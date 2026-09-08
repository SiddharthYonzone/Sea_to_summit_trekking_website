<?php
require_once 'config.php';

$redirectAfter = $_SESSION['oauth_redirect'] ?? 'account.php';

// Validate state (CSRF protection) and that Google actually returned a code
if (empty($_GET['state']) || empty($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    die('Login request could not be verified (state mismatch). <a href="account-login.php">Try again</a>');
}
unset($_SESSION['oauth_state']);

if (empty($_GET['code'])) {
    $errorMsg = $_GET['error'] ?? 'unknown error';
    die('Google login was not completed (' . h($errorMsg) . '). <a href="account-login.php">Try again</a>');
}

$clientId = get_setting('google_client_id', '');
$clientSecret = get_setting('google_client_secret', '');
$redirectUri = site_base_url() . 'oauth-google-callback.php';

// Exchange the authorization code for an access token
$tokenResponse = oauth_http_post('https://oauth2.googleapis.com/token', [
    'code'          => $_GET['code'],
    'client_id'     => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri'  => $redirectUri,
    'grant_type'    => 'authorization_code',
]);

if (empty($tokenResponse['access_token'])) {
    die('Could not exchange the Google login code for an access token. Double-check your Google Client ID/Secret and that the redirect URI is whitelisted in Google Cloud Console. <a href="account-login.php">Try again</a>');
}

// Fetch the user's profile
$profile = oauth_http_get('https://www.googleapis.com/oauth2/v3/userinfo?access_token=' . urlencode($tokenResponse['access_token']));

if (empty($profile['sub'])) {
    die('Could not fetch your Google profile. <a href="account-login.php">Try again</a>');
}

$customer = social_login_upsert(
    'google_id',
    $profile['sub'],
    $profile['email'] ?? null,
    $profile['name'] ?? 'Google User',
    $profile['picture'] ?? null
);

$_SESSION['customer_id'] = $customer['id'];
unset($_SESSION['oauth_redirect']);
header('Location: ' . $redirectAfter);
exit;
