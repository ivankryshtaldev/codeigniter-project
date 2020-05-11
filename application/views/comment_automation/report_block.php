 <section class="section">
  <div class="section-header">
    <h1><i class="fas fa-chart-pie"></i> <?php echo $page_title; ?></h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item"><?php echo $this->lang->line("Comment Feature"); ?></div>
      <div class="breadcrumb-item"><a href="<?php echo base_url("comment_automation/index"); ?>"><?php echo $this->lang->line("Create Campaign");?></a></div>
      <div class="breadcrumb-item"><?php echo $page_title; ?></div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">
      <?php if($this->session->userdata('user_type') == 'Admin' || in_array(251,$this->module_access)) : ?>
      <div class="col-lg-6">
        <div class="card card-large-icons">
          <div class="card-icon text-primary">
            <i class="far fa-comment-dots"></i>
          </div>
          <div class="card-body">
            <h4><?php echo $this->lang->line("Auto Comment Report"); ?></h4>
            <p><?php echo $this->lang->line("Report of auto comment on page's post."); ?></p>
            <a href="<?php echo base_url("comment_automation/all_auto_comment_report"); ?>" class="card-cta"><?php echo $this->lang->line("See Report"); ?> <i class="fas fa-chevron-right"></i></a>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if($this->session->userdata('user_type') == 'Admin' || in_array(80,$this->module_access)) : ?>
      <div class="col-lg-6">
        <div class="card card-large-icons">
          <div class="card-icon text-primary">
            <i class="fas fa-reply"></i>
          </div>
          <div class="card-body">
            <h4><?php echo $this->lang->line("Auto reply report"); ?></h4>
            <p><?php echo $this->lang->line("Report of auto comment reply & private reply."); ?></p>
            <a href="<?php echo base_url("comment_automation/all_auto_reply_report"); ?>" class="card-cta"><?php echo $this->lang->line("See Report"); ?> <i class="fas fa-chevron-right"></i></a>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <?php 
      if($this->basic->is_exist("add_ons",array("project_id"=>29)))
      if($this->session->userdata('user_type') == 'Admin' || in_array(201,$this->module_access)) : ?>
      <div class="col-lg-6">
        <div class="card card-large-icons">
          <div class="card-icon text-primary">
            <i class="fas fa-tags"></i>
          </div>
          <div class="card-body">
            <h4><?php echo $this->lang->line("Comment bulk tag report"); ?></h4>
            <p><?php echo $this->lang->line("Report of bulk tag in single comment."); ?></p>
            <a href="<?php echo base_url("comment_reply_enhancers/bulk_tag_campaign_list"); ?>" class="card-cta"><?php echo $this->lang->line("See Report"); ?> <i class="fas fa-chevron-right"></i></a>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <?php 
      if($this->basic->is_exist("add_ons",array("project_id"=>29)))
      if($this->session->userdata('user_type') == 'Admin' || in_array(202,$this->module_access)) : ?>
      <div class="col-lg-6">
        <div class="card card-large-icons">
          <div class="card-icon text-primary">
            <i class="far fa-comments"></i>
          </div>
          <div class="card-body">
            <h4><?php echo $this->lang->line("Bulk comment reply report"); ?></h4>
            <p><?php echo $this->lang->line("Report of tag in each reply of comment."); ?></p>
            <a href="<?php echo base_url("comment_reply_enhancers/bulk_comment_reply_campaign_list"); ?>" class="card-cta"><?php echo $this->lang->line("See Report"); ?> <i class="fas fa-chevron-right"></i></a>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <?php 
      if($this->basic->is_exist("add_ons",array("project_id"=>29)))
      if($this->session->userdata('user_type') == 'Admin' || in_array(204,$this->module_access)) : ?>
      <div class="col-lg-6">
        <div class="card card-large-icons">
          <div class="card-icon text-primary">
           <i class="fas fa-reply-all"></i>
          </div>
          <div class="card-body">
            <h4><?php echo $this->lang->line("Full PageResponse Report"); ?></h4>
            <p><?php echo $this->lang->line("Report of comment reply & private reply of full pages."); ?></p>
            <a href="<?php echo base_url("comment_reply_enhancers/all_response_report"); ?>" class="card-cta"><?php echo $this->lang->line("See Report"); ?> <i class="fas fa-chevron-right"></i></a>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <?php 
      if($this->basic->is_exist("add_ons",array("project_id"=>29)))
      if($this->session->userdata('user_type') == 'Admin' || in_array(206,$this->module_access)) : ?>
      <div class="col-lg-6">
        <div class="card card-large-icons">
          <div class="card-icon text-primary">
           <i class="fas fa-thumbs-up"></i>
          </div>
          <div class="card-body">
            <h4><?php echo $this->lang->line("Auto Like & Share Report"); ?></h4>
            <p><?php echo $this->lang->line("Report of sharing & liking by other page's you own."); ?></p>
            <a href="<?php echo base_url("comment_reply_enhancers/all_like_share_report"); ?>" class="card-cta"><?php echo $this->lang->line("See Report"); ?> <i class="fas fa-chevron-right"></i></a>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</section>