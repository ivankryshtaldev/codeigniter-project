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
        <div class="card-header">
          <h4><i class="fas fa-sign-in-alt"></i> <?php echo $this->lang->line("Sign in with Facebook"); ?></h4>
        </div>
        <?php
if (form_error('name') != '' || form_error('email') != "") {
	$form_error = "";
	if (form_error('name') != '') {
		$form_error .= form_error('name');
	}

	if (form_error('email') != '') {
		$form_error .= form_error('email');
	}

	echo "<div class='alert alert-danger text-center'>" . $form_error . "</div>";

}
?>
        <div class="card-body">
          <div class="col-12" id="fb_signup" style="max-width: 250px;">
          	<?php echo $fb_login_button2 = str_replace("ThisIsTheLoginButtonForFacebook", $this->lang->line("Sign in with Facebook"), $fb_login_button); ?>
          </div>

        </div>
        <div class="card-footer">
          <div class="custom-control custom-checkbox">
            <input type="checkbox" name="agree" required class="custom-control-input" id="agree">

            <label class="custom-control-label" for="agree">
              <span ><?php echo $this->lang->line("I agree with the"); ?></span>
              <a id="btn_live_button1" href="#"><?php echo $this->lang->line("terms and conditions"); ?></a>
              <span ><?php echo $this->lang->line(" &"); ?></span>
              <a id="btn_live_button2" href="#"><?php echo $this->lang->line("privacy policy"); ?></a>
            </label>

            <p id="fb_error" class="text-danger"><?php echo $this->lang->line("Please check terms of service and the privacy policy."); ?> </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
$('document').ready(function(){
  $('#fb_error').hide();
  $("#fb_signup").click(function(e){
    if ($('#agree').prop('checked') == false) {
      e.preventDefault();
      $('#fb_error').show();
    }
  });
  $("#agree").click(function(e){
    $('#fb_error').hide();
  });

  $('#btn_live_button1').click(function(e){
    $('#exampleModalLong').modal('show');
    setTimeout(function() {
      $('#exampleModalLong').addClass('show');
      $('.modal-backdrop.fade.in').addClass('show');
    }, 100);
  });

  $('#btn_live_button2').click(function(e){
    $('#exampleModalLong2').modal('show');
    setTimeout(function() {
      $('#exampleModalLong2').addClass('show');
      $('.modal-backdrop.fade.in').addClass('show');
    }, 100);
  });

});
</script>