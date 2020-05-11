<?php $this->load->view('admin/theme/message'); ?>
<style>
    #page_id{width: 150px;}
    #searching{max-width: 40%;}
    .swal-text{text-align: left !important;}
    @media (max-width: 575.98px) {
      #page_id{width: 90px;}
      #searching{max-width: 50%;}
      #add_label { max-width: 100% !important; }
    }
</style>

<section class="section section_custom">
	<div class="section-header">
		<h1><i class="fas fa-tags"></i> <?php echo $page_title; ?></h1>
		<div class="section-header-button">
			<a class="btn btn-primary add_label"  href="#">
				<i class="fas fa-plus-circle"></i> <?php echo $this->lang->line("New Label"); ?>
			</a> 
		</div>
		<div class="section-header-breadcrumb">
			<div class="breadcrumb-item"><a href="<?php echo base_url('subscriber_manager'); ?>"><?php echo $this->lang->line("Subscriber Manager");?></a></div>
			<div class="breadcrumb-item"><?php echo $page_title; ?></div>
		</div>
	</div>

	<div class="section-body">
		<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-body data-card">
                        <div class="row">
                            <div class="col-md-8 col-12">
                                <div class="input-group mb-3 float-left" id="searchbox">
                                    
                                    <!-- search by page name -->
                                    <div class="input-group-prepend">
                                        <select class="select2 form-control" id="page_id" name="page_id">
                                            <option value=""><?php echo $this->lang->line("Page Name"); ?></option>
                                            <?php foreach ($page_info as $key => $value): ?>
                                            <option value="<?php echo $value['id'];?>"><?php echo $value['page_name'];?></option>
                                            <?php endforeach ?>
                                      </select>
                                    </div>

                                    <input type="text" class="form-control" id="searching" name="searching" autofocus placeholder="<?php echo $this->lang->line('Search...'); ?>" aria-label="" aria-describedby="basic-addon2">

                                    <div class="input-group-append">
                                        <button class="btn btn-primary" id="search_submit" title="<?php echo $this->lang->line('Search'); ?>" type="button"><i class="fas fa-search"></i> <span class="d-none d-sm-inline"><?php echo $this->lang->line('Search'); ?></span></button>
                                    </div>
                                </div>
                            </div>
                        </div>
						<div class="table-responsive2">
							<table class="table table-bordered" id="mytable">
								<thead>
									<tr>
										<th>#</th>      
										<th><?php echo $this->lang->line("ID"); ?></th>      
										<th><?php echo $this->lang->line("Label"); ?></th>      
										<th><?php echo $this->lang->line("Label ID"); ?></th>      
										<th><?php echo $this->lang->line("Page Name"); ?></th>
										<th><?php echo $this->lang->line("Action"); ?></th>
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


