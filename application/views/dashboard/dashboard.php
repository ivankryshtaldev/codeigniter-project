<?php 
$month_name_array = array(
	'01' => 'January',
	'02' => 'February',
	'03' => 'March',
	'04' => 'April',
	'05' => 'May',
	'06' => 'June',
	'07' => 'July',
	'08' => 'August',
	'09' => 'September',
	'10' => 'October',
	'11' => 'November',
	'12' => 'December'
);
?>
<style>
  .list-unstyled-border li {
    margin-bottom: 45px !important;
  }
  #period_loader {height: 100%;width:100%;display: table;}
  #period_loader i{font-size:60px;display: table-cell; vertical-align: middle;padding:30px 0;}
  
</style>

<section class="section">
  <?php if($other_dashboard == 1) : ?>
    <?php if($system_dashboard == 'yes') : ?>
    <div class="section-header">
      <h1><i class="fas fa-tachometer-alt"></i> <?php echo $this->lang->line('System Dashboard'); ?> </h1>
    </div>
    <?php else : ?>
    <div class="section-header">
      <h1><i class="fas fa-tachometer-alt"></i> <?php echo $this->lang->line('Dashboard for')." ".$user_name." [".$user_email."]"; ?> </h1>
    </div>
    <?php endif; ?>
  <?php endif; ?>
  
  <?php if($other_dashboard == 1) : ?>
  <div class="section-body">
  <?php endif; ?>
    
    <div class="row">
      <div class="col-lg-4 col-md-4 col-sm-12">
        <div class="card card-statistic-2">
          <div class="card-stats">
            <div class="card-stats-title"><?php echo $this->lang->line('Order Statistics'); ?> -
              <div class="dropdown d-inline">
                <a class="font-weight-600 dropdown-toggle" data-toggle="dropdown" href="#" id="orders-month"><?php echo $month_name_array[$month_number]; ?></a>
                <ul class="dropdown-menu dropdown-menu-sm">
                  <li class="dropdown-title"><?php echo $this->lang->line('Select Month'); ?></li>
                  <li><a href="#" class="dropdown-item month_change <?php if($month_number == '01') echo 'active'; ?>" month_no="01">January</a></li>
                  <li><a href="#" class="dropdown-item month_change <?php if($month_number == '02') echo 'active'; ?>" month_no="02">February</a></li>
                  <li><a href="#" class="dropdown-item month_change <?php if($month_number == '03') echo 'active'; ?>" month_no="03">March</a></li>
                  <li><a href="#" class="dropdown-item month_change <?php if($month_number == '04') echo 'active'; ?>" month_no="04">April</a></li>
                  <li><a href="#" class="dropdown-item month_change <?php if($month_number == '05') echo 'active'; ?>" month_no="05">May</a></li>
                  <li><a href="#" class="dropdown-item month_change <?php if($month_number == '06') echo 'active'; ?>" month_no="06">June</a></li>
                  <li><a href="#" class="dropdown-item month_change <?php if($month_number == '07') echo 'active'; ?>" month_no="07">July</a></li>
                  <li><a href="#" class="dropdown-item month_change <?php if($month_number == '08') echo 'active'; ?>" month_no="08">August</a></li>
                  <li><a href="#" class="dropdown-item month_change <?php if($month_number == '09') echo 'active'; ?>" month_no="09">September</a></li>
                  <li><a href="#" class="dropdown-item month_change <?php if($month_number == '10') echo 'active'; ?>" month_no="10">October</a></li>
                  <li><a href="#" class="dropdown-item month_change <?php if($month_number == '11') echo 'active'; ?>" month_no="11">November</a></li>
                  <li><a href="#" class="dropdown-item month_change <?php if($month_number == '12') echo 'active'; ?>" month_no="12">December</a></li>
                  <li><a href="#" class="dropdown-item month_change <?php if($month_number == 'year') echo 'active'; ?>" month_no="year">This Year</a></li>
                </ul>
              </div>
            </div>
            <div class="text-center waiting hidden" id="loader"><i class="fas fa-spinner fa-spin blue text-center" style="font-size: 40px;"></i></div>
            <div class="card-stats-items month_change_middle_content">
              <div class="card-stats-item">
                <div class="card-stats-item-count" id="subscribed"><?php echo custom_number_format($subscribed); ?></div>
                <div class="card-stats-item-label"><?php echo $this->lang->line('Subscribed'); ?></div>
              </div>
              <div class="card-stats-item">
                <div class="card-stats-item-count" id="unsubscribed"><?php echo custom_number_format($unsubscribed); ?></div>
                <div class="card-stats-item-label"><?php echo $this->lang->line('Un-Subscribed'); ?></div>
              </div>
              <div class="card-stats-item">
                <div class="card-stats-item-count" id="message_sent"><?php echo custom_number_format($total_message_sent); ?></div>
                <div class="card-stats-item-label"><?php echo $this->lang->line('Message Sent'); ?></div>
              </div>
            </div>
          </div>
          <div class="card-icon shadow-primary bg-primary">
            <i class="fas fa-users"></i>
          </div>
          <div class="card-wrap">
            <div class="card-header">
              <h4><?php echo $this->lang->line('Total Subscribers'); ?></h4>
            </div>
            <div class="card-body" id="total_subscribers">
              <?php echo custom_number_format($total_subscribers); ?>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-4 col-sm-12">
        <div class="card card-statistic-2">
          <div class="card-chart">
            <canvas id="seven_days_subscriber_chart" height="80"></canvas>
          </div>
          <div class="card-icon shadow-primary bg-primary">
            <i class="fas fa-user-friends"></i>
          </div>
          <div class="card-wrap">
            <div class="card-header">
              <h4><?php echo $this->lang->line('Last 7 days subscribers'); ?></h4>
            </div>
            <div class="card-body">
              <?php echo custom_number_format($seven_days_subscriber_gain); ?>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-4 col-sm-12">
        <div class="card card-statistic-2">
          <div class="card-chart">
            <canvas id="hourly_subscriber_chart" height="80"></canvas>
          </div>
          <div class="card-icon shadow-primary bg-primary">
            <i class="fas fa-user-secret"></i>
          </div>
          <div class="card-wrap">
            <div class="card-header">
              <h4><?php echo $this->lang->line('24 hours interaction'); ?></h4>
            </div>
            <div class="card-body">
              <?php echo custom_number_format($hourly_subscriber_gain); ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header">
            <h4><i class="fas fa-restroom"></i> <?php echo $this->lang->line('Male vs Female Subscribers'); ?></h4>
          </div>
          <div class="card-body">
            <canvas id="male_vs_female_chart" height="134"></canvas>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card gradient-bottom">
          <div class="card-header">
            <h4><i class="fas fa-database"></i> <?php echo $this->lang->line("Subscriber's Data"); ?></h4>
            <div class="card-header-action dropdown">
              <a href="#" data-toggle="dropdown" class="btn btn-danger dropdown-toggle" id="selected_period"><?php echo $this->lang->line("This Month");?></a>
              <ul class="dropdown-menu dropdown-menu-sm dropdown-menu-right">
                <li class="dropdown-title"><?php echo $this->lang->line("Select Period");?></li>
                <li><a href="#" class="dropdown-item period_change" period="today"><?php echo $this->lang->line("Today");?></a></li>
                <li><a href="#" class="dropdown-item period_change" period="week"><?php echo $this->lang->line("Last 7 Days");?></a></li>
                <li><a href="#" class="dropdown-item period_change active" period="month"><?php echo $this->lang->line("This Month");?></a></li>
                <li><a href="#" class="dropdown-item period_change" period="year"><?php echo $this->lang->line("This Year");?></a></li>
              </ul>
            </div>
          </div>
          <div class="card-body" id="top-5-scroll">
            <div class="text-center waiting hidden" id="period_loader"><i class="fas fa-spinner fa-spin blue text-center" style="font-size: 40px;"></i></div>
            <ul class="list-unstyled list-unstyled-border" id="period_change_content">
              <li class="media">
                <img class="mr-3 rounded" width="55" src="<?php echo base_url('assets/img/icon/email.jpg'); ?>" alt="E-mail">
                <div class="media-body">
                  <div class="float-right"><div class="font-weight-600 text-muted text-small" id="total_email_gain"><?php echo number_format($combined_info['email']['total_email_gain']); ?></div></div>
                  <div class="media-title"><?php echo $this->lang->line('E-mail address gain'); ?></div>
                  <div class="mt-1">
                    <div class="budget-price">
                      <div class="budget-price-square bg-danger" id="email_male_percentage" data-width="<?php echo $combined_info['email']['male_percentage']; ?>%"></div>
                      <div class="budget-price-label" id="email_male_number"><?php echo number_format($combined_info['email']['male']); ?></div>
                    </div>
                    <div class="budget-price">
                      <div class="budget-price-square bg-primary" id="email_female_percentage" data-width="<?php echo $combined_info['email']['female_percentage']; ?>%"></div>
                      <div class="budget-price-label" id="email_female_number"><?php echo number_format($combined_info['email']['female']); ?></div>
                    </div>
                  </div>
                </div>
              </li>
              <li class="media">
                <img class="mr-3 rounded" width="55" src="<?php echo base_url('assets/img/icon/phone.png'); ?>" alt="Phone">
                <div class="media-body">
                  <div class="float-right"><div class="font-weight-600 text-muted text-small" id="total_phone_gain"><?php echo number_format($combined_info['phone']['total_phone_gain']); ?></div></div>
                  <div class="media-title"><?php echo $this->lang->line('Phone number gain'); ?></div>
                  <div class="mt-1">
                    <div class="budget-price">
                      <div class="budget-price-square bg-danger" id="phone_male_percentage" data-width="<?php echo $combined_info['phone']['male_percentage']; ?>%"></div>
                      <div class="budget-price-label" id="phone_male_number"><?php echo number_format($combined_info['phone']['male']); ?></div>
                    </div>
                    <div class="budget-price">
                      <div class="budget-price-square bg-primary" id="phone_female_percentage" data-width="<?php echo $combined_info['phone']['female_percentage']; ?>%"></div>
                      <div class="budget-price-label" id="phone_female_number"><?php echo number_format($combined_info['phone']['female']); ?></div>
                    </div>
                  </div>
                </div>
              </li>
              <li class="media">
                <img class="mr-3 rounded" width="55" src="<?php echo base_url('assets/img/icon/birthday.jpg'); ?>" alt="Birthdate">
                <div class="media-body">
                  <div class="float-right"><div class="font-weight-600 text-muted text-small" id="total_birthdate_gain"><?php echo number_format($combined_info['birthdate']['total_birthdate_gain']); ?></div></div>
                  <div class="media-title"><?php echo $this->lang->line('Birthdate gain'); ?></div>
                  <div class="mt-1">
                    <div class="budget-price">
                      <div class="budget-price-square bg-danger" id="birthdate_male_percentage" data-width="<?php echo $combined_info['birthdate']['male_percentage']; ?>%"></div>
                      <div class="budget-price-label" id="birthdate_male_number"><?php echo number_format($combined_info['birthdate']['male']); ?></div>
                    </div>
                    <div class="budget-price">
                      <div class="budget-price-square bg-primary" id="birthdate_female_percentage" data-width="<?php echo $combined_info['birthdate']['female_percentage']; ?>%"></div>
                      <div class="budget-price-label" id="birthdate_female_number"><?php echo number_format($combined_info['birthdate']['female']); ?></div>
                    </div>
                  </div>
                </div>
              </li>
              <!-- 
              <li class="media">
                <img class="mr-3 rounded" width="55" src="<?php echo base_url('assets/img/icon/locale.jpg'); ?>" alt="product">
                <div class="media-body">
                  <div class="media-title"><?php echo $this->lang->line('Location gain'); ?></div>
                  <div class="mt-1">
                    <div class="budget-price">
                      <div class="budget-price-square bg-danger" id="location_male_percentage" data-width="30%"></div>
                      <div class="budget-price-label" id="location_male_number">$9,660</div>
                    </div>
                    <div class="budget-price">
                      <div class="budget-price-square bg-primary" id="location_female_percentage" data-width="45%"></div>
                      <div class="budget-price-label" id="location_female_number">$13,972</div>
                    </div>
                  </div>
                </div>
              </li>
              -->
            </ul>
          </div>
          <div class="card-footer pt-3 d-flex justify-content-center">
            <div class="budget-price justify-content-center">
              <div class="budget-price-square bg-danger" data-width="20"></div>
              <div class="budget-price-label"><?php echo $this->lang->line('Male'); ?></div>
            </div>
            <div class="budget-price justify-content-center">
              <div class="budget-price-square bg-primary" data-width="20"></div>
              <div class="budget-price-label"><?php echo $this->lang->line('Female'); ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h4><i class="fas fa-id-card-alt"></i> <?php echo $this->lang->line('Latest Subscribers'); ?></h4>
          </div>
          <div class="card-body">
            <div class="owl-carousel owl-theme" id="products-carousel">
              <?php foreach($latest_subscriber_list as $value) : ?>
              <div>
                <div class="product-item pb-3">
                  <div class="product-image">
                    <img alt="image" src="<?php echo $value['image_path']; ?>" class="rounded-circle">
                  </div>
                  <div class="product-details">
                    <div class="product-name"><?php if($value['full_name'] != '') echo $value['full_name']; else echo $value['first_name'].' '.$value['last_name']; ?></div>
                    <div class="product-review">
                      <a style="cursor: pointer;" href="https://facebook.com/<?php echo $value['page_id']; ?>" target="_BLANK"><?php echo $value['page_name']; ?></a>
                    </div>
                    <div class="text-muted text-small"><?php echo $value['subscribed_at']; ?></div>
                    <div class="product-cta">
                      <a href="https://facebook.com<?php echo $value['link']; ?>" target="_BLANK" class="btn <?php if($value['link'] == 'disabled') echo 'btn-secondary'; else echo 'btn-primary'; ?>" <?php if($value['link'] == 'disabled') echo 'title="'.$this->lang->line('Please go to subscriber manager menu and then sync leads to get this link.').'"'; ?> <?php if($value['link'] == 'disabled') echo 'onclick="return false"'; ?> ><i class="far fa-hand-point-right"></i> <?php echo $this->lang->line('Go to Inbox'); ?></a>
                    </div>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h4><i class="fas fa-compress-arrows-alt"></i> <?php echo $this->lang->line('Subscribers from Different Sources'); ?></h4>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-sm-6">
                <ul class="list-unstyled list-unstyled-border list-unstyled-noborder mb-0">
                  <li class="media"> 
                    <img class="img-fluid mt-1" src="<?php echo base_url('assets/img/icon/checkbox.png'); ?>" alt="image" width="40">
                    <div class="media-body ml-3">
                      <div class="media-title"><?php echo $refferer_source_info['checkbox_plugin']['title']; ?></div>
                      <div class="text-small text-muted"><?php echo isset($refferer_source_info['checkbox_plugin']['subscribers']) ? number_format($refferer_source_info['checkbox_plugin']['subscribers']) : 0 ?></div>
                    </div>
                  </li>
                  <li class="media">
                    <img class="img-fluid mt-1" src="<?php echo base_url('assets/img/icon/send_to_messenger.png'); ?>" alt="image" width="40">
                    <div class="media-body ml-3">
                      <div class="media-title"><?php echo $refferer_source_info['sent_to_messenger']['title']; ?></div>
                      <div class="text-small text-muted"><?php echo isset($refferer_source_info['sent_to_messenger']['subscribers']) ? number_format($refferer_source_info['sent_to_messenger']['subscribers']) : 0 ?></div>
                    </div>
                  </li>
                  <li class="media">
                    <img class="img-fluid mt-1" src="<?php echo base_url('assets/img/icon/customer_chat_plugin.png'); ?>" alt="image" width="40">
                    <div class="media-body ml-3">
                      <div class="media-title"><?php echo $refferer_source_info['customer_chat_plugin']['title']; ?></div>
                      <div class="text-small text-muted"><?php echo isset($refferer_source_info['customer_chat_plugin']['subscribers']) ? number_format($refferer_source_info['customer_chat_plugin']['subscribers']) : 0 ?></div>
                    </div>
                  </li>
                </ul>
              </div>
              <div class="col-sm-6 mt-sm-0 mt-4">
                <ul class="list-unstyled list-unstyled-border list-unstyled-noborder mb-0">
                  <li class="media">
                    <img class="img-fluid mt-1" src="<?php echo base_url('assets/img/icon/direct.png'); ?>" alt="image" width="40">
                    <div class="media-body ml-3">
                      <div class="media-title"><?php echo $refferer_source_info['direct']['title']; ?></div>
                      <div class="text-small text-muted"><?php echo isset($refferer_source_info['direct']['subscribers']) ? number_format($refferer_source_info['direct']['subscribers']) : 0 ?></div>
                    </div>
                  </li>
                  <li class="media">
                    <img class="img-fluid mt-1" src="<?php echo base_url('assets/img/icon/auto_reply.png'); ?>" alt="image" width="40">
                    <div class="media-body ml-3">
                      <div class="media-title"><?php echo $refferer_source_info['comment_private_reply']['title']; ?></div>
                      <div class="text-small text-muted"><?php echo isset($refferer_source_info['comment_private_reply']['subscribers']) ? number_format($refferer_source_info['comment_private_reply']['subscribers']) : 0 ?></div>
                    </div>
                  </li>
                  <li class="media">
                    <img class="img-fluid mt-1" src="<?php echo base_url('assets/img/icon/me_link.png'); ?>" alt="image" width="40">
                    <div class="media-body ml-3">
                      <div class="media-title"><?php echo $refferer_source_info['me_link']['title']; ?></div>
                      <div class="text-small text-muted"><?php echo isset($refferer_source_info['me_link']['subscribers']) ? number_format($refferer_source_info['me_link']['subscribers']) : 0 ?></div>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>    
    </div>
    <div class="row">
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">
            <h4><i class="fas fa-clipboard-list"></i> <?php echo $this->lang->line('Last Auto Reply'); ?></h4>
            <div class="card-header-action">
              <a href="<?php echo base_url('comment_automation/all_auto_reply_report'); ?>" target="_BLANK" class="btn btn-danger"><?php echo $this->lang->line('View More'); ?> <i class="fas fa-chevron-right"></i></a>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive table-invoice">
              <table class="table table-striped">
                <tr>
                  <!-- <th><?php echo $this->lang->line("sl"); ?></th> -->
                  <th><?php echo $this->lang->line("reply to"); ?></th>
                  <th><?php echo $this->lang->line("reply time"); ?></th>
                  <th><?php echo $this->lang->line("Comment ID"); ?></th>
                  <th><?php echo $this->lang->line("Comment"); ?></th>
                </tr>
                <?php $sl=0; foreach($my_last_auto_reply_data as $key => $value) : $sl++; ?>
                <tr>
                  <!-- <td><?php echo $sl; ?></td> -->
                  <td class="font-weight-600"><?php echo $value["commenter_name"]; ?></td>
                  <td><?php echo date("jS M y H:i",strtotime($value["reply_time"])); ?></td>
                  <td><a target='_blank' href='https://facebook.com/<?php echo $value['comment_id']; ?>'><?php echo $value["comment_id"]; ?></a></td>
                  <td>
                    <?php echo $value["comment_text"]; ?>
                  </td>
                </tr>
                <?php 
                  endforeach; 
                  if($sl==0) echo "<tr><td class='text-center' colspan='4'>No data found.</td></tr>";
                ?>

              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
          <div class="card card-hero">
            <div class="card-header">
              <div class="card-icon">
                <i class="fas fa-mail-bulk"></i>
              </div>
              <h4><?php if(count($upcoming_message_sent_campaign_info) >= 5) echo 5; else echo count($upcoming_message_sent_campaign_info); ?></h4>
              <div class="card-description"><?php echo $this->lang->line("Upcoming Bulk Message Campaign") ?></div>
            </div>
            <div class="card-body p-0">
              <div class="tickets-list">
                <div class="yscroll" style="height: 300px;">
                  <?php $sl=0; foreach ($upcoming_message_sent_campaign_info as $value) : if($sl == 5) break; ?>              
                    <a href="#" class="ticket-item">
                      <div class="ticket-title">
                        <h4 class="no_action"><?php echo $value['campaign_name']; ?></h4>
                      </div>
                      <div class="ticket-info">

                        <div><?php echo $this->lang->line('Leads').":"; ?><?php echo $value["total_thread"]; ?></div>
                        <div class="bullet"></div>
                        <div class="text-primary no_action"><?php echo date("d M y H:i",strtotime($value["schedule_time"])); ?></div>
                      </div>
                    </a>
                  <?php $sl++; endforeach ?>
                </div>
                <a href="<?php echo base_url('messenger_bot_broadcast/conversation_broadcast_campaign'); ?>" target="_BLANK" class="ticket-item ticket-more">
                  <?php echo $this->lang->line('View All'); ?> <i class="fas fa-chevron-right"></i>
                </a>
              </div>
            </div>
          </div>
      </div>
    </div>
    <!-- <div class="row">
    	<div class="col-12">
    	  <div class="card">
    	    <div class="card-header">
    	      <h4><i class="fas fa-id-card-alt"></i> <?php echo $this->lang->line('Latest Subscribers in 24H interaction'); ?></h4>
    	    </div>
    	    <div class="card-body">
    	      <div class="owl-carousel owl-theme" id="carousel_24h">
    	        <?php foreach($latest_24hsubscriber_list as $value) : ?>
    	        <div>
    	          <div class="product-item pb-3">
    	            <div class="product-image">
    	              <img alt="image" src="<?php echo $value['image_path']; ?>" class="rounded-circle">
    	            </div>
    	            <div class="product-details">
    	              <div class="product-name"><?php if($value['full_name'] != '') echo $value['full_name']; else echo $value['first_name'].' '.$value['last_name']; ?></div>
    	              <div class="product-review">
    	                <a style="cursor: pointer;" href="https://facebook.com/<?php echo $value['page_id']; ?>" target="_BLANK"><?php echo $value['page_name']; ?></a>
    	              </div>
    	              <div class="text-muted text-small"><?php echo $value['last_subscriber_interaction_time']; ?></div>
    	              <div class="product-cta">
    	                <a href="https://facebook.com<?php echo $value['link']; ?>" target="_BLANK" class="btn btn-primary"><?php echo $this->lang->line('Detail'); ?></a>
    	              </div>
    	            </div>
    	          </div>
    	        </div>
    	        <?php endforeach; ?>
    	      </div>
    	    </div>
    	  </div>
    	</div>
    </div> -->
    <div class="row">
    	<div class="col-md-4">
    	    <div class="card card-hero">
    	      <div class="card-header">
    	        <div class="card-icon">
    	          <i class="far fa-paper-plane"></i>
    	        </div>
    	        <h4><?php if(count($recently_message_sent_completed_campaing_info) >= 5) echo 5; else echo count($recently_message_sent_completed_campaing_info); ?></h4>
    	        <div class="card-description"><?php echo $this->lang->line("Completed Bulk Message Campaign") ?></div>
    	      </div>
    	      <div class="card-body p-0">
    	        <div class="tickets-list">
    	          <div class="yscroll" style="height: 300px;">
    	            <?php $sl=0; foreach ($recently_message_sent_completed_campaing_info as $value) : if($sl == 5) break; ?>              
    	              <a href="#" class="ticket-item">
    	                <div class="ticket-title">
    	                  <h4 class="no_action"><?php echo $value['campaign_name']; ?></h4>
    	                </div>
    	                <div class="ticket-info">

    	                  <div><?php echo $this->lang->line('Sent').":"; ?><?php echo $value["successfully_sent"]; ?></div>
    	                  <div class="bullet"></div>
    	                  <div class="text-primary no_action"><?php echo date("d M y H:i",strtotime($value["added_at"])); ?></div>
    	                </div>
    	              </a>
    	            <?php $sl++; endforeach ?>
    	          </div>
    	          <a href="<?php echo base_url('messenger_bot_broadcast/conversation_broadcast_campaign'); ?>" target="_BLANK" class="ticket-item ticket-more">
    	            <?php echo $this->lang->line('View All'); ?> <i class="fas fa-chevron-right"></i>
    	          </a>
    	        </div>
    	      </div>
    	    </div>
    	</div>
    	<div class="col-md-4">
    	    <div class="card card-hero">
    	      <div class="card-header">
    	        <div class="card-icon">
    	          <i class="far fa-share-square"></i>
    	        </div>
    	        <h4><?php if(count($upcoming_post_campaign_array) >= 5) echo 5; else echo count($upcoming_post_campaign_array); ?></h4>
    	        <div class="card-description"><?php echo $this->lang->line("Upcoming Facebook Poster Campaign") ?></div>
    	      </div>
    	      <div class="card-body p-0">
    	        <div class="tickets-list">
    	          <div class="yscroll" style="height: 300px;">             
    	            <?php 
    	              $post_names = array(
    	                'image_submit' => $this->lang->line('Image Post'),
    	                'video_submit' => $this->lang->line('Video Post'),
    	                'link_submit' => $this->lang->line('Link Post'),
    	                'text_submit' => $this->lang->line('Text Post'),
    	                'carousel_post' => $this->lang->line('Carousel Post'),
    	                'slider_post' => $this->lang->line('Slider Post'),
    	                'cta_post' => $this->lang->line('CTA Post'),
    	              ); 
    	            ?>
    	            <?php $sl=0; foreach ($upcoming_post_campaign_array as $value) : if($sl == 5) break; ?>              
    	              <a href="#" class="ticket-item">
    	                <div class="ticket-title">
    	                  <h4 class="no_action"><?php echo $value['campaign_name']; ?></h4>
    	                </div>
    	                <div class="ticket-info">

    	                  <div>
    	                    <?php 
    	                      $post_type = '';
    	                      if(isset($value['post_type'])) $post_type = $post_names[$value['post_type']];
    	                      if(isset($value['cta_type'])) $post_type = $post_names['cta_post']; 
    	                      echo $post_type;
    	                    ?>                      
    	                    </div>
    	                  <div class="bullet"></div>
    	                  <div class="text-primary no_action"><?php echo date("d M y H:i",strtotime($value["schedule_time"])); ?></div>
    	                </div>
    	              </a>
    	            <?php $sl++; endforeach ?>
    	          </div>
    	          <a href="<?php echo base_url('ultrapost/index'); ?>" target="_BLANK" class="ticket-item ticket-more">
    	            <?php echo $this->lang->line('View All'); ?> <i class="fas fa-chevron-right"></i>
    	          </a>
    	        </div>
    	      </div>
    	    </div>
    	</div>
    	<div class="col-md-4">
    	    <div class="card card-hero">
    	      <div class="card-header">
    	        <div class="card-icon">
    	          <i class="far fa-share-square"></i>
    	        </div>
    	        <h4><?php if(count($recently_completed_post_array) >= 5) echo 5; else echo count($recently_completed_post_array); ?></h4>
    	        <div class="card-description"><?php echo $this->lang->line("Completed Facebook Poster Campaign") ?></div>
    	      </div>
    	      <div class="card-body p-0">
    	        <div class="tickets-list">
    	          <div class="yscroll" style="height: 300px;">
    	            <?php $sl=0; foreach ($recently_completed_post_array as $value) : if($sl == 5) break; ?>              
    	              <a href="#" class="ticket-item">
    	                <div class="ticket-title">
    	                  <h4 class="no_action"><?php echo $value['campaign_name']; ?></h4>
    	                </div>
    	                <div class="ticket-info">

    	                  <div>
    	                    <?php 
    	                      $post_type = '';
    	                      if(isset($value['post_type'])) $post_type = $post_names[$value['post_type']];
    	                      if(isset($value['cta_type'])) $post_type = $post_names['cta_post']; 
    	                      echo $post_type;
    	                    ?>                      
    	                    </div>
    	                  <div class="bullet"></div>
    	                  <div class="text-primary no_action"><?php echo date("d M y H:i",strtotime($value["last_updated_at"])); ?></div>
    	                </div>
    	              </a>
    	            <?php $sl++; endforeach ?>
    	          </div>
    	          <a href="<?php echo base_url('ultrapost/index'); ?>" target="_BLANK" class="ticket-item ticket-more">
    	            <?php echo $this->lang->line('View All'); ?> <i class="fas fa-chevron-right"></i>
    	          </a>
    	        </div>
    	      </div>
    	    </div>
    	</div>
    </div>
    
  <?php if($other_dashboard == 1) : ?>
  </div>
  <?php endif; ?>
