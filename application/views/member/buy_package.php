<section class="section">
  <div class="section-header">
    <h1><i class="fas fa-cart-plus"></i> <?php echo $page_title; ?></h1>
    <div class="section-header-button">
      <a href="<?php echo base_url('payment/transaction_log'); ?>" class="btn btn-primary"><i class="fas fa-history"></i> <?php echo $this->lang->line("Transaction Log"); ?></a>
    </div>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item"><?php echo $page_title; ?></a></div>
    </div>
  </div>

  <div class="section-body">
    
    <div class="row">
      <?php 
      foreach($payment_package as $pack)
      {?>
        <div class="col-12 col-md-4 col-lg-4">
          <div class="pricing <?php if($pack['highlight']=='1') echo 'pricing-highlight';?>">
            <div class="pricing-title">
              <?php echo $pack["package_name"]; ?>
            </div>
            <div class="pricing-padding">
              <div class="pricing-price">
                <div><?php echo $curency_icon; ?></sup><?php echo $pack["price"]?></div>
                <div><?php echo $pack["validity"]?> <?php echo $this->lang->line("days"); ?></div>
              </div>
              <div class="pricing-details nicescroll" style="height: 180px;">
                <?php 
                $module_ids=$pack["module_ids"];
                $monthly_limit=json_decode($pack["monthly_limit"],true);
                $module_names_array=$this->basic->execute_query('SELECT module_name,id FROM modules WHERE FIND_IN_SET(id,"'.$module_ids.'") > 0  ORDER BY module_name ASC');

                foreach ($module_names_array as $row)
                {                              
                    $limit=0;
                    $limit=$monthly_limit[$row["id"]];
                    if($limit=="0") $limit2=$this->lang->line("unlimited");
                    else $limit2=$limit;
                    $limit2=" : ".$limit2;
                    echo '
                    <div class="pricing-item">
                      <div class="pricing-item-icon_x bg-light_x"><i class="fas fa-check"></i></div>
                      <div class="pricing-item-label">&nbsp;'.$this->lang->line($row["module_name"]).$limit2.'</div>
                    </div>';
                } ?>
                                
              </div>
            </div>
            <div class="pricing-cta">
              <a href="" class="choose_package" data-id="<?php echo $pack['id'];?>"><?php echo $this->lang->line("Select Package"); ?> <i class="fas fa-arrow-right"></i></a>
            </div>
          </div>
        </div>
      <?php 
      } ?>
    </div>
  </div>
</section>


<script>
    var base_url="<?php echo site_url();?>"; 
    function get_payment_button(package) 
    {
        $("#waiting").show();
        $("#button_place").html('');
        $("#payment_modal").modal();
        $.ajax
        ({
            type:'POST',
            data:{package:package},
            url:base_url+'payment/payment_button/',
            success:function(response)
             {
                 $("#waiting").hide();
                 $("#button_place").html(response);
             }
                
         }); 
    }    

    $(function() {
      $(".choose_package").click(function(e){
           e.preventDefault();           
           var package=$(this).attr('data-id'); 
           var has_reccuring = <?php echo $has_reccuring; ?>;
           if(has_reccuring)  
           {
            swal("<?php echo $this->lang->line('Subscription Message'); ?>", "<?php echo $this->lang->line('You have already a subscription enabled in paypal. If you want to use different paypal or different package, make sure to cancel your previous subscription from your paypal.');?>")
            .then((value) => {
              get_payment_button(package);            
            });
          }
          else get_payment_button(package);
        });
      });  

</script>


<div class="modal fade" tabindex="-1" role="dialog" id="payment_modal" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-cart-plus"></i> <?php echo $this->lang->line("Payment Options");?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="text-center" id="waiting" style="width: 100%;margin: 20px 0;"><i class="fas fa-spinner fa-spin blue" style="font-size:40px;"></i></div>
        <div id="button_place"></div>
        <br>
        <?php 
        if ($last_payment_method != '')
        { 
          
          $payment_type = ($has_reccuring == 'true') ? $this->lang->line('Recurring') : $this->lang->line('Manual');

          echo '<br><div class="alert alert-light alert-has-icon">
                  <div class="alert-icon"><i class="far fa-lightbulb"></i></div>
                  <div class="alert-body">
                    <div class="alert-title">'.$this->lang->line("Last Payment").'</div>
                    '.$this->lang->line("Last Payment").' : '.$last_payment_method.' ('.$payment_type.')
                  </div>
                </div>';
        }?>
      </div>
      <div class="modal-footer bg-whitesmoke br">      
        <button type="button" class="btn btn-secondary btn-lg" data-dismiss="modal"><i class="fa fa-remove"></i> <?php echo $this->lang->line("Close"); ?></button>
      </div>
    </div>
  </div>
</div>