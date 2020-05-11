<?php 
  $this->load->view("include/upload_js"); 

  $image_upload_limit = 1; 
  if($this->config->item('messengerbot_image_upload_limit') != '')
  $image_upload_limit = $this->config->item('messengerbot_image_upload_limit'); 

?>

<style type="text/css">
  .button-outline
  {
    background: #fff;
    border: .5px dashed #ccc;
  }
  .button-outline:hover
  {
    border: 1px dashed #6777EF !important;
    cursor: pointer;
  }
  .multi_layout{margin:0;background: #fff}
  .multi_layout .card{margin-bottom:0;border-radius: 0;}
  .multi_layout p, .multi_layout ul:not(.list-unstyled), .multi_layout ol{line-height: 15px;}
  .multi_layout .list-group li{padding: 15px 10px 12px 25px;}
  .multi_layout{border:.5px solid #dee2e6;}
  .multi_layout .collef,.multi_layout .colmid,.multi_layout .colrig{padding-left: 0px; padding-right: 0px;}
  .multi_layout .collef,.multi_layout .colmid{border-right: .5px solid #dee2e6;}
  .multi_layout .main_card{min-height: 500px;box-shadow: none;}
  .multi_layout .collef .makeScroll{max-height: 790px;overflow:auto;}
  .multi_layout .list-group{padding-top:6px;}
  .multi_layout .list-group .list-group-item{border-radius: 0;border:.5px solid #dee2e6;border-left:none;border-right:none;cursor: pointer;z-index: 0;}
  .multi_layout .list-group .list-group-item:first-child{border-top:none;}
  .multi_layout .list-group .list-group-item:last-child{border-bottom:none;}
  .multi_layout .list-group .list-group-item.active{border:.5px solid #6777EF;}
  .multi_layout .mCSB_inside > .mCSB_container{margin-right: 0;}
  .multi_layout .card-statistic-1{border-radius: 0;}
  .multi_layout h6.page_name{font-size: 14px;}
  .multi_layout .card .card-header input{max-width: 100% !important;}
  .multi_layout .waiting,.modal_waiting {height: 100%;width:100%;display: table;}
  .multi_layout .waiting i,.modal_waiting i{font-size:60px;display: table-cell; vertical-align: middle;padding:30px 0;}
  .multi_layout .card .card-header h4 a{font-weight: 700 !important;}  
  .product-item .product-name{font-weight: 500;}
  .badge-status{border-color:#eee;}
  /* #right_column_title i{font-size: 17px;} */
  
  ::placeholder {
    color: #ccc !important;
  }
  .smallspace{padding: 10px 0;}
  .lead_first_name,.lead_last_name,.lead_tag_name{background: #fff !important;}
  .getstarted_lead_first_name,.getstarted_lead_last_name,.getstarted_lead_tag_name{background: #fff !important;}
  .ajax-file-upload-statusbar{width: 100% !important;}
  hr{margin-top: 10px;}
 .custom-top-margin{margin-top: 20px;}
 .sync_page_style{margin-top: 8px;}
  /* .wrapper,.content-wrapper{background: #fafafa !important;} */
  .well{background: #fff;}  
  .emojionearea, .emojionearea.form-control{height: 140px !important;}
  .emojionearea.small-height{height: 140px !important;}

  /*import bot modal section*/
  .radio_check{display:block;position:relative;padding-left:35px;cursor:pointer;font-size:22px;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none}
  .radio_check input{position:absolute;opacity:0;cursor:pointer}
  .checkmark{position:absolute;top:0px;right:0;height:18px;width:18px;background-color:#ccc;}
  .radio_check:hover input~.checkmark{background-color:#eee}
  .radio_check input:checked~.checkmark{background-color:#2196F3}.checkmark:after{content:"";position:absolute;display:none}
  .radio_check input:checked~.checkmark:after{display:block}
  .radio_check .checkmark:after{top:5px;left:5px;width:8px;height:8px;border-radius:50%;background:#fff}
  .template_sec{border:1px solid #dcd7d7;border-top-right-radius:6px;border-bottom-right-radius:6px;padding-right:0;overflow: hidden;}
  .template_img_section img{border-top-left-radius:6px;border-bottom-left-radius:6px}
  .template_body_section{height:94px;padding:3px 10px 0 10px;border-left:none}
  .description_section{font-size:10px;text-align:justify}
  .author-box .author-box-name { font-size: 14px;}
  .author-box .author-box-picture { width:80px;}

  .type3 .ajax-upload-dragdrop{text-align: center;}
  .type3 .ajax-file-upload-filename{width:100% !important;}

  

</style>


<section class="section">
  <div class="section-header">
    <h1><i class="fa fa-cogs"></i> <?php echo $page_title;?></h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="<?php echo base_url('messenger_bot/bot_menu_section'); ?>"><?php echo $this->lang->line("Messenger Bot");?></a></div>
      <div class="breadcrumb-item"><?php echo $page_title;?></div>
    </div>
    </div>
</section>


<?php if(empty($page_info))
{ ?>
   
<div class="card" id="nodata">
  <div class="card-body">
    <div class="empty-state">
      <img class="img-fluid" style="height: 200px" src="<?php echo base_url('assets/img/drawkit/drawkit-nature-man-colour.svg'); ?>" alt="image">
       <h2 class="mt-0"><?php echo $this->lang->line("We could not find any page.");?></h2>
      <p class="lead"><?php echo $this->lang->line("Please import account if you have not imported yet.")."<br>".$this->lang->line("If you have already imported account then enable bot connection for one or more page to continue.") ?></p>
      <a href="<?php echo base_url('social_accounts'); ?>" class="btn btn-outline-primary mt-4"><i class="fas fa-arrow-circle-right"></i> <?php echo $this->lang->line("Continue");?></a>
    </div>
  </div>
</div>

<?php 
}
else
{ ?>
  <div class="row multi_layout">

    <div class="col-12 col-md-5 col-lg-3 collef">
      <div class="card main_card">
        <div class="card-header">
          <div class="col-6 padding-0">
            <h4><i class="fas fa-newspaper"></i> <?php echo $this->lang->line("Pages"); ?></h4>
          </div>
          <div class="col-6 padding-0">            
            <input type="text" class="form-control float-right" id="search_page_list" onkeyup="search_in_ul(this,'page_list_ul')" autofocus placeholder="<?php echo $this->lang->line('Search...'); ?>">
          </div>
        </div>
        <div class="card-body padding-0">
          <div class="makeScroll">
            <ul class="list-group" id="page_list_ul">
              <?php $i=0; foreach($page_info as $value) { ?> 
                <li class="list-group-item <?php if($i==0) echo 'active'; ?> page_list_item" page_table_id="<?php echo $value['id']; ?>">
                  <div class="row">
                    <div class="col-3 col-md-2"><img width="45px" class="rounded-circle" src="<?php echo $value['page_profile']; ?>"></div>
                    <div class="col-9 col-md-10">
                      <h6 class="page_name"><?php echo $value['page_name']; ?></h6>
                      <span class="gray fb_page_id"><?php echo $value['page_id']; ?></span>
                      </div>
                    </div>
                </li> 
                <?php $i++; } ?>                
            </ul>
          </div>
        </div>
      </div>          
    </div>

    <div class="col-12 col-md-7 col-lg-3 colmid" id="middle_column">

      <div class="text-center waiting">
        <i class="fas fa-spinner fa-spin blue text-center"></i>
      </div>

      <div id="middle_column_content"></div>
    </div>

    <div class="col-12 col-md-12 col-lg-6 colrig" id="right_column">

      <div class="text-center waiting">
        <i class="fas fa-spinner fa-spin blue text-center"></i>
      </div>

      <div class="card main_card">
        <div class="card-header padding-left-10 padding-right-10">
          <div class="col-6 padding-0">
            <h4 id="right_column_title"></h4>            
          </div>
          <div class="col-6 padding-0">
            <a href="#" data-toggle="dropdown" class="btn btn-outline-primary dropdown-toggle float-right"><?php echo $this->lang->line("Bot Settings Data");?></a> 
            <ul class="dropdown-menu dropdown-menu-sm dropdown-menu-right">
              <div class="dropdown-title"><?php echo $this->lang->line("Actions");?></div>
              <li><a class="dropdown-item has-icon analytics_bot" href="#"><i class="fas fa-chart-pie"></i> <?php echo $this->lang->line("Data Analytics");?></a></li>
              <?php 
              if($this->basic->is_exist("add_ons",array("project_id"=>31)))
              if($this->session->userdata('user_type') == 'Admin' || in_array(257,$this->module_access)) : ?>
              <li><a class="dropdown-item has-icon tree_bot" href="#"><i class="fas fa-sitemap"></i> <?php echo $this->lang->line("Data Tree View");?></a></li>
              <li><a class="dropdown-item has-icon export_bot" table_id="" href="#"><i class="fas fa-file-export"></i> <?php echo $this->lang->line("Data Export");?></a></li>
              <li><a class="dropdown-item has-icon import_bot" table_id="" href="#"><i class="fas fa-file-import"></i> <?php echo $this->lang->line("Data Import");?></a></li>
              <?php endif; ?>
           </ul>
          </div>
        </div>

        <div class="card-body" style="padding: 10px 17px 10px 10px;">
          <div class="row">
            <div class="col-12">

              <div id="right_column_content">              
                <iframe src="" frameborder="0" width="100%" onload="resizeIframe(this)"></iframe>

                <div id="right_column_bottom_content" style="display: none;">

                  <div class="" id="enable_start_button_modal" data-backdrop="static" data-keyboard="false" style="display: none; padding: 0px;">
                      <div class="modal-dialog modal-full" style="margin: 0 !important; min-width: 100%;">
                          <div class="modal-content no_shadow">
                              <div class="modal-body padding-0" id="enable_start_button_modal_body">
                                  
                                  <div class="form-group">
                                    <label><?php echo $this->lang->line('status');?></label>
                                    <select class="form-control" name="started_button_enabled" id="started_button_enabled">
                                      <option value="1"><?php echo $this->lang->line("enabled");?></option>
                                      <option value="0"><?php echo $this->lang->line("disabled");?></option>
                                    </select>
                                  </div>
                            

                                  <div class=""  id="delay_con2">
                                    <div class="form-group">
                                      <label>
                                        <?php echo $this->lang->line('Welcome Message');?>
                                        <a href="#" data-placement="bottom" data-html="true"  data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("Welcome Message") ?>" data-content="<?php echo $this->lang->line("The greeting text on the welcome screen is your first opportunity to tell a person why they should start a conversation with your Messenger bot. Some things you might include in your greeting text might include a brief description of what your bot does, such as key features, or a tagline. This is also a great place to start establishing the style and tone of your bot.Greetings have a 160 character maximum, so keep it concise.")."<br><br>".$this->lang->line("Variables")." : <br>{{user_first_name}}<br>{{user_last_name}}<br>{{user_full_name}}"; ?>">&nbsp;&nbsp;<i class='fa fa-info-circle'></i> </a>
                                      </label>
                            
                            
                                        <span class='float-right'> 
                                          <a title="<?php echo $this->lang->line("You can include {{user_last_name}} variable inside your message. The variable will be replaced by real names when we will send it.") ?>" data-toggle="tooltip" data-placement="top" class='btn-sm getstarted_lead_last_name button-outline'><i class='fa fa-user'></i> <?php echo $this->lang->line("last name") ?></a>
                                        </span>
                                        <span class='float-right'> 
                                          <a title="<?php echo $this->lang->line("You can include {{user_first_name}} variable inside your message. The variable will be replaced by real names when we will send it.") ?>" data-toggle="tooltip" data-placement="top" class='btn-sm getstarted_lead_first_name button-outline'><i class='fa fa-user'></i> <?php echo $this->lang->line("first name") ?></a>
                                        </span> 
                              
                                        <div class="clearfix"></div>      
                            
                                      <textarea name="welcome_message" id="welcome_message" class="form-control" style="height:100px;"></textarea>
                            
                                    </div>
                                  </div>
                                  
                                  <div> 
                                      <a href="#" target="_BLANK" id="enable_start_button_submit" class="btn-lg btn btn-primary float-left"><i class="fa fa-check-circle"></i> <?php echo $this->lang->line("save");?></a>
                                    
                                      <a href="#" class="btn btn-warning float-right iframed" id="getstarted_button_edit_url"><i class="far fa-edit"></i> <?php echo $this->lang->line("Edit Get Started Reply");?></a>             
                                  </div>
                                  <div class="clearfix"></div>
                              </div>
                          </div>
                      </div>
                  </div>

                  <div class="" id="mark_seen_chat_settings" data-backdrop="static" data-keyboard="false" style="display: none; padding: 0px;">
                      <div class="modal-dialog modal-full" style="margin: 0 !important; min-width: 100%;">
                          <div class="modal-content no_shadow">
                              <div class="modal-body padding-0 ">
                           
                                    <div class="form-group">
                                      <label><?php echo $this->lang->line('Mark as seen status');?></label>
                                      <select class="form-control" name="mark_seen_status" id="mark_seen_status">
                                        <option value="1"><?php echo $this->lang->line("enabled");?></option>
                                        <option value="0"><?php echo $this->lang->line("disabled");?></option>
                                      </select>
                                    </div>
                               

                                    <div class="form-group">
                                      <label>
                                        <?php echo $this->lang->line('Chat with human Email');?>
                                      </label>
                                      <input type="text" class="form-control" name="chat_human_email" id="chat_human_email">
                                    </div>

                                    <div class="form-group">
                                    <label class="custom-switch">
                                      <input type="checkbox" name="no_match_found_reply" value="enabled" id="no_match_found_reply" class="custom-switch-input">
                                      <span class="custom-switch-indicator"></span>
                                      <span class="custom-switch-description"><?php echo $this->lang->line('Reply if no match found');?></span>
                                    </label>
                                    </div>

                                  
                                    <a href="#" id="mark_seen_save_button" class="btn-lg btn btn-primary"><i class="fa fa-check-circle"></i> <?php echo $this->lang->line("Save");?></a>
                                    
                                  <div class="clearfix"></div>
                              </div>
                          </div>
                      </div>
                  </div>

                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

    </div>
    
  </div>

<?php } ?>



<script type="text/javascript">
  function htmlspecialchars_decode(str) 
  {
     if (typeof(str) == "string") 
     {
      str = str.replace("&amp;",/&/g); 
      str = str.replace("&quot;",/"/g);
      str = str.replace("&#039;",/'/g);
      str = str.replace("&#92;",/\\/g);
      str = str.replace("&lt;",/</g);
      str = str.replace("&gt;",/>/g);
      }
     return str;
  }

  $(document).ready(function(){

    var base_url = "<?php echo base_url(); ?>";

    $(".page_list_item").click(function(e) {
      e.preventDefault();
      $('#middle_column .waiting').show();
      $('#middle_column_content').hide();
      $('#right_column .waiting').show();
      $('#right_column .main_card').hide();

      var page_table_id = $(this).attr('page_table_id');
      $('.page_list_item').removeClass('active');
      $(this).addClass('active');

      var analytics_bot_href = "<?php echo base_url('/messenger_bot_analytics/result/'); ?>"+page_table_id;
      var tree_bot_href = "<?php echo base_url('messenger_bot/tree_view/'); ?>"+page_table_id;

      $(".export_bot").attr('table_id',page_table_id);
      $(".import_bot").attr('table_id',page_table_id);

      $(".analytics_bot").attr('href',analytics_bot_href).attr('target','_BLANK');
      $(".tree_bot").attr('href',tree_bot_href).attr('target','_BLANK');

      $.ajax({
        type:'POST' ,
        url:"<?php echo site_url();?>messenger_bot/get_page_details",
        data:{page_table_id:page_table_id},
        dataType:'JSON',
        success:function(response){
          $("#middle_column_content").html(response.middle_column_content).show();
          $('#middle_column .waiting').hide();
          $("#reply_settings").click();
          $("#getstarted_button_edit_url").attr('href',response.getstarted_button_edit_url);
        }
      });
    });

    $(document).on('click','.iframed',function(e){
      e.preventDefault();
      var iframe_url = $(this).attr('href');
      var iframe_height = $(this).attr('data-height');
      $("#right_column_content iframe").attr('src',iframe_url).show();
      $("#right_column_bottom_content").hide();
      // $("#right_column_content iframe").attr('height',iframe_height);
      $("#right_column .main_card").show();
      $('#right_column .waiting').hide();

      $('.card-condensed').removeClass('active');
      $(this).parents('.card-condensed').addClass('active');

      var title='';
      if($(this).hasClass('dropdown-item')) title = $(this).html();
      else 
      {
        title = $(this).parents('.card-condensed').children('.card-icon').html();
        title += $(this).parents('.card-condensed').children('.card-body').children('h4').html();
      }
      $("#right_column_title").html(title);
      
    });

    $(document).on('click','.check_review_status_class',function(e){
      e.preventDefault();
      var auto_id = $(this).attr('data-id');
      if(auto_id=="") return false;
      $(this).addClass('btn-progress');
      $.ajax({
        type:'POST',
        url:"<?php echo site_url();?>messenger_bot_enhancers/check_review_status",
        data:{auto_id:auto_id}, // database id
        dataType:'json',
        context: this,
        success:function(response)
        {  
          $(this).removeClass('btn-progress');

          if(response.status=="0")
            swal('<?php echo $this->lang->line("Error"); ?>', response.message, 'error');
          else 
          {
            swal('<?php echo $this->lang->line("Status"); ?>', response.message, 'success').then((value) => {
                $(".page_list_item.active").click();
              });            
          }
                  
        }
      });

    });

    $(document.body).on('click','.estimate_now_class',function(e){
      e.preventDefault();
      var auto_id = $(this).attr('data-id');
      var successfully="<?php echo $this->lang->line("Estimation was run successfully"); ?>";
      var waiting="<?php echo $this->lang->line("Please wait 20 seconds"); ?>";
      var estimate_now="<?php echo $this->lang->line("Estimate Quick Send Reach"); ?>";
      
      if(auto_id=="") return false;
      $(this).addClass('btn-progress');
      swal('<?php echo $this->lang->line(""); ?>', waiting, '');
      $.ajax({
        type:'POST',
        url:"<?php echo site_url();?>messenger_bot_enhancers/estimate_reach",
        data:{auto_id:auto_id}, // database id
        dataType:'json',
        context: this,
        success:function(response)
        {  
          $(this).removeClass('btn-progress');

          if(response.status=="0")
            swal('<?php echo $this->lang->line("Error"); ?>', response.message, 'error');
          else
            swal('<?php echo $this->lang->line("Estimated reach"); ?>', response.message, 'success').then((value) => {
                $(".page_list_item.active").click();
              });       
        }
      });

    }); 

    $('#err-log, #import_bot_modal, #export_bot_modal').on('hidden.bs.modal', function () { 
      $(".page_list_item.active").click();
    });

    var table1='';
    $(document).on('click','.error_log_report',function(e){
      e.preventDefault();
      var table_id = $(this).attr('table_id');
      $("#put_page_id").val(table_id);
      var base_url = '<?php echo site_url();?>';

      // $("#error_response_div").html('<div class="text-center waiting previewLoader"><i class="fas fa-spinner fa-spin blue text-center" style="font-size: 40px;"></i></div>');
      
      $("#err-log").modal(); 

      if (table1 == '')
      {
        var perscroll1;
        var base_url = "<?php echo base_url(); ?>";
        table1 = $("#mytable1").DataTable({
            serverSide: true,
            processing:true,
            bFilter: false,
            order: [[ 3, "desc" ]],
            pageLength: 10,
            ajax: {
                url: base_url+'messenger_bot/error_log_report',
                type: 'POST',
                data: function ( d )
                {
                    d.table_id = $("#put_page_id").val();
                    d.error_search = $("#error_searching").val();
                }
            },
            language: 
            {
              url: "<?php echo base_url('assets/modules/datatables/language/'.$this->language.'.json'); ?>"
            },
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            columnDefs: [
              {
                  targets: '',
                  className: 'text-center'
              },
              {
                  targets: [0,3],
                  sortable: false
              }
            ],
            fnInitComplete:function(){ // when initialization is completed then apply scroll plugin
            if(areWeUsingScroll)
            {
              if (perscroll1) perscroll1.destroy();
                perscroll1 = new PerfectScrollbar('#mytable1_wrapper .dataTables_scrollBody');
            }
            },
            scrollX: 'auto',
            fnDrawCallback: function( oSettings ) { //on paginition page 2,3.. often scroll shown, so reset it and assign it again 
              if(areWeUsingScroll)
              { 
              if (perscroll1) perscroll1.destroy();
              perscroll1 = new PerfectScrollbar('#mytable1_wrapper .dataTables_scrollBody');
              }
            }
        });
      }
      else table1.draw();


    }); 

    $(document).on('keyup', '#error_searching', function(event) {
      event.preventDefault(); 
      table1.draw();
    });


    $(document).on('click','.enable_start_button',function(e){
      e.preventDefault();
      $('.card-condensed').removeClass('active');
      $(this).parents('.card-condensed').addClass('active');
      
      title = $(this).parents('.card-condensed').children('.card-icon').html();
      title += $(this).parents('.card-condensed').children('.card-body').children('h4').html();      
      $("#right_column_title").html(title);

      var page_id = $(this).attr('sbutton-enable');
      var started_button_enabled = $(this).attr('sbutton-status');
      var welcome_message = htmlspecialchars_decode($(this).attr('welcome-message'));

      $("#welcome_message").val(welcome_message); 
      $("#started_button_enabled").val(started_button_enabled);

      if(started_button_enabled=='0') $("#delay_con2").hide();
      else $("#delay_con2").show();

      $("#right_column_content iframe").hide();
      $("#right_column_bottom_content").show();
      $("#mark_seen_chat_settings").hide();

      $("#enable_start_button_submit").attr("table_id",page_id);
      $("#enable_start_button_modal").show();

      /**Load Emoji For Welcome Screen Message on Get Started Button ***/
       $("#welcome_message").emojioneArea({
            autocomplete: false,
          pickerPosition: "bottom"
         });
    });


    $(document).on('click','#enable_start_button_submit',function(e){
      e.preventDefault();
      var table_id = $(this).attr('table_id');
      var welcome_message = $("#welcome_message").val();
      var started_button_enabled = $("#started_button_enabled").val();
      $(this).addClass('btn-progress');
       $.ajax
        ({
           type:'POST',
           url:base_url+'messenger_bot/get_started_welcome_message',
           data:{table_id:table_id,welcome_message:welcome_message,started_button_enabled:started_button_enabled},
           dataType:'JSON',
           context: this,
           success:function(response)
            {
              $(this).removeClass('btn-progress');
              if(response.status=='1') 
                iziToast.success({title: '',message: response.message,position: 'bottomRight'});
              else iziToast.error({title: '',message: response.message,position: 'bottomRight'});
            } 
        }); 

    });

    $(document).on('change','#started_button_enabled',function(){
      var started_button_enabled = $(this).val();
      if(started_button_enabled=='1') $("#delay_con2").show();
      else $("#delay_con2").hide();
    });


    $(document).on('click','.enable_general_settings',function(e){
      e.preventDefault();
      $('.card-condensed').removeClass('active');
      $(this).parents('.card-condensed').addClass('active');

      title = $(this).parents('.card-condensed').children('.card-icon').html();
      title += $(this).parents('.card-condensed').children('.card-body').children('h4').html();      
      $("#right_column_title").html(title);

      var table_id = $(this).attr('table_id');
      var chat_human_email = $(this).attr('chat_human_email');
      var mark_seen_status = $(this).attr('mark_seen_status');
      var no_match_found_reply = $(this).attr('no_match_found_reply');

      if(no_match_found_reply == 'enabled')
      {
        $("#no_match_found_reply").prop("checked", true);
        $("#no_match_found_reply").val('enabled');
      }
      else
      {
        $("#no_match_found_reply").prop("checked", false);
        $("#no_match_found_reply").val('disabled');
      }


      $("#mark_seen_status").val(mark_seen_status);
      $("#chat_human_email").val(chat_human_email);


      $("#right_column_content iframe").hide();
      $("#right_column_bottom_content").show();
      $("#enable_start_button_modal").hide();

      $("#mark_seen_save_button").attr("table_id",table_id);
      $("#mark_seen_chat_settings").show();
    });

    $(document).on('change','input[name=no_match_found_reply]',function(){
      var checked_property = $("#no_match_found_reply").prop("checked");
      if(checked_property)
        $("#no_match_found_reply").val('enabled');
      else
        $("#no_match_found_reply").val('disabled');

    });


    $(document).on('click','#mark_seen_save_button',function(e){
      e.preventDefault();
      var table_id = $(this).attr('table_id');
      var mark_seen_status = $("#mark_seen_status").val();
      var chat_human_email = $("#chat_human_email").val();
      var no_match_found_reply = $("#no_match_found_reply").val();
      
      $(this).addClass('btn-progress');
       $.ajax
        ({
           type:'POST',
           url:base_url+'messenger_bot/mark_seen_chat_human_settings',
           data:{table_id:table_id,mark_seen_status:mark_seen_status,chat_human_email:chat_human_email,no_match_found_reply:no_match_found_reply},
           dataType:'JSON',
           context: this,
           success:function(response)
            {
              $(this).removeClass('btn-progress');
              if(response.status=='1') 
                iziToast.success({title: '',message: response.message,position: 'bottomRight'});
              else iziToast.error({title: '',message: response.message,position: 'bottomRight'});
            } 
        }); 

    });



    $(document).on('click','.lead_first_name',function(){
        var textAreaTxt = $(this).parent().next().next().next().children('.emojionearea-editor').html();
        var lastIndex = textAreaTxt.lastIndexOf("<br>");
        if(lastIndex!='-1')
          textAreaTxt = textAreaTxt.substring(0, lastIndex);
        var txtToAdd = " #LEAD_USER_FIRST_NAME# ";
        var new_text = textAreaTxt + txtToAdd;
        $(this).parent().next().next().next().children('.emojionearea-editor').html(new_text);
        $(this).parent().next().next().next().children('.emojionearea-editor').click();
    });

    $(document).on('click','.lead_last_name',function(){
      var textAreaTxt = $(this).parent().next().next().next().next().children('.emojionearea-editor').html();
      var lastIndex = textAreaTxt.lastIndexOf("<br>");
      if(lastIndex!='-1')
        textAreaTxt = textAreaTxt.substring(0, lastIndex);
      var txtToAdd = " #LEAD_USER_LAST_NAME# ";
      var new_text = textAreaTxt + txtToAdd;
      $(this).parent().next().next().next().next().children('.emojionearea-editor').html(new_text);
        $(this).parent().next().next().next().next().children('.emojionearea-editor').click();
    });

    $(document).on('click','.getstarted_lead_first_name',function(){      
        var textAreaTxt = $(this).parent().next().next().next().children('.emojionearea-editor').html();          
        var lastIndex = textAreaTxt.lastIndexOf("<br>");        
        if(lastIndex!='-1')
        textAreaTxt = textAreaTxt.substring(0, lastIndex);        
        var txtToAdd = " {{user_first_name}} ";
        var new_text = textAreaTxt + txtToAdd;
        $(this).parent().next().next().next().children('.emojionearea-editor').html(new_text);
        $(this).parent().next().next().next().children('.emojionearea-editor').click();
    });

    $(document).on('click','.getstarted_lead_last_name',function(){      
        var textAreaTxt = $(this).parent().next().next().next().next().children('.emojionearea-editor').html();
        var lastIndex = textAreaTxt.lastIndexOf("<br>");
        if(lastIndex!='-1')
        textAreaTxt = textAreaTxt.substring(0, lastIndex);
        var txtToAdd = " {{user_last_name}} ";
        var new_text = textAreaTxt + txtToAdd;
        $(this).parent().next().next().next().next().children('.emojionearea-editor').html(new_text);
        $(this).parent().next().next().next().next().children('.emojionearea-editor').click();  
    });


  });

  $("document").ready(function(){
    var session_value = "<?php echo $this->session->userdata('bot_list_get_page_details_page_table_id'); ?>";
    if(session_value=='')  $(".list-group li:first").click();    
    else $("li[page_table_id='"+session_value+"']").click();

    var base_url = "<?php echo base_url(); ?>";

    $(document).on('click','.import_bot',function(e){
      e.preventDefault();
      var table_id = $(this).attr('table_id');
      $("#import_id").val(table_id);
      $(".post_to").prop("checked", false);
      $("#json_upload_input").val('');
      $("#import_bot_modal").modal();
    });

    $(document).on('click','#import_bot_submit',function(e){
      e.preventDefault();
      var template_id = $("#template_id").val();
      var filename = $("#json_upload_input").val();

      if(template_id=="" && filename=="")
      {
        swal('<?php echo $this->lang->line("Warning"); ?>', "<?php echo $this->lang->line('You must select a template or upload one.');?>", 'warning');
        return;
      }

      $(this).addClass('btn-progress');

      var queryString = new FormData($("#import_bot_form")[0]);
      $.ajax({
            type:'POST' ,
            url: base_url+"messenger_bot/import_bot_check",
            dataType: 'JSON',
            data: queryString,
            cache: false,
            contentType: false,
            processData: false,
            context: this,
            success:function(response)
            { 
              $(this).removeClass('btn-progress');
              if(response.status=='1')
              {
                var json_upload_input=response.json_upload_input;
                swal({
                  title: '<?php echo $this->lang->line("Warning!"); ?>',
                  text: response.message,
                  icon: 'warning',
                  buttons: true,
                  dangerMode: true,
                })
                .then((willDelete) => {
                  if (willDelete) 
                  {
                    $(this).addClass('btn-progress');
                    $.ajax({
                      context: this,
                      type:'POST' ,
                      url:"<?php echo site_url();?>messenger_bot/import_bot",
                      // dataType: 'json',
                      data:{json_upload_input:json_upload_input,page_id:response.page_id,template_id:response.template_id},
                      success:function(response2){ 
                        $(this).removeClass('btn-progress');
                        var success_message=response2;
                        var span = document.createElement("span");
                        span.innerHTML = success_message;
                        swal({ title:'<?php echo $this->lang->line("Import Status"); ?>', content:span,icon:'success'});
                      }
                    });
                  } 
                });
              }
              else
              {
                swal('<?php echo $this->lang->line("Error"); ?>', response.message, 'error');
              }
            }
      });
    });

    $(document).on('click', '#cancel_import_bot', function(e){
      e.preventDefault();
      $("#import_bot_modal").modal('hide');
    });


    $(document).on('click','.export_bot',function(e){
      e.preventDefault();
      var table_id = $(this).attr('table_id');
      $("#export_id").val(table_id);

      $('#allowed_package_ids').val(null).trigger('change');
      $("#template_name").val('');
      $("#template_description").val('');
      $("#template_preview_image").val('');
      $("#only_me_input").prop("checked", true);
      $("#other_user_input").prop("checked", false); 
      $("#allowed_package_ids_con").addClass('hidden')

      $("#export_bot_modal").modal();
    });

    $(document).on('change','input[name=template_access]',function(){
      var template_access = $(this).val();
      if(template_access=='private') $("#allowed_package_ids_con").addClass('hidden');
      else $("#allowed_package_ids_con").removeClass('hidden');
    });

    $(document).on('click','#export_bot_submit',function(e){
      e.preventDefault();
      var template_name = $("#template_name").val();
      var template_access = $('input[name=template_access]:checked').val();
      var allowed_package_ids = $("#allowed_package_ids").val();

      if(template_name=="")
      {
        swal('<?php echo $this->lang->line("Warning"); ?>', "<?php echo $this->lang->line('Please provide template name.');?>", 'warning');
        return;
      }

      if(template_access=="public" && allowed_package_ids==null)
      {
        swal('<?php echo $this->lang->line("Warning"); ?>', "<?php echo $this->lang->line('You must choose user packages to give them template access.');?>", 'warning');
        return;
      }

      $(this).addClass('btn-progress');
      var queryString = new FormData($("#export_bot_form")[0]);
      $.ajax({
            type:'POST' ,
            url: base_url+"messenger_bot/export_bot",
            dataType: 'JSON',
            data: queryString,
            cache: false,
            contentType: false,
            processData: false,
            context: this,
            success:function(response)
            { 
              $(this).removeClass('btn-progress');

              var success_message=response.message;
              var span = document.createElement("span");
              span.innerHTML = success_message;
              swal({ title:'<?php echo $this->lang->line("Export Status"); ?>', content:span,icon:'success'});
            }
      });

    });

    $(document).on('click', '#cancel_bot_submit', function(e){
      e.preventDefault();
      $("#export_bot_modal").modal('hide');
    });


    $("#allowed_package_ids").select2({ width: "100%" });

    $('.modal').on("hidden.bs.modal", function (e) { 
        if ($('.modal:visible').length) { 
            $('body').addClass('modal-open');
        }
    });

    $(document).on('click','.load_preview_modal',function(e){
      e.preventDefault();
      var item_type = $(this).attr('item_type');
      var file_path = $(this).next().val();
      var user_id = "<?php echo $this->user_id; ?>";

      var res = file_path.match(/http/g);
      if(file_path != '' && res === null)
        file_path = base_url+"upload/image/"+user_id+"/"+file_path;

      $("#preview_text_field").val(file_path);
      if(item_type == 'image')
      {
        $("#modal_preview_image").attr('src',file_path);
        $("#image_preview_div_modal").show();
        $("#video_preview_div_modal").hide();
        $("#audio_preview_div_modal").hide();
        
      }
      $("#modal_for_preview").modal();
    });

    var user_id = "<?php echo $this->session->userdata('user_id'); ?>";
    var image_upload_limit = "<?php echo $image_upload_limit; ?>";
    $("#template_preview_image_div").uploadFile({
      url:base_url+"messenger_bot/upload_image_only",
      fileName:"myfile",
      maxFileSize:image_upload_limit*1024*1024,
      showPreview:false,
      returnType: "json",
      dragDrop: true,
      showDelete: true,
      multiple:false,
      maxFileCount:1, 
      acceptFiles:".png,.jpg,.jpeg,.JPEG,.JPG,.PNG,.gif,.GIF",
      deleteCallback: function (data, pd) {
          var delete_url="<?php echo site_url('messenger_bot/delete_uploaded_file');?>";
          $.post(delete_url, {op: "delete",name: data},
              function (resp,textStatus, jqXHR) {
                $("#template_preview_image").val('');                    
              });
         
       },
       onSuccess:function(files,data,xhr,pd)
         {
             var data_modified = base_url+"upload/image/"+user_id+"/"+data;
             $("#template_preview_image").val(data_modified);
         }
    });

    $("#json_upload").uploadFile({
        url:base_url+"messenger_bot/upload_json_template",
        fileName:"myfile",
        showPreview:false,
        returnType: "json",
        dragDrop: true,
        showDelete: true,
        multiple:false,
        maxFileCount:1, 
        acceptFiles:".json",
        deleteCallback: function (data, pd) {
            var delete_url="<?php echo site_url('messenger_bot/upload_json_template_delete');?>";
              $.post(delete_url, {op: "delete",name: data},
                  function (resp,textStatus, jqXHR) { 
                    $("#json_upload_input").val(''); 
                    $(".type1,.type2").show();                      
                  });
           
         },
         onSuccess:function(files,data,xhr,pd)
           {
               var data_modified = data;
               $("#json_upload_input").val(data_modified);
               $("#template_id").val('');
               $(".type1,.type2").hide();
           }
    });


  });
</script>


<div class="modal fade" id="err-log" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="padding-left: 30px;">
                <h5 class="modal-title"><i class="fa fa-bug"></i> <?php echo $this->lang->line("Error Report");?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- <div class="col-12 table-responsive" id="error_response_div" style="padding: 20px;"></div> -->
                  <div class="col-12 margin-top">
                    <input type="text" id="error_searching" name="error_searching" class="form-control" placeholder="<?php echo $this->lang->line("Search..."); ?>" style='width:200px;'>                                          
                  </div>
                  <div class="col-12">
                    <div class="data-card">   
                      <input type="hidden" name="put_page_id" id="put_page_id">                  
                      <div class="table-responsive2">
                        <table class="table table-bordered" id="mytable1">
                          <thead>
                            <tr>
                              <th>#</th>
                              <th><?php echo $this->lang->line("Bot Name"); ?></th> 
                              <th><?php echo $this->lang->line("Error Message"); ?></th> 
                              <th><?php echo $this->lang->line("Error Time"); ?></th> 
                              <th><?php echo $this->lang->line("Actions"); ?></th>  
                            </tr>
                          </thead>
                        </table>
                      </div>
                    </div>
                  </div>

                </div>               
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="export_bot_modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="padding-left: 30px;">
                <h5 class="modal-title"><i class="fa fa-file-export"></i> <?php echo $this->lang->line("Export Bot Settings");?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body" id="export_bot_modal_body">

                <form id="export_bot_form" method="POST">
                  <input type="hidden" name="export_id" id="export_id">
                  <div class="col-12">
                    <div class="form-group">
                      <label><?php echo $this->lang->line('Template Name');?> *</label>
                      <input type="text" name="template_name" class="form-control" id="template_name">                    
                    </div>
                  </div>

                  <div class="col-12">
                    <div class="form-group">
                      <label><?php echo $this->lang->line('Template Description');?> </label>
                      <textarea type="text" rows="4" name="template_description" class="form-control" id="template_description"></textarea>                    
                    </div>
                  </div>

                  <div class="col-12">
                    <div class="form-group">
                      <label><?php echo $this->lang->line('Template Preview Image');?> [Square image like (400x400) is recommended]</label>
                      <span style="cursor:pointer;" class="badge badge-status blue load_preview_modal float-right" item_type="image" file_path=""><i class="fa fa-eye"></i> <?php echo $this->lang->line('preview'); ?></span>

                      <input type="hidden" name="template_preview_image" class="form-control" id="template_preview_image">                   
                      <div id="template_preview_image_div"><?php echo $this->lang->line("upload") ?></div>
                    </div>
                  </div>

                  <?php if($this->session->userdata("user_type")=='Admin'){ ?>
                    <div class="col-12">

                      <div class="form-group">
                        <div class="control-label"><?php echo $this->lang->line('Template Access'); ?> *</div>
                        <div class="custom-switches-stacked mt-2">
                          <label class="custom-switch">
                            <input type="radio" name="template_access" value="private" id="only_me_input" class="custom-switch-input" checked>
                            <span class="custom-switch-indicator"></span>
                            <span class="custom-switch-description"><?php echo $this->lang->line("Only me"); ?></span>
                          </label>
                          <label class="custom-switch">
                            <input type="radio" name="template_access" value="public" id="other_user_input" class="custom-switch-input">
                            <span class="custom-switch-indicator"></span>
                            <span class="custom-switch-description"><?php echo $this->lang->line("Me as well as other users"); ?></span>
                          </label>
                        </div>                
                      </div>

                    </div>

                    <div class="col-12 hidden" id="allowed_package_ids_con">
                      <div class="form-group">
                        <label><?php echo $this->lang->line('Choose User Packages');?> *</label><br/>
                        <?php echo form_dropdown('allowed_package_ids[]', $package_list, '','class="form-control select2" id="allowed_package_ids" multiple'); ?>
                      </div>
                    </div>
                  <?php } ?>
                  
                  <div class="row">
                    <div class="col-6"><a href="#" id="export_bot_submit" class="btn btn-primary btn-lg"><i class="fa fa-file-export"></i> <?php echo $this->lang->line("Export");?></a></div>                
                    <div class="col-6"><a href="#" id="cancel_bot_submit" class="btn btn-secondary btn-lg float-right"><i class="fa fa-close"></i> <?php echo $this->lang->line("Cancel");?></a></div>
                  </div>
                  <div class="clearfix"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="import_bot_modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="padding-left: 30px;">
                <h5 class="modal-title"><i class="fa fa-file-import"></i> <?php echo $this->lang->line("Import Bot Settings");?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body" id="import_bot_modal_body">
                <div id="preloader" class="text-center waiting hidden"><i class="fas fa-spinner fa-spin blue text-center" style="font-size: 40px;"></i></div>
                <form id="import_bot_form" method="POST">
                  <input type="hidden" name="import_id" id="import_id">

                  <!-- New section -->
                  <?php if(!empty($saved_template_list)) : ?>
                  <!-- zilani -->
                  <p class="text-center" style="font-weight: bold;"><?php echo $this->lang->line('Choose from previous template'); ?></p><br>
                  <div class="makeScroll" style="height: 400px;overflow: auto;">
                    <div class="row">

                      <?php $i=1; foreach ($saved_template_list as $key=>$val) : 
                        $id=$val['id'];
                        $template_name=isset($val['template_name']) ? $val['template_name'] : '';
                        $description=isset($val['description']) ? $val['description'] : '';
                        $preview_image=isset($val['preview_image']) ? $val['preview_image'] : ''; 
                        $added_date = date("M j, y H:i",strtotime($val['saved_at']));
                      ?>
                      <div class="col-12 col-md-6">
                        <div class="card author-box">
                          <div class="card-body">
                            <div class="row">
                              <div class="col-4">
                                  <div class="avatar-item">
                                    <?php if($preview_image != '') : ?>
                                      <a target="_BLANK" href="<?php echo base_url('messenger_bot_export_import/view/'.$id);?>" data-toggle='tooltip' title="<?php echo $this->lang->line('Click here to see template details'); ?>">
                                        <img alt="image" width="80" height="80" src="<?php echo base_url('upload/image/'.$val['user_id'].'/'.$preview_image); ?>" class="rounded">
                                      </a>
                                    <?php else : ?>
                                      <a target="_BLANK" href="<?php echo base_url('messenger_bot_export_import/view/'.$id);?>" data-toggle='tooltip' title="<?php echo $this->lang->line('Click here to see template details'); ?>">
                                        <img alt="image" style="width:80px !important;height:80px !important;" src="<?php echo base_url("assets/img/avatar/avatar-1.png");?>" class="rounded">
                                      </a>
                                    <?php endif; ?>
                                  </div>
                              </div>

                              <div class="col-8">
                                <div class="author-box-details" style="margin-left: 0;">
                                  <div class="author-box-name">
                                    <div class="row">
                                      <div class="col-10">
                                        <h6 class="text-left">
                                          <?php 
                                            if(strlen($template_name) > 17)
                                            {
                                              $short_template_name = substr($template_name,0,16);
                                              echo $short_template_name."..."; 
                                            } else 
                                            {
                                              echo $template_name;
                                            }
                                          ?>
                                        </h6>
                                      </div>
                                      <div class="col-2">
                                        <div class="custom-control custom-radio" data-toggle='tooltip' title="<?php echo $this->lang->line('Click here to select this template'); ?>">
                                          <input type="radio" name="template_id" class="post_to custom-control-input" value="<?php echo $id; ?>" id="<?php echo $id; ?>">
                                          <label class="custom-control-label" for="<?php echo $id; ?>" style="display:inline !important;"></label>
                                        </div>
                                      </div>
                                    </div>
                                    
                                  </div>
                                  <div class="author-box-description" style="margin-top: 0;">
                                    <p class="text-justify">
                                      <?php
                                        if(strlen($description) > 60)
                                        {
                                          $short_des = substr($description,0,59);
                                          echo $short_des."..."; 
                                        } else 
                                        {
                                          echo $description;
                                        }
                                      ?>
                                    </p>
                                  </div>
                                  <div class="w-100 d-sm-none"></div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <?php $i++; endforeach; ?>
                    </div>
                  </div>
                  <!-- zilani -->

                  <!-- <div class="container-fluid">
                    <div class="row">
                      <p class="text-center" style="font-weight: bold;"><?php echo $this->lang->line('Choose from previous template'); ?></p>
                      <div class="yscroll" style="height: 400px;overflow: auto;">
                          <?php foreach ($saved_template_list as $key=>$val) : 
                            $id=$val['id'];
                            $template_name=isset($val['template_name']) ? $val['template_name'] : '';
                            $description=isset($val['description']) ? $val['description'] : '';
                            $preview_image=isset($val['preview_image']) ? $val['preview_image'] : ''; 
                            $added_date = date("M j, y H:i",strtotime($val['saved_at']));
                          ?>

                          <div class="col-12 col-md-6">
                            <div class="box box-solid" style="">
                              <div class="box-body" style="padding-top: 10px;padding-bottom: 0;">
                                <h4 style="border:1px solid #fafafa; font-size: 15px; text-align: center; padding: 7px 10px; margin-top: 0;">
                                    <?php
                                          if(strlen($template_name) > 22)
                                          {
                                            $short_template_name = substr($template_name,0,19);
                                            echo $short_template_name."..."; 
                                          } else 
                                          {
                                            echo $template_name;
                                          }
                                        ?> 
                                        <div class="form-check float-right">
                                            <div class="clearfix"></div>
                                             <label class="radio_check">
                                               <input type="radio" name="template_id" class="post_to" value="<?php echo $id; ?>" id="<?php echo $id; ?>" >
                                               <span class="checkmark" data-toggle='tooltip' title="<?php echo $this->lang->line('Click here to select this template'); ?>" ></span>
                                             </label>
                                           </div> 
                                           <div class="clearfix"></div>
                                </h4>
                                <div class="media">
                                  <div class="media-left">
                                                                                
                                          <?php if($preview_image != '') : ?>
                                                <a data-toggle='tooltip' title="<?php echo $this->lang->line('Click here to see template details'); ?>" target="_BLANK" href="<?php echo base_url('messenger_bot_export_import/view/'.$id);?>">
                                                  <img style="width: 100px;height: 100px;border-radius: 4px;box-shadow: 0 1px 3px rgba(0,0,0,.15);" class="media-object" src="<?php echo base_url('upload/image/'.$val['user_id'].'/'.$preview_image); ?>" alt="preview image"><br>
                                                </a>
                                                <?php else : ?>
                                                  <a data-toggle='tooltip' title="<?php echo $this->lang->line('click here to see template details'); ?>" target="_BLANK" href="<?php echo base_url('messenger_bot_export_import/view/'.$id);?>">
                                                    <img style="width: 100px;height: 100px;border-radius: 4px;box-shadow: 0 1px 3px rgba(0,0,0,.15);"  class="media-object" src="https://via.placeholder.com/100x100.png" alt="preview image"><br>
                                                  </a>
                                          <?php endif; ?>
                                  </div>
                                  <div class="media-body">
                                      <div class="clearfix">
                                          <p class="text-justify">
                                            <?php
                                              if(strlen($description) > 173)
                                              {
                                                $short_des = substr($description,0,170);
                                                echo $short_des."..."; 
                                              } else 
                                              {
                                                echo $description;
                                              }
                                            ?>
                                          </p>
                                      </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                          <?php endforeach; ?>
                      </div>
                    </div>
                  </div> -->
              <?php endif; ?>
                  <!-- end new section -->
              <br><div class="col-12 text-center type2" style="font-weight: bold;"><?php echo $this->lang->line('OR'); ?></div><br>
              <div class="col-12 type3">
                <div class="text-center">
                  <label><?php echo $this->lang->line('Upload Template JSON');?></label>
                  <div class="form-group">    
                    <div id="json_upload"><?php echo $this->lang->line('Upload');?></div>
                    <input type="hidden" id="json_upload_input" name="json_upload_input">
                  </div>                
                </div>
              </div>

                  <div class="row">
                    <div class="col-6"><a href="#" id="import_bot_submit" class="btn btn-primary btn-lg"><i class="fa fa-file-import"></i> <?php echo $this->lang->line("Import");?></a></div>                
                    <div class="col-6"><a href="#" id="cancel_import_bot" class="btn btn-secondary btn-lg float-right"><i class="fa fa-close"></i> <?php echo $this->lang->line("Cancel");?></a></div>
                  </div>

                  <div class="clearfix"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal_for_preview" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-eye"></i> <?php echo $this->lang->line('item preview'); ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      </div>
      <div class="modal-body">
        <div id="image_preview_div_modal" style="display: none;">
          <img id="modal_preview_image" width="100%" src="">
        </div>
        <div id="video_preview_div_modal" style="display: none;">
          <video width="100%" id="modal_preview_video" controls>
            
          </video>
        </div>
        <div id="audio_preview_div_modal" style="display: none;">
          <audio width="100%" id="modal_preview_audio" controls>
            
          </audio>
        </div>
        <div>
          <input class="form-control" type="text" id="preview_text_field">
        </div>
      </div>
    </div>
  </div>
</div>