</section>
	
<script type="text/javascript">
	$(document).on('click', '.no_action', function(event) {
	  event.preventDefault();
	});
	var myChart1; 
	$(document).ready(function() {		
    var stepsize = "<?php echo $step_size; ?>";	
		var male_vs_female_chart = document.getElementById('male_vs_female_chart').getContext('2d');
		var myChart1 = new Chart(male_vs_female_chart, {
		  type: 'line',
		  data: {
		    labels: <?php echo json_encode(array_values($male_female_date_list));?>,
		    datasets: [{
		      label: '<?php echo $this->lang->line('Female'); ?>',
		      data: <?php echo json_encode(array_values($female_subscribers));?>,
		      borderWidth: 2,
		      backgroundColor: 'rgba(63,82,227,.8)',
		      borderWidth: 0,
		      borderColor: 'transparent',
		      pointBorderWidth: 0,
		      pointRadius: 3.5,
		      pointBackgroundColor: 'transparent',
		      pointHoverBackgroundColor: 'rgba(63,82,227,.8)',
		    },
		    {
		      label: '<?php echo $this->lang->line('Male'); ?>',
		      data: <?php echo json_encode(array_values($male_subscribers));?>,
		      borderWidth: 2,
		      backgroundColor: 'rgba(254,86,83,.7)',
		      borderWidth: 0,
		      borderColor: 'transparent',
		      pointBorderWidth: 0 ,
		      pointRadius: 3.5,
		      pointBackgroundColor: 'transparent',
		      pointHoverBackgroundColor: 'rgba(254,86,83,.8)',
		    }]
		  },
		  options: {
		    legend: {
		      display: false
		    },
		    scales: {
		      yAxes: [{
		        gridLines: {
		          // display: false,
		          drawBorder: false,
		          color: '#f2f2f2',
		        },
		        ticks: {
		          beginAtZero: true,
		          stepSize: stepsize,
              // display: false,
		          // callback: function(value, index, values) {
		          //   return value;
		          // }
		        }
		      }],
		      xAxes: [{
		        gridLines: {
		          display: false,
		          tickMarkLength: 15,
		        }
		      }]
		    },
		  }
		});
	});
