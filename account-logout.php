<?php
require_once 'config.php';
unset($_SESSION['customer_id']);
header('Location: index.html');
exit;
