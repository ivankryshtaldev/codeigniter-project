<?php $this->load->view('admin/theme/message'); ?>
<?php $is_demo=$this->is_demo; ?>
<?php $is_admin=($this->session->userdata('user_type') == "Admin") ? 1:0; ?>
<style type="text/css">
    .button{
        margin-top: 10px;
    }
    .datagrid-body
    {
      overflow: hidden !important; 
    }

    .emojionearea, .emojionearea.form-control
    {
        height: 150px !important;
    }


</style>

<div id="dynamic_field_modal" class="modal fade" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" id="add_name">
                <div class="modal-header">
                    <h5 style="text-align: center;" class="modal-title"><i class="fa fa-th-large"></i> <?php echo $this->lang->line('Please Give The Following Information For Post Auto Comment'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" style="padding-bottom:0">

                    <label>
                        <i class="fa fa-th-large"></i>
                         <?php echo $this->lang->line('Template Name'); ?>
                    </label>
                    <div class="form-group">
                        <input type="text" name="template_name" id="name" class="form-control" placeholder="<?php echo $this->lang->line('Your Template Name'); ?>" />
                    </div>
                    <div id="dynamic_field">

                    </div>

                    <button style="font-size: 10px; text-align:center;" type="button" name="add_more" id="add_more" class="btn btn-sm btn-outline-primary add_more_edit float-right"><i class="fa fa-plus-circle"></i> <?php echo $this->lang->line('add more'); ?></button>
                    <button style="font-size: 10px;text-align:center;" type="button" id="add_more_new" class="btn btn-sm btn-outline-primary add_more_new float-right">
                        <i class="fa fa-plus-circle"></i> <?php echo $this->lang->line('add more'); ?>
                    </button>
                    <div class="smallspace clearfix"></div>
                    <div class="col-xs-12 text-center" id="response_status"></div>
                </div>
                <div class="modal-footer" style="margin-top: 10px;">
                    <input type="hidden" name="hidden_id" id="hidden_id" />
                    <input type="hidden" name="action" id="action" value="insert" />
                                           
                    <button type="submit" name="submit" id="submit" class="btn btn-primary btn-lg"><i class='fa fa-save'></i> <?php echo $this->lang->line('Save'); ?></button>
                    <button class="btn btn-lg btn-secondary float-right" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo $this->lang->line("Cancel"); ?></button>
                    
                </div>
            </form>
        </div>
    </div>

</div> 


<section class="section section_custom">
    <div class="section-header">
        <h1><i class="fa fa fa-th-large"></i> <?php echo $page_title; ?></h1>
        <div class="section-header-button">
         <a class="btn btn-primary" name="add" id="add"  href="#">
            <i class="fas fa-plus-circle"></i> <?php echo $this->lang->line("Create new template"); ?>
         </a> 
        </div>
        <div class="section-header-breadcrumb">
          <div class="breadcrumb-item"><?php echo $this->lang->line("Comment Automation"); ?></div>
          <div class="breadcrumb-item"><?php echo $page_title; ?></div>
        </div>
    </div>

    <div class="section-body">
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-body data-card">
                <div class="table-responsive">
                  <table class="table table-bordered" id="mytable">
                    <thead>
                      <tr>
                        <th>#</th>      
                        <th><?php echo $this->lang->line("Template ID"); ?></th>      
                        <th><?php echo $this->lang->line("Template Name"); ?></th>
                        <th style="min-width: 150px"><?php echo $this->lang->line("Actions"); ?></th>
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
    
    $Youdidntprovideallinformation = $this->lang->line("you didn't provide template information.");
   
    $Youdidntselectanyoption = $this->lang->line("you didn\'t select any option.");

    $Youdidntprovideallcomment = $this->lang->line("You didn\'t provide comment information ");

    $Doyouwanttodeletethisrecordfromdatabase = $this->lang->line("do you want to delete this record from database?");
    $AutoComment = $this->lang->line("auto comment");
    $remove = $this->lang->line("remove");
    $AddComments = $this->lang->line("add comments");
?>

<script>
$(document).ready(function(){

    var base_url="<?php echo site_url(); ?>";
    var is_demo='<?php echo $is_demo;?>';  
    var is_admin='<?php echo $is_admin;?>';

    // datatable section started
    var table = $("#mytable").DataTable({
        serverSide: true,
        processing:true,
        bFilter: true,
        order: [[ 1, "desc" ]],
        pageLength: 10,
        ajax: 
        {
            "url": base_url+'comment_automation/template_manager_data',
            "type": 'POST',
            "dataSrc": function ( json ) 
            {
              $(".table-responsive").niceScroll();
              return json.data;
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
              targets: '',
              className: 'text-center',
              sortable: false
            },
            {
                targets: [3],
                "render": function ( data, type, row, meta ) 
                {
                    var id = row[1];
                    var edit_str="<?php echo $this->lang->line('Edit');?>";
                    var delete_str="<?php echo $this->lang->line('Delete');?>";
                    var str="";   
                    str="&nbsp;<a class='text-center edit_reply_info btn btn-circle btn-outline-warning edit' href='#' name='edit' title='"+edit_str+"' id='"+id+"'>"+'<i class="fa fa-edit"></i>'+"</a>";
                    str=str+"&nbsp;<a name='delete' href='#' class='text-center delete_reply_info btn btn-circle btn-outline-danger delete' title='"+delete_str+"' id="+id+" '>"+'<i class="fa fa-trash"></i>'+"</a>";
                  
                    return str;
                }
            }
        ]
    });
    // End of datatable section



    var count = 10;
    var wrapper= $('#dynamic_field');
    var add_button_edit      = $(".add_more_edit");
    var add_button = $("#add_more_new");
    var x=1;
    var AutoComment = "<?php echo $AutoComment; ?>";
    var remove = "<?php echo $remove; ?>";
    var AddComments = "<?php echo $AddComments; ?>";

    function add_dynamic_input_field(x)
    {     
        output = '<div class="card card-primary single_item" style="margin-top: 10px;padding-bottom:0;margin-bottom:0;">';
        output += '<div class="card-header"><h4 class="modal-title text-center"><i class="fa fa-comments"></i> '+AutoComment+'</h4></div> <div class="card-body"><textarea type="text" name="auto_reply_comment_text[]" id="auto_reply_comment_text_'+x+'" class="form-control name_list" style="height:70px;width:100%;" placeholder="'+AddComments+'"></textarea><span class="clearfix"><a href="#" style="font-size:10px;text-align:center;" class="btn btn-sm btn-outline-danger remove_field float-right clearfix"><i class="fas fa-times"></i> '+remove+'</a></span></div>';
        output += '</div>';
        $(wrapper).append(output);
    }

    $('#add').click(function(e){
        e.preventDefault();
        add_dynamic_input_field(x);
        $(".add_more_edit").hide();
        $('#action').val("insert");
        $('#submit').val('<?php echo $this->lang->line("submit"); ?>');
        $('#dynamic_field_modal').modal('show');

         $("#auto_reply_comment_text_"+x).emojioneArea({
                autocomplete: false,
                pickerPosition: "bottom"
        });
    });

    $(add_button).on('click', function(e){

        e.preventDefault();
        if(x<count){
            x++;
            add_dynamic_input_field(x);

             $("#auto_reply_comment_text_"+x).emojioneArea({
                autocomplete: false,
                pickerPosition: "bottom"
            });
            
        }

    });

    $(wrapper).on("click",".remove_field", function(e){ //user click on remove text
        e.preventDefault(); 
        
        $(this).parent().parent().parent().remove(); x--;
        

    });

    // $(document).on('submit', '#add_name', function(event) {
    //     event.preventDefault();
    //     /* Act on the event */
    // });

    $(document).on('submit', '#add_name', function(event) {
        event.preventDefault();

        $("#response_status").html('');

        var Youdidntprovideallinformation = "<?php echo $Youdidntprovideallinformation; ?>";
        var name = $("#name").val().trim();

        const comment_block_num = $(".single_item").length;
        let comment_block_content;


        if (comment_block_num === 1) {

            comment_block_content = $(".single_item").find('textarea').val();
            console.log(comment_block_content);
        }

        if(name === '' || comment_block_num === 0 || (comment_block_num === 1 && comment_block_content == '')){
            swal('<?php echo $this->lang->line("Warning!"); ?>', Youdidntprovideallinformation, 'warning');
            return false;
        }
        var form_data = $(this).serialize();

        var Youdidntprovideallcomment = "<?php echo $Youdidntprovideallcomment; ?>";
        var auto_reply_comment_text = $('#auto_reply_comment_text').val();
        if(auto_reply_comment_text == ''){
            swal('<?php echo $this->lang->line("Warning!"); ?>', Youdidntprovideallcomment, 'warning');
            return false;
        }
        var action = $('#action').val();

        $(this).addClass('btn-progress');
        $.ajax({
            url:base_url+"comment_automation/create_template_action",
            method:"POST",
            data:form_data,
            context:this,
            success:function(data)
            {
                $(this).removeClass('btn-progress');
                

                if(action == 'insert')
                {
                    swal('<?php echo $this->lang->line("Success"); ?>', '<?php echo $this->lang->line("Your data has been successfully inseret into the database."); ?>', 'success').then((value) => {
                                      $("#dynamic_field_modal").modal('hide');
                                      $('#add_name')[0].reset();
                                      location.reload();
                                    });
                }

                if(action == 'edit')
                {
                    swal('<?php echo $this->lang->line("Success"); ?>', '<?php echo $this->lang->line("Your data has been successfully editted from the database."); ?>', 'success').then((value) => {
                                      $("#dynamic_field_modal").modal('hide');
                                      $('#add_name')[0].reset();
                                      location.reload();
                                    });
                }
                
      
            }
        });
      
    });

    $(document).on('click', '.edit', function(e){
        e.preventDefault();
        var id = $(this).attr("id");
        // var x;
        $("#add_more_new").hide();

        $.ajax({
            url:base_url+"comment_automation/ajaxselect",
            method:"POST",
            data:{id:id},
            dataType:"JSON",
            success:function(data)
            {
                $('#name').val(data.template_name);
                $('#dynamic_field').html(data.auto_reply_comment_text);
                $('#action').val('edit');
                $('.modal-title').html("<i class='fa fa-comments'></i> <?php echo $this->lang->line('auto comment'); ?>");
                $('.modal-header .modal-title').html("<i class='fa fa-comments'></i> <?php echo $this->lang->line('Please Give The Following Information For Post Auto Comment'); ?>");
                $('#submit').val("<?php echo $this->lang->line('update'); ?>");
                $('#hidden_id').val(id);
                $('#dynamic_field_modal').modal('show');

                x=data.x;

                for(var k=1; k<=x;k++){

                      $("#auto_reply_comment_text_"+k).emojioneArea({
                            autocomplete: false,
                            pickerPosition: "bottom"
                        });
                }
                
                $count=10
               
                $(add_button_edit).on('click', function(e){
                    e.preventDefault();
                    if(x<count){
                        x++;
                        add_dynamic_input_field(x);

                        $("#auto_reply_comment_text_"+x).emojioneArea({
                            autocomplete: false,
                            pickerPosition: "bottom"
                        });
                        
                    }
                });
            }
        });
    });



    var Doyouwanttodeletethisrecordfromdatabase = "<?php echo $Doyouwanttodeletethisrecordfromdatabase; ?>";
  
    $(document).on('click','.delete',function(e){
        if(is_demo=='1' && is_admin=='1')
        {
            swal('<?php echo $this->lang->line("Warning"); ?>', 'You can not delete templates from admin account.', 'warning');
            return;
        }
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
                var id = $(this).attr("id");

                $.ajax({
                    context: this,
                    type:'POST' ,
                    url:"<?php echo base_url('comment_automation/delete_comment')?>",
                    data: {id:id},
                    success:function(response){ 
                        iziToast.success({title: '',message: '<?php echo $this->lang->line("Template has been deleted successfully."); ?>',position: 'bottomRight'});
                        table.draw();
                    }
                });
            } 
        });

    });


    $('#dynamic_field_modal').on('hidden.bs.modal', function () { 
        location.reload();
    })


});
</script>


<div class="modal fade" id="delete_template_modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title text-center"><?php  echo $this->lang->line("Template Delete Confirmation") ?></h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body" id="delete_template_modal_body">                

            </div>
        </div>
    </div>
</div>
