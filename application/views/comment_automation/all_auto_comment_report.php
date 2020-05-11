<?php 
	$this->load->view("include/upload_js"); 
	if(ultraresponse_addon_module_exist())	$commnet_hide_delete_addon = 1;
	else $commnet_hide_delete_addon = 0;

	if(addon_exist(201,"comment_reply_enhancers")) $comment_tag_machine_addon = 1;
	else $comment_tag_machine_addon = 0;		
	$report_page_name=urldecode($this->uri->segment(3));

	$image_upload_limit = 1; 
	if($this->config->item('autoreply_image_upload_limit') != '')
	$image_upload_limit = $this->config->item('autoreply_image_upload_limit'); 

	$video_upload_limit = 3; 
	if($this->config->item('autoreply_video_upload_limit') != '')
	$video_upload_limit = $this->config->item('autoreply_video_upload_limit');
?>

<style>
	.dropdown-toggle::after{content:none !important;}
	.dropdown-toggle::before{content:none !important;}
	#page_id{width: 150px;}
	#campaign_name{max-width: 30%;}
	@media (max-width: 575.98px) {
	  #page_id{width: 90px !important;}
	  #campaign_name{max-width: 50% !important;}
	}
</style>

<section class="section section_custom">
  <div class="section-header">
    <h1><i class="fas fa-comments"></i> <?php echo $page_title; ?></h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item"><?php echo $this->lang->line("Comment Autoamtion"); ?></div>
      <div class="breadcrumb-item">
      	<a href="<?php echo base_url("comment_automation/comment_section_report"); ?>">
      		<?php echo $this->lang->line("Report"); ?>
      	</a>
      </div>
      <div class="breadcrumb-item"><?php echo $page_title; ?></div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-body data-card">
          	<div class="input-group mb-3" id="searchbox">
          	  	<div class="input-group-prepend">
          	      	<select class="select2 form-control" id="page_id">
          	        	<option value=""><?php echo $this->lang->line("Page Name"); ?></option>
		          	        <?php foreach ($page_info as $value): ?>
		          	        	<option value="<?php echo $value['id']; ?>" <?php if($value['id'] == $page_id) echo 'selected'; ?> ><?php echo $value['page_name']; ?></option>
		          	        <?php endforeach ?>
      	      		</select>
          	    </div>
          	    <input type="text" class="form-control" value="<?php if($post_id != 0) echo $post_id; ?>" id="campaign_name" autofocus placeholder="<?php echo $this->lang->line('Search...'); ?>" aria-label="" aria-describedby="basic-addon2">
          	  	<div class="input-group-append">
          	    	<button class="btn btn-primary" id="search_submit" type="button"><i class="fas fa-search"></i> <span class="d-none d-sm-inline"><?php echo $this->lang->line('Search'); ?></span></button>
      	 	 	</div>
          	</div>
            <div class="table-responsive2">
              <table class="table table-bordered" id="mytable">
                <thead>
                  <tr>
                    <th>#</th>      
                    <th><?php echo $this->lang->line("Page ID"); ?></th>
                    <th><?php echo $this->lang->line("Avatar")?></th>
                    <th><?php echo $this->lang->line("Name")?></th>
                    <th style="min-width: 70px;"><?php echo $this->lang->line("Page Name")?></th>
                    <th><?php echo $this->lang->line("Post ID")?></th>
                    <th><?php echo $this->lang->line("Actions")?></th>
                    <th><?php echo $this->lang->line("Reply Sent")?></th>
                    <th><?php echo $this->lang->line("status")?></th>
                    <th><?php echo $this->lang->line("Last Reply Time")?></th>
                    <th><?php echo $this->lang->line("Error Message")?></th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div>             
          </div>

        </div>
      </div>
    </div>
    
  </div>
</section> 

