<section class="section">
	<div class="section-header">
		<h1><i class="fas fa-toolbox"></i> <?php echo $page_title; ?></h1>
		<div class="section-header-breadcrumb">
			<div class="breadcrumb-item"><?php echo $this->lang->line("System"); ?></div>
			<div class="breadcrumb-item active"><a href="<?php echo base_url('admin/settings'); ?>"><?php echo $this->lang->line("Settings"); ?></a></div>
			<div class="breadcrumb-item"><?php echo $page_title; ?></div>
		</div>
	</div>

	<?php $this->load->view('admin/theme/message'); ?>

	<?php $save_button = '<div class="card-footer bg-whitesmoke">
	                      <button class="btn btn-primary btn-lg" id="save-btn" type="submit"><i class="fas fa-save"></i> '.$this->lang->line("Save").'</button>
	                      <button class="btn btn-secondary btn-lg float-right" onclick=\'goBack("admin/settings")\' type="button"><i class="fa fa-remove"></i> '. $this->lang->line("Cancel").'</button>
	                    </div>'; ?>
	
	<form class="form-horizontal text-c" enctype="multipart/form-data" action="<?php echo site_url().'admin/general_settings_action';?>" method="POST">	
		<div class="section-body">
			<div id="output-status"></div>
			<div class="row">
				<div class="col-md-8">					
					<div class="card" id="brand">

						<div class="card-header">
							<h4><i class="fas fa-flag"></i> <?php echo $this->lang->line("Brand"); ?></h4>
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-12 col-md-6">
									<div class="form-group">
										<label for=""><i class="fa fa-globe"></i> <?php echo $this->lang->line("Application Name");?> </label>
										<input name="product_name" value="<?php echo $this->config->item('product_name');?>"  class="form-control" type="text">		          
										<span class="red"><?php echo form_error('product_name'); ?></span>
									</div>
								</div>
								<div class="col-12 col-md-6">
									<div class="form-group">
										<label for=""><i class="fa fa-compress"></i> <?php echo $this->lang->line("Application Short Name");?> </label>
										<input name="product_short_name" value="<?php echo $this->config->item('product_short_name');?>"  class="form-control" type="text">
										<span class="red"><?php echo form_error('product_short_name'); ?></span>
									</div>
								</div>
							</div>

							<div class="form-group">
								<label for=""><i class="fas fa-tag"></i> <?php echo $this->lang->line("Slogan");?> </label>
								<input name="slogan" value="<?php echo $this->config->item('slogan');?>"  class="form-control" type="text">
								<span class="red"><?php echo form_error('slogan'); ?></span>
							</div>

							<div class="row">
								<div class="col-12 col-md-6">
									<div class="form-group">
										<label for=""><i class="fa fa-briefcase"></i> <?php echo $this->lang->line("Company Name");?></label>
										<input name="institute_name" value="<?php echo $this->config->item('institute_address1');?>"  class="form-control" type="text">	
										<span class="red"><?php echo form_error('institute_name'); ?></span>
									</div>
								</div>

								<div class="col-12 col-md-6">
									<div class="form-group">
										<label for=""><i class="fa fa-map-marker"></i> <?php echo $this->lang->line("Company Address");?></label>
										<input name="institute_address" value="<?php echo $this->config->item('institute_address2');?>"  class="form-control" type="text">
										<span class="red"><?php echo form_error('institute_address'); ?></span>
									</div>
								</div>
							</div>

							<div class="row">
								<div class="col-12 col-md-6">
									<div class="form-group">
										<label for=""><i class="fa fa-envelope"></i> <?php echo $this->lang->line("Company Email");?> *</label>
										<input name="institute_email" value="<?php echo $this->config->item('institute_email');?>"  class="form-control" type="email">
										<span class="red"><?php echo form_error('institute_email'); ?></span>
									</div>  
								</div>

								<div class="col-12 col-md-6">	
									<div class="form-group">
										<label for=""><i class="fa fa-mobile"></i> <?php echo $this->lang->line("Company Phone");?></label>
										<input name="institute_mobile" value="<?php echo $this->config->item('institute_mobile');?>"  class="form-control" type="text">
										<span class="red"><?php echo form_error('institute_mobile'); ?></span>
									</div>
								</div>
							</div>
						</div>
						<?php echo $save_button; ?>
					</div>

					<div class="card" id="preference">
						<div class="card-header">
							<h4><i class="fas fa-tasks"></i> <?php echo $this->lang->line("Preference"); ?></h4>
						</div>
						<div class="card-body">

				            <div class="row">
								<div class="col-12 col-md-6">
									<div class="form-group">
						             	<label for=""><i class="fa fa-language"></i> <?php echo $this->lang->line("Language");?></label>            			
				               			<?php
										$select_lan="english";
										if($this->config->item('language')!="") $select_lan=$this->config->item('language');
										echo form_dropdown('language',$language_info,$select_lan,'class="form-control select2" id="language"');  ?>		          
				             			<span class="red"><?php echo form_error('language'); ?></span>
						            </div>
						        </div>

						        <div class="col-12 col-md-6">
						            <div class="form-group">
						             	<label for=""><i class="fa fa-clock-o"></i> <?php echo $this->lang->line("Time Zone");?></label>          			
				               			<?php	$time_zone['']=$this->lang->line('Time Zone');
										echo form_dropdown('time_zone',$time_zone,$this->config->item('time_zone'),'class="form-control select2" id="time_zone"');  ?>		          
				             			<span class="red"><?php echo form_error('time_zone'); ?></span>
						            </div>
						        </div>
					        </div>						
						   

				            <div class="form-group">
				             	<label for="email_sending_option"><i class="fa fa-at"></i> <?php echo $this->lang->line('Email Sending Option');?></label> 
		               			<?php
		               			$email_sending_option= $this->config->item('email_sending_option');
		               			if($email_sending_option == '') $email_sending_option = 'php_mail';
		               			?>
								<div class="row">
									<div class="col-12 col-md-6">
										<label class="custom-switch">
										  <input type="radio" name="email_sending_option" value="php_mail" class="custom-switch-input" <?php if($email_sending_option=='php_mail') echo 'checked'; ?>>
										  <span class="custom-switch-indicator"></span>
										  <span class="custom-switch-description"><?php echo $this->lang->line('Use PHP Email Function'); ?></span>
										</label>
									</div>
									<div class="col-12 col-md-6">
										<label class="custom-switch">
										  <input type="radio" name="email_sending_option" value="smtp" class="custom-switch-input" <?php if($email_sending_option=='smtp') echo 'checked'; ?>>
										  <span class="custom-switch-indicator"></span>
										  <span class="custom-switch-description"><?php echo $this->lang->line('Use SMTP Email'); ?>
										  	&nbsp;:&nbsp;<a href="<?php echo base_url('admin/smtp_settings');?>" class="float-right"> <?php echo $this->lang->line("SMTP Setting"); ?> </a></span>
										</label>
									</div>
								</div>
		             			<span class="red"><?php echo form_error('email_sending_option'); ?></span>
				           </div>

   						    <div class="row">
   						        <div class="col-12 col-md-6">
   						        	<div class="form-group">
   						        	  <?php	
   						        	  $force_https = $this->config->item('force_https');
   						        	  if($force_https == '') $force_https='0';
   						        	  ?>
   						        	  <label class="custom-switch mt-2">
   						        	    <input type="checkbox" name="force_https" value="1" class="custom-switch-input"  <?php if($force_https=='1') echo 'checked'; ?>>
   						        	    <span class="custom-switch-indicator"></span>
   						        	    <span class="custom-switch-description"><?php echo $this->lang->line('Force HTTPS');?>?</span>
   						        	    <span class="red"><?php echo form_error('force_https'); ?></span>
   						        	  </label>
   						        	</div>
   						        </div>

   					           	<div class="col-12 col-md-6">
   					           		<div class="form-group">
   					           		  <?php	
   					           		  $enable_signup_form = $this->config->item('enable_signup_form');
           		               			if($enable_signup_form == '') $enable_signup_form='1';
   					           		  ?>
   					           		  <label class="custom-switch mt-2">
   					           		    <input type="checkbox" name="enable_signup_form" value="1" class="custom-switch-input"  <?php if($enable_signup_form=='1') echo 'checked'; ?>>
   					           		    <span class="custom-switch-indicator"></span>
   					           		    <span class="custom-switch-description"><?php echo $this->lang->line('Display Signup Page');?></span>
   					           		    <span class="red"><?php echo form_error('enable_signup_form'); ?></span>
   					           		  </label>
   					           		</div>        				           	
   					            </div>
   					        </div>

						</div>
						<?php echo $save_button; ?>
					</div>

					<div class="card" id="logo-favicon">
						<div class="card-header">
							<h4><i class="fas fa-images"></i> <?php echo $this->lang->line("Logo & Favicon"); ?></h4>
						</div>
						<div class="card-body">			             	

			             	<div class="row">
			             		<div class="col-6">
 					             	<label for=""><i class="fas fa-image"></i> <?php echo $this->lang->line("logo");?> (png)</label>
 					             	<div class="custom-file">
 			                            <input type="file" name="logo" class="custom-file-input">
 			                            <label class="custom-file-label"><?php echo $this->lang->line("Choose File"); ?></label>
 			                            <small><?php echo $this->lang->line("Max Dimension");?> : 700 x 200, <?php echo $this->lang->line("Max Size");?> : 500KB </small>	          
 			                            <span class="red"> <?php echo $this->session->userdata('logo_error'); $this->session->unset_userdata('logo_error'); ?></span>
 			                         </div>
			             		</div>
			             		<div class="col-6 my-auto text-center">
			             			<img class="img-fluid" src="<?php echo base_url().'assets/img/logo.png';?>" alt="Logo"/>
			             		</div>
			             	</div>

			             	<div class="row">
			             		<div class="col-6">
 					             	<label for=""><i class="fas fa-portrait"></i> <?php echo $this->lang->line("Favicon");?> (png)</label>
 					             	<div class="custom-file">
 			                            <input type="file" name="favicon" class="custom-file-input">
 			                            <label class="custom-file-label"><?php echo $this->lang->line("Choose File"); ?></label>
 			                            <small><?php echo $this->lang->line("Dimension");?> : 100 x 100, <?php echo $this->lang->line("Max Size");?> : 50KB </small>	          
 			                            <span class="red"> <?php echo $this->session->userdata('favicon_error'); $this->session->unset_userdata('favicon_error'); ?></span>
 			                         </div>
			             		</div>
			             		<div class="col-6 my-auto text-center">
			             			<img class="img-fluid" src="<?php echo base_url().'assets/img/favicon.png';?>" alt="Favicon" style="max-width:50px;"/>
			             		</div>
			             	</div>
						</div>
						<?php echo $save_button; ?>
					</div>

					<div class="card" id="master-password">
						<div class="card-header">
							<h4><i class="fab fa-keycdn"></i> <?php echo $this->lang->line("Master Password & App Access"); ?></h4>
						</div>
						<div class="card-body">
				           <div class="form-group">
				             	<label for=""><i class="fa fa-key"></i> <?php echo $this->lang->line("Master Password (will be used for login as user)");?></label>
		               			<input name="master_password" value="******"  class="form-control" type="text">
		             			<span class="red"><?php echo form_error('master_password'); ?></span>
				           </div>
						   <div class="row">
						        <div class="col-12 col-md-6">
						        	<div class="form-group">
						        	  <?php	
						        	  $backup_mode = $this->config->item('backup_mode');
						        	  if($backup_mode == '') $backup_mode='0';
						        	  ?>
						        	  <label class="custom-switch mt-2">
						        	    <input type="checkbox" name="backup_mode" value="1" class="custom-switch-input"  <?php if($backup_mode=='1') echo 'checked'; ?>>
						        	    <span class="custom-switch-indicator"></span>
						        	    <span class="custom-switch-description"><?php echo $this->lang->line('Give access to user to set their own Facebook APP');?>?</span>
						        	    <span class="red"><?php echo form_error('backup_mode'); ?></span>
						        	  </label>
						        	</div>
						        </div>

					           	<!-- <div class="col-12 col-md-6">
					           		<div class="form-group">
					           		  <?php	
					           		  $developer_access = $this->config->item('developer_access');
	   		               			if($developer_access == '') $developer_access='0';
					           		  ?>
					           		  <label class="custom-switch mt-2">
					           		    <input type="checkbox" name="developer_access" value="1" class="custom-switch-input"  <?php if($developer_access=='1') echo 'checked'; ?>>
					           		    <span class="custom-switch-indicator"></span>
					           		    <span class="custom-switch-description"><?php echo $this->lang->line('Use Approved Facebook App of Author?');?> </span>
					           		    <a href="#" data-placement="top"  data-html="true" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("Use Approved Facebook App of Author?") ?>" data-content="<?php echo $this->lang->line("If you select Yes, you may skip to add your own app. You can use Author's app. But this option only for the admin only. This can't be used for other system users. User management feature will be disapeared."); ?><br><br><?php echo $this->lang->line("If select No , you will need to add your own app & get approval and system users can use it.");?>"><i class='fa fa-info-circle'></i> </a>

					           		    <span class="red"><?php echo form_error('developer_access'); ?></span>
					           		    
					           		  </label>
					           		</div>        				           	
					            </div> -->
					        </div>
						</div>
						<?php echo $save_button; ?>
					</div>

					<div class="card" id="subscriber">
						<div class="card-header">
							<h4><i class="fas fa-user-circle"></i> <?php echo $this->lang->line("Subscriber"); ?></h4>
						</div>
						<div class="card-body">				       
			              <div class="row">
			              		<div class="col-12 col-md-6">
	 				              	<div class="form-group">
	 					             	<label for=""><i class="fa fa-sort-numeric-asc"></i> <?php echo $this->lang->line("Avatar download limit per cron job");?></label>
				             			<?php 
					             			$messengerbot_subscriber_avatar_download_limit_per_cron_job=$this->config->item('messengerbot_subscriber_avatar_download_limit_per_cron_job');
					             			if($messengerbot_subscriber_avatar_download_limit_per_cron_job=="") $messengerbot_subscriber_avatar_download_limit_per_cron_job=25; 
				             			?>
	 			               			<input name="messengerbot_subscriber_avatar_download_limit_per_cron_job" value="<?php echo $messengerbot_subscriber_avatar_download_limit_per_cron_job;?>"  class="form-control" type="number" min="1">          
	 			             			<span class="red"><?php echo form_error('messengerbot_subscriber_avatar_download_limit_per_cron_job'); ?></span>
	 					            </div>
			              		</div>
			              		<div class="col-12 col-md-6">
	 				              	<div class="form-group">
	 					             	<label for=""><i class="fas fa-edit"></i> <?php echo $this->lang->line("Profile information update limit per cron job");?></label>
				             			<?php 
					             			$messengerbot_subscriber_profile_update_limit_per_cron_job=$this->config->item('messengerbot_subscriber_profile_update_limit_per_cron_job');
					             			if($messengerbot_subscriber_profile_update_limit_per_cron_job=="") $messengerbot_subscriber_profile_update_limit_per_cron_job=100; 
				             			?>
	 			               			<input name="messengerbot_subscriber_profile_update_limit_per_cron_job" value="<?php echo $messengerbot_subscriber_profile_update_limit_per_cron_job;?>"  class="form-control" type="number" min="1">		          
	 			             			<span class="red"><?php echo form_error('messengerbot_subscriber_profile_update_limit_per_cron_job'); ?></span>
	 					            </div>
			              		</div>
			              	</div>
						</div>
						<?php echo $save_button; ?>
					</div>

					<!-- <div class="card" id="auto-reply">
						<div class="card-header">
							<h4><i class="fas fa-reply-all"></i> <?php echo $this->lang->line("Auto Reply"); ?></h4>
						</div>
						<div class="card-body">
			              	<div class="row">
			              		<div class="col-12 col-md-6">
      				              	<div class="form-group">
      					             	<label for=""><i class="fas fa-business-time"></i> <?php echo $this->lang->line("Delay used in auto-reply (seconds)");?></label>
             	             			<?php 
             		             			$auto_reply_delay_time=$this->config->item('auto_reply_delay_time');
             		             			if($auto_reply_delay_time=="") $auto_reply_delay_time=10; 
             	             			?>
      			               			<input name="auto_reply_delay_time" value="<?php echo $auto_reply_delay_time;?>"  class="form-control" type="number" min="1">		          
      			             			<span class="red"><?php echo form_error('auto_reply_delay_time'); ?></span>
      					            </div>
			              		</div>
			              		<div class="col-12 col-md-6">
      				              	<div class="form-group">
      					             	<label for=""><i class="fa fa-sort-numeric-asc"></i> <?php echo $this->lang->line("Num: of campaign processed per auto reply");?></label>
	 			             			<?php 
	 				             			$auto_reply_campaign_per_cron_job=$this->config->item('auto_reply_campaign_per_cron_job');
	 				             			if($auto_reply_campaign_per_cron_job=="") $auto_reply_campaign_per_cron_job=10; 
	 			             			?>
      			               			<input name="auto_reply_campaign_per_cron_job" value="<?php echo $auto_reply_campaign_per_cron_job;?>"  class="form-control" type="number" min="1">		          
      			             			<span class="red"><?php echo form_error('auto_reply_campaign_per_cron_job'); ?></span>
      					            </div>
			              		</div>
			              	</div>	

				            <div class="row">
			              		<div class="col-12 col-md-6">
			              			<div class="form-group">
						             	<label for=""><i class="fas fa-history"></i> <?php echo $this->lang->line("Old comment that system will auto reply?");?></label>
             	             			<?php 
             		             			$number_of_old_comment_reply=$this->config->item('number_of_old_comment_reply');
             		             			if($number_of_old_comment_reply=="") $number_of_old_comment_reply=20; 
             	             			?>
				               			<input name="number_of_old_comment_reply" value="<?php echo $number_of_old_comment_reply;?>"  class="form-control" type="number" min="1">	
				               			<span><i><small><?php echo $this->lang->line('system has the ability to reply maximum 200 old comments and minimum 20 old comments'); ?></small></i></span>	 	          
				             			<span class="red"><?php echo form_error('number_of_old_comment_reply'); ?></span>
						            </div>
			              		</div>

			              		<div class="col-12 col-md-6">
      					            <div class="form-group">
      					             	<label for=""><i class="fa fa-clock-o"></i> <?php echo $this->lang->line("Auto-reply campaign live duration (days)");?></label>
             	             			<?php 
             		             			$auto_reply_campaign_live_duration=$this->config->item('auto_reply_campaign_live_duration');
             		             			if($auto_reply_campaign_live_duration=="") $auto_reply_campaign_live_duration=50; 
             	             			?>
      			               			<input name="auto_reply_campaign_live_duration" value="<?php echo $auto_reply_campaign_live_duration;?>"  class="form-control" type="number" min="1">		          
      			             			<span class="red"><?php echo form_error('auto_reply_campaign_live_duration'); ?></span>
      					            </div>
			              		</div>
			              	</div>
						    <div class="row">
						        <div class="col-12 col-md-6">
						        	<div class="form-group">
						        	  <?php	
						        	  $autoreply_renew_access = $this->config->item('autoreply_renew_access');
						        	  if($autoreply_renew_access == '') $autoreply_renew_access='0';
						        	  ?>
						        	  <label class="custom-switch mt-2">
						        	    <input type="checkbox" name="autoreply_renew_access" value="1" class="custom-switch-input"  <?php if($autoreply_renew_access=='1') echo 'checked'; ?>>
						        	    <span class="custom-switch-indicator"></span>
						        	    <span class="custom-switch-description"><?php echo $this->lang->line('Give autoreply renew access to users');?>?</span>
						        	    <span class="red"><?php echo form_error('autoreply_renew_access'); ?></span>
						        	  </label>
						        	</div>
						        </div>

					           	<div class="col-12 col-md-6">
					           		<div class="form-group">
					           		  <?php	
					           		  $read_page_mailboxes_permission = $this->config->item('read_page_mailboxes_permission');
        		               			if($read_page_mailboxes_permission == '') $read_page_mailboxes_permission='yes';
					           		  ?>
					           		  <label class="custom-switch mt-2">
					           		    <input type="checkbox" name="read_page_mailboxes_permission" value="yes" class="custom-switch-input"  <?php if($read_page_mailboxes_permission=='yes') echo 'checked'; ?>>
					           		    <span class="custom-switch-indicator"></span>
					           		    <span class="custom-switch-description"><?php echo $this->lang->line('Do you have read_page_mailboxes permission?');?></span>
					           		    <span class="red"><?php echo form_error('read_page_mailboxes_permission'); ?></span>
					           		  </label>
					           		</div>        				           	
					            </div> 
					        </div>

						</div>
						<?php echo $save_button; ?>
					</div> -->
					
					<div class="card" id="persistent-menu">
						<div class="card-header">
							<h4><i class="fas fa-bars"></i> <?php echo $this->lang->line("Persistent Menu"); ?></h4>
						</div>
						<div class="card-body">
			              	<div class="row">
			              		<div class="col-12 col-md-6">
      				              	<div class="form-group">
      					             	<label for=""><i class="fas fa-copyright"></i> <?php echo $this->lang->line("Copyright text");?></label>
             	             			<?php 
             		             			$persistent_menu_copyright_text=$this->config->item('persistent_menu_copyright_text');
             		             			if($persistent_menu_copyright_text=="") $persistent_menu_copyright_text=$this->config->item("product_name");
             	             			?>
      			               			<input name="persistent_menu_copyright_text" value="<?php echo $persistent_menu_copyright_text;?>"  class="form-control" type="text">		          
      			             			<span class="red"><?php echo form_error('persistent_menu_copyright_text'); ?></span>
      					            </div>
			              		</div>
			              		<div class="col-12 col-md-6">
      				              	<div class="form-group">
      					             	<label for=""><i class="fa fa-link"></i> <?php echo $this->lang->line("Copyright URL");?></label>
	 			             			<?php 
	 				             			$persistent_menu_copyright_url=$this->config->item('persistent_menu_copyright_url');
	 				             			if($persistent_menu_copyright_url=="") $persistent_menu_copyright_url=base_url();
	 			             			?>
      			               			<input name="persistent_menu_copyright_url" value="<?php echo $persistent_menu_copyright_url;?>"  class="form-control" type="text">		          
      			             			<span class="red"><?php echo form_error('persistent_menu_copyright_url'); ?></span>
      					            </div>
			              		</div>
			              	</div>

						</div>
						<?php echo $save_button; ?>
					</div>
					<div class="card" id="messenger-broadcast">
						<div class="card-header">
							<h4><i class="fas fa-mail-bulk"></i> <?php echo $this->lang->line("Messenger Broadcast"); ?></h4>
						</div>
						<div class="card-body">
			              	<div class="row">
			              		<div class="col-12 col-md-6">
      				              	<div class="form-group">
      					             	<label for=""><i class="fa fa-sort-numeric-asc"></i> <?php echo $this->lang->line("Conversation Broadcast - number of message send per cron job");?></label>
     			             			<?php 
     				             			$number_of_message_to_be_sent_in_try=$this->config->item('number_of_message_to_be_sent_in_try');
     				             			if($number_of_message_to_be_sent_in_try=="") $number_of_message_to_be_sent_in_try=10; 
     			             			?>
      			               			<input name="number_of_message_to_be_sent_in_try" value="<?php echo $number_of_message_to_be_sent_in_try;?>"  class="form-control" type="number" min="1">          
      			             			<span class="red"><?php echo form_error('number_of_message_to_be_sent_in_try'); ?></span>
      					            </div>
			              		</div>
			              		<div class="col-12 col-md-6">
      				              	<div class="form-group">
      					             	<label for=""><i class="fas fa-edit"></i> <?php echo $this->lang->line("Conversation Broadcast - message sending report update frequency");?></label>
     			             			<?php 
     				             			$update_report_after_time=$this->config->item('update_report_after_time');
     				             			if($update_report_after_time=="") $update_report_after_time=5; 
     			             			?>
      			               			<input name="update_report_after_time" value="<?php echo $update_report_after_time;?>"  class="form-control" type="number" min="1">		          
      			             			<span class="red"><?php echo form_error('update_report_after_time'); ?></span>
      					            </div>
			              		</div>
			              	</div>	

			              	<div class="row <?php if(!$this->is_broadcaster_exist) echo 'hidden';?>">
			              		<div class="col-12 col-md-6">
      				              	<div class="form-group">
      					             	<label for=""><i class="fa fa-sort-numeric-asc"></i> <?php echo $this->lang->line("Subscriber Broadcast - number of message send per cron job");?></label>
     			             			<?php 
     				             			$broadcaster_number_of_message_to_be_sent_in_try=$this->config->item('broadcaster_number_of_message_to_be_sent_in_try');
     				             			if($broadcaster_number_of_message_to_be_sent_in_try=="") $broadcaster_number_of_message_to_be_sent_in_try=120; 
     			             			?>
      			               			<input name="broadcaster_number_of_message_to_be_sent_in_try" value="<?php echo $broadcaster_number_of_message_to_be_sent_in_try;?>"  class="form-control" type="number" min="1">          
      			             			<span class="red"><?php echo form_error('broadcaster_number_of_message_to_be_sent_in_try'); ?></span>
      					            </div>
			              		</div>
			              		<div class="col-12 col-md-6">
      				              	<div class="form-group">
      					             	<label for=""><i class="fas fa-edit"></i> <?php echo $this->lang->line("Subscriber Broadcast - message sending report update frequency");?></label>
     			             			<?php 
     				             			$broadcaster_update_report_after_time=$this->config->item('broadcaster_update_report_after_time');
     				             			if($broadcaster_update_report_after_time=="") $broadcaster_update_report_after_time=20; 
     			             			?>
      			               			<input name="broadcaster_update_report_after_time" value="<?php echo $broadcaster_update_report_after_time;?>"  class="form-control" type="number" min="1">		          
      			             			<span class="red"><?php echo form_error('broadcaster_update_report_after_time'); ?></span>
      					            </div>
			              		</div>
			              	</div>


						</div>
						<?php echo $save_button; ?>
					</div>

					<div class="card" id="group-posting">
						<div class="card-header">
							<h4><i class="fas fa-share-square"></i> <?php echo $this->lang->line("Facebook Poster"); ?></h4>
						</div>
						<div class="card-body">
						    <div class="row">
						    	<div class="col-12">
						    		<div class="form-group">
						    		  <?php	
						    		  $facebook_poster_botenabled_pages = $this->config->item('facebook_poster_botenabled_pages');
						    		  if($facebook_poster_botenabled_pages == '') $facebook_poster_botenabled_pages='0';
						    		  ?>
						    		  <label class="custom-switch mt-2">
						    		    <input type="checkbox" name="facebook_poster_botenabled_pages" value="1" class="custom-switch-input"  <?php if($facebook_poster_botenabled_pages=='1') echo 'checked'; ?>>
						    		    <span class="custom-switch-indicator"></span>
						    		    <span class="custom-switch-description"><?php echo $this->lang->line('Use only bot connection enabled pages for posting.');?></span>
						    		    <span class="red"><?php echo form_error('facebook_poster_botenabled_pages'); ?></span>
						    		  </label>
						    		</div>
						    	</div>
						    	<!--  
						        <div class="col-12">
						        	<div class="form-group">
						        	  <?php	
						        	  $facebook_poster_group_enable_disable = $this->config->item('facebook_poster_group_enable_disable');
						        	  if($facebook_poster_group_enable_disable == '') $facebook_poster_group_enable_disable='0';
						        	  ?>
						        	  <label class="custom-switch mt-2">
						        	    <input type="checkbox" name="facebook_poster_group_enable_disable" value="1" class="custom-switch-input"  <?php if($facebook_poster_group_enable_disable=='1') echo 'checked'; ?>>
						        	    <span class="custom-switch-indicator"></span>
						        	    <span class="custom-switch-description"><?php echo $this->lang->line('Do You Want To Enable Group Post?');?></span>
						        	    <span class="red"><?php echo form_error('facebook_poster_group_enable_disable'); ?></span>
						        	  </label>
						        	</div>
						        </div>
						        -->
					        </div>
						</div>
						<?php echo $save_button; ?>
					</div>

					<?php if($this->session->userdata('license_type') == 'double') { ?>
					<div class="card" id="support-desk">
						<div class="card-header">
							<h4><i class="fas fa-headset"></i> <?php echo $this->lang->line("Support Desk"); ?></h4>
						</div>
						<div class="card-body">
			           		<div class="form-group">
			           		  <?php	
		               			$enable_support = $this->config->item('enable_support');
		               			if($enable_support == '') $enable_support='1';
		               		  ?>
			           		  <label class="custom-switch mt-2">
			           		    <input type="checkbox" name="enable_support" value="1" class="custom-switch-input"  <?php if($enable_support=='1') echo 'checked'; ?>>
			           		    <span class="custom-switch-indicator"></span>
			           		    <span class="custom-switch-description"><?php echo $this->lang->line('Enable Support Desk for Users');?></span>
			           		    <span class="red"><?php echo form_error('enable_support'); ?></span>
			           		  </label>
			           		</div>
						</div>
						<?php echo $save_button; ?>
					</div>
					<?php } ?>

					<div class="card" id="file-upload">
						<div class="card-header">
							<h4><i class="fas fa-cloud-upload-alt"></i> <?php echo $this->lang->line("File Upload"); ?></h4>
						</div>
						<div class="card-body">
			              	<div class="row">
			              		<div class="col-12 col-md-4">
      				              	<ul class="nav nav-pills flex-column" id="myTab4" role="tablist">
      				              	  <li class="nav-item">
      				              	    <a class="nav-link active" id="facebook_poster_content" data-toggle="tab" href="#facebook_poster" role="tab" aria-controls="home" aria-selected="true"><?php echo $this->lang->line("Facebook Poster"); ?></a>
      				              	  </li>
      				              	  
      				              	  <li class="nav-item">
      				              	    <a class="nav-link" id="auto_reply_content" data-toggle="tab" href="#auto_reply_up" role="tab" aria-controls="profile" aria-selected="false"><?php echo $this->lang->line("Auto Reply"); ?></a>
      				              	  </li>
      				              	  
      				              	  <li class="nav-item hidden">
      				              	    <a class="nav-link" id="comboposter_content" data-toggle="tab" href="#comboposter" role="tab" aria-controls="contact" aria-selected="false"><?php echo $this->lang->line("Combo Poster"); ?></a>
      				              	  </li>
									
								      <li class="nav-item hidden">
										  <a class="nav-link" id="vidcaster_content" data-toggle="tab" href="#vidcaster" role="tab" aria-controls="contact" aria-selected="false"><?php echo $this->lang->line("Vidcaster Live"); ?></a>
									  </li>

								        <li class="nav-item">
								  		  <a class="nav-link" id="messenger_content" data-toggle="tab" href="#messenger_bot" role="tab" aria-controls="contact" aria-selected="false"><?php echo $this->lang->line("Messenger Bot") ?></a>
								  	  </li>

      				              	</ul>
			              		</div>
			              		<div class="col-12 col-md-8">
      				              	<div class="tab-content no-padding" id="myTab2Content">

      				              	 <div class="tab-pane fade show active" id="facebook_poster" role="tabpanel" aria-labelledby="facebook_poster_content">
		     				              	<div class="form-group">
		     					             	<label for=""><i class="fas fa-image"></i> <?php echo $this->lang->line("Image Upload Limit (MB)");?></label>
		    			             			<?php 
		    				             			$facebook_poster_image_upload_limit=$this->config->item('facebook_poster_image_upload_limit');
		    				             			if($facebook_poster_image_upload_limit=="") $facebook_poster_image_upload_limit=1; 
		    			             			?>
		     			               			<input name="facebook_poster_image_upload_limit" value="<?php echo $facebook_poster_image_upload_limit;?>"  class="form-control" type="number" min="1">	
		     			               			          
		     			             			<span class="red"><?php echo form_error('facebook_poster_image_upload_limit'); ?></span>
		     					            </div>

	         				              	<div class="form-group">
	         					             	<label for=""><i class="fas fa-video"></i> <?php echo $this->lang->line("Video Upload Limit (MB)");?></label>
	        			             			<?php 
	        				             			$facebook_poster_video_upload_limit=$this->config->item('facebook_poster_video_upload_limit');
	        				             			if($facebook_poster_video_upload_limit=="") $facebook_poster_video_upload_limit=10; 
	        			             			?>
	         			               			<input name="facebook_poster_video_upload_limit" value="<?php echo $facebook_poster_video_upload_limit;?>"  class="form-control" type="number" min="1">	
	         			               			          
	         			             			<span class="red"><?php echo form_error('facebook_poster_video_upload_limit'); ?></span>
	         					            </div>
      				              	  </div>
      				              	 
      				              	  <div class="tab-pane fade" id="auto_reply_up" role="tabpanel" aria-labelledby="auto_reply_content">
		     				              	<div class="form-group">
		     					             	<label for=""><i class="fas fa-image"></i> <?php echo $this->lang->line("Image Upload Limit (MB)");?></label>
		    			             			<?php 
		    				             			$autoreply_image_upload_limit=$this->config->item('autoreply_image_upload_limit');
		    				             			if($autoreply_image_upload_limit=="") $autoreply_image_upload_limit=1; 
		    			             			?>
		     			               			<input name="autoreply_image_upload_limit" value="<?php echo $autoreply_image_upload_limit;?>"  class="form-control" type="number" min="1">	
		     			               			          
		     			             			<span class="red"><?php echo form_error('autoreply_image_upload_limit'); ?></span>
		     					            </div>

		     				              	<div class="form-group">
		     					             	<label for=""><i class="fas fa-video"></i> <?php echo $this->lang->line("Video Upload Limit (MB)");?></label>
		    			             			<?php 
		    				             			$autoreply_video_upload_limit=$this->config->item('autoreply_video_upload_limit');
		    				             			if($autoreply_video_upload_limit=="") $autoreply_video_upload_limit=3; 
		    			             			?>
		     			               			<input name="autoreply_video_upload_limit" value="<?php echo $autoreply_video_upload_limit;?>"  class="form-control" type="number" min="1">	
		     			               			          
		     			             			<span class="red"><?php echo form_error('autoreply_video_upload_limit'); ?></span>
		     					            </div>
      				              	  </div>
      				              	  
      				              	  <div class="tab-pane fade" id="comboposter" role="tabpanel" aria-labelledby="comboposter_content">
	  	     				              	<div class="form-group">
	  	     					             	<label for=""><i class="fas fa-image"></i> <?php echo $this->lang->line("Image Upload Limit (MB)");?></label>
	  	    			             			<?php 
	  	    				             			$comboposter_image_upload_limit=$this->config->item('comboposter_image_upload_limit');
	  	    				             			if($comboposter_image_upload_limit=="") $comboposter_image_upload_limit=1; 
	  	    			             			?>
	  	     			               			<input name="comboposter_image_upload_limit" value="<?php echo $comboposter_image_upload_limit;?>"  class="form-control" type="number" min="1">	
	  	     			               			          
	  	     			             			<span class="red"><?php echo form_error('comboposter_image_upload_limit'); ?></span>
	  	     					            </div>

	  	     				              	<div class="form-group">
	  	     					             	<label for=""><i class="fas fa-video"></i> <?php echo $this->lang->line("Video Upload Limit (MB)");?></label>
	  	    			             			<?php 
	  	    				             			$comboposter_video_upload_limit=$this->config->item('comboposter_video_upload_limit');
	  	    				             			if($comboposter_video_upload_limit=="") $comboposter_video_upload_limit=10; 
	  	    			             			?>
	  	     			               			<input name="comboposter_video_upload_limit" value="<?php echo $comboposter_video_upload_limit;?>"  class="form-control" type="number" min="1">	
	  	     			               			          
	  	     			             			<span class="red"><?php echo form_error('comboposter_video_upload_limit'); ?></span>
	  	     					            </div>
      				              	  </div>

    				              	  <div class="tab-pane fade" id="vidcaster" role="tabpanel" aria-labelledby="vidcaster_content">
	  	     				              	<div class="form-group">
	  	     					             	<label for=""><i class="fas fa-image"></i> <?php echo $this->lang->line("Image Upload Limit (MB)");?></label>
	  	    			             			<?php 
	  	    				             			$vidcaster_image_upload_limit=$this->config->item('vidcaster_image_upload_limit');
	  	    				             			if($vidcaster_image_upload_limit=="") $vidcaster_image_upload_limit=1; 
	  	    			             			?>
	  	     			               			<input name="vidcaster_image_upload_limit" value="<?php echo $vidcaster_image_upload_limit;?>"  class="form-control" type="number" min="1">	
	  	     			               			          
	  	     			             			<span class="red"><?php echo form_error('vidcaster_image_upload_limit'); ?></span>
	  	     					            </div>

	  	     				              	<div class="form-group">
	  	     					             	<label for=""><i class="fas fa-video"></i> <?php echo $this->lang->line("Video Upload Limit (MB)");?></label>
	  	    			             			<?php 
	  	    				             			$vidcaster_video_upload_limit=$this->config->item('vidcaster_video_upload_limit');
	  	    				             			if($vidcaster_video_upload_limit=="") $vidcaster_video_upload_limit=30; 
	  	    			             			?>
	  	     			               			<input name="vidcaster_video_upload_limit" value="<?php echo $vidcaster_video_upload_limit;?>"  class="form-control" type="number" min="1">	
	  	     			               			          
	  	     			             			<span class="red"><?php echo form_error('vidcaster_video_upload_limit'); ?></span>
	  	     					            </div>
    				              	  </div>
      				              	  <div class="tab-pane fade" id="messenger_bot" role="tabpanel" aria-labelledby="messenger_content">
  	  	     				              	<div class="form-group">
  	  	     					             	<label for=""><i class="fas fa-image"></i> <?php echo $this->lang->line("Image Upload Limit (MB)");?></label>
  	  	    			             			<?php 
  	  	    				             			$messengerbot_image_upload_limit=$this->config->item('messengerbot_image_upload_limit');
  	  	    				             			if($messengerbot_image_upload_limit=="") $messengerbot_image_upload_limit=1; 
  	  	    			             			?>
  	  	     			               			<input name="messengerbot_image_upload_limit" value="<?php echo $messengerbot_image_upload_limit;?>"  class="form-control" type="number" min="1">	
  	  	     			               			          
  	  	     			             			<span class="red"><?php echo form_error('messengerbot_image_upload_limit'); ?></span>
  	  	     					            </div>

  	  	     				              	<div class="form-group">
  	  	     					             	<label for=""><i class="fas fa-video"></i> <?php echo $this->lang->line("Video Upload Limit (MB)");?></label>
  	  	    			             			<?php 
  	  	    				             			$messengerbot_video_upload_limit=$this->config->item('messengerbot_video_upload_limit');
  	  	    				             			if($messengerbot_video_upload_limit=="") $messengerbot_video_upload_limit=5; 
  	  	    			             			?>
  	  	     			               			<input name="messengerbot_video_upload_limit" value="<?php echo $messengerbot_video_upload_limit;?>"  class="form-control" type="number" min="1">	
  	  	     			               			          
  	  	     			             			<span class="red"><?php echo form_error('messengerbot_video_upload_limit'); ?></span>
  	  	     					            </div>

             				              	<div class="form-group">
             					             	<label for=""><i class="fas fa-headset"></i> <?php echo $this->lang->line("Audio Upload Limit (MB)");?></label>
            			             			<?php 
            				             			$messengerbot_audio_upload_limit=$this->config->item('messengerbot_audio_upload_limit');
            				             			if($messengerbot_audio_upload_limit=="") $messengerbot_audio_upload_limit=3; 
            			             			?>
             			               			<input name="messengerbot_audio_upload_limit" value="<?php echo $messengerbot_audio_upload_limit;?>"  class="form-control" type="number" min="1">	
             			               			          
             			             			<span class="red"><?php echo form_error('messengerbot_audio_upload_limit'); ?></span>
             					            </div>

             				              	<div class="form-group">
             					             	<label for=""><i class="fas fa-file"></i> <?php echo $this->lang->line("File Upload Limit (MB)");?></label>
            			             			<?php 
            				             			$messengerbot_file_upload_limit=$this->config->item('messengerbot_file_upload_limit');
            				             			if($messengerbot_file_upload_limit=="") $messengerbot_file_upload_limit=2; 
            			             			?>
             			               			<input name="messengerbot_file_upload_limit" value="<?php echo $messengerbot_file_upload_limit;?>"  class="form-control" type="number" min="1">	
             			               			          
             			             			<span class="red"><?php echo form_error('messengerbot_file_upload_limit'); ?></span>
             					            </div>
      				              	  </div>

      				              	</div>
			              		</div>
			              	</div>	

				         
						</div>
						<?php echo $save_button; ?>
					</div>

					<div class="card" id="server-status">
						<div class="card-header">
							<h4><i class="fas fa-server"></i> <?php echo $this->lang->line("Server Status"); ?></h4>
						</div>
						<div class="card-body">
							<?php

							$sql="SHOW VARIABLES;";
				            $mysql_variables=$this->basic->execute_query($sql);
				            $variables_array_format=array();
				            foreach($mysql_variables as $my_var){
				                $variables_array_format[$my_var['Variable_name']]=$my_var['Value'];
				            }
				            $disply_index = array("version","innodb_version","innodb_log_file_size","wait_timeout","max_connections","connect_timeout","max_allowed_packet");

							$list1=$list2="";						  
						    $make_dir = (!function_exists('mkdir')) ? $this->lang->line("Disabled"):$this->lang->line("Enabled");
						    $zip_archive = (!class_exists('ZipArchive')) ? $this->lang->line("Disabled"):$this->lang->line("Enabled");
						    $list1 .= "<li class='list-group-item'><b>mkdir</b> : ".$make_dir."</li>"; 
						    $list2 .= "<li class='list-group-item'><b>ZipArchive</b> : ".$zip_archive."</li>"; 

						    if(function_exists('curl_version'))	$curl="Enabled";								    
							else $curl="Disabled";

							if(function_exists('mb_detect_encoding')) $mbstring="Enabled";								    
							else $mbstring="Disabled";

							if(function_exists('set_time_limit')) $set_time_limit="Enabled";								    
							else $set_time_limit="Disabled";

							if(function_exists('exec')) $exec="Enabled";								    
							else $exec="Disabled";

							$list2 .= "<li class='list-group-item'><b>curl</b> : ".$curl."</li>";
						    $list1 .= "<li class='list-group-item'><b>exec</b> : ".$exec."</li>"; 
							$list2 .= "<li class='list-group-item'><b>mb_detect_encoding</b> : ".$mbstring."</li>"; 
							$list2 .= "<li class='list-group-item'><b>set_time_limit</b> : ".$set_time_limit."</li>"; 


						    if(function_exists('ini_get'))
							{								 
								if( ini_get('safe_mode') )
							    $safe_mode="ON, please set safe_mode=off";								    
							    else $safe_mode="OFF";

							    if( ini_get('open_basedir')=="")
							    $open_basedir="No Value";								    
							    else $open_basedir="Has value";

							    if( ini_get('allow_url_fopen'))
							    $allow_url_fopen="TRUE";								    
							    else $allow_url_fopen="FALSE";

							    $list1 .= "<li class='list-group-item'><b>safe_mode</b> : ".$safe_mode."</li>"; 
							    $list2 .= "<li class='list-group-item'><b>open_basedir</b> : ".$open_basedir."</li>"; 
							    $list1 .= "<li class='list-group-item'><b>allow_url_fopen</b> : ".$allow_url_fopen."</li>";	
								$list1 .= "<li class='list-group-item'><b>upload_max_filesize</b> : ".ini_get('upload_max_filesize')."</li>";   
						    	$list1 .= "<li class='list-group-item'><b>max_input_time</b> : ".ini_get('max_input_time')."</li>";
					       		$list2 .= "<li class='list-group-item'><b>post_max_size</b> : ".ini_get('post_max_size')."</li>"; 
						    	$list2 .= "<li class='list-group-item'><b>max_execution_time</b> : ".ini_get('max_execution_time')."</li>";
													    
							}						       

					        $php_version = (function_exists('ini_get') && phpversion()!=FALSE) ? phpversion() : ""; ?>							

						    <div class="row">
							  	<div class="col-12 col-lg-6">								  		
									<ul class="list-group">
										<li class='list-group-item active'>PHP</li>  
							  			<li class='list-group-item'><b>PHP version : </b> <?php echo $php_version; ?></li>   
										<?php echo $list1; ?>
									</ul>
							  	</div>
							  	<div class="col-12 col-lg-6">
							  		<ul class="list-group">
							  			<li class='list-group-item active'>PHP</li>
							  			<?php echo $list2; ?>
									</ul>
							  	</div>
							  	<div class="col-12">
							  		<br>
							  		<ul class="list-group">
							  			<li class='list-group-item active'>MySQL</li>  
							  			
							  			<?php 
							  			foreach ($disply_index as $value) 
							  			{
							  				if(isset($variables_array_format[$value]))
							  				echo "<li class='list-group-item'><b>".$value."</b> : ".$variables_array_format[$value]."</li>";  
							  			} 
							  			?>
									</ul>
							  	</div>

							  	<!-- <div class="col-12">
							  		<br>
							  		<ul class="list-group">
							  			<li class='list-group-item active'>FFMPEG</li>
								  		<?php 
		  		        				if(function_exists('ini_get'))
		  		        				{		  		        				
		  		        					$ffmpeg_path = $this->config->item("ffmpeg_path");
	  										
	  										if($ffmpeg_path=='') $ffmpeg_path="ffmpeg";
	  										echo "<li class='list-group-item'><b>FFMPEG version : </b>";
	  		        						
	  		        						if(!function_exists('exec')) echo "unknown</li>";
	  		        						else
	  		        						{	  		        							
	  											$a=exec($ffmpeg_path." -version -loglevel error 2>&1",$error_message);
	  		        							if($a!='') echo $a."</li>";
	  		        							echo "<li class='list-group-item'>";
	  		        								if(isset($error_message) && !empty($error_message))
	  		        								echo '<pre class="language-javascript text-left"><code class="dlanguage-javascript"><span class="token keyword">FFMPEG Info :';print_r($error_message);echo '</span></code></pre>';
		  		        						echo "</li>";
	  		        						}
		  		        				} 

		  		        				?>
		  		        			</ul>
							  	</div> -->
						    </div>
							  	
						</div>
					</div>	
				</div>

				<div class="col-md-4 d-none d-sm-block">
					<div class="sidebar-item">
						<div class="make-me-sticky">
							<div class="card">
								<div class="card-header">
									<h4><i class="fas fa-columns"></i> <?php echo $this->lang->line("Sections"); ?></h4>
								</div>
								<div class="card-body">
									<ul class="nav nav-pills flex-column settings_menu">
										<li class="nav-item"><a href="#brand" class="nav-link"><i class="fas fa-flag"></i> <?php echo $this->lang->line("Brand"); ?></a></li>
										<li class="nav-item"><a href="#preference" class="nav-link"><i class="fas fa-tasks"></i> <?php echo $this->lang->line("Preference"); ?></a></li>
										<li class="nav-item"><a href="#logo-favicon" class="nav-link"><i class="fas fa-images"></i> <?php echo $this->lang->line("Logo & Favicon"); ?></a></li>
										<li class="nav-item"><a href="#master-password" class="nav-link"><i class="fab fa-keycdn"></i> <?php echo $this->lang->line("Master Password & APP Access"); ?></a></li>
										<li class="nav-item"><a href="#subscriber" class="nav-link"><i class="fas fa-user-circle"></i> <?php echo $this->lang->line("Subscriber"); ?></a></li>
										<!-- <li class="nav-item"><a href="#auto-reply" class="nav-link"><i class="fas fa-reply-all"></i> <?php echo $this->lang->line("Auto Reply"); ?></a></li> -->
										<li class="nav-item"><a href="#persistent-menu" class="nav-link"><i class="fas fa-bars"></i> <?php echo $this->lang->line("Persistent Menu"); ?></a></li>
										<li class="nav-item"><a href="#messenger-broadcast" class="nav-link"><i class="fas fa-mail-bulk"></i> <?php echo $this->lang->line("Messenger Broadcast"); ?></a></li>
										<li class="nav-item"><a href="#group-posting" class="nav-link"><i class="fas fa-share-square"></i> <?php echo $this->lang->line("Facebook Poster"); ?></a></li>
										<?php if($this->session->userdata('license_type') == 'double') { ?>
										<li class="nav-item"><a href="#support-desk" class="nav-link"><i class="fas fa-headset"></i> <?php echo $this->lang->line("Support Desk"); ?></a></li>
										<?php } ?>
										<li class="nav-item"><a href="#file-upload" class="nav-link"><i class="fas fa-cloud-upload-alt"></i> <?php echo $this->lang->line("File Upload"); ?></a></li>								
										<li class="nav-item"><a href="#server-status" class="nav-link"><i class="fas fa-server"></i> <?php echo $this->lang->line("Server Status"); ?></a></li>								
									</ul>
								</div>						
							</div>
							
						</div>
					</div>
				</div>				
			</div>
		</div>
	</form>
</section>


<script type="text/javascript">
  $('document').ready(function(){
    $(".settings_menu a").click(function(){
    	$(".settings_menu a").removeClass("active");
    	$(this).addClass("active");
    });
  });
</script>
<script>
	$('[data-toggle="popover"]').popover();
	$('[data-toggle="popover"]').on('click', function(e) {e.preventDefault(); return true;});
</script>