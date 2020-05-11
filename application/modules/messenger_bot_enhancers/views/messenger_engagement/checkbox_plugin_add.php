<div id="put_script"></div>
<section class="section section_custom">
	<div class="section-header">
		<h1><i class="fa fa-check-square"></i> <?php echo $page_title; ?></h1>
		<div class="section-header-breadcrumb">
			<div class="breadcrumb-item"><a href="<?php echo base_url('messenger_bot'); ?>"><?php echo $this->lang->line("Messenger Bot"); ?></a></div>
			<div class="breadcrumb-item"><a href="<?php echo base_url('messenger_bot_enhancers/checkbox_plugin_list'); ?>"><?php echo $this->lang->line("Checkbox Plugin"); ?></a></div>
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
						  <div class="form-group col-12 col-md-4" style="padding: 10px;">
						    <label>
						       <?php echo $this->lang->line("select page"); ?> *
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("select page") ?>" data-content='<?php echo $this->lang->line("Select your Facebook page for which you want to generate the plugin.") ?>'><i class='fas fa-info-circle'></i> </a>
						    </label>
						    <?php $page_info['']= $this->lang->line("select page"); ?>
						    <?php echo form_dropdown('page', $page_info,'', 'class="form-control select2" id="page" style="width:100%;"' ); ?>                   
						  </div>  
						  <div class="form-group col-12 col-md-4" style="padding: 10px;">
						    <label>
						      <?php echo $this->lang->line("domain"); ?> *
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("domain") ?>" data-content='<?php echo $this->lang->line("Domain where you want to embed this plugin. Domain must have https.") ?>'><i class='fas fa-info-circle'></i> </a>
						    </label>
						    <input type="text" name="domain_name" autocomplete="off" id="domain_name" class="form-control" placeholder="https://example.com">                      
						  </div>
						  <div class="form-group col-12 col-md-4" style="padding: 10px;">
						    <label>
						      <?php echo $this->lang->line("language"); ?> *
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("language") ?>" data-content='<?php echo $this->lang->line("plugin will be loaded in this language.") ?>'><i class='fas fa-info-circle'></i> </a>
						    </label>
						    <?php echo form_dropdown('language', $sdk_list,'en_US', 'class="form-control select2" id="language" style="width:100%;"'); ?>                   
						  </div>  
						</div> 
						<div class="row">
							<div class="form-group col-12 col-md-3" style="padding: 10px;">
								<label>
									<?php echo $this->lang->line("Plugin skin"); ?> *
									<a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("Plugin skin") ?>" data-content='<?php echo $this->lang->line("light skin is suitable for pages with dark background and dark skin is suitable for pages with light background.") ?>'><i class='fas fa-info-circle'></i> </a>
								</label>
								<div class="row">
									<div class="col-12 col-md-6">
										<div class="selectgroup w-100">
											<label class="selectgroup-item">
												<input type="radio" name="skin" value="dark" id="dark" class="selectgroup-input">
												<span class="selectgroup-button"> <?php echo $this->lang->line("Light") ?></span>
											</label>
											<label class="selectgroup-item">
												<input type="radio" name="skin" value="light" id="light" class="selectgroup-input" checked>
												<span class="selectgroup-button"> <?php echo $this->lang->line("Dark") ?></span>
											</label>
										</div>
									</div>
								</div>

							</div>  
							<div class="form-group col-12 col-md-3" style="padding: 10px;">
								<label>
									<?php echo $this->lang->line("Center align"); ?> *
									<a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("center align") ?>" data-content='<?php echo $this->lang->line("choosing yes will make the plugin aligned center, otherwise left.") ?>'><i class='fas fa-info-circle'></i> </a>
								</label>
								<div class="row">
									<div class="col-12 col-md-6">
										<div class="selectgroup w-100">
											<label class="selectgroup-item">
												<input type="radio" name="center_align" value="true" id="centeryes" class="selectgroup-input" checked>
												<span class="selectgroup-button"> <?php echo $this->lang->line("Yes"); ?></span>
											</label>
											<label class="selectgroup-item">
												<input type="radio" name="center_align" value="false" id="centerno" class="selectgroup-input">
												<span class="selectgroup-button"> <?php echo $this->lang->line("No") ?></span>
											</label>
										</div> 
									</div>
								</div>
		                 
							</div>

						
						  <div class="form-group col-12 col-md-6" style="padding: 10px;">
						    <label>
						      <?php echo $this->lang->line("Plugin size"); ?> *
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("plugin size") ?>" data-content='<?php echo $this->lang->line("overall plugin size.") ?>'><i class='fa fa-info-circle'></i> </a>
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
									echo '<label class="selectgroup-item">
									                    <input type="radio" name="btn_size" value="'.$value.'" id="btn_size'.$i.'" '.$checked.' class="selectgroup-input">
									                    <span class="selectgroup-button">'.$this->lang->line($val_print).'</span>
									                  </label>';
						    } 
						    ?>  
						    </div>
						  </div>
						  <!--<div class="form-group col-12 col-md-6" style="padding: 10px;">
						    <label>
						      <?php echo $this->lang->line("JS Event"); ?> *
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("javascript event") ?>" data-content='<?php echo $this->lang->line("What javascript event you want to perform the OPT-IN functionality?") ?>'><i class='fa fa-info-circle'></i> </a>
						    </label>
						    <div class="selectgroup selectgroup-pills">
						    <?php 
						    $i=0;
						    foreach ($js_events as $key => $value) 
						    {
						      $i++;
						      $checked=$selected='';
						      if($key=='click') 
						      {
						        $selected='default-label';
						        $checked='checked';
						      }

							 echo '<label class="selectgroup-item">
							                     <input type="radio" name="js_event" value="'.$key.'" id="js_event_'.$i.'" '.$checked.' class="selectgroup-input">
							                     <span class="selectgroup-button">'.$this->lang->line($value).'</span>
							                   </label>';

						    } 
						    ?>
						    </div>               
						  </div>  -->

						</div>  

						<div class="row">

				           	<!-- <div class="col-12 col-md-6" style="padding: 10px;">
				           		<div class="form-group">
				           		  <label class="custom-switch mt-2">

				           		    <input type="checkbox" name="new_button" id="new_or_old" class="custom-switch-input">
				           		    <span class="custom-switch-indicator"></span>
				           		    <span class="custom-switch-description"><?php echo $this->lang->line("I want to add a new html element") ?></span>
				           		  
				           		  </label>
				           		</div>        				           	
				            </div>  -->
			            	
			            	<div class="col-12 col-md-6" style="padding: 10px;">
			            		<div class="form-group">
			            		  <label class="custom-switch mt-2">

			            		    <input type="checkbox" name="redirect" id="redirect_or_not"  class="custom-switch-input">
			            		    <span class="custom-switch-indicator"></span>
			            		    <span class="custom-switch-description"><?php echo $this->lang->line("Redirect to a webpage on successful OPT-IN") ?></span>
			            		  
			            		  </label>
			            		</div>        				           	
			             </div> 
						</div> 

						<!-- <div class="row existing_button_block">
						  <div class="form-group col-12 col-md-6" style="padding: 10px;">
						    <label>
						      <?php echo $this->lang->line("Element type"); ?> *
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("element type") ?>" data-content='<?php echo $this->lang->line("the HTML element will trigger the OPT-IN functionality is a class or ID?") ?>'><i class='fa fa-info-circle'></i> </a>
						    </label>
						    <br>

						     <div class="custom-control custom-radio custom-control-inline">
	                           <input type="radio" value="class" id="elementclass" name="element_type" class="custom-control-input">
	                           <label class="custom-control-label" for="elementclass"><?php echo $this->lang->line('Class'); ?></label>
	                         </div>
	                         <div class="custom-control custom-radio custom-control-inline">
                               <input type="radio" value="id" id="elementid" name="element_type" class="custom-control-input" checked>
                               <label class="custom-control-label" for="elementid"><?php echo $this->lang->line('ID'); ?></label>
                             </div>
           
						  </div>  
						  <div class="form-group col-12 col-md-6" style="padding: 10px;">
						    <label>
						      <?php echo $this->lang->line("Element selector (Class/Id value)"); ?> *
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("element selector (class/id value)") ?>" data-content='<?php echo $this->lang->line("If element typr is an ID then put ID value otherwise put the class value.") ?>'><i class='fa fa-info-circle'></i> </a>
						    </label>
						    <input type="text" name="id_or_class_value" id="id_or_class_value" class="form-control">                      
						  </div>  
						</div> 

						<div class="row new_button_block">
						  <div class="form-group col-12 col-md-6" style="padding: 10px;">
						    <label class="margin-bottom-label">
						      <?php echo $this->lang->line("New button text"); ?> *
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("new button text") ?>" data-content='<?php echo $this->lang->line("System will create a new button to perform the OPT-IN functionality. Type the button text here.") ?>'><i class='fa fa-info-circle'></i> </a>
						    </label>
						    <input type="text" name="new_button_display" id="new_button_display" class="form-control" value="Confirm OPT-IN">                     
						  </div>
						  <div class="form-group col-12 col-md-6" style="padding: 10px;">
						    <label class="margin-bottom-label">
						      <?php echo $this->lang->line("Button position"); ?> *
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("button position") ?>" data-content='<?php echo $this->lang->line("where will be the new button placed relative to the checkbox?") ?>'><i class='fa fa-info-circle'></i> </a>
						    </label>

    					    <div class="selectgroup selectgroup-pills">
                                <label class="selectgroup-item">
                                  <input type="radio" name="new_button_position" value="top" class="selectgroup-input" id="new_button_position1">
                                  <span class="selectgroup-button selectgroup-button-icon"><?php echo $this->lang->line('Top'); ?></span>
                                </label>
                                <label class="selectgroup-item">
                                  <input type="radio" name="new_button_position" value="bottom" class="selectgroup-input" id="new_button_position2" checked>
                                  <span class="selectgroup-button selectgroup-button-icon"> <?php echo $this->lang->line('Bottom'); ?></span>
                                </label>
    					     </div> 
             
						  </div>
						</div>  -->

						<div class="row new_button_block">
						  <div class="form-group col-12 col-md-3" style="padding: 10px;">
						    <label>
						      <?php echo $this->lang->line("Button background"); ?> *
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("button background") ?>" data-content='<?php echo $this->lang->line("new button backgroung color") ?>'><i class='fa fa-info-circle'></i> </a>
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
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("button text color") ?>" data-content='<?php echo $this->lang->line("new button text color") ?>'><i class='fa fa-info-circle'></i> </a>
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
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("button hover background") ?>" data-content='<?php echo $this->lang->line("new button background color on mouse over") ?>'><i class='fa fa-info-circle'></i> </a>
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
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("button text hover color") ?>" data-content='<?php echo $this->lang->line("new button text color on mouse over") ?>'><i class='fa fa-info-circle'></i> </a>
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



						<div class="row display_messsage_block">
							<div class="form-group col-12" style='padding:10px;'>
							  <label>
							    <?php echo $this->lang->line("OPT-IN success message in website"); ?>
							     <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("OPT-IN success message") ?>" data-content='<?php echo $this->lang->line("this message will be displayed after successful OPT-IN.") ?> <?php echo $this->lang->line('Keep it blank if you do not want.');?>'><i class='fa fa-info-circle'></i> </a>
							  </label>
							  <textarea class="form-control" placeholder="<?php echo $this->lang->line('Keep it blank if you do not want.');?>" name="button_click_success_message" id="button_click_success_message" style="width: 100%;"><?php echo 'You have been subscribed successfully, thank you.';?></textarea>

		  					<div class="custom-control custom-checkbox">
		                        <input type="checkbox" value="1" class="custom-control-input" name="add_button_with_message" id="add_button_with_message">
		                        <label class="custom-control-label" for="add_button_with_message"><?php echo $this->lang->line("I want to add a button in success message");?></label>
		                      </div>
	
							</div>
						

						</div>

						<div class="row display_messsage_block display_button_block">
						  <div class="form-group col-12 col-md-6" style='padding:10px;'>
						    <label>
						      <?php echo $this->lang->line("button text"); ?> *
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("button text") ?>" data-content='<?php echo $this->lang->line("This button will be embeded with OPT-IN successful message.") ?>'><i class='fa fa-info-circle'></i> </a>
						    </label>
						    <input type="text" name="success_button" id="success_button" class="form-control" value="Send Message">                     
						  </div>
						  <div class="form-group col-12 col-md-6" style='padding:10px;'>
						    <label>
						      <?php echo $this->lang->line("Button URL"); ?> *
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("Button URL") ?>" data-content='<?php echo $this->lang->line("Button click action URL") ?>'><i class='fa fa-info-circle'></i> </a>
						    </label>
						    <input type="text" name="success_url" id="success_url" class="form-control" value="">                     
						  </div>
						</div>

						<div class="row display_messsage_block display_button_block">


							<div class="form-group col-12 col-md-3" style="padding: 10px;">
							  <label>
							    <?php echo $this->lang->line("Button background"); ?> *
							 
							  </label>
							  <div class="input-group colorpicker-component color-picker-rgb" >
							    <input type="text" class="form-control" name="success_button_bg_color" id="success_button_bg_color" value="#5CB85C">
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
					
							  </label>   
							  <div class="input-group colorpicker-component color-picker-rgb" >
							    <input type="text" class="form-control" name="success_button_color" id="success_button_color" value="#FFFFFF">
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
						
							  </label>
							  <div class="input-group colorpicker-component color-picker-rgb" >
							    <input type="text" class="form-control" name="success_button_bg_color_hover" id="success_button_bg_color_hover" value="#339966">
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
			
							  </label>
							  <div class="input-group colorpicker-component color-picker-rgb" >
							    <input type="text" class="form-control" name="success_button_color_hover" id="success_button_color_hover" value="#FFFDDD">
							    <div class="input-group-append">
							      <div class="input-group-text">
							      	<span class="input-group-addon"><i></i></span>
							      </div>
							    </div>
							  </div>                 
							</div>            
							
						</div>

						<div class="row redirect_block">
						  <div class="form-group col-12" style='padding:10px;'>
						    <label class="margin-bottom-label">
						      <?php echo $this->lang->line("OPT-IN success redirect URL"); ?> *
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("OPT-IN success redirect URL") ?>" data-content='<?php echo $this->lang->line("Visitors will be redirected to this URL after successful OPT-IN.") ?>'><i class='fa fa-info-circle'></i> </a>
						    </label>
						    <input type="text" name="success_redirect_url" id="success_redirect_url" class="form-control" value="">                   
						  </div>
						</div>

						<div class="row">
						  <div class="form-group col-12" style='padding:10px;'>
						    <label>
						      <?php echo $this->lang->line("checkbox validation error message"); ?>
						       <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("checkbox validation error message") ?>" data-content='<?php echo $this->lang->line("this message will be displayed if checkbox is not checked.") ?>'><i class='fa fa-info-circle'></i> </a>
						    </label>
						    <textarea name="validation_error" id="validation_error" style="width: 100%;" class="form-control"></textarea>
						  </div>
						</div>

						<div class="row">
						  <div class="form-group col-12 <?php if(!$this->is_broadcaster_exist) echo 'col-md-6'; else echo 'col-md-5';?>" style='padding:10px;'>
						    <label>
						      <?php echo $this->lang->line("OPT-IN inbox confirmation message template"); ?> *
						       <a href="#" data-html="true" data-placement="top" data-toggle="popover" data-trigger="focus" title="<?php echo $this->lang->line("OPT-IN inbox confirmation message template") ?>" data-content='<?php echo $this->lang->line("This content will be sent to messenger inbox on OPT-IN.") ?> <?php echo $this->lang->line("You must select page to fill this list with data."); ?> <?php echo $this->lang->line("You can create template from ").' <a href="'.base_url("messenger_bot/create_new_template").'">'.$this->lang->line("here.")?></a> <?php echo $this->lang->line("First name & last name in template will not work."); ?>'><i class='fa fa-info-circle'></i> </a>
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
		$('.new_button_block,.redirect_block,.display_button_block').css('display','none');

		// $(document).on('change', '#new_or_old', function(event) {
		// 	event.preventDefault();

		// 	var checkBox = document.getElementById("new_or_old");
		// 		if (checkBox.checked == true)
		// 		{
		// 			$('.new_button_block').show(500);
		// 			$('.existing_button_block').hide(500);
				
		// 		}
		// 		else
		// 		{
		// 			$('.new_button_block').hide(500);
		// 			$('.existing_button_block').show(500);
				
		// 		}
			

		// 	});
		$(document).on('change', '#redirect_or_not', function(event){

			var r_or_not= document.getElementById("redirect_or_not");

			if(r_or_not.checked == true)
			{
				$('.redirect_block').show(500);
				$('.display_messsage_block').hide(500);
			}
			else
			{
				$('.display_messsage_block').show(500);
				$('.redirect_block').hide(500);
				if($('input[name=add_button_with_message]').prop('checked'))
					$('.display_button_block').show();
				else
					$('.display_button_block').hide();
			}

		});

		$(document).on('click','#add_button_with_message', function(event){

			var btnw_msg = document.getElementById('add_button_with_message');
			if (btnw_msg.checked == true) 
				$('.display_button_block').show(500);
			else
				$('.display_button_block').hide(500);
		
		});

		$(document).on('blur','#domain_name',function(event){
			event.preventDefault();
			var ref=$(this).val();
			ref=ref.replace("http://", "");
			ref=ref.replace("https://", "");
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

		});

		$(document).on('change','#page',function(event){
			event.preventDefault();

			var page_id=$(this).val();

			 
			  $.ajax({
			  type:'POST' ,
			  url: base_url+"messenger_bot_enhancers/get_template_label_dropdown",
			  data: {page_id:page_id},
			  dataType : 'JSON',
			  success:function(response){
			    $("#template_id").html(response.template_option);
			    $("#label_ids").html(response.label_option);
			    $("#put_script").html(response.script);
			    $("#success_url").val(response.mme_link);
			    $("#success_redirect_url").val(response.mme_link);
			  }

			});
		});

		$(document).on('click','#get_button',get_button);
		function get_button()
		{        

			var page = $("#page").val();
			var domain_name = $("#domain_name").val();
			var id_or_class_value = $("#id_or_class_value").val();
			var new_button_display = $("#new_button_display").val();
			var template_id = $("#template_id").val();
			var reference = $("#reference").val();

			if(page=="")
			{
				swal("<?php echo $this->lang->line('Error'); ?>", "<?php echo $this->lang->line('Please select a page.'); ?>", 'error');
				return false;
			}

			if(domain_name=="")
			{
				swal("<?php echo $this->lang->line('Error'); ?>", "<?php echo $this->lang->line('Please put your domain name.'); ?>", 'error');
				return false;
			}

			// if($('#new_or_old').val()=='1' && new_button_display=='')
			// {
			// 	swal("<?php echo $this->lang->line('Error'); ?>", "<?php echo $this->lang->line('Please enter new button text.'); ?>", 'error');
			// 	return false;
			// }

			// if($('#new_or_old').val()=='0' && id_or_class_value=='')
			// {
			// 	swal("<?php echo $this->lang->line('Error'); ?>", "<?php echo $this->lang->line('Please enter ID/class value.'); ?>", 'error');
			// 	return false;
			// }


			if($("#redirect_or_not").val()=="1")
			{
				if($("#success_redirect_url").val()=='')
				{
					swal("<?php echo $this->lang->line('Error'); ?>", "<?php echo $this->lang->line('OPT-IN success redirect URL is required.'); ?>", 'error');
					return false;
				}
			}
			else
			{
				if($('input[name=add_button_with_message]').prop('checked'))
				{
					if($("#success_button").val()=='' || $("#success_url").val()=='')
					{
						swal("<?php echo $this->lang->line('Error'); ?>", "<?php echo $this->lang->line('Missing OPT-IN success button parameters.'); ?>", 'error');
						return false;
					}
				}
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

			
			$("#response").attr('class','').html('');
			$('#get_button').addClass('btn-progress');
			
			var queryString = new FormData($("#plugin_form")[0]);
			// var new_or_old = document.getElementById("new_or_old");
			var redirect_or_not = document.getElementById("redirect_or_not");
			// if(new_or_old.checked == true)
			// 	queryString.append('new_button','1');
			// else
			// 	queryString.append('new_button','0');
			if(redirect_or_not.checked == true)
				queryString.append('redirect','1');
			else
				queryString.append('redirect','0');
	
			$.ajax({
				type:'POST' ,
				url: base_url+"messenger_bot_enhancers/checkbox_plugin_add_action",
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
						Prism.highlightElement($('#test')[0]);

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
		   window.location.href = base_url+"messenger_bot_enhancers/checkbox_plugin_list";
		});


		$(".xscroll1").mCustomScrollbar({
		autoHideScrollbar:true,
		theme:"light-thick",
		axis: "x"
		});



	});
</script>

<div class="modal fade" role="dialog" id="get_plugin_modal">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title"><i class="fas fa-code"></i> <?php echo $this->lang->line('Checkbox Plugin Embed Code'); ?></h5>
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

