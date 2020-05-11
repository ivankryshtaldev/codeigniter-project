<style>
	.card { padding-top: 0 !important; }
	.card .media-body .media-title { margin-bottom: 0px !important; }
	.card .media-body .page_email { line-height: 12px !important; }
	.card .page_delete { margin-top:10px;margin-right:10px; padding: .1rem .5rem !important; }
	.card .right-button { margin-top:10px;margin-right:10px; padding: .1rem .5rem !important; }
	.card .enable_webhook { margin-top:10px; padding: .1rem .5rem !important; }
	.card .disable_webhook { margin-top:10px; padding: .1rem .5rem !important; }
	/* .profile-widget-header {margin-bottom: -18px !important;} */
	/* .profile-widget-header img { margin: -20px -5px 0 22px !important; } */
	/* .profile-widget-header h6 { text-align: left;margin-left: 20px; } */
	.profile-widget-header .delete_account { position: absolute;top:10px;right:10px;}
	.profile-widget .profile-widget-items:after{position: relative;}
	.list-unstyled .media{padding-right:10px;}

	/* .profile-widget-item{border:none;} */
	.btn-circle{margin:0 !important;}

	@media (max-width: 575.98px)
	{
		.profile-widget { margin-top: 0 !important; }
	}

</style>
<style type="text/css">
		.media .page_thumbnail{
			width: 50px;
		height: auto;
		margin-right: 15px;
		position: relative;
		overflow: hidden;
		}
		.media .page_thumbnail .overlay{
			position: absolute;
		display: flex;
		align-items: center;
		text-decoration: none;
		font-size: 11px;
		color: #ddd;
		text-align: center;
		height: 100%;
		width: 100%;
		top: 0;
		left: 0;
		background-color: rgba(0,0,0,0.5);
		-webkit-background-color: rgba(0,0,0,0.5);
		-moz-background-color: rgba(0,0,0,0.5);
		-ms-background-color: rgba(0,0,0,0.5);
		-o-background-color: rgba(0,0,0,0.5);
		transform: translateY(-100%);
		-webkit-transform: translateY(-100%);
		-moz-transform: translateY(-100%);
		-ms-transform: translateY(-100%);
		-o-transform: translateY(-100%);
		transition: 0.40s;
		-webkit-transition: 0.40s;
		-moz-transition: 0.40s;
		-ms-transition: 0.40s;
		-o-transition: 0.40s;
		}
		.media .page_thumbnail:hover .overlay{
		transform: translateY(0);
		-webkit-transform: translateY(0);
		-moz-transform: translateY(0);
		-ms-transform: translateY(0);
		-o-transform: translateY(0);
		}
	</style>
<?php $fb_login_button = str_replace("ThisIsTheLoginButtonForFacebook", $this->lang->line("Login with Facebook"), $fb_login_button);?>

<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/itour.css">

<section class="section">
	<div class="section-header">
	  <h1><i class="fa fa-facebook-official"></i> <?php echo $this->lang->line("your existing accounts") ?></h1>
	  <div class="section-header-breadcrumb">
		<div class="breadcrumb-item"><?php echo $this->lang->line("System"); ?></div>
		<div class="breadcrumb-item"><?php echo $page_title; ?></div>
	  </div>
	</div>

	<?php
if ($this->session->userdata('success_message') == 'success') {
	echo "<div class='text-info text-center' style='font-size : 20px;'><i class='fa fa-check-circle'></i> " . $this->lang->line('Your account has been imported successfully.') . "</div><br/>";
	$this->session->unset_userdata('success_message');
}

if ($this->session->userdata('limit_cross') != '') {
	echo "<div class='text-danger text-center' style='font-size : 20px;'><i class='fa fa-remove'></i> " . $this->session->userdata('limit_cross') . "</div><br/>";
	$this->session->unset_userdata('limit_cross');
}
$is_demo = $this->is_demo;

