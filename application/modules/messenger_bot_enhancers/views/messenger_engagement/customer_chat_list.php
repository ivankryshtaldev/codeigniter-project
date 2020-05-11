<?php $this->load->view('admin/theme/message'); ?>
<style>
    .dropdown-toggle::after{content:none !important;}
  .dropdown-toggle::before{content:none !important;}
  #search_ref_name{max-width: 30% !important;}
  #search_page_id{width: 150px !important;}
  @media (max-width: 575.98px) {
    #search_page_id{width: 130px !important;}
    #search_ref_name{max-width: 77% !important;}
  }
</style>


<section class="section section_custom">
  <div class="section-header">
    <h1><i class="fa fa-wechat"></i> <?php echo $page_title; ?></h1>
    <div class="section-header-button">
      <a class="btn btn-primary" href="<?php echo base_url("messenger_bot_enhancers/customer_chat_add");?>">
        <i class="fas fa-plus-circle"></i> <?php echo $this->lang->line("Create Plugin"); ?>
      </a> 
    </div>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item"><a href="<?php echo base_url('messenger_bot'); ?>"><?php echo $this->lang->line("Messenger Bot"); ?></a></div>
      <div class="breadcrumb-item"><?php echo $page_title; ?></div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-body data-card">
            <div class="row">
              <div class="col-md-9 col-12">
                <div class="input-group mb-3 float-left" id="searchbox">


                  <!-- search by page name -->
                  <div class="input-group-prepend">
                    <?php $page_info['']=$this->lang->line("All Pages"); ?>
                    <?php echo form_dropdown('search_page_id', $page_info,'','id="search_page_id"  class="form-control select2"'); ?>

                  </div>
                  <input type="text" class="form-control" id="search_ref_name" name="search_ref_name" autofocus placeholder="<?php echo $this->lang->line('Search...'); ?>" aria-label="" aria-describedby="basic-addon2">
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
                    <th><?php echo $this->lang->line("Campaign ID"); ?></th>      
                    <th><?php echo $this->lang->line("Domain"); ?></th>      
                    <th><?php echo $this->lang->line("Page"); ?></th>
                    <th><?php echo $this->lang->line("Embed Code"); ?></th>
                    <th><?php echo $this->lang->line("Domain Code"); ?></th>
                    <th><?php echo $this->lang->line("Actions"); ?></th>
                    <th><?php echo $this->lang->line('Created at'); ?></th>
                    <th><?php echo $this->lang->line('Language Code'); ?></th>
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


<div class="modal fade" role="dialog" id="get_embed_modal">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-code"></i> <?php echo $this->lang->line('Chat plugin embed code'); ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12">
            <div class="form-group">
              <label for="description"> <?php echo $this->lang->line("Copy the code below and paste inside body tag or at the very last of your webpage.")?> </label>

              <pre class="language-javascript" ><code id="test" class="dlanguage-javascript description" ></code></pre>

            </div>
          </div>

        </div>
      </div>
      <div class="modal-footer">
        <!-- <button id="copy_js_code" type="button" class="btn btn-primary"><i class="fas fa-cut"></i> <?php echo $this->lang->line('Copy'); ?> -->
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?php echo $this->lang->line('Close'); ?></button>
      </button>
    </div>
  </div>
</div>
</div>

<script>
$(document).ready(function($) {
   
  var base_url = '<?php echo base_url(); ?>';



  // datatable section started
  var perscroll;
  var table = $("#mytable").DataTable({
      serverSide: true,
      processing:true,
      bFilter: false,
      order: [[ 1, "desc" ]],
      pageLength: 10,
      ajax: 
      {
        "url": base_url+'messenger_bot_enhancers/customer_chat_plugin_list_data',
        "type": 'POST',
        data: function ( d )
        {
            d.search_page_id = $('#search_page_id').val();
            d.search_ref_name = $('#search_ref_name').val();
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
            targets: [0,1,4,5,6,7,8],
            className: 'text-center'
          },
          {
            targets:[0,1,4,5,6,8],
            sortable: false
          },
          {
            targets:[4],
            render: function( data, type, row, meta )
                 {
                    var embed_js = '<a campaign_id='+row[1]+' class="badge badge-status get_js_embed" title="<?php echo $this->lang->line('Get Embed Code') ?>" style="cursor: pointer;"><i class="fas fa-code"></i> <?php echo $this->lang->line('Js Code'); ?></a>';
                    return embed_js;
                 }
          },






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

  $(document).on('change', '#search_page_id', function(event) {
    event.preventDefault(); 
    table.draw();
  });


  $(document).on('click', '#search_submit', function(event) {
    event.preventDefault(); 
    table.draw();
  });  



  $(document).on('click', '.get_js_embed', function(event) {
    event.preventDefault();
    
        var campaign_id = $(this).attr('campaign_id');
        
        $.ajax({
          url: '<?php echo base_url('messenger_bot_enhancers/customer_chat_js_code') ?>',
          type: 'POST',
          //dataType:'JSON',
          data: {campaign_id: campaign_id},
          success: function(response) {
              if(response)
              {
               
                $(".description").text(response);
                $("#get_embed_modal").modal();
                Prism.highlightElement($('#test')[0]);

                $(".toolbar-item").find('a').addClass('copy');
              }
              else
              {

                swal('<?php echo $this->lang->line("Error"); ?>', '<?php echo $this->lang->line("Something went wrong"); ?>', 'error');
              }

            
          }

        });

    
    
  });


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


  $(document).on('click', '.delete_campaign', function(event) {
      event.preventDefault();
      
      swal({
            title: '<?php echo $this->lang->line("Delete 2-Way Chat Plugin"); ?>',
            text: '<?php echo $this->lang->line("Do you really want to delete this 2-Way Chat Plugin?"); ?>',
            icon: 'warning',
            buttons: true,
            dangerMode: true,
          })
          .then((willDelete) => {
            if (willDelete) 
            {
                var base_url = '<?php echo site_url();?>';
                $(this).addClass('btn-progress');
                $(this).removeClass('btn-outline-danger');
                var that = $(this);
                var campaign_id = $(this).attr('campaign_id');

                $.ajax({
                  context: this,
                  type:'POST' ,
                  url:base_url+'messenger_bot_enhancers/customer_chat_delete',
                  dataType: 'JSON',
                  data:{campaign_id : campaign_id},
                  success:function(response){ 
                    
                     $(that).removeClass('btn-danger btn-progress');
                    
                     if(response.status == '1')
                        iziToast.success({title: '<?php echo $this->lang->line("Deleted Successfully"); ?>', message: response.message,position: 'bottomRight'});
                     else
                          iziToast.error({title: '<?php echo $this->lang->line("Error"); ?>',message: response.message ,position: 'bottomRight'});

                       // swal('<?php echo $this->lang->line("Success"); ?>', response.message, 'success');
                       // swal('<?php echo $this->lang->line("Error"); ?>', response.message, 'error');
                     

                     table.draw();
                  }
                });
            } 
          });
    });   

    $(".xscroll1").mCustomScrollbar({
    autoHideScrollbar:true,
    theme:"light-thick",
    axis: "x"
    });



  });

</script>
