<?php
require_once 'config.php';

$appId = get_setting('facebook_app_id', '');
if ($appId === '') {
    die('Facebook login is not configured yet. An admin needs to add a Facebook App ID under Admin &rarr; Edit Website &rarr; Social Login. <a href="account-login.php">Back to login</a>');
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_redirect'] = $_GET['redirect'] ?? 'account.php';

$redirectUri = site_base_url() . 'oauth-facebook-callback.php';

$params = http_build_query([
    'client_id'     => $appId,
    'redirect_uri'  => $redirectUri,
    'state'         => $state,
    'scope'         => 'email,public_profile',
]);

header('Location: https://www.facebook.com/v18.0/dialog/oauth?' . $params);
exit;
