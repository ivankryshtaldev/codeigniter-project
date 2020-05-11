<li class="dropdown"><a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
  <img src="<?php echo $this->session->userdata("brand_logo"); ?>" class="rounded-circle mr-1">
  <div class="d-sm-none d-lg-inline-block"><?php echo $this->session->userdata('username'); ?></div></a>
  <div class="dropdown-menu dropdown-menu-right">

    <div class="dropdown-title"><?php echo $this->config->item("product_short_name")." - ".$this->lang->line($this->session->userdata("user_type")); ?></div>
    <a href="<?php echo base_url('member/edit_profile'); ?>" class="dropdown-item has-icon">
      <i class="far fa-user"></i> <?php echo $this->lang->line("Profile"); ?>
    </a>
    <a href="<?php echo base_url('calendar/index'); ?>" class="dropdown-item has-icon">
      <i class="fas fa-bolt"></i> <?php echo $this->lang->line("Activities"); ?>
    </a>
    <a href="<?php echo base_url('change_password/reset_password_form'); ?>" class="dropdown-item has-icon">
      <i class="fas fa-key"></i> <?php echo $this->lang->line("Change Password"); ?>
    </a>  

    <a href="<?php echo base_url('home/logout'); ?>" class="dropdown-item has-icon text-danger">
      <i class="fas fa-sign-out-alt"></i> <?php echo $this->lang->line("Logout"); ?>
    </a>

    <div class="dropdown-divider"></div>
  
    <?php $current_account = isset($fb_rx_account_switching_info[$this->session->userdata("facebook_rx_fb_user_info")]) ? $fb_rx_account_switching_info[$this->session->userdata("facebook_rx_fb_user_info")] : $this->lang->line("Facebook Account"); ?>
    <a href="#" data-toggle="dropdown" class="dropdown-toggle dropdown-item has-icon"><i class="fab fa-facebook"></i>  <?php echo $current_account; ?></a>
    <ul class="dropdown-menu dropdown-menu-sm dropdown-menu-right">
      <li class="dropdown-title"><?php echo $this->lang->line("Switch Accounts"); ?></li>
      <?php 
      foreach ($fb_rx_account_switching_info as $key => $value) 
      {
        $selected='';
        if($key==$this->session->userdata("facebook_rx_fb_user_info")) $selected='active';
        echo '<li><a href="" data-id="'.$key.'" class="dropdown-item account_switch '.$selected.'">'.$value.'</a></li>';
      } 
      ?>
    </ul>  



  </div>
</li>