?>

	<div class="section-body">
		<div class="">
			<?php if ($show_import_account_box == 0): ?>
				<br/>
				<div style="padding: 15px;">
					<div class='alert alert-danger text-center'><i class='fa fa-times-circle'></i> <?php echo $this->lang->line('Due to system configuration change you have to delete one or more imported FB accounts and import again. Please check the following accounts and delete the account that has warning to delete.'); ?></div>
				</div>
			<?php endif;?>


			<div class="row  justify-content-center" style="padding:0 15px;">
				<!-- <div class="col text-center">		 -->
					<?php
if ($is_demo && $this->session->userdata("user_type") == "Admin") {
	echo '<div class="alert alert-warning text-center">Account import has been disabled in admin account because you will not be able to unlink the Facebook account you import as admin. If you want to test with your own accout then <a href="' . base_url('home/sign_up') . '" target="_BLANK">sign up</a> to create your own demo account then import your Facebook account there.</div>';
} else if ($existing_accounts != '0') {
	?>
					<!-- <h4> -->
						<div class="text-center">
							<p data-name="startStep_4" data-toggle="tooltip" data-placement="bottom" title="<?php echo $this->lang->line("You must be logged in your facebook account for which you want to refresh your access token. for synch your new page, simply refresh your token. if any access token is restricted for any action, refresh your access token."); ?>"> <?php if ($this->config->item('developer_access') != '1') {
		echo $fb_login_button;
	}
	?></p>
						</div>
					<!-- </h4> -->
					<?php }?>
				<!-- </div> -->
			</div>

			<?php if ($existing_accounts != '0'): ?>
				<div>
					<div class="row" style="padding:0 15px;">
					<?php $i = 0;foreach ($existing_accounts as $value): ?>
						<div class="col-12 col-sm-12 col-md-6">

							<?php $profile_picture = "https://graph.facebook.com/me/picture?access_token={$value['user_access_token']}&width=150&height=150";?>

							<div class="card profile-widget" style="margin-bottom: 0;">
								<div class="profile-widget-header">
									<img src="<?php echo $profile_picture; ?>" class="img-thumbnail profile-widget-picture">
									<div class="profile-widget-items">
									  <div class="profile-widget-item">
										<div class="profile-widget-item-label">
											<?php echo count($value['page_list']); ?> <?php echo $this->lang->line("pages"); ?>
											<?php if ($this->config->item('facebook_poster_group_enable_disable') == '1'): ?>
											/ <?php echo count($value['group_list']); ?> <?php echo $this->lang->line("groups"); ?>
											<?php endif;?>
										</div>
										<div class="profile-widget-item-value">
											<?php echo $value['name']; ?>

											  <button class="delete_account btn-circle btn btn-outline-danger btn-sm" table_id="<?php echo $value['userinfo_table_id']; ?>" data-toggle="tooltip" data-placement="bottom" title="<?php echo $this->lang->line("Do you want to remove this account from our database? you can import again."); ?>"><i class="fas fa-dumpster"></i> </button>
											</div>
										</div>

									</div>
								</div>

							</div>

							<div class="card">
							  <div class="card-body">
								<div class="summary">
									<?php
if ($value['need_to_delete'] == 1) {
	echo "<div class='alert alert-danger text-center'><i class='fa fa-close'></i> " . $this->lang->line('you have to delete this account.') . "</div>";
}
?>
									<?php
