<?php
require_once 'config.php';

$_SESSION = [];
session_destroy();

header('Location: ' . APP_URL . '/index.php');
exit;
