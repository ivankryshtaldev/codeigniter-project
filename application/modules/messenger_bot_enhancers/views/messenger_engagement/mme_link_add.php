<div id="put_script"></div>
<section class="section section_custom">
	<div class="section-header">
		<h1><i class="fas fa-link"></i> <?php echo $page_title; ?></h1>
		<div class="section-header-breadcrumb">
			<div class="breadcrumb-item"><a href="<?php echo base_url('messenger_bot'); ?>"><?php echo $this->lang->line("Messenger Bot"); ?></a></div>
			<div class="breadcrumb-item"><a href="<?php echo base_url('messenger_bot_enhancers/mme_link_list'); ?>"><?php echo $this->lang->line("M.me link"); ?></a></div>
			<div class="breadcrumb-item"><?php echo $page_title; ?></div>
		</div>
	</div>

	<div class="section-body">
		<div class="row">
			<div class="col-12">
				<div class="card main_card">
			
					<div class="card-body">
					 	<form action="#" enctype="multipart/form-data" id="plugin_form">
						<div class="row">
						  <div class="form-group col-12 col-md-6" style="padding: 10px;">
						    <label>
						       <?php echo $this->lang->line("Select Page"); ?> *
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("select page") ?>" data-content='<?php echo $this->lang->line("Select your Facebook page for which you want to generate the plugin.") ?>'><i class='fas fa-info-circle'></i> </a>
						    </label>
						    <?php $page_info['']= $this->lang->line("Select Page"); ?>
						    <?php echo form_dropdown('page', $page_info,'', 'class="form-control select2" id="page" style="width:100%;"' ); ?>                   
						  </div>  
						  <div class="form-group col-12 col-md-6" style='padding:10px;'>
						      <label>
						          <?php echo $this->lang->line("button text"); ?> *
						           <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("button text") ?>" data-content='<?php echo $this->lang->line("System will create a new button that will take visitors to m.me link") ?>'><i class='fa fa-info-circle'></i> </a>
						      </label>
						      <input type="text" name="new_button_display" id="new_button_display" class="form-control" value="Send us Message">                                          
						  </div>


						</div>

						<div class="row">
							


							<div class="form-group col-12 col-md-3" style="padding: 10px;">
							  <label>
							    <?php echo $this->lang->line("Button background"); ?> *
							 	<a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("button background") ?>" data-content='<?php echo $this->lang->line("new button background color") ?>'><i class='fa fa-info-circle'></i> </a>
							  </label>
							  <div class="input-group colorpicker-component color-picker-rgb" >
							    <input type="text" class="form-control" name="new_button_bg_color" id="new_button_bg_color" value="#0084FF">
							    <div class="input-group-append">
							      <div class="input-group-text">
							      	<span class="input-group-addon"><i></i></span>
							      </div>
							    </div>
							  </div>                 
							</div>
							<div class="form-group col-12 col-md-3" style="padding: 10px;">
							  <label class="margin-bottom-label">
							    <?php echo $this->lang->line("Button text color"); ?> *
								<a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("button background") ?>" data-content='<?php echo $this->lang->line("new button text color") ?>'><i class='fa fa-info-circle'></i> </a>
							  </label>   
							  <div class="input-group colorpicker-component color-picker-rgb" >
							    <input type="text" class="form-control" name="new_button_color" id="new_button_color" value="#FFFFFF">
							    <div class="input-group-append">
							      <div class="input-group-text">
							      	<span class="input-group-addon"><i></i></span>
							      </div>
							    </div>
							  </div>               
							</div>
							<div class="form-group col-12 col-md-3" style="padding: 10px;">
							  <label class="margin-bottom-label">
							    <?php echo $this->lang->line("Button hover background"); ?> *
								<a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("button background") ?>" data-content='<?php echo $this->lang->line("New button background color on mouse over") ?>'><i class='fa fa-info-circle'></i> </a>
							  </label>
							  <div class="input-group colorpicker-component color-picker-rgb" >
							    <input type="text" class="form-control" name="new_button_bg_color_hover" id="new_button_bg_color_hover" value="#367FA9">
							    <div class="input-group-append">
							      <div class="input-group-text">
							      	<span class="input-group-addon"><i></i></span>
							      </div>
							    </div>
							  </div>                
							</div>
							<div class="form-group col-12 col-md-3" style="padding: 10px;">
							  <label class="margin-bottom-label">
							    <?php echo $this->lang->line("Button text hover color"); ?> *
								<a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("button background") ?>" data-content='<?php echo $this->lang->line("New button text color on mouse over") ?>'><i class='fa fa-info-circle'></i> </a>
							  </label>
							  <div class="input-group colorpicker-component color-picker-rgb" >
							    <input type="text" class="form-control" name="new_button_color_hover" id="new_button_color_hover" value="#FFFDDD">
							    <div class="input-group-append">
							      <div class="input-group-text">
							      	<span class="input-group-addon"><i></i></span>
							      </div>
							    </div>
							  </div>                 
							</div>            
						</div> 
						<div class="row">
							<div class="form-group col-12 col-md-6" style='padding:10px;'>
							    <label>
							        <?php echo $this->lang->line("Button Size"); ?> *
							         <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("plugin size") ?>" data-content='<?php echo $this->lang->line("choose how big you want the button to be.") ?>'><i class='fa fa-info-circle'></i> </a>
							    </label>
							    <div class="selectgroup selectgroup-pills">

							    <?php 
							    $i=0;
							    foreach ($btn_sizes as $key => $value) 
							    {
							        $i++;
							        $checked=$selected='';
							        if($value=='medium') 
							        {
							            $selected='default-label';
							            $checked='checked';
							        }
							        $val_print=$value;
							        if($val_print=="xlarge") $val_print="Extra Large";

							        echo '<label class="selectgroup-item"><input class="selectgroup-input" type="radio" name="btn_size" value="'.$value.'" id="btn_size'.$i.'" '.$checked.'> 
							        		<span class="selectgroup-button">'.$this->lang->line($val_print).'</span> 
							        	</label>';
							    } 
							    ?> 
							    </div>
							</div>  

						</div> 

						<div class="row">
						  <div class="form-group col-12 <?php if(!$this->is_broadcaster_exist) echo 'col-md-6'; else echo 'col-md-5';?>" style='padding:10px;'>
						    <label>
						      <?php echo $this->lang->line("OPT-IN inbox confirmation message template"); ?> *

						        <a href="#" data-html="true" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("OPT-IN inbox confirmation message template") ?>" data-content='<?php echo $this->lang->line("This content will be sent to messenger inbox on OPT-IN.") ?> <?php echo $this->lang->line("You must select page to fill this list with data."); ?> <?php echo $this->lang->line("You can create template from ").' <a href="'.base_url("messenger_bot/create_new_template").'">'.$this->lang->line("here.")?></a>'><i class='fa fa-info-circle'></i> </a>
						    </label>
						    <?php echo form_dropdown('template_id',array(), '','class="form-control select2" id="template_id" style="width:100%;"'); ?>
						  </div>
						  <div class="form-group col-12 <?php if(!$this->is_broadcaster_exist) echo 'col-md-6'; else echo 'col-md-3';?>" style='padding:10px;'>
						    <label>
						      <?php echo $this->lang->line("reference"); ?> *
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("reference") ?>" data-content='<?php echo $this->lang->line("put a unique reference to track this plugin later.") ?>'><i class='fa fa-info-circle'></i> </a>
						    </label>
						    <input type="text" name="reference" id="reference" class="form-control" value="">                 
						  </div>
						  <div class="form-group col-12 col-md-4 <?php if(!$this->is_broadcaster_exist) echo 'hidden';?>" style='padding:10px;'>
						    <label>
						      <?php echo $this->lang->line("select label"); ?>
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("select label") ?>" data-content='<?php echo $this->lang->line("subscriber obtained from this plugin will be enrolled in these labels.") ?> <?php echo $this->lang->line("You must select page to fill this list with data."); ?>'><i class='fa fa-info-circle'></i> </a>
						    </label>
						    <?php echo form_dropdown('label_ids[]',array(), '','style="height:45px;overflow:hidden;width:100%;" multiple="multiple" class="form-control select2" id="label_ids"'); ?>
						  </div>              
						</div>

						<button class="btn btn-lg btn-primary" id="get_button" name="get_button" type="button"><i class="fa fa-code"></i> <?php echo $this->lang->line("Generate Embed code");?></button>

						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>