if ($value['validity'] == 'no') {
	echo "<div class='alert alert-danger text-center'><i class='fa fa-close'></i> " . $this->lang->line('your login validity has been expired.') . "</div>";
}
?>
								 <div class="summary-item">
									<!-- page lists -->
									<h6 class="mt-3"><?php echo $this->lang->line('Page List') ?> <span class="text-muted">(<?php echo count($value['page_list']); ?> <?php echo $this->lang->line("pages"); ?>)</span></h6>
									<div style="height: 310px;overflow-y:auto;" class="nicescroll">
										<ul class="list-unstyled list-unstyled-border">
											<?php foreach ($value['page_list'] as $page_info): ?>
												<li class="media">
													<div class="page_thumbnail">
														<img alt="image" class="mr-3 rounded" width="50" src="<?php echo $page_info['page_profile']; ?>">
														<a target="_BLANK" href="<?php echo base_url('messenger_bot_analytics/result/') . $page_info['id']; ?>" class="overlay">
															<?php echo $this->lang->line('Data Analytics'); ?>
														</a>
													</div><!--/.page_thumbnail-->

													<div class="media-body">
														<div class="media-right">

															<?php if ($page_info['bot_enabled'] == '1'): ?>
																<button data-name="startStep_1" style="margin-top:10px;margin-right:5px;" class="btn-sm btn btn-circle btn-outline-danger delete_full_bot" bot-enable="<?php echo $page_info['id']; ?>" id="bot-<?php echo $page_info['id']; ?>" already_disabled="no" title="<?php echo $this->lang->line("Delete Bot Connection & all settings."); ?>" data-placement="right" data-toggle="tooltip">
																	<i class="fas fa-eraser"></i>
																</button>
															<?php elseif ($page_info['bot_enabled'] == '2'): ?>
																<button data-name="startStep_1" style="margin-top:10px;margin-right:5px;" class="btn-sm btn btn-circle btn-outline-danger delete_full_bot" bot-enable="<?php echo $page_info['id']; ?>" id="bot-<?php echo $page_info['id']; ?>" already_disabled="yes" title="<?php echo $this->lang->line("Delete Bot Connection & all settings."); ?>" data-placement="right" data-toggle="tooltip">
																	<i class="fas fa-eraser"></i>
																</button>
															<?php endif;?>

															<?php if ($page_info['bot_enabled'] == '0'): ?>
																<button data-name="startStep_1" style="margin-top:10px;margin-right:5px;"  restart='0' bot-enable="<?php echo $page_info['id']; ?>" id="bot-<?php echo $page_info['id']; ?>" class="btn btn-sm btn-outline-primary btn-circle enable_webhook" title="<?php echo $this->lang->line("Enable Bot Connection"); ?>" data-placement="left" data-toggle="tooltip"><i class="fas fa-plug"></i></button>
															<?php elseif ($page_info['bot_enabled'] == '1'): ?>
																<button data-name="startStep_1" style="margin-top:10px;margin-right:5px;"  restart='0' bot-enable="<?php echo $page_info['id']; ?>" id="bot-<?php echo $page_info['id']; ?>" class="btn btn-sm btn-outline-dark btn-circle disable_webhook" title="<?php echo $this->lang->line("Disable Bot Connection"); ?>" data-placement="left" data-toggle="tooltip"><i class="fas fa-power-off"></i></button>
															<?php else: ?>
																<button data-name="startStep_1" style="margin-top:10px;margin-right:5px;"  restart='1' bot-enable="<?php echo $page_info['id']; ?>" id="bot-<?php echo $page_info['id']; ?>" class="btn btn-sm btn-outline-primary btn-circle enable_webhook" title="<?php echo $this->lang->line("Re-start Bot Connection"); ?>" data-placement="left" data-toggle="tooltip"><i class="fas fa-toggle-on"></i></button>
															<?php endif;?>

															<?php if ($page_info['bot_enabled'] == 1): ?>
																<button data-name="startStep_1" class="btn-sm btn btn-outline-danger btn-circle right-button disabled" table_id="<?php echo $page_info['id']; ?>" title="<?php echo $this->lang->line("To enable delete button, first disable bot connection."); ?>" data-placement="right" data-toggle="tooltip">
																	<i class="fas fa-trash-alt"></i>
																</button>
															<?php else: ?>
																<button class="btn-sm btn btn-outline-danger btn-circle page_delete" table_id="<?php echo $page_info['id']; ?>" title="<?php echo $this->lang->line("Delete this page from database."); ?>" data-placement="right" data-toggle="tooltip">
																		<i class="fas fa-trash-alt"></i>
																</button>
															<?php endif;?>
														</div>

														<div class="media-title" style="margin-bottom: 0px !important;">
															<a target="_BLANK" href="<?php echo base_url('messenger_bot_analytics/result/') . $page_info['id']; ?>" ><?php echo $page_info['page_name']; ?></a>
														</div>

														<div class="text-small text-muted" style="line-height: 12px !important;">
														  <?php echo $this->lang->line('email'); ?> : </b> <?php echo $page_info['page_email']; ?>
														</div>
														<div class="text-small text-muted">
														  <?php echo $this->lang->line('Page ID'); ?> : </b> <a target="_BLANK" href="https://facebook.com/<?php echo $page_info['page_id']; ?>" ><?php echo $page_info['page_id']; ?></a>

														</div>
													</div>
												</li>
											<?php endforeach;?>
										</ul>
									</div>

									<!-- group lists -->
									<?php if ($this->config->item('facebook_poster_group_enable_disable') == '1'): ?>
										<div class="clearfix"></div> <br>
										<h6 class="mt-3"><?php echo $this->lang->line('Group List') ?> <span class="text-muted">(<?php echo count($value['group_list']); ?> <?php echo $this->lang->line("groups"); ?>)</span></h6>
										<div style="height: 310px;overflow-y:auto;" class="yscroll">
											<ul class="list-unstyled list-unstyled-border">
												<?php foreach ($value['group_list'] as $group_info): ?>
													<li class="media">

														<img alt="image" class="mr-3 rounded" width="50" src="<?php echo $group_info['group_profile']; ?>">

														<div class="media-body">
															<div class="media-right">
																<a  href="#" class="btn-circle btn btn-outline-danger group_delete" table_id="<?php echo $group_info['id']; ?>" title="<?php echo $this->lang->line("Do you want to remove this group from our database?"); ?>" data-placement="left" data-toggle="tooltip">
																	<i class="fas fa-trash-alt"></i>
																</a>
															</div>

															<div class="media-title"><a target="_BLANK" href="https://facebook.com/<?php echo $group_info['group_id']; ?>" ><?php echo $group_info['group_name']; ?></a>
															</div>

															<div class="text-small text-muted">
																<?php echo $this->lang->line('Group ID'); ?> : </b> <?php echo $group_info['group_id']; ?>
															</div>
														</div>
													</li>
												<?php endforeach;?>
											</ul>
										</div>
									<?php endif;?>
								  </div>
								</div>
							  </div>
							</div>


						</div>

					<?php
