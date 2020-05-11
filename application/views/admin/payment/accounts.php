<section class="section section_custom">
  <div class="section-header">
    <h1><i class="far fa-credit-card"></i> <?php echo $page_title; ?></h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item"><?php echo $this->lang->line("Subscription"); ?></div>
      <div class="breadcrumb-item"><?php echo $page_title; ?></div>
    </div>
  </div>

  <?php $this->load->view('admin/theme/message'); ?>

  <div class="section-body">
    <div class="row">
      <div class="col-12">
          <form action="<?php echo base_url("payment/accounts_action"); ?>" method="POST">
          <div class="card">
            <div class="card-body">

                <div class="row">
                  <div class="col-12 col-md-4">
                    <div class="form-group">
                        <label for=""><i class="fas fa-at"></i> <?php echo $this->lang->line("Paypal Email");?> </label>
                        <input name="paypal_email" value="<?php echo isset($xvalue['paypal_email']) ? $xvalue['paypal_email'] :""; ?>"  class="form-control" type="email">              
                        <span class="red"><?php echo form_error('paypal_email'); ?></span>
                    </div>
                  </div>

                  <div class="col-12 col-md-4">
                    <div class="form-group">
                      <label for="paypal_payment_type" ><i class="fas fa-hand-holding-usd"></i>  <?php echo $this->lang->line('Paypal Recurring Payment');?></label>
                        
                        <div class="form-group">
                          <?php 
                          $paypal_payment_type =isset($xvalue['paypal_payment_type'])?$xvalue['paypal_payment_type']:"";
                          if($paypal_payment_type == '') $smtp_type='manual';
                          ?>
                          <label class="custom-switch mt-2">
                            <input type="checkbox" name="paypal_payment_type" value="recurring" class="custom-switch-input"  <?php if($paypal_payment_type=='recurring') echo 'checked'; ?>>
                            <span class="custom-switch-indicator"></span>
                            <span class="custom-switch-description"><?php echo $this->lang->line('Enable');?></span>
                            <span class="red"><?php echo form_error('paypal_payment_type'); ?></span>
                          </label>
                        </div>
                    </div> 
                  </div>

                  <div class="col-12 col-md-4">
                    <div class="form-group">
                      <label for=""><i class="fas fa-vial"></i> <?php echo $this->lang->line('Paypal Sandbox Mode');?></label>
                      <br>
                      <?php 
                      $paypal_mode =isset($xvalue['paypal_mode'])?$xvalue['paypal_mode']:"";
                      if($paypal_mode == '') $paypal_mode='live';
                      ?>
                      <label class="custom-switch mt-2">
                        <input type="checkbox" name="paypal_mode" value="sandbox" class="custom-switch-input"  <?php if($paypal_mode=='sandbox') echo 'checked'; ?>>
                        <span class="custom-switch-indicator"></span>
                        <span class="custom-switch-description"><?php echo $this->lang->line('Enable');?></span>
                        <span class="red"><?php echo form_error('paypal_mode'); ?></span>
                      </label>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-12 col-md-6">
                    <div class="form-group">
                      <label for=""><i class="fas fa-key"></i>  <?php echo $this->lang->line("Stripe Secret Key");?></label>
                      <input name="stripe_secret_key" value="<?php echo isset($xvalue['stripe_secret_key']) ? $xvalue['stripe_secret_key'] :""; ?>" class="form-control" type="text">  
                      <span class="red"><?php echo form_error('stripe_secret_key'); ?></span>
                    </div>
                  </div>

                  <div class="col-12 col-md-6">
                    <div class="form-group">
                      <label for=""><i class="fab fa-keycdn"></i> <?php echo $this->lang->line("Stripe Publishable Key");?></label>
                      <input name="stripe_publishable_key" value="<?php echo isset($xvalue['stripe_publishable_key']) ? $xvalue['stripe_publishable_key'] :""; ?>" class="form-control" type="text">  
                      <span class="red"><?php echo form_error('stripe_publishable_key'); ?></span>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-12 col-md-6">
                    <div class="form-group">
                      <label for=""><i class="fas fa-coins"></i>  <?php echo $this->lang->line("Currency");?></label>
                      <?php $default_currency = isset($xvalue['currency']) ? $xvalue['currency'] : "USD"; ?>
                      <?php echo form_dropdown('currency', $currency_list, $default_currency,"class='form-control select2'"); ?> 
                      <span class="red"><?php echo form_error('currency'); ?></span>
                    </div>
                  </div>
                </div>

                
            </div>

            <div class="card-footer bg-whitesmoke">
              <button class="btn btn-primary btn-lg" id="save-btn" type="submit"><i class="fas fa-save"></i> <?php echo $this->lang->line("Save");?></button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