</script>

<script type="text/javascript">
	
  var seven_days_subscriber_chart = document.getElementById("seven_days_subscriber_chart").getContext('2d');

  var sevendays_subscriber_chart_bgcolor = seven_days_subscriber_chart.createLinearGradient(0, 0, 0, 70);
  sevendays_subscriber_chart_bgcolor.addColorStop(0, 'rgba(63,82,227,.2)');
  sevendays_subscriber_chart_bgcolor.addColorStop(1, 'rgba(63,82,227,0)');

  var myChart = new Chart(seven_days_subscriber_chart, {
    type: 'line',
    data: {
      labels: <?php echo json_encode($seven_days_subscriber_chart_label);?>,
      datasets: [{
        label: '<?php echo $this->lang->line("Subscribers");?>',
        data: <?php echo json_encode($seven_days_subscriber_chart_data) ;?>,
        backgroundColor: sevendays_subscriber_chart_bgcolor,
        borderWidth: 3,
        borderColor: 'rgba(63,82,227,1)',
        pointBorderWidth: 0,
        pointBorderColor: 'transparent',
        pointRadius: 3,
        pointBackgroundColor: 'transparent',
        pointHoverBackgroundColor: 'rgba(63,82,227,1)',
      }]
    },
    options: {
      layout: {
        padding: {
          bottom: -1,
          left: -1
        }
      },
      legend: {
        display: false
      },
      scales: {
        yAxes: [{
          gridLines: {
            display: false,
            drawBorder: false,
          },
          ticks: {
            beginAtZero: true,
            display: false
          }
        }],
        xAxes: [{
          gridLines: {
            drawBorder: false,
            display: false,
          },
          ticks: {
            display: false
          }
        }]
      },
    }
  });

  var hourly_subscriber_chart = document.getElementById("hourly_subscriber_chart").getContext('2d');

  var hourly_subscriber_chart_bgcolor = hourly_subscriber_chart.createLinearGradient(0, 0, 0, 70);
  hourly_subscriber_chart_bgcolor.addColorStop(0, 'rgba(63,82,227,.2)');
  hourly_subscriber_chart_bgcolor.addColorStop(1, 'rgba(63,82,227,0)');

  var myChart = new Chart(hourly_subscriber_chart, {
    type: 'line',
    data: {
      labels: <?php echo json_encode($hourly_subscriber_chart_label);?>,
      datasets: [{
        label: '<?php echo $this->lang->line("Subscribers");?>',
        data: <?php echo json_encode($hourly_subscriber_chart_data) ;?>,
        backgroundColor: hourly_subscriber_chart_bgcolor,
        borderWidth: 3,
        borderColor: 'rgba(63,82,227,1)',
        pointBorderWidth: 0,
        pointBorderColor: 'transparent',
        pointRadius: 3,
        pointBackgroundColor: 'transparent',
        pointHoverBackgroundColor: 'rgba(63,82,227,1)',
      }]
    },
    options: {
      layout: {
        padding: {
          bottom: -1,
          left: -1
        }
      },
      legend: {
        display: false
      },
      scales: {
        yAxes: [{
          gridLines: {
            display: false,
            drawBorder: false,
          },
          ticks: {
            beginAtZero: true,
            display: false
          }
        }],
        xAxes: [{
          gridLines: {
            drawBorder: false,
            display: false,
          },
          ticks: {
            display: false
          }
        }]
      },
    }
  });



  $("#products-carousel").owlCarousel({
    items: 3,
    margin: 10,
    autoplay: true,
    autoplayTimeout: 5000,
    loop: true,
    responsive: {
      0: {
        items: 2
      },
      768: {
        items: 2
      },
      1200: {
        items: 3
      }
    }
  });

  $("#carousel_24h").owlCarousel({
    items: 3,
    margin: 10,
    autoplay: true,
    autoplayTimeout: 5000,
    loop: true,
    responsive: {
      0: {
        items: 2
      },
      768: {
        items: 2
      },
      1200: {
        items: 3
      }
    }
  });