$i++;
if ($i % 2 == 0) {
	echo "</div><div class='row' style='padding:0 15px;'>";
}

endforeach;
?>
					</div>
				</div>
			<?php else: ?>
				<div class="card" id="nodata">
				  <div class="card-body">
					<div class="empty-state">
					  <img class="img-fluid" style="height: 200px" src="<?php echo base_url('assets/img/drawkit/drawkit-nature-man-colour.svg'); ?>" alt="image">
					  <h2 class="mt-0"><?php echo $this->lang->line("You haven not connected any account yet.") ?></h2>
					  <br/>
					  <h4>
						<div class="text-center">
							<p data-toggle="tooltip" data-placement="bottom" title="<?php echo $this->lang->line("you must be logged in your facebook account for which you want to refresh your access token. for synch your new page, simply refresh your token. if any access token is restricted for any action, refresh your access token."); ?>"> <?php if ($this->config->item('developer_access') != '1') {
	echo $fb_login_button;
}
?></p>
						</div>
					  </h4>
					</div>
				  </div>
				</div>
			<?php endif;?>
		</div>
	</div>

</section>


<div class="modal fade" id="delete_confirmation" data-keyboard="false">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title text-center"><i class="fa fa-flag"></i> <?php echo $this->lang->line("Deletion Report") ?></h4>
			</div>
			<div class="modal-body" id="delete_confirmation_body">

			</div>
		</div>
	</div>
</div>

<!-- <p>
	<span data-name="startStep_1">Element 1 </span>
	-
	<span data-name="startStep_2">Element 2 </span>
	-
	<span data-name="startStep_3">Element 3 </span>
</p> -->

<?php

