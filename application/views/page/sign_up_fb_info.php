<script type="text/template" id="qq-template-manual-trigger">
  <div class="qq-uploader-selector qq-uploader" qq-drop-area-text="Drop files here">
      <div class="qq-total-progress-bar-container-selector qq-total-progress-bar-container">
          <div role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" class="qq-total-progress-bar-selector qq-progress-bar qq-total-progress-bar"></div>
      </div>
      <div class="qq-upload-drop-area-selector qq-upload-drop-area" qq-hide-dropzone>
          <span class="qq-upload-drop-area-text-selector"></span>
      </div>
      <div class="buttons">
          <div class="qq-upload-button-selector qq-upload-button">
              <div>Select files</div>
          </div>
      </div>
      <span class="qq-drop-processing-selector qq-drop-processing">
          <span>Processing dropped files...</span>
          <span class="qq-drop-processing-spinner-selector qq-drop-processing-spinner"></span>
      </span>
      <ul class="qq-upload-list-selector qq-upload-list" aria-live="polite" aria-relevant="additions removals">
          <li>
              <div class="qq-progress-bar-container-selector">
                  <div role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" class="qq-progress-bar-selector qq-progress-bar"></div>
              </div>
              <span class="qq-upload-spinner-selector qq-upload-spinner"></span>
              <img class="qq-thumbnail-selector" qq-max-size="100" qq-server-scale>
              <span class="qq-upload-file-selector qq-upload-file"></span>
              <span class="qq-edit-filename-icon-selector qq-edit-filename-icon" aria-label="Edit filename"></span>
              <input class="qq-edit-filename-selector qq-edit-filename" tabindex="0" type="text">
              <span class="qq-upload-size-selector qq-upload-size"></span>
              <button type="button" class="qq-btn qq-upload-cancel-selector qq-upload-cancel">Cancel</button>
              <button type="button" class="qq-btn qq-upload-retry-selector qq-upload-retry">Retry</button>
              <button type="button" class="qq-btn qq-upload-delete-selector qq-upload-delete">Delete</button>
              <span role="status" class="qq-upload-status-text-selector qq-upload-status-text"></span>
          </li>
      </ul>

      <dialog class="qq-alert-dialog-selector">
          <div class="qq-dialog-message-selector"></div>
          <div class="qq-dialog-buttons">
              <button type="button" class="qq-cancel-button-selector">Close</button>
          </div>
      </dialog>

      <dialog class="qq-confirm-dialog-selector">
          <div class="qq-dialog-message-selector"></div>
          <div class="qq-dialog-buttons">
              <button type="button" class="qq-cancel-button-selector">No</button>
              <button type="button" class="qq-ok-button-selector">Yes</button>
          </div>
      </dialog>

      <dialog class="qq-prompt-dialog-selector">
          <div class="qq-dialog-message-selector"></div>
          <input type="text">
          <div class="qq-dialog-buttons">
              <button type="button" class="qq-cancel-button-selector">Cancel</button>
              <button type="button" class="qq-ok-button-selector">Ok</button>
          </div>
      </dialog>
  </div>
</script>
<div class="header-logo ">
  <div class="elementor-container elementor-column-gap-default">
    <div class="elementor-row">
      <div><img src="<?php echo base_url(); ?>assets/img/logo.png" alt="<?php echo $this->config->item('product_name');?>" ></div>
    </div> 
  </div>
</div>
<div class="container">
  <div class="row">
    <div class="col-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3 col-xl-6 offset-xl-3">
      <div class="card card-primary login-style" style="border-top: none;">
        <div class="card-header"><h4><i class="far fa-user-circle"></i> <?php echo $this->lang->line("Facebook Sign Up information"); ?></h4></div>

        <div class="card-body">
          <form method="POST" action="<?php echo site_url('1/thanks'); ?>" >
            <div class="row">
              <div class="form-group col-6">
                <label for="frist_name"><?php echo $this->lang->line("Name"); ?> </label>
                <input id="name" type="text" readonly class="form-control" name="name" autofocus required value="<?php echo ($name); ?>">
              </div>
              <div class="form-group col-6">
                <label for="last_name"><?php echo $this->lang->line("Email"); ?> </label>
                <input id="email" type="email" readonly class="form-control" name="email" required value="<?php echo ($email); ?>">
                <input type="hidden" name="config_id" value="<?php echo ($config_id); ?>">
                <input type="hidden" name="access_token_set" value="<?php echo ($access_token_set); ?>">
              </div>
            </div>
            <div class="row">
              <div class="form-group col-6">
                <label for="bname"><?php echo $this->lang->line("Business Name"); ?> </label>
                <input id="bname" type="text" class="form-control" name="bname" autofocus>
              </div>
              <div class="form-group col-6">
                <label for="btype"><?php echo $this->lang->line("Business Type"); ?> </label>
                <input id="btype" type="text" class="form-control" name="btype" placeholder="<?php echo $this->lang->line("e.g. Restaurant"); ?>" >
              </div>
            </div>
            <div class="row">
              <div class="form-group col-12">
                <label for="burl"><?php echo $this->lang->line("Business URL"); ?> </label>
                <input id="burl" type="text" class="form-control" name="burl">
              </div>              
            </div>
            <div class="form-group">
              <label><?php echo $this->lang->line("Upload Menu/Services List"); ?></label>
                <div id="fine-uploader-manual-trigger"></div>
                <input type="hidden" name="filename" id="filename_list" value="">
            </div>
            <div class='alert alert-danger text-center' id="fb_continue_error" style="visibility: hidden;"><?php echo $this->lang->line("Please input your Business Information"); ?></div>
            <div class="form-group">
              <button type="submit" id="fb_continue" class="btn btn-primary btn-lg btn-block">
                <i class="fa fa-user-circle"></i> <?php echo $this->lang->line("Continue"); ?>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
$('document').ready(function(){
  $('#fb_continue').click(function (e) {
    if ($('#bname').val() === "" || $('#btype').val() ==="" ) {
      e.preventDefault();
      $('#fb_continue_error').css("visibility", 'visible');
    }
  });
});
</script>

<script>
  $('document').ready(function(){
    var filename_list = [];
    var manualUploader = new qq.FineUploader({
        element: document.getElementById('fine-uploader-manual-trigger'),
        template: 'qq-template-manual-trigger',
        request: {
            endpoint: '<?php echo base_url(); ?>1/upload_menu_files',
            params: {email: '<?php echo ($email); ?>'},
        },
        thumbnails: {
            placeholders: {
                waitingPath: '<?php echo base_url(); ?>assets/file_uploader/placeholders/waiting-generic.png',
                notAvailablePath: '<?php echo base_url(); ?>assets/file_uploader/placeholders/not_available-generic.png'
            }
        },
        debug: true,
        deleteFile: {
            enabled: false
        },
        callbacks:{
          onComplete: function(id, name, response){
            // console.log(response.filename);
            filename_list.push(response.filename);
            $('#filename_list').val(filename_list.join(';'));
          },
        },
    });
  });
</script>

