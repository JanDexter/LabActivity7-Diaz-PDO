<?php
session_start();
// wipe the session so the user has to log in again
session_destroy();
header('Location: login.php');
exit;
