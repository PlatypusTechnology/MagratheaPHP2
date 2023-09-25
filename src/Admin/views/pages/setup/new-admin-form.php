<div class="card">
	<div class="card-header">
		Create main user
	</div>
	<div class="card-body">
		<form action="#" method="post">
			<input type="hidden" name="action" value="insert_user" />
			<div class="row">
				<div class="col-sm-4 col-xs-12">
					<div class="form-group">
						<label for="admin_login">Login Email</label>
						<input type="email" class="form-control" id="admin_login" name="admin_login" placeholder="admin@admin.com">
					</div>
				</div>
				<div class="col-sm-4 col-xs-12">
					<div class="form-group">
						<label for="admin_password">Login Password</label>
						<input type="password" class="form-control" id="admin_password" name="admin_password"/>
					</div>
				</div>
				<div class="col-sm-4 col-xs-12">
					<div class="form-group">
						<label for="admin_password">Generated Password</label>
						<input type="text" class="form-control" id="admin_password_gen" name="admin_password_gen" disabled/>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-12 right">
					<br/>
					<button class="btn btn-success" type="button" onclick="generateRandomPassword()">Generate Random Password</button>
					&nbsp;&nbsp;&nbsp;
					<button class="btn btn-primary" type="submit">Create Main User</button>
				</div>
			</div>
		</form>
	</div>
</div>
