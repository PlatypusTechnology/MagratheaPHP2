<div class="card">
	<div class="card-header center card-header-login">
		<div class="card-logo">
			<?
				Magrathea2\Admin\AdminManager::Instance()->PrintLogo(200);
			?>
		</div>
		<div class="card-title"><span>Login</span></div>
	</div>
	<div class="card-body">
		<form action="" method="post">
			<input type="hidden" name="action" value="login_user" />
			<div class="row">
				<div class="col-sm-6 col-xs-12">
					<div class="form-group">
						<label for="admin_login">E-mail</label>
						<input type="email" class="form-control" id="admin_login" name="admin_login" placeholder="admin@admin.com" value="<?=@$loginEmail?>">
					</div>
				</div>
				<div class="col-sm-6 col-xs-12">
					<div class="form-group">
						<label for="admin_password">Password</label>
						<input type="password" class="form-control" id="admin_password" name="admin_password"/>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-12 center">
					<br/><br/>
					<button class="btn btn-primary w-100" type="submit">Login</button>
				</div>
			</div>
		</form>
	</div>
</div>
