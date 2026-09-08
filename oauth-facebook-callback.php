<?php
require_once 'config.php';

$redirectAfter = $_SESSION['oauth_redirect'] ?? 'account.php';

if (empty($_GET['state']) || empty($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    die('Login request could not be verified (state mismatch). <a href="account-login.php">Try again</a>');
}
unset($_SESSION['oauth_state']);

if (empty($_GET['code'])) {
    $errorMsg = $_GET['error_message'] ?? ($_GET['error'] ?? 'unknown error');
    die('Facebook login was not completed (' . h($errorMsg) . '). <a href="account-login.php">Try again</a>');
}

$appId = get_setting('facebook_app_id', '');
$appSecret = get_setting('facebook_app_secret', '');
$redirectUri = site_base_url() . 'oauth-facebook-callback.php';

// Exchange the code for an access token
$tokenUrl = 'https://graph.facebook.com/v18.0/oauth/access_token?' . http_build_query([
    'client_id'     => $appId,
    'client_secret' => $appSecret,
    'redirect_uri'  => $redirectUri,
    'code'          => $_GET['code'],
]);
$tokenResponse = oauth_http_get($tokenUrl);

if (empty($tokenResponse['access_token'])) {
    die('Could not exchange the Facebook login code for an access token. Double-check your Facebook App ID/Secret and that the redirect URI is whitelisted in Facebook for Developers. <a href="account-login.php">Try again</a>');
}

// Fetch the user's profile
$profileUrl = 'https://graph.facebook.com/me?' . http_build_query([
    'fields'       => 'id,name,email,picture.type(large)',
    'access_token' => $tokenResponse['access_token'],
]);
$profile = oauth_http_get($profileUrl);

if (empty($profile['id'])) {
    die('Could not fetch your Facebook profile. <a href="account-login.php">Try again</a>');
}

$avatarUrl = $profile['picture']['data']['url'] ?? null;

$customer = social_login_upsert(
    'facebook_id',
    $profile['id'],
    $profile['email'] ?? null,
    $profile['name'] ?? 'Facebook User',
    $avatarUrl
);

$_SESSION['customer_id'] = $customer['id'];
unset($_SESSION['oauth_redirect']);
header('Location: ' . $redirectAfter);
exit;
