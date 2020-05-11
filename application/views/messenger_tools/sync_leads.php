
<style type="text/css">
  .multi_layout{margin:0;background: #fff}
  .multi_layout .card{margin-bottom:0;border-radius: 0;}
  .multi_layout p, .multi_layout ul:not(.list-unstyled), .multi_layout ol{line-height: 15px;}
  .multi_layout .list-group li{padding: 15px 10px 12px 25px;}
  .multi_layout{border:.5px solid #dee2e6;}
  .multi_layout .collef,.multi_layout .colmid{padding-left: 0px; padding-right: 0px;border-right: .5px solid #dee2e6;}
  .multi_layout .colmid .card-icon{border:.5px solid #dee2e6;}
  .multi_layout .colmid .card-icon i{font-size:30px !important;}
  .multi_layout .main_card{box-shadow: none;}
  .multi_layout .collef .makeScroll{max-height:550px;overflow:auto;}
  .multi_layout .list-group .list-group-item{border-radius: 0;border:.5px solid #dee2e6;border-left:none;border-right:none;cursor: pointer;z-index: 0;}
  .multi_layout .list-group .list-group-item:first-child{border-top:none;}
  .multi_layout .list-group .list-group-item:last-child{border-bottom:none;}
  .multi_layout .list-group .list-group-item.active{border:.5px solid #6777EF;}
  .multi_layout .mCSB_inside > .mCSB_container{margin-right: 0;}
  .multi_layout .card-statistic-1{border:.5px solid #dee2e6;border-radius: 4px;}
  .multi_layout h6.page_name{font-size: 14px;}
  .multi_layout .card .card-header input{max-width: 100% !important;}
  .multi_layout .card .card-header h4 a{font-weight: 700 !important;}
  .multi_layout .card-primary{margin-top: 35px;margin-bottom: 15px;}
  .multi_layout .product-details .product-name{font-size: 12px;}
  .multi_layout .margin-top-50 {margin-top: 70px;}
  .multi_layout .waiting {height: 100%;width:100%;display: table;}
  .multi_layout .waiting i{font-size:60px;display: table-cell; vertical-align: middle;padding:30px 0;}
  .subscriber_info_modal {cursor: pointer;}

</style>


<section class="section">
  <div class="section-header">
    <h1><i class="fas fa-sync-alt"></i> <?php echo $page_title;?></h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item"><a href="<?php echo base_url('subscriber_manager'); ?>"><?php echo $this->lang->line("Subscriber Manager");?></a></div>
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
              <?php 
              $i=0; 
              foreach($page_info as $value)
              { ?>
                <li class="list-group-item page_list_item" page_table_id="<?php echo $value['id']; ?>">
                  <div class="row">
                    <div class="col-3 col-md-2"><img width="45px" class="rounded-circle" src="<?php echo $value['page_profile'];?>"></div>
                    <div class="col-9 col-md-10">
                      <h6 class="page_name"><?php echo $value['page_name']; ?></h6>
                      <span class="gray"><?php echo $value['page_id']; ?></span>
                      </div>
                    </div>
                </li> 
                <?php } ?>                
            </ul>
          </div>
        </div>
      </div>          
    </div>

    <div class="col-12 col-md-7 col-lg-9 colmid" id="middle_column">

      <div class="text-center waiting">
        <i class="fas fa-spinner fa-spin blue text-center"></i>
      </div>

      <div id="middle_column_content"></div>
      
    </div>

    
    
  </div>
<?php 
} ?>


<?php     
    $disabledsuccessfully = $this->lang->line("Backgound scanning has been disabled successfully.");
    $enabledsuccessfully = $this->lang->line("Backgound scanning has been enabled successfully.");
    $youhavenotselected = $this->lang->line("You have not selected any subscriber to assign label. You can choose upto");
    $leadsatatime = $this->lang->line("subscribers at a time.");
    $youcanselectupto = $this->lang->line("You can select upto");
    $leadsyouhaveselected = $this->lang->line(",you have selected");
    $leads = $this->lang->line("subscribers.");
    $youhavenotselectedanyleadtoassigngroup = $this->lang->line("You have not selected any subscriber to assign label.");
    $youhavenotselectedanyleadgroup = $this->lang->line("You have not selected any label.");
    $groupshavebeenassignedsuccessfully = $this->lang->line("Labels have been assigned successfully");
?>

<script type="text/javascript">

  var base_url="<?php echo base_url();?>";
  var user_id="<?php echo $this->user_id;?>";

  var youhavenotselected = "<?php echo $youhavenotselected;?>";
  var leadsatatime = "<?php echo $leadsatatime;?>";
  var youcanselectupto = "<?php echo $youcanselectupto;?>";
  var leadsyouhaveselected = "<?php echo $leadsyouhaveselected;?>";
  var leads = "<?php echo $leads;?>";
  var youhavenotselectedanyleadtoassigngroup = "<?php echo $youhavenotselectedanyleadtoassigngroup; ?>";
  var youhavenotselectedanyleadgroup = "<?php echo $youhavenotselectedanyleadgroup; ?>";
  var groupshavebeenassignedsuccessfully = "<?php echo $groupshavebeenassignedsuccessfully; ?>";

  $("document").ready(function(){

    $(".page_list_item").click(function(e) {
      e.preventDefault();
      $('#middle_column .waiting').show();
      $('#middle_column_content').hide();

      var page_table_id = $(this).attr('page_table_id');
      $('.page_list_item').removeClass('active');
      $(this).addClass('active');

      $.ajax({
        type:'POST' ,
        url:"<?php echo site_url();?>subscriber_manager/get_page_details",
        data:{page_table_id:page_table_id},
        dataType:'JSON',
        success:function(response){
          $("#middle_column_content").html(response.middle_column_content).show();
          $('#middle_column .waiting').hide();   
        }
      });
    });  

    $(document).on('click','.import_data',function(e){
      e.preventDefault();
      var id=$(this).attr('id');
      $("#start_scanning").attr("data-id",id);
      $("#import_lead_modal").modal();
    });

    $(document).on('click','.subscriber_info_modal',function(e){
      e.preventDefault();
      $("#subscriber_info_modal").modal();
    });



    $(document).on('click','#start_scanning',function(e){
      e.preventDefault();
      var id=$(this).attr('data-id');
      var scan_limit=$("#scan_limit").val();
      var folder=$("#folder").val();
      $("#start_scanning").addClass('btn-progress');
      $(".auto_sync_lead_page").addClass('disabled');
      $(".user_details_modal").addClass('disabled');  
      $("#scan_load").attr('class','');
      $.ajax({
        context:this,
        type:'POST' ,
        url:"<?php echo site_url();?>subscriber_manager/import_lead_action",
        data:{id:id,scan_limit:scan_limit,folder:folder},
        dataType:'JSON',
        success:function(response){
         $("#start_scanning").removeClass('btn-progress');

         if(response.status == '1')
         {
           swal('<?php echo $this->lang->line("Success"); ?>', response.message, 'success');
         }
         else
         {
           swal('<?php echo $this->lang->line("Error"); ?>', response.message, 'error');
         }
        }
      });

    });


    var table1 = '';
    $(document).on('click','.user_details_modal',function(e){
      
      e.preventDefault();
      var page_id = $(this).attr('id');
      var fb_page_id = $(this).attr('fb-page-id');
      $("#put_page_id").val(page_id);

      var download_url = base_url+"subscriber_manager/download_full/"+user_id+"-"+page_id;
      var migrate_id = user_id+"-"+page_id;

      $("#download_list").attr("href",download_url);
      $("#migrate_list").attr("button_id",migrate_id);
      $("#assign_group").attr("button_id",page_id);

      $("#htm").modal();

      if (table1 == '')
      {
        var perscroll;
        table1 = $("#mytable").DataTable({
            serverSide: true,
            processing:true,
            bFilter: false,
            order: [[ 7, "desc" ]],
            pageLength: 10,
            ajax: {
                url: base_url+'subscriber_manager/lead_list_data',
                type: 'POST',
                dataSrc: function ( json ) 
                {
                  $(".table-responsive").niceScroll();
                  return json.data;
                },
                data: function ( d )
                {
                    d.page_id = $('#put_page_id').val();
                    d.search_value = $('#search_value').val();
                    d.label_id = $('#label_id').val();
                }
            },
            language: 
            {
              url: "<?php echo base_url('assets/modules/datatables/language/'.$this->language.'.json'); ?>"
            },
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            columnDefs: [
              {
                  targets: [5],
                  visible: false
              },
              {
                  targets: [0,5,6,7],
                  className: 'text-center'
              },
              {
                  targets: [0,1,4,5,6],
                  sortable: false
              }
            ],
            fnInitComplete:function(){  // when initialization is completed then apply scroll plugin
                if(areWeUsingScroll)
                {
                  if (perscroll) perscroll.destroy();
                  perscroll = new PerfectScrollbar('#mytable_wrapper .dataTables_scrollBody');
                }
            },
            scrollX: 'auto',
            fnDrawCallback: function( oSettings ) { //on paginition page 2,3.. often scroll shown, so reset it and assign it again 
                if(areWeUsingScroll)
                { 
                  if (perscroll) perscroll.destroy();
                  perscroll = new PerfectScrollbar('#mytable_wrapper .dataTables_scrollBody');
                }
            }
        });
      }
      else table1.draw();
    });


    $(document).on('click', '#search_subscriber', function(e) {
        e.preventDefault(); 
        table1.draw();
    });

    $(document).on('change', '#label_id', function(e) {
        table1.draw();
    });


    $(document).on('click','.auto_sync_lead_page',function(e){
      e.preventDefault();
      var page_id = $(this).attr('auto_sync_lead_page_id');
      var operation = $(this).attr('enable_disable');
      var base_url = '<?php echo site_url();?>';

      var disabledsuccessfully = '<?php echo $disabledsuccessfully;?>';
      var enabledsuccessfully = '<?php echo $enabledsuccessfully;?>';

      $(".import_data").addClass('disabled');
      $(".auto_sync_lead_page").addClass('disabled');
      $(".user_details_modal").addClass('disabled');
      $.ajax({
        type:'POST' ,
        url:"<?php echo site_url();?>subscriber_manager/enable_disable_auto_sync",
        data:{page_id:page_id,operation:operation},
        success:function(response)
        { 
           if(operation=="0") iziToast.success({title: '',message: disabledsuccessfully,position: 'bottomRight'});
           else iziToast.success({title: '',message: enabledsuccessfully,position: 'bottomRight'});

           $(".page_list_item.active").click();
        }
      });

    });

    $(document).on('click','.client_thread_subscribe_unsubscribe',function(e){
      e.preventDefault();
      $(this).addClass('btn-progress');
      var client_subscribe_unsubscribe_status = $(this).attr('id');

      $.ajax({
        context: this,
        type:'POST',
        dataType:'JSON',
        url:"<?php echo site_url();?>subscriber_manager/client_subscribe_unsubscribe_status_change",
        data:{client_subscribe_unsubscribe_status:client_subscribe_unsubscribe_status},
        success:function(response){
           $(this).removeClass('btn-progress');
           // $("#"+client_subscribe_unsubscribe_status).parent().parent().prev().html(response.label); 
           $("#"+client_subscribe_unsubscribe_status).parent().html(response.button);

           if(response.status=='1') iziToast.success({title: '',message: response.message,position: 'bottomRight'});
           else iziToast.error({title: '',message: response.message,position: 'bottomRight'});
        }
      });

    });

    $(document).on('click','#migrate_list',function(e){
      
      e.preventDefault();

      swal({
           title: '<?php echo $this->lang->line("Migrate as Bot Subscriber"); ?>',
           text: '<?php echo $this->lang->line("Do you really want to migrate this list as your bot subscribers?"); ?>',
           icon: 'warning',
           buttons: true,
           dangerMode: true,
         })
         .then((willDelete) => {
           if (willDelete) 
           {
               var base_url = '<?php echo site_url();?>';
               $(this).parent().prev().addClass('btn-progress');

               var user_page_id = $("#migrate_list").attr('button_id');

               $.ajax({
                 context: this,
                 type:'POST' ,
                 url:"<?php echo site_url();?>subscriber_manager/migrate_lead_to_bot",
                 dataType: 'json',
                 data:{user_page_id : user_page_id},
                 success:function(response){ 
                    $(this).parent().prev().removeClass('btn-progress');
                    if(response.status == '1')
                    {
                      swal('<?php echo $this->lang->line("Migration Successful"); ?>', response.message, 'success');
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

    $(document).on('click','#assign_group',function(e){
        e.preventDefault();
        var upto = 500;
        var selected_page = $("#assign_group").attr('button_id');// database id
        var ids = [];
        $(".datatableCheckboxRow:checked").each(function ()
        {
            ids.push(parseInt($(this).val()));
        });
        var selected = ids.length;

        if(ids=="") 
        {
          swal('<?php echo $this->lang->line("Warning") ?>', youhavenotselected+" "+upto+" "+leadsatatime, 'warning');
          return;
        } 
        if(selected>upto) 
        {
            swal('<?php echo $this->lang->line("Warning") ?>',youcanselectupto+" "+upto+" "+leadsyouhaveselected+" "+selected+" "+leads, 'warning');
            return;
        }
        
        $.ajax({
          type:'POST' ,
          url: "<?php echo site_url(); ?>subscriber_manager/get_label_dropdown_multiple",
          data:{selected_page:selected_page},
          success:function(response)
          {
             $("#get_labels").html(response);
          }
        });  

        $("#assign_group_modal").modal();         
    });

    $(document).on('click','#assign_group_submit',function(e){
        e.preventDefault();
        swal({
           title: '<?php echo $this->lang->line("Assign Label"); ?>',
           text: '<?php echo $this->lang->line("Do you really want to assign selected labels to your selected subscribers? Please be noted that bulk assigning labels will replace subscribers previous labels if any."); ?>',
           icon: 'warning',
           buttons: true,
           dangerMode: true,
         })
         .then((willDelete) => {
           if (willDelete) 
           {
               var ids = [];
               $(".datatableCheckboxRow:checked").each(function ()
               {
                   ids.push(parseInt($(this).val()));
               });
               var selected = ids.length;        
               
               
               if(ids=="") 
               {
                 swal('<?php echo $this->lang->line("Warning") ?>', youhavenotselected+" "+upto+" "+leadsatatime, 'warning');
                 return;
               } 

               var group_id=$("#label_ids").val();
               var page_id=$("#assign_group").attr('button_id');
               var count=group_id.length;
               
               if(count==0) 
               {
                 swal('<?php echo $this->lang->line("Error") ?>', youhavenotselectedanyleadgroup, 'error');
                 return;
               } 

               $("#assign_group_submit").addClass("btn-progress");

               $.ajax({
                 type:'POST' ,
                 url: "<?php echo site_url(); ?>subscriber_manager/bulk_group_assign",
                 data:{ids:ids,group_id:group_id,page_id:page_id},
                 success:function(response)
                 {
                  $("#assign_group_submit").removeClass("btn-progress");
                  swal('<?php echo $this->lang->line("Label Assign") ?>', groupshavebeenassignedsuccessfully+" ("+selected+")", 'success')
                  .then((value) => {
                   $("#assign_group_modal").modal('hide');  
                   table1.draw();
                  });

                 }
               });         
           } 
         });        
    });

    $('.modal').on("hidden.bs.modal", function (e) { 
      if ($('.modal:visible').length) { 
          $('body').addClass('modal-open');
      }
  });

  });  

  $("document").ready(function(){  
    $('#import_lead_modal').on('hidden.bs.modal', function () { 
      $(".page_list_item.active").click();
    });
  });

  $("document").ready(function(){  
    $('#htm').on('hidden.bs.modal', function () { 
      $(".page_list_item.active").click();
    });
  });

  $("document").ready(function(){
    var session_value = "<?php echo $this->session->userdata('sync_subscribers_get_page_details_page_table_id'); ?>";
    if(session_value=='')  $(".list-group li:first").click();    
    else $("li[page_table_id='"+session_value+"']").click();
  });
   


</script>


<div class="modal fade" id="htm" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-mega">
        <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="fa fa-user-check"></i> <?php echo $this->lang->line("Subscriber List");?></h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">×</span>
              </button>
            </div>
            <div class="modal-body data-card">
                <div class="row">
                    <!-- <div class="col-12 waiting_response"></div> -->
                    <div class="col-12 col-md-9">
                      <?php echo 
                      '<div class="input-group mb-3" id="searchbox">
                       <input type="text" class="form-control" id="search_value" name="search_value" placeholder="'.$this->lang->line("Search...").'" style="max-width:50%;">
                        <div class="input-group-prepend">
                          '.$dropdown.'
                        </div>
                        <div class="input-group-append">
                          <button class="btn btn-primary" type="button" id="search_subscriber"><i class="fas fa-search"></i> '.$this->lang->line("Search").'</button>
                        </div>
                      </div>'; ?>                                          
                    </div>

                    <div class="col-12 col-md-3">
                        <div class="dropdown dropleft large">
                          <button class="btn btn-primary dropdown-toggle float-right btn-lg" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <?php echo $this->lang->line('Options'); ?>
                          </button>
                          <div class="dropdown-menu large">
                            <a class="dropdown-item" href="#" button_id=""  id="assign_group"><i class="fas fa-user-tag"></i> <?php echo $this->lang->line('Assign Label'); ?></a>
                            <a class="dropdown-item" href="#"  id="download_list"><i class="fas fa-cloud-download-alt"></i> <?php echo $this->lang->line('Download Full List'); ?></a>
                            <a class="dropdown-item" href="#" button_id=""  id="migrate_list"><i class="fas fa-file-export"></i> <?php echo $this->lang->line('Migrate Full List to Bot'); ?></a>
                          </div>
                        </div>                          
                    </div>

                    <div class="col-12">
                      <div class="table-responsive2">
                        <input type="hidden" id="put_page_id">
                        <table class="table table-bordered" id="mytable">
                          <thead>
                            <tr>
                              <th>#</th>      
                              <th style="vertical-align:middle;width:20px">
                                  <input class="regular-checkbox" id="datatableSelectAllRows" type="checkbox"/><label for="datatableSelectAllRows"></label>        
                              </th>
                              <th><?php echo $this->lang->line("Subscriber ID"); ?></th>      
                              <th><?php echo $this->lang->line("Subscriber Name"); ?></th>      
                              <th><?php echo $this->lang->line("Label/Tag"); ?></th>      
                              <th><?php echo $this->lang->line("Thread ID"); ?></th>      
                              <th><?php echo $this->lang->line("Actions"); ?></th>      
                              <th><?php echo $this->lang->line("Synced at"); ?></th>
                              <!-- <th><?php echo $this->lang->line("Status"); ?></th> -->
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

<div class="modal fade" id="import_lead_modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="fas fa-qrcode"></i> <?php echo $this->lang->line("Scan Page Inbox");?></h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">×</span>
              </button>
            </div>
            <div class="modal-body ">
                <div class="row">
                    <div class="col-12">
                      <div id="import_lead_body">
                        <div id="scan_load"></div><br>
                        <div class="row">
                          <div class="form-group col-12 col-lg-6">              
                              <label>
                                <?php echo $this->lang->line("Scan Latest Leads");?>
                                <a href="#" data-placement="right" data-toggle="popover" data-trigger="focus" title="" data-content="<?php echo $this->lang->line('Scanning process scans your page conversation and import them as subscriber. We strongly recommend to use cron based scanning feature for first time, if your page conversation is huge. After importing all subscribers, the cron feature will not import any future new subscribers, you have to scan for latest subscribers manually occasionally using the scan limit feature. Although you can enable the cron based scanning again manually but be informed that it will rescan the full page conversation. If you are scanning for first time and your inbox conversation is moderate, then you can scan all of them at once. To get future new subscribers scan occasionally same as stated earlier.');?>" data-original-title="<?php echo $this->lang->line('Scan Latest Leads');?>"><i class="fa fa-info-circle"></i> </a>
                              </label>
                              <?php 
                              $scan_drop=
                              array
                              (
                                ''=>$this->lang->line("Scan all subscribers"),
                                "500"=>"500 ".$this->lang->line("Subscribers"),
                                "1000"=>"1000 ".$this->lang->line("Subscribers"),
                                "2000"=>"2000 ".$this->lang->line("Subscribers"),
                                "3000"=>"3000 ".$this->lang->line("Subscribers"),
                                "5000"=>"5000 ".$this->lang->line("Subscribers"),
                                "10000"=>"10000 ".$this->lang->line("Subscribers"),
                                "20000"=>"20000 ".$this->lang->line("Subscribers"),
                                "30000"=>"30000 ".$this->lang->line("Subscribers"),
                                "50000"=>"50000 ".$this->lang->line("Subscribers"),
                                "100000"=>"100000 ".$this->lang->line("Subscribers")
                              );
                              echo form_dropdown('lead_limit',$scan_drop, '','class="form-control select2" id="scan_limit" style="width:100%;"'); ?>
                          </div>

                          <div class="form-group col-12 col-lg-6">              
                              <label>
                                <?php echo $this->lang->line("Folder");?>
                                <a href="#" data-placement="right" data-toggle="popover" data-trigger="focus" title="" data-content="<?php echo $this->lang->line('The target folder from which to retrieve conversations.');?>" data-original-title="<?php echo $this->lang->line('Folder');?>"><i class="fa fa-info-circle"></i> </a>
                              </label>
                              <?php 
                              $scan_drop=
                              array
                              (
                                "inbox"=>$this->lang->line("Inbox"),
                                "page_done"=>$this->lang->line("Done"),
                                "spam"=>$this->lang->line("Spam"),
                                "other"=>$this->lang->line("Other")
                              );
                              echo form_dropdown('folder',$scan_drop, '','class="form-control select2" id="folder" style="width:100%;"'); ?>
                          </div>
                        </div>
                      </div>                      
                    </div>
                </div>               
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default  btn-lg" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo $this->lang->line("Close"); ?></button>
              <button type="button" class="btn btn-primary btn-lg"  id="start_scanning"><i class="fas fa-check-circle"></i> <?php echo $this->lang->line("Start Scanning"); ?></button>
          </div>

        </div>
    </div>
</div>


<div class="modal fade" id="assign_group_modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="fas fa-user-tag"></i> <?php echo $this->lang->line("Assign Label");?></h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">×</span>
              </button>
            </div>

            <div class="modal-body">    
                <div id="get_labels">              
                </div>
            </div>
            <div class="modal-footer">
              <a class="btn btn-primary float-left" href="" id="assign_group_submit"><i class="fas fa-user-tag"></i> <?php echo $this->lang->line("Assign Label") ?></a>
              <a class="btn btn-outline-secondary float-right" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo $this->lang->line("Close") ?></a>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="subscriber_info_modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="fas fa-users"></i> <?php echo $this->lang->line("Subscribers");?></h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">×</span>
              </button>
            </div>

            <div class="modal-body">    
              <div class="section">                
                <h2 class="section-title"><?php echo $this->lang->line('Conversation Subscribers'); ?></h2>
                <p><?php echo $this->lang->line("Conversation Subscribers are, who have conversation in your page inbox. These users may come from Messenger Bot, Comment Private Reply, Click to Messenger Ads or Send Message CTA Post.  These users are eligible to get Conversation Broadcast message. Even if after getting private reply, users doesn't reply back will be counted for Conversation Broadcast."); ?></p>
              </div>
              <div class="section">                
                <h2 class="section-title"><?php echo $this->lang->line('BOT Subscribers'); ?></h2>
                <p><?php echo $this->lang->line("BOT Subscribers are those users who have given message & get reply from Messenger BOT after enabling in our system. However you can also migrate Conversation Subscribers (Existing Subscribers) to BOT subscribers. In this case BOT subscribers are those who have given message to your page. BOT subscribers may less than Conversation subscribers for different reason like"); ?></p>
                <ol>
                  <li><?php echo $this->lang->line("The user deactivated their account."); ?></li>
                  <li><?php echo $this->lang->line("The user blocked your page."); ?></li>
                  <li><?php echo $this->lang->line("The user don't have activity for long days with your page."); ?></li>
                  <li><?php echo $this->lang->line("The user may in conversation subscriber list as got private reply of comment but never reply may not eligible for BOT Subscriber."); ?></li>
                </ol>
              </div>
              <div class="section">                
                <h2 class="section-title"><?php echo $this->lang->line('24H Subscribers'); ?></h2>
                <p><?php echo $this->lang->line("Those users who interacted with your messenger bot within 24 hours. This subscribers are eligible to get promotional message through Subscriber Broadcast."); ?></p>
              </div>
              <div class="section">                
                <h2 class="section-title"><?php echo $this->lang->line('24H+1 Subscribers'); ?></h2>
                <p><?php echo $this->lang->line("Pages may also send one additional message after the 24-hour time limit has expired. . This is commonly referred to as the 24 + 1 policy. This is subscriber count that may eligible for getting promotional message by 24+1 policy. This count may less due to some extra security measured in calculation to avoid risk for sending promotional message."); ?></p>
              </div>
              <div class="section">                
                <h2 class="section-title"><?php echo $this->lang->line('Unavailable'); ?></h2>
                <p><?php echo $this->lang->line("You may find red color number as unavailable beside both Conversation Subscribers & BOT Subscribers means the number of users are unavailable for broadcast, because in last broadcasting campaign, Facebook responded with error during sending message to them. They will not be eligible for future broadcast campaign. However once that user send message to your page again, then user become available again."); ?></p>
              </div>
              <div class="section">                
                <h2 class="section-title"><?php echo $this->lang->line('Migrated Subscribers'); ?></h2>
                <p><?php echo $this->lang->line("Those subscribers are migrated as BOT subscribers from Conversation Subscribers. These are basically all old subscribers achieved before using our system for Messenger BOT."); ?></p>
              </div>
            </div>

            <div class="modal-footer">
              <a class="btn btn-outline-secondary float-right" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo $this->lang->line("Close") ?></a>
            </div>
        </div>
    </div>
</div>