$doyouwanttodelete = $this->lang->line("Do you want to delete this group from database?");
$ifyoudeletethispage = $this->lang->line("If you delete this page, all the campaigns corresponding to this page will also be deleted. Do you want to delete this page from database?");
$ifyoudeletethisaccount = $this->lang->line("If you delete this account, all the pages, groups and all the campaigns corresponding to this account will also be deleted form database. do you want to delete this account from database?");
$facebooknumericidfirst = $this->lang->line("Please enter your facebook numeric id first");
$ifyoudeletethisgroup = $this->lang->line("If you delete this group, all the campaigns corresponding to this group will also be deleted. Do you want to delete this group from database?");

?>
<script src="<?php echo base_url(); ?>assets/js/jquery.itour.js"></script>

<script>
	$(document).ready(function(){
		$('[data-toggle="tooltip"]').tooltip();
	});

	$("document").ready(function() {
		var base_url = "<?php echo base_url(); ?>";


		// sweet alert + confirmation
		$(document).on('click','.enable_webhook',function(){
			var restart = $(this).attr('restart');
			if(restart == 1)
			{
				var confirm_str = "<?php echo $this->lang->line("Do you really want to re-start Bot Connection for this page?"); ?>";
				var confirm_alert = '<?php echo $this->lang->line("Re-start Bot Connection"); ?>';
			}
			else
			{
				var confirm_str = "<?php echo $this->lang->line("Do you really want to enable Bot Connection for this page?"); ?>";
				var confirm_alert = '<?php echo $this->lang->line("Enable Bot Connection"); ?>';
			}
			swal({
				title: confirm_alert,
				text: confirm_str,
				icon: 'warning',
				buttons: true,
				dangerMode: true,
			})
			.then((willDelete) => {
				if (willDelete)
				{
					var page_id = $(this).attr('bot-enable');

					$(this).removeClass('btn-outline-primary');
					$(this).addClass('btn-primary');
					$(this).addClass('btn-progress');

					$.ajax({
						context: this,
						type:'POST' ,
						url:"<?php echo site_url(); ?>social_accounts/enable_disable_webhook",
						dataType: 'json',
						data:{page_id:page_id,enable_disable:'enable',restart:restart},
						success:function(response){
							$(this).removeClass('btn-progress');
							$(this).removeClass('btn-primary');
							$(this).addClass('btn-outline-primary');
							if(response.status == 1)
							{
								swal('<?php echo $this->lang->line("Success"); ?>', response.message, 'success').then((value) => {
									  location.reload();
									});
							}
							else
							{
								swal('<?php echo $this->lang->line("Error"); ?>', response.message, 'error');
							}
						}
					});
				}
			});


		});

		$(document).on('click','.disable_webhook',function(){

			swal({
				title: '<?php echo $this->lang->line("Disable Bot Connection"); ?>',
				text: '<?php echo $this->lang->line("Do you really want to disable Bot Connection for this page?"); ?>',
				icon: 'warning',
				buttons: true,
				dangerMode: true,
			})
			.then((willDelete) => {
				if (willDelete)
				{
					var page_id = $(this).attr('bot-enable');
					var restart = $(this).attr('restart');

					$(this).removeClass('btn-outline-dark');
					$(this).addClass('btn-dark');
					$(this).addClass('btn-progress');

					$.ajax({
						context: this,
						type:'POST' ,
						url:"<?php echo site_url(); ?>social_accounts/enable_disable_webhook",
						dataType: 'json',
						data:{page_id:page_id,enable_disable:'disable',restart:restart},
						success:function(response){
							$(this).removeClass('btn-progress');
							$(this).removeClass('btn-dark');
							$(this).addClass('btn-outline-dark');
							if(response.status == 1)
							{
								swal('<?php echo $this->lang->line("Success"); ?>', response.message, 'success').then((value) => {
									  location.reload();
									});
							}
							else
							{
								swal('<?php echo $this->lang->line("Error"); ?>', response.message, 'error');
							}
						}
					});
				}
			});


		});


		$(document).on('click','.delete_full_bot',function(){
			var confirm_str = "<?php echo $this->lang->line("By proceeding, it will delete all settings of messenger bot, auto reply campaign, posting campaign, subscribers and all campaign reports of this page. This data can not be retrived. It will not delete the page itself from the system."); ?>";
			swal({
				title: '<?php echo $this->lang->line("Delete Bot Connection & all settings"); ?>',
				text: confirm_str,
				icon: 'warning',
				buttons: true,
				dangerMode: true,
			})
			.then((willDelete) => {
				if (willDelete)
				{
					var page_id = $(this).attr('bot-enable');
					var already_disabled = $(this).attr('already_disabled');

					$(this).removeClass('btn-outline-danger');
					$(this).addClass('btn-danger');
					$(this).addClass('btn-progress');

					$.ajax({
						context: this,
						type:'POST' ,
						url:"<?php echo site_url(); ?>social_accounts/delete_full_bot",
						dataType: 'json',
						data:{page_id:page_id,already_disabled:already_disabled},
						success:function(response){
							$(this).removeClass('btn-progress');
							$(this).removeClass('btn-danger');
							$(this).addClass('btn-outline-danger');
							if(response.status == 1)
							{
								swal('<?php echo $this->lang->line("Success"); ?>', response.message, 'success').then((value) => {
									  location.reload();
									});
							}
							else
							{
								swal('<?php echo $this->lang->line("Error"); ?>', response.message, 'error');
							}
						}
					});
				}
			});


		});



		$(document).on('click','.group_delete',function(e){
			e.preventDefault();
			var ifyoudeletethisgroup = "<?php echo $ifyoudeletethisgroup; ?>";
			var group_table_id = $(this).attr('table_id');
			swal({
				title: '<?php echo $this->lang->line("Warning!"); ?>',
				text: ifyoudeletethisgroup,
				icon: 'warning',
				buttons: true,
				dangerMode: true,
			})
			.then((willDelete) => {
				if (willDelete)
				{
					$(this).removeClass('btn-outline-danger');
					$(this).addClass('btn-danger');
					$(this).addClass('btn-progress');

					$.ajax({
						context: this,
						type:'POST' ,
						url:"<?php echo site_url(); ?>social_accounts/group_delete_action",
						dataType: 'json',
						data:{group_table_id:group_table_id},
						success:function(response){
							$(this).removeClass('btn-progress');
							$(this).removeClass('btn-danger');
							$(this).addClass('btn-outline-danger');
							if(response.status == 1)
							{
								swal('<?php echo $this->lang->line("Success"); ?>', response.message, 'success').then((value) => {
									  location.reload();
									});
							}
							else
							{
								swal('<?php echo $this->lang->line("Error"); ?>', response.message, 'error');
							}
						}
					});
				}
			});


		});



		$(document).on('click','.page_delete',function(){
			var ifyoudeletethispage = "<?php echo $ifyoudeletethispage; ?>";
			swal({
				title: '<?php echo $this->lang->line("Are you sure"); ?>',
				text: ifyoudeletethispage,
				icon: 'warning',
				buttons: true,
				dangerMode: true,
			})
			.then((willDelete) => {
				if (willDelete)
				{
					var page_table_id = $(this).attr('table_id');

					$(this).removeClass('btn-outline-danger');
					$(this).addClass('btn-danger');
					$(this).addClass('btn-progress');

					$.ajax({
						context: this,
						type:'POST' ,
						url:"<?php echo site_url(); ?>social_accounts/page_delete_action",
						dataType: 'json',
						data:{page_table_id : page_table_id},
						success:function(response){
							if(response.status == 1)
							{
								$(this).removeClass('btn-progress');
								$(this).removeClass('btn-danger');
								$(this).addClass('btn-outline-danger');

								swal('<?php echo $this->lang->line("Success"); ?>', response.message, 'success').then((value) => {
									  location.reload();
									});
							}
							else
							{
								swal('<?php echo $this->lang->line("Error"); ?>', response.message, 'error');
							}
						}
					});
				}
			});


		});



		$(document).on('click','.delete_account',function(){
			var ifyoudeletethisaccount = "<?php echo $ifyoudeletethisaccount; ?>";
			swal({
				title: '<?php echo $this->lang->line("Are you sure"); ?>',
				text: ifyoudeletethisaccount,
				icon: 'warning',
				buttons: true,
				dangerMode: true,
			})
			.then((willDelete) => {
				if (willDelete)
				{
					var user_table_id = $(this).attr('table_id');
					$(this).removeClass('btn-outline-danger');
					$(this).addClass('btn-danger');
					$(this).addClass('btn-progress');

					$.ajax({
						context: this,
						type:'POST' ,
						url:"<?php echo site_url(); ?>social_accounts/account_delete_action",
						dataType: 'json',
						data:{user_table_id : user_table_id},
						success:function(response){

							$(this).removeClass('btn-progress');
							$(this).removeClass('btn-danger');
							$(this).addClass('btn-outline-danger');

							if(response.status == 1)
							{
								swal('<?php echo $this->lang->line("Success"); ?>', response.message, 'success').then((value) => {
									  location.reload();
									});
							}
							else
							{
								swal('<?php echo $this->lang->line("Error"); ?>', response.message, 'error');
							}
						}
					});
				}
			});


		});


		// $('#delete_confirmation').on('hidden.bs.modal', function () {
		// 	location.reload();
		// });


		$("#submit").click(function(){
			var facebooknumericidfirst = "<?php echo $facebooknumericidfirst; ?>";
			var fb_numeric_id = $("#fb_numeric_id").val().trim();
			if(fb_numeric_id == '')
			{
				alert(facebooknumericidfirst);
				return false;
			}

			var loading = '<br/><br/><img src="'+base_url+'assets/pre-loader/Fading squares2.gif" class="center-block"><br/>';
			$("#response").html(loading);

			$.ajax
			({
			   type:'POST',
			   // async:false,
			   url:base_url+'social_accounts/send_user_roll_access',
			   data:{fb_numeric_id:fb_numeric_id},
			   success:function(response)
				{
					$("#response").html(response);
				}

			});
		});


		$(document.body).on('click','#fb_confirm',function(){
			var loading = '<br/><br/><img src="'+base_url+'assets/pre-loader/Fading squares2.gif" class="center-block"><br/>';
			$("#response").html(loading);
			$.ajax
			({
			   type:'POST',
			   // async:false,
			   url:base_url+'social_accounts/ajax_get_login_button',
			   data:{},
			   success:function(response)
				{
					$("#response").html(response);
				}

			});
		});


		$(document).ready(function(){

			// $auth_email = $this->db->where('user_login_info', "user_email");
			// console.log($auth_email);
			$.ajax
			({
			   type:'POST',
			   url:base_url+'home/login_confirm',
			   success:function(response)
				{
					if (response.count == 1 && getCookie('tour_email') != response.email) {
						$('body').itour({
							startStep:1,	//Step from which the tour begins
							tourTitle:'Hostbot',
							tourMapVisible:false,
							steps:[{
								title:'Hostbot Guide',
								content:'Enable bot connection',
								name:'startStep_1'
							},{
								title:'Hostbot Guide',
								content:'Start creating bots!',
								name:'startStep_2'
							},{					
								title:'Hostbot Guide',
								content:'For more information, checkout our <a href="https://hostbot.uk/knowledgebase" style="color:#253380; font-weight: bolder;" target="_blank">knowledgebase</a>.',
								contentPosition:'center'
							}]
						});

						setCookie('tour_email', response.email);
						return false;
					}
				}
			});

			function setCookie(name,value,days) {
				var expires = "";
				if (days) {
					var date = new Date();
					date.setTime(date.getTime() + (days*24*60*60*1000));
					expires = "; expires=" + date.toUTCString();
				}
				document.cookie = name + "=" + (value || "")  + expires + "; path=/";
			}
			function getCookie(name) {
				var nameEQ = name + "=";
				var ca = document.cookie.split(';');
				for(var i=0;i < ca.length;i++) {
					var c = ca[i];
					while (c.charAt(0)==' ') c = c.substring(1,c.length);
					if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
				}
				return null;
			}
			function eraseCookie(name) {   
				document.cookie = name+'=; Max-Age=-99999999;';  
			}			
		});		
	})


</script>
