<?php


use Magrathea2\Admin\Features\User\AdminUserControl;
use Magrathea2\Admin\Features\User\AdminUser;
use Magrathea2\MagratheaPHP;

MagratheaPHP::Instance()->Connect();

$control = new AdminUserControl();
$data = $_POST;

$user = new AdminUser($data["id"]);
$rs = $control->SetNewPassword($user, $data["new_pass"]);
echo $rs["success"];