<script>       
    var base_url="<?php echo site_url(); ?>";
   
    $(document).ready(function() {

    	var perscroll;
        var table = $("#mytable").DataTable({
    		serverSide: true,
    		processing:true,
    		bFilter: false,
    		order: [[ 2, "asc" ]],
    		pageLength: 10,
    		ajax: {
    			"url": base_url+'subscriber_manager/contact_group_data',
    			"type": 'POST',
                data: function ( d )
                {
                    d.page_id = $('#page_id').val();
                    d.searching = $('#searching').val();
                }
    		},
    		language: 
    		{
    			url: "<?php echo base_url('assets/modules/datatables/language/'.$this->language.'.json'); ?>"
    		},
    		dom: '<"top"f>rt<"bottom"lip><"clear">',
    		columnDefs: [
    		{
    			targets: [1],
    			visible: false
    		},
    		{
    			targets: [3,5],
    			className: 'text-center'
    		},
    		{
    			targets: [0,5],
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

        $(document).on('change', '#page_id', function(event) {
          event.preventDefault(); 
          table.draw();
        });

        $(document).on('click', '#search_submit', function(event) {
          event.preventDefault(); 
          table.draw();
        });
        // end of datatable

        $(document).on('click', '.add_label', function(event) {
            event.preventDefault();
            $("#name_err").text("");
            $("#page_err").text("");
            $("#group_name").val("");
            $("#selected_page_id").val("").change();
            $("#add_label").modal();
        });

        // create new label
        $(document).on('click', '#create_label', function(event) {
            event.preventDefault();

            $("#name_err").text("");
            $("#page_err").text("");

            group_name = $("#group_name").val();
            selected_page_id = $("#selected_page_id").val();

            if(group_name == '') {
                $("#name_err").text("<?php echo $this->lang->line('Name is Required') ?>")
                return false;
            }
            if(selected_page_id == '') {
                $("#page_err").text("<?php echo $this->lang->line('Page is Required') ?>")
                return false;
            }

            $(this).addClass('btn-progress');
            var that = $(this);

            $.ajax({
                url: '<?php echo base_url('subscriber_manager/ajax_label_insert'); ?>',
                type: 'POST',
                dataType: 'json',
                data: {group_name:group_name,selected_page_id:selected_page_id},
                success: function(response) {
                    $("#result_status").html('');
                    $("#result_status").css({"background":"","padding":"","margin":""});

                    if(response.status =="0")
                    {   
                        var errorMessage = JSON.stringify(response,null,10);
                        swal('<?php echo $this->lang->line("Error"); ?>',errorMessage, "error");
                        // iziToast.error({title: '',message: response.message,position: 'bottomRight'});
                        $("#result_status").css({"background":"#EEE","margin":"10px"});

                    } else if(response.status=='1')
                    {
                        iziToast.success({title: '',message: response.message,position: 'bottomRight'});
                    }

                    table.draw();
                    $(that).removeClass('btn-progress');
                }
            });

        });

        $(document).on('keyup', '#group_name', function(event) {
            event.preventDefault();
            $("#name_err").text("");
        });

        $(document).on('change', '#selected_page_id', function(event) {
            event.preventDefault();
            $("#page_err").text("");
        });


        // delete label
        $(document).on('click', '.delete_label', function(event) {
            event.preventDefault();

            swal({
                title: '<?php echo $this->lang->line("Delete Label"); ?>',
                text: '<?php echo $this->lang->line("Do you want to delete this label?"); ?>',
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            })
            .then((willDelete) => {
                if (willDelete) 
                {
                    var table_id = $(this).attr("table_id");

                    $(this).addClass('btn-danger btn-progress').removeClass('btn-outline-danger');
                    var that = $(this);

                    $.ajax({
                        url: '<?php echo base_url('subscriber_manager/ajax_delete_label'); ?>',
                        type: 'POST',
                        dataType: 'json',
                        data: {table_id:table_id},
                        success: function(response) {
                            if(response.status == 'successfull')
                            {
                                iziToast.success({title: '',message: response.message,position: 'bottomRight'});

                            } else if(response.status == 'failed')
                            {
                                swal("<?php echo $this->lang->line('Error') ?>", response.message, "error")

                            } else if(response.status == 'error')
                            {
                                var errorMessage = JSON.stringify(response,null,10);
                                swal('<?php echo $this->lang->line("Error"); ?>',errorMessage, "error");
                            } else if(response.status == 'wrong')
                            {
                                swal('<?php echo $this->lang->line("Error"); ?>',response.message, "error");
                            }

                            table.draw();
                            $(that).removeClass('btn-danger btn-progress').addClass('btn-outline-danger');
                        }
                    });
                } 
            });

        });

        $('#add_label').on('hidden.bs.modal', function() { 
            $("#name_err").text("");
            $("#page_err").text("");
            $("#group_name").val("");
            $("#selected_page_id").val("").change();
            table.draw();
        })
      
  });
 
 
</script>


<div class="modal fade" id="add_label" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" style="min-width: 30%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-plus-circle"></i> <?php echo $this->lang->line("Add Label") ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="add_label_modal_body">
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                          <label><i class="fas fa-tags"></i> <?php echo $this->lang->line('Label Name'); ?></label>
                          <input type="text" name="group_name" id="group_name" class="form-control">
                          <span id="name_err" class="red"></span>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                          <label><i class="fas fa-file-alt"></i> <?php echo $this->lang->line('Page Name'); ?></label>
                          <select class="form-control select2" id="selected_page_id" name="selected_page_id" style="width: 100%;">
                            <option value=""><?php echo $this->lang->line('Page Name'); ?></option>
                            <?php foreach ($page_info as $key => $value) 
                            {
                                echo "<option value='".$value['id']."'>".$value['page_name']."</option>";
                            } ?>
                          </select>
                          <span id="page_err" class="red"></span>
                        </div>
                    </div>
                </div>            
            </div>

            <div id="result_status"></div>
            <div class="modal-footer">
              <button type="button" class="btn btn-lg btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo $this->lang->line('Close'); ?></button>
              <button id="create_label" type="button" class="btn btn-lg btn-primary"><i class="fas fa-save"></i> <?php echo $this->lang->line('Save'); ?></button>
            </div>
        </div>
    </div>
</div>
