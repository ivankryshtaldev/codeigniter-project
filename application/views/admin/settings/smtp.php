<section class="section section_custom">
  <div class="section-header">
    <h1><i class="fas fa-envelope"></i> <?php echo $page_title; ?></h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item"><?php echo $this->lang->line("System"); ?></div>
      <div class="breadcrumb-item active"><a href="<?php echo base_url('admin/settings'); ?>"><?php echo $this->lang->line("Settings"); ?></a></div>
      <div class="breadcrumb-item"><?php echo $page_title; ?></div>
    </div>
  </div>

  <?php $this->load->view('admin/theme/message'); ?>

  <div class="section-body">
    <div class="row">
      <div class="col-12">
          <form action="<?php echo base_url("admin/smtp_settings_action"); ?>" method="POST">
          <div class="card">
            <div class="card-body">              
                <div class="form-group">
                    <label for=""><i class="fa fa-at"></i> <?php echo $this->lang->line("Sender Email Address");?> </label>
                    <input name="email_address" value="<?php echo isset($xvalue['email_address']) ? $xvalue['email_address'] :""; ?>"  class="form-control" type="email">              
                    <span class="red"><?php echo form_error('email_address'); ?></span>
                </div>

                <div class="row">
                  <div class="col-12 col-md-6">
                    <div class="form-group">
                      <label for=""><i class="fa fa-server"></i>  <?php echo $this->lang->line("SMTP Host");?></label>
                      <input name="smtp_host" value="<?php echo isset($xvalue['smtp_host']) ? $xvalue['smtp_host'] :""; ?>" class="form-control" type="text">  
                      <span class="red"><?php echo form_error('smtp_host'); ?></span>
                    </div>
                  </div>

                  <div class="col-12 col-md-6">
                    <div class="form-group">
                      <label for=""><i class="fas fa-plug"></i>  <?php echo $this->lang->line("SMTP Port");?></label>
                      <input name="smtp_port" value="<?php echo isset($xvalue['smtp_port']) ? $xvalue['smtp_port'] :""; ?>" class="form-control" type="text">  
                      <span class="red"><?php echo form_error('smtp_port'); ?></span>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-12 col-md-6">
                    <div class="form-group">
                      <label for=""><i class="fas fa-user-circle"></i>  <?php echo $this->lang->line("SMTP User");?></label>
                      <input name="smtp_user" value="<?php echo isset($xvalue['smtp_user']) ? $xvalue['smtp_user'] :""; ?>" class="form-control" type="text">  
                      <span class="red"><?php echo form_error('smtp_user'); ?></span>
                    </div>
                  </div>

                  <div class="col-12 col-md-6">
                    <div class="form-group">
                      <label for=""><i class="fas fa-key"></i>  <?php echo $this->lang->line("SMTP Password");?></label>
                      <input name="smtp_password" value="<?php echo isset($xvalue['smtp_password']) ? $xvalue['smtp_password'] :""; ?>" class="form-control" type="text">  
                      <span class="red"><?php echo form_error('smtp_password'); ?></span>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label for="smtp_type" ><i class="fa fa-shield-alt"></i> <?php echo $this->lang->line('Connection Type');?>?</label>
                    <?php 
                    $smtp_type =isset($xvalue['smtp_type'])?$xvalue['smtp_type']:"";
                    if($smtp_type == '') $smtp_type='Default';
                    ?>
                    <div class="custom-switches-stacked mt-2">
                      <div class="row">   
                        <div class="col-4 col-md-2">
                          <label class="custom-switch">
                            <input type="radio" name="smtp_type" value="Default" class="custom-switch-input" <?php if($smtp_type=='Default') echo 'checked'; ?>>
                            <span class="custom-switch-indicator"></span>
                            <span class="custom-switch-description"><?php echo $this->lang->line('Default'); ?></span>
                          </label>
                        </div>
                        <div class="col-4 col-md-2">
                          <label class="custom-switch">
                            <input type="radio" name="smtp_type" value="tls" class="custom-switch-input" <?php if($smtp_type=='tls') echo 'checked'; ?>>
                            <span class="custom-switch-indicator"></span>
                            <span class="custom-switch-description"><?php echo $this->lang->line('TLS'); ?></span>
                          </label>
                        </div>
                        <div class="col-4 col-md-2">
                          <label class="custom-switch">
                            <input type="radio" name="smtp_type" value="ssl" class="custom-switch-input" <?php if($smtp_type=='ssl') echo 'checked'; ?>>
                            <span class="custom-switch-indicator"></span>
                            <span class="custom-switch-description"><?php echo $this->lang->line('SSL'); ?></span>
                          </label>
                        </div>
                      </div>                                  
                    </div>
                    <span class="red"><?php echo form_error('smtp_type'); ?></span>
                </div> 
            </div>

            <div class="card-footer bg-whitesmoke">
              <button class="btn btn-primary btn-lg" id="save-btn" type="submit"><i class="fas fa-save"></i> <?php echo $this->lang->line("Save");?></button>
              <button class="btn btn-secondary btn-lg float-right" onclick='goBack("admin/settings")' type="button"><i class="fa fa-remove"></i>  <?php echo $this->lang->line("Cancel");?></button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
