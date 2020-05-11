<style>
  .margined{padding:20px;}
  .refresh_button_holder
  {
    padding: 0px;
    margin-top: 5px; 
    margin-bottom:5px;
  }
  .header_title{
    margin: 0px;
    padding: 0px;
    color: #3C8DBC;
  }
  .right_content
  {
    float: right;
    margin-bottom: 10px;

  }
  .right_content_message
  {
    background: #0084FF;
    color: white;
    padding: 10px;
    margin: 0px;
    border-radius: 15px;
  }
  .right_content_name
  {
    color: gray;
    text-align: right;
    padding: 0px;
    padding-right: 10px;
    margin: 0px;
  }
  .right_content_date
  {
    color: gray;
    text-align: right;
    font-weight: 500;
    padding: 0px;
    padding-right: 10px;
    margin: 0px;
  }

  .left_content
  {
    float: left;
    margin-bottom: 10px;
  }
  .left_content_message
  {
    background: #F1F0F0;
    padding: 10px;
    margin: 0px;
    border-radius: 15px;
  }
  .left_content_name
  {
    color: gray;
    text-align: left;
    padding: 0px;
    padding-left: 10px;
    margin: 0px;
  }
  .left_content_date
  {
    color: gray;
    text-align: left;
    font-weight: 500;
    padding: 0px;
    padding-left: 10px;
    margin: 0px;
  }
</style>
<section class="section section_custom">
  <div class="section-header">
    <h1><i class="fas fa-comments"></i> <?php echo $this->lang->line('Latest Conversations'); ?> </h1>
    <div class="section-header-button">
     <a class="btn btn-primary" name="refresh_data" id="refresh_data" href="#" page_table_id="<?php echo $page_table_id; ?>">
        <i class="fas fa-sync"></i> <?php echo $this->lang->line("Refresh Data"); ?>
     </a> 
    </div>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item"><a href="<?php echo base_url('subscriber_manager'); ?>"><?php echo $this->lang->line("Subscriber Manager");?></a></div>
      <div class="breadcrumb-item"><a href="<?php echo base_url('subscriber_manager/sync_subscribers'); ?>"><?php echo $this->lang->line("Sync Subscribers"); ?></a></div>
      <div class="breadcrumb-item"><?php echo $this->lang->line('Latest Conversations'); ?></div>
    </div>
  </div>

  <div class="section-body">
    <div class="card">
      <div class="card-body">        
        <div class="row" id="put_content">
        </div>    
      </div>
    </div>
  </div>
</section>

<div class="modal fade" id="conversation_modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-center"><i class="fa fa-wechat"></i> <span id="conversation_page_name"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body" id="conversation_modal_body">                

            </div>
            <div class="modal-footer">
              <input class="form-control" type="text" placeholder="<?php echo $this->lang->line('type your reply message here');?>" id="reply_message" name="reply_message">
              <br>
              <button class="btn btn-primary btn-sm" id="final_reply_button" style="margin-top: 5px;"><i class="fa fa-send"></i> <?php echo $this->lang->line('send') ?></button>
            </div>
        </div>
    </div>
</div>

<script>
  $(document).ready(function(){
      $('[data-toggle="tooltip"]').tooltip();
  });
  
  $("document").ready(function(){
    var base_url="<?php echo base_url(); ?>";
    var loading = '<br><div class="col-12 text-center waiting previewLoader"><i class="fas fa-spinner fa-spin blue text-center" style="font-size: 60px; margin-top: 100px; margin-bottom: 100px;"></i></div>';

    $(document).on('click','.reply_button',function(){
      var thread_id = $(this).attr('thread_id');
      $("#final_reply_button").attr('thread_id',thread_id);
      var page_table_id = $(this).attr('page_table_id');
      $("#final_reply_button").attr('page_table_id',page_table_id);
      var page_name = $(this).attr('page_name');
      $("#conversation_modal").modal();
      $("#conversation_modal_body").html(loading);          
      $("#conversation_page_name").html(page_name);         
      $.ajax({
          url:base_url+'message_manager/get_post_conversation',
          type:'POST',
          data:{thread_id:thread_id,page_table_id:page_table_id},
          success:function(response){
            $("#conversation_modal_body").html(response);
            var element = document.getElementById("conversation_modal_body");
            element.scrollTop = element.scrollHeight;         
          }
          
        });
    });

    $(document).on('click','#final_reply_button',function(){
      var thread_id = $(this).attr('thread_id');
      var page_table_id = $(this).attr('page_table_id');
      var reply_message = $("#reply_message").val().trim();
      if(reply_message == '')
      {
        alertify.alert("<?php $this->lang->line('Alert'); ?>","<?php echo $this->lang->line('You did not provide any reply message'); ?>",function(){});
        return false;
      }

      $("#conversation_modal_body").html(loading);
      $("#reply_message").val('');
      $("#final_reply_button").addClass('disabled');
      $.ajax({
          url:base_url+'message_manager/reply_to_conversation',
          type:'POST',
          data:{thread_id:thread_id,page_table_id:page_table_id,reply_message:reply_message},
          success:function(response){
            $("#conversation_modal_body").html(response);
            $("#final_reply_button").removeClass('disabled');     
          }
          
        });
    });

      $("#put_content").html(loading);

      function ajax_call()
      {   
        var page_table_id = "<?php echo $page_table_id; ?>";    
        $.ajax({
            url:base_url+'message_manager/get_pages_conversation',
            type:'POST',
            data:{page_table_id:page_table_id},
            success:function(response){
              $("#put_content").html(response);         
            }
          });       
      }
      
      $(document).on('click','#refresh_data',function(e){
        e.preventDefault();
        $("#put_content").html(loading);     
        ajax_call();
      });

      ajax_call();
  });
</script>
