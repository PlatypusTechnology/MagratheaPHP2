<?php

use Magrathea2\Admin\AdminUsers;
AdminUsers::Instance()->Logout();

$url = strtok($_SERVER['REQUEST_URI'], '?');
header('Location: ' . $url);
exit;