<script>
	var base_url="<?php echo site_url(); ?>";
	$("document").ready(function()	{
	
		$('.color-picker-rgb').colorpicker({
		                 format: 'hex'
		             });




		$(document).on('change','#page',function(event){
			event.preventDefault();
			var page_id=$(this).val();
			var ref=$("#page option:selected").text();
			ref=ref.replace(/ /g, ""); 
			ref=ref.replace(/-/g, "");
			ref=ref.replace(/_/g, "");
			ref=ref.replace(/"/g, "");
			ref=ref.replace(/'/g, "");
			ref=ref.replace(/:/g, "");
			ref=ref.replace(/;/g, "");
			ref=ref.replace(/,/g, "");
			ref=ref.toUpperCase();

			$("#reference").val(ref);

			  $.ajax({
			  type:'POST' ,
			  url: base_url+"messenger_bot_enhancers/get_template_label_dropdown",
			  data: {page_id:page_id},
			  dataType : 'JSON',
			  success:function(response){
			    $("#template_id").html(response.template_option);
			    $("#label_ids").html(response.label_option);
			    $("#put_script").html(response.script);
			  }

			});

		});

		$(document).on('click','#get_button',get_button);
		function get_button()
		{        

			var page = $("#page").val();
			var domain_name = $("#domain_name").val();
			var template_id = $("#template_id").val();
			var reference = $("#reference").val();

			if(page=="")
			{
				swal("<?php echo $this->lang->line('Error'); ?>", "<?php echo $this->lang->line('Please select a page.'); ?>", 'error');
				return false;
			}

			if(template_id=='')
			{
				swal("<?php echo $this->lang->line('Error'); ?>", "<?php echo $this->lang->line('Please select OPT-IN inbox confirmation message template.'); ?>", 'error');
				return false;
			}

			if(reference=='')
			{
				swal("<?php echo $this->lang->line('Error'); ?>", "<?php echo $this->lang->line('Please enter an reference.'); ?>", 'error');
				return false;
			}

			$('#get_button').addClass('btn-progress');
			
			var queryString = new FormData($("#plugin_form")[0]);

	
			$.ajax({
				type:'POST' ,
				url: base_url+"messenger_bot_enhancers/mme_link_add_action",
				data: queryString,
				dataType : 'JSON',
				// async: false,
				cache: false,
				contentType: false,
				processData: false,
				success:function(response)
				{  
					if(response.status=='1') 
					{
					   
						$("#response").attr('class','alert alert-success text-center').html(response.message);
						$("#get_button").removeClass('btn-progress');
						$("#get_button").attr('disabled',true);
						$(".description").text(response.js_code);
						Prism.highlightAll();          
						$("#js_code2").text(response.js_code2);         
						$('.js_code_con').removeClass('hidden');
						$("#get_plugin_modal").modal();

						$(".toolbar-item").find('a').addClass('copy');
					}
					else 
					{
					    swal("<?php echo $this->lang->line('Error'); ?>", response.message, 'error');
						$("#get_button").removeClass('btn-progress');
					}

				
					
				}

				});

		}

		$(document).on('click', '.copy', function(event) {
		    event.preventDefault();

		    $(this).html('<?php echo $this->lang->line("Copied!"); ?>');
		    var that = $(this);
		    
		    var text = $(this).parent().parent().parent().find('code').text();
		    var $temp = $("<input>");
		    $("body").append($temp);
		    $temp.val(text).select();
		    document.execCommand("copy");
		    $temp.remove();


		    // iziToast.success({
		    //     title: "",
		    //     message: "<?php echo $this->lang->line('Copied to clipboard') ?>",
		    // });

		    setTimeout(function(){
		      $(that).html('<?php echo $this->lang->line("Copy"); ?>');
		    }, 2000); 

		});

		$('#get_plugin_modal').on('hidden.bs.modal', function () {
		   window.location.href = base_url+"messenger_bot_enhancers/mme_link_list";
		})

	});
</script>

<div class="modal fade" role="dialog" id="get_plugin_modal">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title"><i class="fas fa-code"></i> <?php echo $this->lang->line('M.me Plugin Embed Code'); ?></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-12">
						<div id="response"></div>
						<div class="form-group js_code_con">
							<label for="description"> <?php echo $this->lang->line("copy the code below and paste inside the html element of your webpage where you want to display this plugin.")?> </label>
							<pre class="language-javascript" ><code id="test" class="dlanguage-javascript description" ></code></pre>
							<br>
							<label for="js_code2"> <?php echo $this->lang->line("M.me Link")?> </label>
							<pre class="language-javascript" ><code id="js_code2" class="dlanguage-javascript" ></code></pre>
							<!-- <input type="text" id="js_code2" class="form-control"> -->
						</div>
					</div>

				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo $this->lang->line('Close'); ?></button>
			   </button>
		    </div>
	 </div>
   </div>
</div>

