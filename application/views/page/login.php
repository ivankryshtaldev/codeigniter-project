<div class="header-logo ">
  <div class="elementor-container elementor-column-gap-default">
    <div class="elementor-row">
      <div><img src="<?php echo base_url(); ?>assets/img/logo.png" alt="<?php echo $this->config->item('product_name');?>" ></div>
    </div> 
  </div>
</div>
<div class="container">
  <div class="row">
    <div class="col-12 col-sm-8 offset-sm-2 col-md-8 offset-md-2 col-lg-6 offset-lg-3 col-xl-6 offset-xl-3">

      <div class="card card-primary login-style" style="border-top: none;">
        <div class="card-header"><h4><i class="fas fa-sign-in-alt"></i> <?php echo $this->lang->line("Login"); ?></h4></div>
        <?php 
          if($this->session->flashdata('login_msg')!='') 
          {
              echo "<div class='alert alert-danger text-center'>"; 
                  echo $this->session->flashdata('login_msg');
              echo "</div>"; 
          }   
          if($this->session->flashdata('reset_success')!='') 
          {
              echo "<div class='alert alert-success text-center'>"; 
                  echo $this->session->flashdata('reset_success');
              echo "</div>"; 
          } 
          if($this->session->userdata('reg_success') != ''){
            echo '<div class="alert alert-success text-center">'.$this->session->userdata("reg_success").'</div>';
            $this->session->unset_userdata('reg_success');
          }    
          if(form_error('username') != '' || form_error('password')!="" ) 
          {
            $form_error="";
            if(form_error('username') != '') $form_error.=form_error('username');
            if(form_error('password') != '') $form_error.=form_error('password');
            echo "<div class='alert alert-danger text-center'>".$form_error."</div>";
           
          }     
        ?>
        <div class="card-body">
          <form method="POST" action="<?php echo base_url('home/login'); ?>" class="needs-validation" novalidate="">
            <div class="form-group">
              <label for="email"><?php echo $this->lang->line("Email"); ?></label>
              <input id="email" type="email" class="form-control" name="username" tabindex="1" required autofocus>
              <!-- <div class="invalid-feedback">
                Please fill in your email
              </div> -->
            </div>

            <div class="form-group">
              <div class="d-block">
              	<label for="password" class="control-label"><?php echo $this->lang->line("Password"); ?></label>
                <div class="float-right">
                  <a href="<?php echo site_url();?>home/forgot_password" class="text-small">
                    <?php echo $this->lang->line("Forgot your password?"); ?>
                  </a>
                </div>
              </div>
              <input id="password" type="password" class="form-control" name="password" tabindex="2" required>
              <!-- <div class="invalid-feedback">
                please fill in your password
              </div> -->
            </div>

            <!-- <div class="form-group">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" name="remember" class="custom-control-input" tabindex="3" id="remember-me">
                <label class="custom-control-label" for="remember-me">Remember Me</label>
              </div>
            </div> -->

            <div class="form-group">
              <button type="submit" class="btn btn-primary btn-lg btn-block login_btn" tabindex="4">
                <i class="fa fa-sign-in-alt"></i> <?php echo $this->lang->line("login"); ?>
              </button>
            </div>
          </form>
          
          <?php if($this->config->item('enable_signup_form')!='0') : ?>
          <div class="row sm-gutters">
            <div class="col-12">
	             <div class="text-muted text-center">
	            	<br><?php echo $this->lang->line("Do not have an account?"); ?> <a href="<?php echo base_url('1/signup'); ?>"><?php echo $this->lang->line("Create one"); ?></a>
	        	</div>
	      	 </div>
          </div>
	      	<?php endif; ?>

        </div>
      </div>
    </div>
  </div>
</div>