<?php 
	
	$Doyouwanttopausethiscampaign = $this->lang->line("do you want to pause this campaign?");
	$Doyouwanttostartthiscampaign = $this->lang->line("do you want to start this campaign?");
	$Doyouwanttodeletethisrecordfromdatabase = $this->lang->line("do you want to delete this record from database?");
	$Youdidntselectanyoption = $this->lang->line("you didn't select any option.");
	$Youdidntprovideallinformation = $this->lang->line("you didn't provide all information.");
	$Youdidntprovideallinformation = $this->lang->line("you didn't provide all information.");
	$Doyouwanttostarthiscampaign = $this->lang->line("do you want to start this campaign?");

	$edit = $this->lang->line("Edit");
	$report = $this->lang->line("Report");
	$deletet = $this->lang->line("Delete");
	$pausecampaign = $this->lang->line("Pause Campaign");
	$startcampaign = $this->lang->line("Start Campaign");

	$doyoureallywanttoReprocessthiscampaign = $this->lang->line("Force Reprocessing means you are going to process this campaign again from where it ended. You should do only if you think the campaign is hung for long time and didn't send message for long time. It may happen for any server timeout issue or server going down during last attempt or any other server issue. So only click OK if you think message is not sending. Are you sure to Reprocessing ?");
	$alreadyEnabled = $this->lang->line("this campaign is already enable for processing.");
	$TypeAutoCampaignname = $this->lang->line("You didn\'t Type auto campaign name");
	$YouDidnotchosescheduleType = $this->lang->line("You didn\'t choose any schedule type");
	$YouDidnotchosescheduletime = $this->lang->line("You didn\'t select any schedule time");
	$YouDidnotchosescheduletimezone = $this->lang->line("You didn\'t select any time zone");
	$YoudidnotSelectPerodicTime = $this->lang->line("You didn\'t select any periodic time");
	$YoudidnotSelectCampaignStartTime = $this->lang->line("You didn\'t choose campaign start time");
	$YoudidnotSelectCampaignEndTime = $this->lang->line("You didn\'t choose campaign end time");
	$Youdidntselectanytemplate = $this->lang->line("you didn\'t select any template.");
	$Youdidntselectanyoptionyet = $this->lang->line("you didn\'t select any option yet.");
	$Youdidntselectanyoption = $this->lang->line("you didn\'t select any option.");


?>