</script>


<script>
	$(document).ready(function() {
		
		$(document).on('click', '.month_change', function(e) {
		  e.preventDefault(); 
		  $(".month_change").removeClass('active');
		  $(this).addClass('active');
		  var month_no = $(this).attr('month_no');
		  var month_name = $(this).html();
		  $("#orders-month").html(month_name);

		  $(".month_change_middle_content").hide();
		  $("#loader").removeClass('hidden');

		  $.ajax({
		     type:'POST' ,
		     url: "<?php echo base_url('dashboard/get_first_div_content')?>",
		     data: {month_no:month_no},
		     dataType : 'JSON',
		     success:function(response)
		     {
		      	$("#loader").addClass('hidden');
		      	$("#subscribed").html(response.subscribed);
		      	$("#unsubscribed").html(response.unsubscribed);
		      	$("#total_subscribers").html(response.total_subscribers);
		      	$("#message_sent").html(response.total_message_sent);
		      	$(".month_change_middle_content").show();
		     }
		  });
		});

		$(document).on('click', '.period_change', function(e) {
		  e.preventDefault(); 
		  $(".period_change").removeClass('active');
		  $(this).addClass('active');
		  var period = $(this).attr('period');
		  var selected_period = $(this).html();
		  $("#selected_period").html(selected_period);

		  $("#period_change_content").hide();
		  $("#period_loader").removeClass('hidden');

		  $.ajax({
		     type:'POST' ,
		     url: "<?php echo base_url('dashboard/get_subscriber_data_div')?>",
		     data: {period:period},
		     dataType : 'JSON',
		     success:function(response)
		     {
		      	$("#period_loader").addClass('hidden');

		      	$("#total_email_gain").html(response.email.total_email_gain);
		      	$("#email_male_percentage").css('width',response.email.male_percentage);
		      	$("#email_male_number").html(response.email.male);
		      	$("#email_female_percentage").css('width',response.email.female_percentage);
		      	$("#email_female_number").html(response.email.female);

		      	$("#total_phone_gain").html(response.phone.total_phone_gain);
		      	$("#phone_male_percentage").css('width',response.phone.male_percentage);
		      	$("#phone_male_number").html(response.phone.male);
		      	$("#phone_female_percentage").css('width',response.phone.female_percentage);
		      	$("#phone_female_number").html(response.phone.female);

		      	$("#total_birthdate_gain").html(response.birthdate.total_birthdate_gain);
		      	$("#birthdate_male_percentage").css('width',response.birthdate.male_percentage);
		      	$("#birthdate_male_number").html(response.birthdate.male);
		      	$("#birthdate_female_percentage").css('width',response.birthdate.female_percentage);
		      	$("#birthdate_female_number").html(response.birthdate.female);

		      	$("#period_change_content").show();
		     }
		  });

		});


	});
</script>