<script>
	$("document").ready(function(){

		
		$('[data-toggle="popover"]').popover(); 
		$('[data-toggle="popover"]').on('click', function(e) {e.preventDefault(); return true;});

		var image_upload_limit = "<?php echo $image_upload_limit; ?>";
		var video_upload_limit = "<?php echo $video_upload_limit; ?>";

		var base_url="<?php echo site_url(); ?>";

		// datatable section started
		var perscroll;
		var table = $("#mytable").DataTable({
		    serverSide: true,
		    processing:true,
		    bFilter: true,
		    order: [[ 1, "desc" ]],
		    pageLength: 10,
		    ajax: 
		    {
		        "url": base_url+'comment_automation/all_auto_comment_report_data',
		        "type": 'POST',
			    data: function ( d )
			    {
			        d.page_id = $('#page_id').val();
			        d.campaign_name = $('#campaign_name').val();
			    }
		    },
		    language: 
		    {
		      url: "<?php echo base_url('assets/modules/datatables/language/'.$this->language.'.json'); ?>"
		    },
		    dom: '<"top">rt<"bottom"lip><"clear">',
		    columnDefs: [
		        {
		          targets: [1],
		          visible: false
		        },
		        {
		          targets: [0,1,2,5,7,8,10],
		          sortable: false
		        },
		        {
		        	targets:[0,2,5,6,7,8,9],
		        	className:'text-center'
		        }
		    ],
		    fnInitComplete:function(){ // when initialization is completed then apply scroll plugin
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

		$(document).on('change', '#page_id', function(event) {
		  event.preventDefault(); 
		  table.draw();
		});

		var post_id = "<?php echo $post_id; ?>";
		if(post_id != 0) $("#search_submit").click();

		var page_id = "<?php echo $page_id; ?>";
		if(page_id != 0) $("#search_submit").click();


		$(document).on('click', '#search_submit', function(event) {
		  event.preventDefault(); 
		  table.draw();
		});
		// End of datatable section


		// report table started
		var table1 = '';
		var perscroll1;
		$(document).on('click','.view_report',function(e){
		  e.preventDefault();

			var table_id = $(this).attr('table_id');
			if(table_id !='') 
			{
				$("#put_row_id").val(table_id);
			}

			$("#view_report_modal").modal();

			if (table1 == '')
			{
				table1 = $("#mytable1").DataTable({
				    serverSide: true,
				    processing:true,
				    bFilter: false,
				    order: [[ 5, "desc" ]],
				    pageLength: 10,
				    ajax: {
				        url: base_url+'comment_automation/ajax_get_autocomment_reply_info',
				        type: 'POST',
				        data: function ( d )
				        {
				            d.table_id = $("#put_row_id").val();
				            d.searching = $("#searching").val();
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
				          targets: '',
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

		$(document).on('keyup', '#searching', function(event) {
		  event.preventDefault(); 
		  table1.draw();
		});

		$('#view_report_modal').on('hidden.bs.modal', function () {
			$("#download").attr("href","");
			$("#put_row_id").val('');
			$("#searching").val("");
			table1.draw();
			table.draw();
		});

		// $(document).on('click','.view_report',function(){
		// 	var loading = '<img src="'+base_url+'assets/pre-loader/Fading squares2.gif" class="center-block">';
		// 	$("#view_report_modal_body").html(loading);
		// 	$("#view_report").modal();
		// 	var table_id = $(this).attr('table_id');
		// 	$.ajax({
		//     	type:'POST' ,
		//     	url: base_url+"comment_automation/ajax_get_autocomment_reply_info",
		//     	data: {table_id:table_id},
		//     	// async: false,
		//     	success:function(response){
		//          	$("#view_report_modal_body").html(response);
		//     	}

		//     });

		// });

		$(document).on('click','#edit_modal_close',function(){        
			var manual_post_id = $("#manual_edit_post_id").val();
			if(manual_post_id != '')
			{
				$("#edit_auto_reply_message_modal").modal("hide");
				$("#manual_edit_reply_by_post").modal("hide");
				$("#manual_edit_post_id").val('');
				table.draw();
			}
			else
			{
				$("#edit_auto_reply_message_modal").removeClass("modal");
				table.draw();
			}
		});



		// var base_url="<?php echo site_url(); ?>";


		var edit = "<?php echo $edit; ?>";
		var report = "<?php echo $report; ?>";
		var deletet = "<?php echo $deletet; ?>";
		var pausecampaign = "<?php echo $pausecampaign; ?>";
		var startcampaign = "<?php echo $startcampaign; ?>";
		

		var Doyouwanttopausethiscampaign = "<?php echo $Doyouwanttopausethiscampaign; ?>";

		$(document).on('click','.pause_campaign_info',function(e){
			e.preventDefault();
			swal({
				title: '',
				text: Doyouwanttopausethiscampaign,
				icon: 'warning',
				buttons: true,
				dangerMode: true,
			})
			.then((willDelete) => {
				if (willDelete) 
				{
					var table_id = $(this).attr('table_id');

					$.ajax({
						context: this,
						type:'POST' ,
						url:"<?php echo base_url('comment_automation/ajax_autocomment_pause')?>",
						data: {table_id:table_id},
						success:function(response){ 
				         	iziToast.success({title: '',message: '<?php echo $this->lang->line("Campaign has been paused successfully."); ?>',position: 'bottomRight'});
							table.draw();
						}
					});
				} 
			});

		});

		$(document).on('click','.renew_campaign',function(){		
			var table_id = $(this).attr('table_id');
			$.ajax({
				type:'POST' ,
				url: base_url+"comment_automation/ajax_autocomment_renew_campaign",
				data: {table_id:table_id},
				success:function(response){
					table.draw();
				}
			});		
		});

		var Doyouwanttostarthiscampaign = "<?php echo $Doyouwanttostarthiscampaign; ?>";
		
		$(document).on('click','.play_campaign_info',function(e){
			e.preventDefault();
			swal({
				title: '',
				text: Doyouwanttostarthiscampaign,
				icon: 'warning',
				buttons: true,
				dangerMode: true,
			})
			.then((willDelete) => {
				if (willDelete) 
				{
					var table_id = $(this).attr('table_id');

					$.ajax({
						context: this,
						type:'POST' ,
						url:"<?php echo base_url('comment_automation/ajax_autocomment_play')?>",
						data: {table_id:table_id},
						success:function(response){ 
				         	iziToast.success({title: '',message: '<?php echo $this->lang->line("Campaign has been started successfully."); ?>',position: 'bottomRight'});
							table.draw();
						}
					});
				} 
			});

		});



		$(document).on('click','.force',function(e){
			e.preventDefault();
			var doyoureallywanttoReprocessthiscampaign = "<?php echo $doyoureallywanttoReprocessthiscampaign; ?>";
			swal({
				title: '<?php echo $this->lang->line("Are you sure?"); ?>',
				text: doyoureallywanttoReprocessthiscampaign,
				icon: 'warning',
				buttons: true,
				dangerMode: true,
			})
			.then((willDelete) => {
				if (willDelete) 
				{
					var id = $(this).attr('id');
					var alreadyEnabled = "<?php echo $alreadyEnabled; ?>";

					$.ajax({
						context: this,
						type:'POST' ,
						url:"<?php echo base_url('comment_automation/autocomment_force_reprocess_campaign')?>",
						// dataType: 'json',
						data: {id:id},
						success:function(response){ 
							if(response=='1')
							{
								iziToast.success({title: '',message: "<?php echo $this->lang->line('Force processing has been enabled successfully.'); ?>",position: 'bottomRight'});
								table.draw();
							}
							else 
							iziToast.error({title: '',message: alreadyEnabled,position: 'bottomRight'});
						}
					});
				} 
			});

		});


		var Doyouwanttodeletethisrecordfromdatabase = "<?php echo $Doyouwanttodeletethisrecordfromdatabase; ?>";

		$(document).on('click','.delete_report',function(e){
			e.preventDefault();
			swal({
				title: '<?php echo $this->lang->line("Are you sure?"); ?>',
				text: Doyouwanttodeletethisrecordfromdatabase,
				icon: 'warning',
				buttons: true,
				dangerMode: true,
			})
			.then((willDelete) => {
				if (willDelete) 
				{
					var table_id = $(this).attr('table_id');

					$.ajax({
						context: this,
						type:'POST' ,
						url:"<?php echo base_url('comment_automation/ajax_autocomment_delete')?>",
						data: {table_id:table_id},
						success:function(response){ 
				         	iziToast.success({title: '',message: '<?php echo $this->lang->line("Campaign has been deleted successfully."); ?>',position: 'bottomRight'});
							table.draw();
						}
					});
				} 
			});

		});


		$(document).on('click','.edit_reply_info',function(e){
			e.preventDefault();
		
			$(".previewLoader").show();
			$("#manual_edit_reply_by_post").removeClass('modal');
			$("#edit_auto_reply_message_modal").addClass("modal");
			$("#edit_response_status").html("");
			var table_id = $(this).attr('table_id');
			$.ajax({
			  type:'POST' ,
			  url:"<?php echo site_url();?>comment_automation/ajax_edit_autocomment_info",
			  data:{table_id:table_id},
			  dataType:'JSON',
			  success:function(response){

			    $("#edit_auto_reply_page_id_template").val(response.edit_auto_reply_page_id);
			    $("#edit_auto_reply_post_id_template").val(response.edit_auto_reply_post_id);
			  	$("#edit_campaign_name_template").val(response.edit_campaign_name);

                if(response.edit_schedule_type == 'onetime')
                {
                    
                	$("#edit_schedule_type_o").attr('checked',true);
                	$(".schedule_block_item_o").show();
                	$(".schedule_block_item_new_p").hide();
                	
                	$("#edit_schedule_time_o").val(response.edit_schedule_time_o);
                	$("#edit_time_zone_o").val(response.edit_time_zone_o).change();

                }
                if(response.edit_schedule_type == 'periodic')
                {
                    
                	$("#edit_schedule_type_p").attr('checked',true);
                	$(".schedule_block_item_new_p").show();
                	$(".schedule_block_item_o").hide();
                	$("#edit_periodic_time").val(response.edit_periodic_time).change();
                	$("#edit_campaign_start_time").val(response.edit_campaign_start_time);
                	$("#edit_campaign_end_time").val(response.edit_campaign_end_time);
                	$("#edit_comment_start_time").val(response.edit_comment_start_time);
                	$("#edit_comment_end_time").val(response.edit_comment_end_time);
                	$("#edit_periodic_time_zone").val(response.edit_periodic_time_zone).change();
                	if(response.edit_auto_comment_type=='random')
                	{
                		$("#edit_random").attr('checked',true);

                	}
                	if(response.edit_auto_comment_type =='serially')
                	{
                		$("#edit_serially").attr('checked',true);
                	}

                }
         
  	
              $("#edit_auto_comment_template_id").val(response.edit_auto_comment_template_id).change();
              $("#edit_auto_reply_message_modal").modal();
			  }
			});
				
			setTimeout(function(){			
				$(".previewLoader").hide();				
			},1000);
			
			
		});

		$(document).on('click','#edit_add_more_button',function(){
			if(edit_content_counter == 11)
				$("#edit_add_more_button").hide();
			$("#edit_content_counter").val(edit_content_counter);

			$("#edit_filter_div_"+edit_content_counter).show();
			
			/** Load Emoji For Filter Word when click on add more button during Edit**/
				
			$("#edit_filter_message_"+edit_content_counter).emojioneArea({
	    		autocomplete: false,
				pickerPosition: "bottom"
	 	 	});
			
			$("#edit_comment_reply_msg_"+edit_content_counter).emojioneArea({
	    		autocomplete: false,
				pickerPosition: "bottom"
	 	 	});
			
			edit_content_counter++;

		});



		$(document).on('click','#edit_save_button',function(){
			var post_id = $("#edit_auto_reply_post_id_template").val();
			var edit_campaign_name = $("#edit_campaign_name_template").val();
			var edit_schedule_type = $("input[name=edit_schedule_type]:checked").val();
			var edit_schedule_time_o = $("#edit_schedule_time_o").val();
			var edit_time_zone_o = $("#edit_time_zone_o").val();
			var edit_periodic_time = $("#edit_periodic_time").val();
			var edit_campaign_start_time = $("#edit_campaign_start_time").val();
			var edit_campaign_end_time = $("#edit_campaign_end_time").val();
			var Youdidntselectanyoption = "<?php echo $Youdidntselectanyoption; ?>";
			var Youdidntprovideallinformation = "<?php echo $Youdidntprovideallinformation; ?>";
			var YouDidnotchosescheduletime = "<?php echo $YouDidnotchosescheduletime; ?>";
			var YouDidnotchosescheduletimezone = "<?php echo $YouDidnotchosescheduletimezone; ?>";
			var YoudidnotSelectPerodicTime = "<?php echo $YoudidnotSelectPerodicTime; ?>";
			var YoudidnotSelectCampaignStartTime = "<?php echo $YoudidnotSelectCampaignStartTime; ?>";
			var YoudidnotSelectCampaignEndTime = "<?php echo $YoudidnotSelectCampaignEndTime; ?>";

			if (typeof(edit_schedule_type)==='undefined')
			{
				swal('<?php echo $this->lang->line("Warning"); ?>', Youdidntselectanyoption, 'warning');
				return false;
			}

			if(edit_campaign_name == ''){
				swal('<?php echo $this->lang->line("Warning"); ?>', Youdidntprovideallinformation, 'warning');
				return false;
			}

			if($("#edit_auto_comment_template_id").val()== 0){
				swal('<?php echo $this->lang->line("Warning"); ?>', '<?php echo $this->lang->line("you have not select any template.");?>', 'warning');
				return false;
			}	

			if(edit_schedule_type == "onetime")
			{
				if(edit_schedule_time_o == ''){
					swal('<?php echo $this->lang->line("Warning"); ?>', YouDidnotchosescheduletime, 'warning');
					return false;
				}				
				if(edit_time_zone_o == ''){
					swal('<?php echo $this->lang->line("Warning"); ?>', YouDidnotchosescheduletimezone, 'warning');
					return false;
				}
			}
			if(edit_schedule_type == "periodic")
			{
				if(edit_periodic_time == ''){
					swal('<?php echo $this->lang->line("Warning"); ?>', YoudidnotSelectPerodicTime, 'warning');
					return false;
				}	
				if($("#edit_periodic_time_zone").val() == ''){
					swal('<?php echo $this->lang->line("Warning"); ?>', YouDidnotchosescheduletimezone, 'warning');
					return false;
				}				
				if(edit_campaign_start_time == ''){
					swal('<?php echo $this->lang->line("Warning"); ?>', YoudidnotSelectCampaignStartTime, 'warning');
					return false;
				}			
				if(edit_campaign_end_time == ''){
					swal('<?php echo $this->lang->line("Warning"); ?>', YoudidnotSelectCampaignEndTime, 'warning');
					return false;
				}

				var edit_comment_start_time=$("#edit_comment_start_time").val();
				var edit_comment_end_time=$("#edit_comment_end_time").val();
				var rep1 = parseFloat(edit_comment_start_time.replace(":", "."));
				var rep2 = parseFloat(edit_comment_end_time.replace(":", "."));

				if( edit_comment_start_time== '' ||  edit_comment_end_time== ''){
					swal('<?php echo $this->lang->line("Warning"); ?>', "<?php echo $this->lang->line('Please select comment between times.');?>", 'warning');
					return false;
				}

				if(rep1 >= rep2)
				{
					swal('<?php echo $this->lang->line("Warning"); ?>', "<?php echo $this->lang->line('Comment between start time must be less than end time.');?>", 'warning');
					return false;
				}

			}		

			$(this).addClass('btn-progress');

			var queryString = new FormData($("#edit_auto_reply_info_form")[0]);
		    $.ajax({
		    	type:'POST' ,
		    	url: base_url+"comment_automation/ajax_update_autocomment_submit",
		    	data: queryString,
		    	dataType : 'JSON',
		    	// async: false,
		    	cache: false,
		    	contentType: false,
		    	processData: false,
		    	context: this,
		    	success:function(response){
		    		$(this).removeClass('btn-progress');
		         	if(response.status=="1")
			        {
			         	swal('<?php echo $this->lang->line("Success"); ?>', response.message, 'success').then((value) => {
		         			  $("#edit_auto_reply_message_modal").modal('hide');
							  table.draw();
							});
			        }
			        else
			        {
			         	swal('<?php echo $this->lang->line("Error"); ?>', response.message, 'error');
			        }
		    	}

		    });

		});

		$(document).on('click','.cancel_button',function(){
			$("#edit_auto_reply_message_modal").modal('hide');
		    table.draw();
		});

	});
</script>

<div class="modal fade" id="view_report_modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-mega">
        <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="fas fa-comments"></i> <?php echo $this->lang->line("Auto Comment Report");?></h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">×</span>
              </button>
            </div>
            <div class="modal-body data-card">
                <div class="row">
          			<div class="col-12 col-md-9">
      			  		<input type="text" id="searching" name="searching" class="form-control" placeholder="<?php echo $this->lang->line("Search..."); ?>" style='width:200px;'>                                          
          			</div>
                    <div class="col-12">
                      <div class="table-responsive2">
                        <input type="hidden" id="put_row_id">
                        <table class="table table-bordered" id="mytable1">
                          	<thead>
	                            <tr>
	                              <th>#</th>
	                              <th><?php echo $this->lang->line("Comment ID"); ?></th> 
	                              <th><?php echo $this->lang->line("Comment"); ?></th> 
	                              <th><?php echo $this->lang->line("comment time"); ?></th>      
	                              <th><?php echo $this->lang->line("Schedule Type"); ?></th>
	                              <th><?php echo $this->lang->line("Comment Stuts"); ?></th>
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


<div class="modal fade" id="edit_auto_reply_message_modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" style="min-width: 70%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-center" style="padding: 10px 20px 10px 20px;"><?php echo $this->lang->line("Please give the following information for post auto comment") ?></h5>
                <button type="button" id='edit_modal_close' class="close">&times;</button>
            </div>
            <form action="#" id="edit_auto_reply_info_form" method="post">
	            <input type="hidden" name="edit_auto_reply_page_id_template" id="edit_auto_reply_page_id_template" value="">
	            <input type="hidden" name="edit_auto_reply_post_id_template" id="edit_auto_reply_post_id_template" value="">
	            <div class="modal-body" id="edit_auto_reply_message_modal_body">   
				
				<div class="text-center waiting previewLoader"><i class="fas fa-spinner fa-spin blue text-center" style="font-size: 40px;"></i></div>

	        	<div class="row" style="padding: 10px 20px 10px 20px;">

	        		<div class="col-12" style="margin-top: 15px;">
	        			<div class="form-group">
	        				<label>
	        					<i class="fas fa-monument"></i> <?php echo $this->lang->line('Auto comment campaign name'); ?> <span class="red">*</span> 
	        				</label>
	        				<br>
	        				<input class="form-control"type="text" name="edit_campaign_name_template" id="edit_campaign_name_template" placeholder="Write your auto reply campaign name here">
	        			</div>
	        		</div>

					<div class="col-12">
	                    <div class="form-group col-12 col-md-12" style="padding: 0;">
							<label>
								<i class="fa fa-th-large"></i> <?php echo $this->lang->line('Auto Comment Template'); ?> <span class="red">*</span> 
							</label>
							<br>
							<select  class="form-control select2" style="width:100%;" id="edit_auto_comment_template_id" name="edit_auto_comment_template_id">
							<?php
								echo "<option value='0'>{$this->lang->line('Please select a template')}</option>";
								foreach($auto_comment_template as $key=>$val)
								{
									$id=$val['id'];
									$group_name=$val['template_name'];
									echo "<option value='{$id}'>{$group_name}</option>";
								}
							 ?>
							</select>
					    </div>
					</div>
					<br>

	               <br>
					<div class="col-12">
						<div class="form-group">
							<label>
								<i class="fas fa-clock"></i> <?php echo $this->lang->line('Schedule Type'); ?> <span class="red">*</span> 
								<a href="#" data-placement="bottom" data-toggle="popover" data-trigger="focus" title="" data-content="<?php echo $this->lang->line('Onetime campaign will comment only the first comment of the selected template and periodic campaign will auto comment multiple time periodically as per your settings.'); ?>" data-original-title="<?php echo $this->lang->line('Schedule Type'); ?>"><i class="fa fa-info-circle"></i> </a>
							</label>
							<br>
							<label class="custom-switch">
							  <input type="radio" name="edit_schedule_type" value="onetime" id="edit_schedule_type_o" class="custom-switch-input">
							  <span class="custom-switch-indicator"></span>
							  <span class="custom-switch-description"><?php echo $this->lang->line('One Time'); ?></span>
							</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<label class="custom-switch">
							  <input type="radio" name="edit_schedule_type" value="periodic" id="edit_schedule_type_p" class="custom-switch-input">
							  <span class="custom-switch-indicator"></span>
							  <span class="custom-switch-description"><?php echo $this->lang->line('Periodic'); ?>
							</label>
						</div>
						
						<div class="row">							
							<div class="form-group schedule_block_item_o col-12 col-md-6">
								<label><?php echo $this->lang->line('Schedule time'); ?></label>
								<input placeholder="<?php echo $this->lang->line('Time'); ?>"  name="edit_schedule_time_o" id="edit_schedule_time_o" class="form-control datepicker_x" type="text"/>
							</div>

							<div class="form-group schedule_block_item_o col-12 col-md-6">
								<label><?php echo $this->lang->line('Time zone'); ?></label>
								<?php
								$time_zone[''] =$this->lang->line('Please Select');
								echo form_dropdown('edit_time_zone_o',$time_zone,set_value('time_zone'),' class="form-control select2" style="width:100%;" id="edit_time_zone_o" required');
								?>
							</div>
						</div>

						<div class='schedule_block_item_new_p' style="padding:30px 30px 20px 30px !important; border:1px solid #f9f9f9 !important; background: #FAFDFB !important;">
							<div class="clearfix"></div>
							<div class="row">
								<div class="form-group schedule_block_item_new_p col-12 col-md-6">
									<label><?php echo $this->lang->line('Periodic Schedule time'); ?>
										<a href="#" data-placement="bottom" data-toggle="popover" data-trigger="focus" title="" data-content="<?php echo $this->lang->line('Choose how frequently you want to comment'); ?>" data-original-title="<?php echo $this->lang->line('Periodic Schedule time'); ?>"><i class="fa fa-info-circle"></i> </a>
									</label>
									<?php
									$periodic_time[''] =$this->lang->line('Please Select Periodic Time Schedule');
									echo form_dropdown('edit_periodic_time',$periodic_time,set_value('edit_periodic_time'),' class="form-control select2" style="width:100%;" id="edit_periodic_time" required');
									?>
								</div>

								<div class="form-group schedule_block_item_new_p col-12 col-md-6">
									<label><?php echo $this->lang->line('Time zone'); ?></label>
									<?php
									$time_zone[''] =$this->lang->line('Please Select');
									echo form_dropdown('edit_periodic_time_zone',$time_zone,set_value('edit_periodic_time_zone'),' class="form-control select2" style="width:100%;" id="edit_periodic_time_zone" required');
									?>
								</div>
							</div>
						
							<div class="row">
								<div class="form-group schedule_block_item_new_p col-12 col-md-6">
									<label><?php echo $this->lang->line('Campaign Start time'); ?></label>
									<input placeholder="<?php echo $this->lang->line('Time'); ?>"  name="edit_campaign_start_time" id="edit_campaign_start_time" class="form-control datepicker_x" type="text"/>
								</div>						
								<div class="form-group schedule_block_item_new_p col-12 col-md-6">
									<label><?php echo $this->lang->line('Campaign End time'); ?></label>
									<input placeholder="<?php echo $this->lang->line('Time'); ?>"  name="edit_campaign_end_time" id="edit_campaign_end_time" class="form-control datepicker_x" type="text"/>
								</div>
							</div>
							
							<div class="row">
								<div class="form-group schedule_block_item_new_p col-12 col-md-6">
									<label>
										<?php echo $this->lang->line('Comment Between Time'); ?>
										<a href="#" data-placement="bottom" data-toggle="popover" data-trigger="focus" title="" data-content="<?php echo $this->lang->line("Set the allowed time of the comment. As example you want to auto comment by page from 10 AM to 8 PM. You don't want to comment other time. So set it 10:00 & 20:00"); ?>" data-original-title="<?php echo $this->lang->line('Comment Between Time'); ?>"><i class="fa fa-info-circle"></i> 
										</a>												
									</label> 
									<input placeholder="<?php echo $this->lang->line('Time'); ?>"  name="edit_comment_start_time" id="edit_comment_start_time" class="form-control datetimepicker2" type="text"/>
								</div>
								<div class="form-group schedule_block_item_new_p col-12 col-md-6">
									<label style="position: relative;right: 22px;top: 32px;"><?php echo $this->lang->line('to'); ?></label> 
									<input placeholder="<?php echo $this->lang->line('Time'); ?>"  name="edit_comment_end_time" id="edit_comment_end_time" class="form-control datetimepicker2" type="text"/>
								</div>
							</div>

							<div class="form-group schedule_block_item_new_p col-12 col-md-12">

								<label>
									<i class="fas fa-comment"></i> <?php echo $this->lang->line('Auto Comment Type'); ?> <span class="red">*</span> 
									<a href="#" data-placement="bottom" data-toggle="popover" data-trigger="focus" title="" data-content="<?php echo $this->lang->line('Random type will pick a comment from template randomly each time and serial type will pick the comment serially from selected template first to last.'); ?>" data-original-title="<?php echo $this->lang->line('Auto Comment Type'); ?>"><i class="fa fa-info-circle"></i> </a>
								</label>
								<br>
								<label class="custom-switch">
								  <input type="radio" name="edit_auto_comment_type" value="random" id="edit_random" class="custom-switch-input">
								  <span class="custom-switch-indicator"></span>
								  <span class="custom-switch-description"><?php echo $this->lang->line('Random'); ?></span>
								</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
								<label class="custom-switch">
								  <input type="radio" name="edit_auto_comment_type" value="serially" id="edit_serially" class="custom-switch-input">
								  <span class="custom-switch-indicator"></span>
								  <span class="custom-switch-description"><?php echo $this->lang->line('Serially'); ?>
								</label>
							</div>
							<div class="clearfix"></div>
						</div>	
					</div>
				<br/>
				</div>  
				<div class="row" style="padding: 10px 20px 10px 20px;">
						<!-- added by mostofa on 26-04-2017 -->
					<div class="smallspace clearfix"></div>
				</div>

				<div class="col-12 text-center" id="edit_response_status"></div>
	            </div>
            </form>
            <div class="clearfix"></div>

            <div class="modal-footer" style="padding-left: 45px; padding-right: 45px; ">
              <div class="row">
                <div class="col-6">
                  <button class="btn btn-lg btn-primary float-left" id="edit_save_button"><i class='fa fa-save'></i> <?php echo $this->lang->line("save") ?></button>
                </div>  
                <div class="col-6">
                  <button class="btn btn-lg btn-secondary float-right cancel_button"><i class='fas fa-times'></i> <?php echo $this->lang->line("cancel") ?></button>
                </div>
              </div>
            </div>

        </div>
    </div>
</div>



<script>

$(document).on('change','input[name=schedule_type]',function(){
	if($("input[name=schedule_type]:checked").val()=="onetime")
	{
		$(".schedule_block_item").show();
		$(".schedule_block_item_new").hide();
		$("#periodic_time").val("");
		$("#campaign_start_time").val("");
		$("#campaign_end_time").val("");
	}
	else
	{
		$("#schedule_time").val("");
		$("#time_zone").val("");
		$(".schedule_block_item_new").show();
		$(".schedule_block_item").hide();
	}
});

$(document).on('change','input[name=schedule_type]',function(){
	if($("input[name=schedule_type]:checked").val()=="onetime")
	{
		$(".schedule_block_item").show();
		$(".schedule_block_item_new").hide();
		$("#periodic_time").val("");
		$("#campaign_start_time").val("");
		$("#campaign_end_time").val("");
	}
	else
	{
		$("#schedule_time").val("");
		$("#time_zone").val("");
		$(".schedule_block_item_new").show();
		$(".schedule_block_item").hide();
	}
});


$(document).on('change','input[name=edit_schedule_type]',function(){
	if($("input[name=edit_schedule_type]:checked").val()=="onetime")
	{
		$(".schedule_block_item_o").show();
		$(".schedule_block_item_new_p").hide();
		$("#periodic_time_p").val("");
		$("#campaign_start_time_p").val("");
		$("#campaign_end_time_p").val("");
	}
	else
	{
		$("#schedule_time_o").val("");
		$("#time_zone_o").val("");
		$(".schedule_block_item_new_p").show();
		$(".schedule_block_item_o").hide();
	}
});


$(document).ready(function(){
     $(".schedule_block_item").hide();
     $(".schedule_block_item_new").hide();

    var today = new Date();
    var next_date = new Date(today.getFullYear(), today.getMonth() + 1, today.getDate());
    $('.datepicker_x').datetimepicker({
    	theme:'light',
    	format:'Y-m-d H:i:s',
    	formatDate:'Y-m-d H:i:s',
    	minDate: today,
    	maxDate: next_date

    })

    // $('.datepicker').datetimepicker();
    $('.datetimepicker2').datetimepicker({
      datepicker:false,
      format:'H:i'
    });


});
</script>


<style type="text/css">
	.smallspace{padding: 10px 0;}
	.lead_first_name,.lead_last_name,.lead_tag_name{background: #fff !important;}
	.ajax-file-upload-statusbar{width: 100% !important;}
	.ajax-upload-dragdrop{width:100% !important;}
	.renew_campaign
	{
		cursor: pointer;
	}
</style>