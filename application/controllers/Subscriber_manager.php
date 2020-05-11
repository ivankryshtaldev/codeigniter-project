<?php

require_once("Home.php"); // loading home controller

class Subscriber_manager extends Home
{

    public function __construct()
    {
        parent::__construct();
        if ($this->session->userdata('logged_in') != 1)
        redirect('home/login_page', 'location');   
        // if($this->session->userdata('user_type') != 'Admin' && !in_array(76,$this->module_access))
        // redirect('home/login_page', 'location'); 

        $function_name=$this->uri->segment(2);
        if($function_name!="" && $function_name!="index" && $function_name!="sync_subscribers" && $function_name!="bot_subscribers" && $function_name!="bot_subscribers_data" &&  $function_name!="contact_group" &&  $function_name!="contact_group_data")
        {
          if($this->session->userdata("facebook_rx_fb_user_info")==0)
          redirect('social_accounts/index','refresh');
          $this->load->library("fb_rx_login");
        }
        $this->important_feature();
        $this->member_validity();        
    }

    
    public function index()
    {
        $data['body'] = 'messenger_tools/subscriber_manager_menu_block';
        $data['page_title'] = $this->lang->line('Subscriber Manager');
        $this->_viewcontroller($data);
    }
 

    //zilani label list
    public function contact_group()
    { 
      $userid = $this->session->userdata("facebook_rx_fb_user_info");
      $page_list = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("facebook_rx_fb_user_info_id"=>$userid,'bot_enabled'=>'1')),array('page_name','id'));

      $data['page_info'] = $page_list;
      $data['body'] = 'messenger_tools/label_list';
      $data['page_title'] = $this->lang->line("Labels/Tags");
      $this->_viewcontroller($data);  
      
    }

    public function contact_group_data()
    { 
      $this->ajax_check();

      $page_id = $this->input->post('page_id',true);
      $searching = $this->input->post('searching',true);
      $display_columns = array("#","id","group_name","label_id","page_name","actions");

      $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
      $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
      $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
      $sort_index = isset($_POST['order'][0]['column']) ? strval($_POST['order'][0]['column']) : 2;
      $sort = isset($display_columns[$sort_index]) ? $display_columns[$sort_index] : 'group_name';
      $order = isset($_POST['order'][0]['dir']) ? strval($_POST['order'][0]['dir']) : 'asc';
      $order_by=$sort." ".$order;

      $where_simple = array();
      $where_simple['messenger_bot_broadcast_contact_group.deleted'] = '0';
      $where_simple['messenger_bot_broadcast_contact_group.invisible'] = '0';
      $where_simple['messenger_bot_broadcast_contact_group.user_id'] = $this->user_id;
      $where_simple['facebook_rx_fb_page_info.bot_enabled'] = "1";

      if($page_id !='') $where_simple['messenger_bot_broadcast_contact_group.page_id'] = $page_id;

      $sql = '';
      if($searching != '') $sql = "(messenger_bot_broadcast_contact_group.group_name LIKE  '%".$searching."%' OR messenger_bot_broadcast_contact_group.label_id LIKE '%".$searching."%')";
      if($sql != '') $this->db->where($sql);

      $where = array("where"=> $where_simple);

      $select = array("messenger_bot_broadcast_contact_group.*","facebook_rx_fb_page_info.page_name","facebook_rx_fb_page_info.page_id AS pageid");
      $join  = array("facebook_rx_fb_page_info"=>"messenger_bot_broadcast_contact_group.page_id=facebook_rx_fb_page_info.id,left");


      $table="messenger_bot_broadcast_contact_group";
      $info = $this->basic->get_data($table,$where,$select,$join,$limit,$start,$order_by,$group_by='group_name');

      $total_rows_array=$this->basic->count_row($table,$where,$count=$table.".id",$join,$group_by='group_name');
      $total_result=$total_rows_array[0]['total_rows'];


      for($i=0;$i<count($info);$i++) 
      {
        $info[$i]['page_name'] = "<a title='".$this->lang->line("Visit Page")."' target='_BLANK' href='https://facebook.com/".$info[$i]['pageid']."'>".$info[$i]['page_name']."</a>";

        if($info[$i]['unsubscribe'] == '1')
          $info[$i]['actions'] = "<a href='#' class='btn btn-outline-danger btn-circle disabled' title='".$this->lang->line("Delete Label")."'><i class='fas fa-trash-alt'></i></a>";
        else
          $info[$i]['actions'] = "<a href='#' class='btn btn-outline-danger btn-circle delete_label' table_id='".$info[$i]['id']."' title='".$this->lang->line("Delete Label")."'><i class='fas fa-trash-alt'></i></a>";
      }

      $data['draw'] = (int)$_POST['draw'] + 1;
      $data['recordsTotal'] = $total_result;
      $data['recordsFiltered'] = $total_result;
      $data['data'] = convertDataTableResult($info, $display_columns ,$start,$primary_key="id");

      echo json_encode($data);
    }

    
    //DEPRECATED FUNCTION FOR QUICK BROADCAST
    public function ajax_label_insert()
    {
      $this->ajax_check();

      $return = array();
      $user_id = $this->user_id;
      $group_name = trim($this->input->post("group_name",true));
      $page_id    = trim($this->input->post("selected_page_id",true));

      $getdata = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_id)));

      $page_access_token = isset($getdata[0]['page_access_token'])?$getdata[0]['page_access_token']:"";

      $response = $this->fb_rx_login->create_label($page_access_token,$group_name);
      if(isset($response['id']) && !empty($response['id']))
      { 
        $inserted_data = array(
          'user_id'=> $user_id,
          'group_name'=> $group_name,
          'label_id'=> $response['id'],
          'page_id'=> $page_id
        ); 

        if($this->basic->insert_data("messenger_bot_broadcast_contact_group",$inserted_data))
        {
          $return['status'] = "1";
          $return['message'] = $this->lang->line("Label has been created successfully.");
        }
        
      }
      if(isset($response['error']))
      {
        $return['status'] = "0";
        $return['error_message'] = $response['error'];
      }

      echo json_encode($return);
    }
    


    // public function ajax_label_insert()
    // {
    //   $this->ajax_check();

    //   $return = array();
    //   $user_id = $this->user_id;
    //   $group_name = trim($this->input->post("group_name",true));
    //   $page_id    = trim($this->input->post("selected_page_id",true));

     
    //   $inserted_data = array(
    //     'user_id'=> $user_id,
    //     'group_name'=> $group_name,
    //     'label_id'=> '',
    //     'page_id'=> $page_id
    //   ); 

    //   if(!$this->basic->is_exist("messenger_bot_broadcast_contact_group",$inserted_data))
    //   {
    //     if($this->basic->insert_data("messenger_bot_broadcast_contact_group",$inserted_data))
    //     {
    //       $return['status'] = "1";
    //       $return['message'] = $this->lang->line("Label has been created successfully.");
    //     } 
       
    //   }
    //   else
    //   {
    //       $return['status'] = "0";
    //       $return['message'] = $this->lang->line("Label is already exist.");
    //   }

    //    echo json_encode($return);
    // }



    
    //DEPRECATED FUNCTION FOR QUICK BROADCAST
    public function ajax_delete_label()
    {
      $this->ajax_check();

      $return = array();

      $primary_key = trim($this->input->post("table_id",true));

      $getdata = $this->basic->get_data("messenger_bot_broadcast_contact_group",array("where"=>array("id"=>$primary_key)));

      $page_id = isset($getdata[0]['page_id']) ? $getdata[0]['page_id']:""; //database id
      $label_id = isset($getdata[0]['label_id']) ? $getdata[0]['label_id']:"";

      $getdata = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_id)));
      $page_access_token = isset($getdata[0]['page_access_token']) ? $getdata[0]['page_access_token']:"";

      if($this->basic->is_exist("messenger_bot_broadcast_contact_group",array("unsubscribe"=>"1","id"=>$primary_key)))
      {   
        $return['status'] = 'failed';
        $return['message'] = $this->lang->line('Sorry, Unsubscribe label can not be deleted.');

      } 
      else
      {
        $response = $this->fb_rx_login->delete_label($page_access_token,$label_id);

        if(isset($response['success']) && $response['success']=='1')
        {
          $this->basic->delete_data("messenger_bot_broadcast_contact_group",array("id"=>$primary_key));
          $return['status'] = 'successfull';
          $return['message'] = $this->lang->line('Label has been deleted Successfully.');

        } else if(isset($response['error']))
        {

          $return['status'] = 'error';
          $return['error_message'] = $response['error'];

        } else
        {
          $return['status'] = 'wrong';
          $return['message'] = $this->lang->line("Something Went Wrong, please try once again.");

        }

      }

      echo json_encode($return); 
    }
    

    // public function ajax_delete_label()
    // {
    //   $this->ajax_check();
    //   $return = array();
    //   $primary_key = trim($this->input->post("table_id",true));       
    //   $this->basic->delete_data("messenger_bot_broadcast_contact_group",array("id"=>$primary_key,"user_id"=>$this->user_id));
    //   $return['status'] = 'successfull';
    //   $return['message'] = $this->lang->line('Label has been deleted successfully.');
    //   echo json_encode($return); 
    // }


    public function sync_subscribers()
    {
        $data['body'] = 'messenger_tools/sync_leads';
        $data['page_title'] = $this->lang->line('Sync Subscribers');  
        $facebook_rx_fb_user_info_id  =  $this->session->userdata('facebook_rx_fb_user_info');

        $table_name = "facebook_rx_fb_page_info";
        $where['where'] = array('facebook_rx_fb_user_info_id' => $facebook_rx_fb_user_info_id,"bot_enabled"=>"1");
        $page_list = $this->basic->get_data($table_name,$where,'','','','','page_name asc');


        $page_info = array();

        if(!empty($page_list))
        {
            $i = 1;
            $selected_page_id = $this->session->userdata('sync_subscribers_get_page_details_page_table_id');
            foreach($page_list as $value)
            {
                if($value['id'] == $selected_page_id) $page_info[0] = $value;
                else $page_info[$i] = $value;
                $i++;
            }
        }
        ksort($page_info);

        $data['page_info'] = $page_info;

        $label_id=array(''=>$this->lang->line("Label"));
        $table = 'messenger_bot_broadcast_contact_group';
        $where['where'] = array('user_id'=>$this->user_id,"invisible"=>"0");
        $info = $this->basic->get_data($table,$where);
        foreach ($info as $key => $value) {
            $result = $value['id'];
            $label_id[$result] = $value['group_name'];
        }

        $data['dropdown']=form_dropdown('label_id',$label_id,'','class="form-control select2" id="label_id" style="width:150px !important;"');  
      
        $this->_viewcontroller($data);
    }

    public function get_page_details()
    {
        $this->ajax_check();
        $page_table_id = $this->input->post('page_table_id',true);
        $facebook_rx_fb_user_info_id  =  $this->session->userdata('facebook_rx_fb_user_info');
        $this->session->set_userdata('sync_subscribers_get_page_details_page_table_id',$page_table_id);

        $table_name = "facebook_rx_fb_page_info";
        $where['where'] = array('facebook_rx_fb_user_info_id' => $facebook_rx_fb_user_info_id,'id'=>$page_table_id);
        $page_info = $this->basic->get_data($table_name,$where,'','','','','page_name asc');

        $last_lead_sync = $this->lang->line("Never Synced");
        if($page_info[0]['last_lead_sync']!='0000-00-00 00:00:00') $last_lead_sync = date_time_calculator($page_info[0]['last_lead_sync'],true);

        // $total_subscriber = 0;
        // if(!empty($page_info[0]['current_lead_count'])) $total_subscriber = custom_number_format($page_info[0]['current_lead_count']);

        // $subscribed = 0;
        // if(!empty($page_info[0]['current_subscribed_lead_count'])) $subscribed = custom_number_format($page_info[0]['current_subscribed_lead_count']);

        $unsubscribed = 0;
        if(!empty($page_info[0]['current_unsubscribed_lead_count'])) $unsubscribed = custom_number_format($page_info[0]['current_unsubscribed_lead_count']);

        $conversation_subscriber_info = $this->basic->get_data('messenger_bot_subscriber',array('where'=>array('user_id'=>$this->user_id,'page_table_id'=>$page_table_id,'client_thread_id !='=>'')),array('count(id) as total_subscriber'));
        $conversation_unavailable_info = $this->basic->get_data('messenger_bot_subscriber',array('where'=>array('user_id'=>$this->user_id,'page_table_id'=>$page_table_id,'client_thread_id !='=>'','unavailable_conversation'=>'1')),array('count(id) as unavailable_conversation'));
        $conversation_subscriber = 0;
        $conversation_unavailable = 0;
        if(isset($conversation_subscriber_info[0]['total_subscriber'])) $conversation_subscriber = custom_number_format($conversation_subscriber_info[0]['total_subscriber']);
        if(isset($conversation_unavailable_info[0]['unavailable_conversation'])) $conversation_unavailable = custom_number_format($conversation_unavailable_info[0]['unavailable_conversation']);

        $bot_subscriber_info = $this->basic->get_data('messenger_bot_subscriber',array('where'=>array('user_id'=>$this->user_id,'page_table_id'=>$page_table_id,'is_bot_subscriber'=>'1')),array('count(id) as total_subscriber'));
        $bot_unavailable_info = $this->basic->get_data('messenger_bot_subscriber',array('where'=>array('user_id'=>$this->user_id,'page_table_id'=>$page_table_id,'is_bot_subscriber'=>'1','unavailable'=>'1')),array('count(id) as unavailable'));
        $bot_subscriber = 0;
        $bot_unavailable = 0;
        if(isset($bot_subscriber_info[0]['total_subscriber'])) $bot_subscriber = custom_number_format($bot_subscriber_info[0]['total_subscriber']);
        if(isset($bot_unavailable_info[0]['unavailable'])) $bot_unavailable = custom_number_format($bot_unavailable_info[0]['unavailable']);

        $subscriber_24 = 0;
        $subscriber_24_1 = 0;
        $migrated_bot_subscriber = 0;

        date_default_timezone_set('UTC');
        $current_time = date("Y-m-d H:i:s");
        $previous_time = date("Y-m-d H:i:s",strtotime('-24 hour',strtotime($current_time)));
        $this->_time_zone_set();
        $where_simple2 = array();

        $where_simple2['messenger_bot_subscriber.last_subscriber_interaction_time <'] = $previous_time;
        $where_simple2['messenger_bot_subscriber.last_subscriber_interaction_time !='] = "0000-00-00 00:00:00";
        $where_simple2['messenger_bot_subscriber.is_24h_1_sent'] = '0';
        $where_simple2['user_id'] = $this->user_id;
        $where_simple2['page_table_id'] = $page_table_id;
        $where_simple2['unavailable'] = '0';
        $where_simple2['is_bot_subscriber'] = '1';
        $where = array('where'=>$where_simple2);

        $subscriber_24_1_info = $this->basic->get_data('messenger_bot_subscriber',$where,array('count(id) as total_subscriber'));
        if(isset($subscriber_24_1_info[0]['total_subscriber'])) $subscriber_24_1 = custom_number_format($subscriber_24_1_info[0]['total_subscriber']);


        $where = array(
            'where' => array(
                'user_id' => $this->user_id,
                'last_subscriber_interaction_time >=' => $previous_time,
                'last_subscriber_interaction_time !=' => "0000-00-00 00:00:00",
                'page_table_id' => $page_table_id,
                'unavailable' => '0',
                'is_bot_subscriber' => '1'
            )
        );
        $subscriber_24_info = $this->basic->get_data('messenger_bot_subscriber',$where,array('count(id) as total_subscriber'));
        if(isset($subscriber_24_info[0]['total_subscriber'])) $subscriber_24 = custom_number_format($subscriber_24_info[0]['total_subscriber']);

        $migrated_bot_subscriberinfo = $this->basic->get_data('messenger_bot_subscriber',array('where'=>array('user_id'=>$this->user_id,'page_table_id'=>$page_table_id,'is_imported'=>'1','is_bot_subscriber'=>'1')),array('count(id) as total_subscriber'));
        if(isset($migrated_bot_subscriberinfo[0]['total_subscriber'])) $migrated_bot_subscriber = custom_number_format($migrated_bot_subscriberinfo[0]['total_subscriber']);


        $see_list = "<a href='' fb-page-id='".$page_info[0]['page_id']."' id ='".$page_info[0]['id']."' class='btn btn-outline-info user_details_modal'><i class='fas fa-eye'></i> ".$this->lang->line("See List")."</a>";
        
        $details = "<a href='".base_url('message_manager/message_dashboard/').$page_info[0]['id']."' class='btn btn-outline-danger'><i class='fas fa-eye'></i> ".$this->lang->line("Details")."</a>";

        $scan_now = '<a href="#" id ="'.$page_info[0]['id'].'" class="btn btn-outline-primary import_data"><i class="fas fa-qrcode"></i> '.$this->lang->line("Scan Now").'</a>';

        $popover="";
        if($page_info[0]['auto_sync_lead']=="0" || $page_info[0]['auto_sync_lead']=="3")
        {
          $enable_disable = 1;
          $enable_disable_class = "auto_sync_lead_page btn-outline-warning";
          $enable_disable_text = "<i class='fas fa-check-circle'></i> ".$this->lang->line("Enable");
        }
        if($page_info[0]['auto_sync_lead']=="1")
        {
          $enable_disable = 0;
          $enable_disable_class = "btn-outline-danger disabled";
          $enable_disable_text = "<i class='fas fa-clock-o'></i> ".$this->lang->line("Queued")."...";
          $popover=' <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="'.$this->lang->line("Queued").'" data-content="'.$this->lang->line("Background scanning will be completed by multiple steps depending on total number of subscribers. Queued means it is waiting for the next step. Background scanning will scan page's inbox in background with multiple step & once all subscribers from inbox is imported, it will turn into default state again with Enable button.This option mostly used for pages that has a big subscribers list & possibly get error during Scan page inbox option").'"><i class="fas fa-info-circle"></i> </a>';
        }
        if($page_info[0]['auto_sync_lead']=="2")
        {
          $enable_disable = 1;
          $enable_disable_class = "btn-outline-warning auto_sync_lead_page";
          $enable_disable_text = "<i class='fas fa-spinner'></i> ".$this->lang->line("Force Restart");
          $popover=' <a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="'.$this->lang->line("Processing, Force Restart").'" data-content="'.$this->lang->line("Background scanning is processing. Due to any unexpected server unavailability this process can be corrupted and can run forever. If you think this is processing forever, then you can force restart it.").'"><i class="fas fa-info-circle"></i> </a>';
        }        
        if($this->session->userdata('user_type') == 'Admin' || in_array(78,$this->module_access))
        {
          $background_scan ='<a href="#" auto_sync_lead_page_id="'.$page_info[0]['id'].'" enable_disable="'.$enable_disable.'" class="btn '.$enable_disable_class.'">'.$enable_disable_text.'</a>';
          $col = 'col-6 col-md-3';
          $background_scan_content ='
          <div class="col-6 col-md-3">
            <div class="product-item pb-3">
              <div class="product-image">
                <img src="'.base_url("assets/img/icon/clock.png").'" class="img-fluid rounded-circle">
              </div>
              <div class="product-details">
                <div class="product-name">'.$this->lang->line("Background Scanning").' '.$popover.'</div>                      
                <div class="product-cta">
                  '.$background_scan.'
                </div>
              </div>
            </div>
          </div>';
        }
        else
        {
          $col = 'col-6 col-md-4';
          $background_scan_content = "";
        }  

        $middle_column_content = '
            <div class="card main_card">
                <div class="card-header">
                  <h4 class="full_width">
                    <i class="fab fa-facebook-square"></i> <a href="https://facebook.com/'.$page_info[0]['page_id'].'" target="_BLANK">'.$page_info[0]['page_name'].'</a> <i class="fas fa-info-circle subscriber_info_modal"></i>
                    <code class="float-right" data-toggle="tooltip" title="'.$this->lang->line('Last Scanned').'"><i class="far fa-clock"> '.$last_lead_sync.'</i></code>
                  </h4>
                </div>
                <div class="card-body">
                  <br>
                  <div class="row">
                    <div class="col-md-4 col-12">
                      <div class="card card-statistic-1">
                        <div class="card-icon bg-body">
                          <i class="far fa-user-circle text-info"></i>
                        </div>
                        <div class="card-wrap">
                          <div class="card-header">
                            <h4>'. $this->lang->line("Conversation Subscriber").'</h4>
                          </div>
                          <div class="card-body">
                            '.$conversation_subscriber.'<span class="red" data-toggle="tooltip" data-placement="bottom" title="'.$this->lang->line('Unavailable').'"> ('.$conversation_unavailable.')</span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4 col-12">
                      <div class="card card-statistic-1">
                        <div class="card-icon bg-body">
                          <i class="fas fa-user-astronaut text-primary"></i>
                        </div>
                        <div class="card-wrap">
                          <div class="card-header">
                            <h4>'.$this->lang->line("Bot Subscriber").'</h4>
                          </div>
                          <div class="card-body">
                            '.$bot_subscriber.'<span class="red" data-toggle="tooltip" data-placement="bottom" title="'.$this->lang->line('Unavailable').'"> ('.$bot_unavailable.')</span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4 col-12">
                      <div class="card card-statistic-1">
                        <div class="card-icon bg-body">
                          <i class="fas fa-ban text-warning"></i>
                        </div>
                        <div class="card-wrap">
                          <div class="card-header">
                            <h4>'.$this->lang->line("Unsubscribed").'</h4>
                          </div>
                          <div class="card-body">
                            '.$unsubscribed.'
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row" style="margin-top: 10px;">
                    <div class="col-md-4 col-12">
                      <div class="card card-statistic-1">
                        <div class="card-icon bg-body">
                          <i class="fas fa-user-clock text-info"></i>
                        </div>
                        <div class="card-wrap">
                          <div class="card-header">
                            <h4>'. $this->lang->line("24H Interaction Subscriber").'</h4>
                          </div>
                          <div class="card-body">
                            '.$subscriber_24.'
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4 col-12">
                      <div class="card card-statistic-1">
                        <div class="card-icon bg-body">
                          <i class="fas fa-user-plus text-primary"></i>
                        </div>
                        <div class="card-wrap">
                          <div class="card-header">
                            <h4>'.$this->lang->line("24+1 Eligible Subscriber").'</h4>
                          </div>
                          <div class="card-body">
                            '.$subscriber_24_1.'
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4 col-12">
                      <div class="card card-statistic-1">
                        <div class="card-icon bg-body">
                          <i class="far fa-clone text-warning"></i>
                        </div>
                        <div class="card-wrap">
                          <div class="card-header">
                            <h4>'.$this->lang->line("Migrated BOT Subscriber").'</h4>
                          </div>
                          <div class="card-body">
                            '.$migrated_bot_subscriber.'
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row margin-top-50">
                    <div class="'.$col.'">
                      <div class="product-item pb-3">
                        <div class="product-image">
                          <img src="'.base_url("assets/img/icon/user.png").'" class="img-fluid rounded-circle">
                        </div>
                        <div class="product-details">
                          <div class="product-name">'.$this->lang->line("Subscriber List").'</div>                      
                          <div class="product-cta">
                            '.$see_list.'
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="'.$col.'">
                      <div class="product-item pb-3">
                        <div class="product-image">
                          <img src="'.base_url("assets/img/icon/scan.png").'" class="img-fluid rounded-circle">
                        </div>
                        <div class="product-details">
                          <div class="product-name">'.$this->lang->line("Scan Page Inbox").'</div>                      
                          <div class="product-cta">
                           '.$scan_now.'
                          </div>
                        </div>
                      </div>
                    </div>
                    '.$background_scan_content.'
                    <div class="'.$col.'">
                      <div class="product-item pb-3">
                        <div class="product-image">
                          <img src="'.base_url("assets/img/icon/comments.png").'" class="img-fluid rounded-circle">
                        </div>
                        <div class="product-details">
                          <div class="product-name">'.$this->lang->line("Latest Conversations").'</div>                      
                          <div class="product-cta">
                            '.$details.'
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
            </div>';
          $middle_column_content .='
          <script>
          $(\'[data-toggle="tooltip"]\').tooltip(); 
          $(\'[data-toggle="popover"]\').popover(); 
          $(\'[data-toggle="popover"]\').on("click", function(e) {e.preventDefault(); return true;});
          </script>
          ';     

        $response['middle_column_content'] = $middle_column_content;
        echo json_encode($response);
    }

    public function lead_list_data()
    { 
        $this->ajax_check();

        $page_id = $this->input->post("page_id");
        $search_value = $this->input->post("search_value");
        $label_id = $this->input->post("label_id");
        $display_columns = array("#","CHECKBOX",'subscribe_id','full_name','label_names','client_thread_id','actions','subscribed_at' );
        $search_columns = array('first_name','last_name','full_name','subscribe_id');

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $sort_index = isset($_POST['order'][0]['column']) ? strval($_POST['order'][0]['column']) : 7;
        $sort = isset($display_columns[$sort_index]) ? $display_columns[$sort_index] : 'id';
        $order = isset($_POST['order'][0]['dir']) ? strval($_POST['order'][0]['dir']) : 'desc';
        $order_by=$sort." ".$order;

        $where_custom="user_id = ".$this->user_id." AND page_table_id = ".$page_id;

        if ($search_value != '') 
        {
            foreach ($search_columns as $key => $value) 
            $temp[] = $value." LIKE "."'%$search_value%'";
            $imp = implode(" OR ", $temp);
            $where_custom .=" AND (".$imp.") ";
        }
        if($label_id!="") 
        {
            $this->db->where("FIND_IN_SET('$label_id',messenger_bot_subscriber.contact_group_id) !=", 0);
        }
          
        $table="messenger_bot_subscriber";
        $this->db->where($where_custom);
        $info=$this->basic->get_data($table,$where='',$select='',$join='',$limit,$start,$order_by,$group_by='');
        
        $this->db->where($where_custom);
        if($label_id!="") 
        {
            $this->db->where("FIND_IN_SET('$label_id',messenger_bot_subscriber.contact_group_id) !=", 0);
        }
        $total_rows_array=$this->basic->count_row($table,$where='',$count=$table.".id",$join='',$group_by='');

        $total_result=$total_rows_array[0]['total_rows'];

        $table = 'messenger_bot_broadcast_contact_group';
        $select = array('group_name','id');
        $where_group['where'] = array('user_id'=>$this->user_id);
        $contact_group_info = $this->basic->get_data($table,$where_group,$select);
        $contact_group_info_formatted = array();
        foreach ($contact_group_info as $key => $value) 
        {
          $contact_group_info_formatted[$value['id']] = $value['group_name'];
        }

        foreach($info as $key => $value) 
        {
            $contact_group_id = $info[$key]['contact_group_id'];
            $exp = explode(",",$contact_group_id);

            $str = '';
            foreach ($exp as $value1)
            {
               if(isset($contact_group_info_formatted[$value1]))
               $str.= $contact_group_info_formatted[$value1].", ";
            }
               
            $str = trim($str);
            $str = trim($str, ",");

            $info[$key]['label_names']= $str;
            $info[$key]['subscribed_at']= date("jS M y H:i",strtotime($info[$key]["subscribed_at"]));
            
            $view_conversation= "<a title='".$this->lang->line("Conversation")."' class='btn btn-outline-info btn-circle' target='_BLANK' href='https://facebook.com/".$info[$key]['link']."' data-toggle='tooltip' data-placement='bottom'><i class='fas fa-comments'></i></a>";

            if($info[$key]['permission'] == '1')
            {
                $status = "<span class='subscribe_unsubscribe_container'><a data-toggle='tooltip' data-placement='bottom' title='".$this->lang->line("Unsubscribe")."' id ='".$info[$key]['id']."-".$info[$key]['permission']."'  class='btn btn-danger btn-circle client_thread_subscribe_unsubscribe'  href=''><i class='fas fa-user-times'></i></a></div>";
            }
            else
            {
                 $status = "<span class='subscribe_unsubscribe_container'><a data-toggle='tooltip' data-placement='bottom' title='".$this->lang->line("Subscribe")."' id ='".$info[$key]['id']."-".$info[$key]['permission']."'  class='btn btn-primary btn-circle client_thread_subscribe_unsubscribe'  href=''><i class='fas fa-user-check'></i></i></a></div>";
            }

            $info[$key]['actions'] = "<div style='min-width:90px'>".$view_conversation." ".$status."</div>";
        }

        $data['draw'] = (int)$_POST['draw'] + 1;
        $data['recordsTotal'] = $total_result;
        $data['recordsFiltered'] = $total_result;
        $data['data'] = convertDataTableResult($info, $display_columns ,$start,$primary_key="id");
        echo json_encode($data);
    }   

 
    public function import_lead_action(){

        $facebook_rx_fb_page_info_id = $this->input->post('id');
        $scan_limit = $this->input->post('scan_limit');
        $folder = $this->input->post('folder');

        $table_name = "facebook_rx_fb_page_info";
        $where['where'] = array('id' => $facebook_rx_fb_page_info_id);
        $facebook_rx_fb_page_info = $this->basic->get_data($table_name,$where);
        $get_concersation_info = $this->fb_rx_login->get_all_conversation_page($facebook_rx_fb_page_info[0]['page_access_token'],$facebook_rx_fb_page_info[0]['page_id'],0,$scan_limit,$folder);

        if(isset($get_concersation_info['error'])){

            $response =array();
            $response["message"] = "<i class='fa fa-warning' style='color:red'></i> Error: ".$get_concersation_info['error_msg'];
            $response["status"] = '1';
            $response["count"] = 0;
            echo json_encode($response);
            exit; 
        }

        $success = 0;
        $total=0;

        $facebook_rx_fb_user_info_id = $facebook_rx_fb_page_info[0]['facebook_rx_fb_user_info_id']; 
        $db_page_id =  $facebook_rx_fb_page_info[0]['page_id'];
        $db_user_id =  $facebook_rx_fb_page_info[0]['user_id'];

        foreach($get_concersation_info as &$item) 
        {           
            $db_client_id  =  isset($item['id']) ? $item['id'] : "";
            $db_client_thread_id  =  isset($item['thead_id']) ? $item['thead_id']: "" ;

            $lead_name= isset($item['name']) ? $item['name']: "" ;

            if($db_client_id=="") continue;     

            $insert_name=0;


            if($lead_name != 'Facebook User')
                $insert_name=1;

            $db_client_name  =  $this->db->escape($lead_name);
            $link = isset($item['link']) ? $item['link']: "" ;

            $db_permission  =  '1';

            $subscribed_at = date("Y-m-d H:i:s");
            if($insert_name)
            {                
                 $sql="INSERT INTO messenger_bot_subscriber (page_table_id,page_id,user_id,client_thread_id,subscribe_id,full_name,permission,subscribed_at,link,is_imported,is_updated_name,is_bot_subscriber) 
                VALUES ('$facebook_rx_fb_page_info_id','$db_page_id',$db_user_id,'$db_client_thread_id','$db_client_id',$db_client_name,'$db_permission','$subscribed_at','$link','1','1','0')
                ON DUPLICATE KEY UPDATE client_thread_id =  '$db_client_thread_id',link='$link',full_name=$db_client_name";

                $this->basic->execute_complex_query($sql);
               
                if($this->db->affected_rows() != 0) $success++ ;
                $total++;
            }

        }
        
        $sql = "SELECT count(id) as permission_count FROM `messenger_bot_subscriber` WHERE page_table_id='$facebook_rx_fb_page_info_id' AND permission='1' AND user_id=".$this->user_id;
        $count_data = $this->db->query($sql)->row_array();

        $sql2 = "SELECT count(id) as permission_count FROM `messenger_bot_subscriber` WHERE page_table_id='$facebook_rx_fb_page_info_id' AND permission='0' AND user_id=".$this->user_id;
        $count_data2 = $this->db->query($sql2)->row_array();

        // how many are subscribed and how many are unsubscribed
        $subscribed = isset($count_data["permission_count"]) ? $count_data["permission_count"] : 0;
        $unsubscribed = isset($count_data2["permission_count"]) ? $count_data2["permission_count"] : 0;
        $current_lead_count=$subscribed+$unsubscribed;

        $this->basic->update_data("facebook_rx_fb_page_info",array("id"=>$facebook_rx_fb_page_info_id,"facebook_rx_fb_user_info_id"=>$facebook_rx_fb_user_info_id),array("current_subscribed_lead_count"=>$subscribed,"current_unsubscribed_lead_count"=>$unsubscribed,"last_lead_sync"=>date("Y-m-d H:i:s"),"current_lead_count"=>$current_lead_count));
        
        $str = "$success"." ".$this->lang->line(" subscribers has been imported successfully.");
    
        $response =array();
        $response["message"] = $str;
        $response["status"] = '1';
        $response["count"] = $success;

        echo json_encode($response);
    }


    public function download_full($user_id_and_page_info)
    {        
        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                echo "<div class='alert alert-danger text-center'><i class='fa fa-ban'></i> This function is disabled from admin account in this demo!!</div>";
                exit();
            }
        }

        $user_id_and_page_id = explode("-",$user_id_and_page_info);
        $user_id = $user_id_and_page_id[0];
        $page_id = $user_id_and_page_id[1];

        $where['where'] = array('user_id' => $this->user_id,'page_table_id' => $page_id); 

        $info = $this->basic->get_data('messenger_bot_subscriber',$where);

        $info_count = count($info);

        for($i=0; $i<$info_count; $i++)
        {
              $value = $info[$i]['contact_group_id'];
              $type_id = explode(",",$value);

              $table = 'messenger_bot_broadcast_contact_group';
              $select = array('group_name');

              $where_group['where_in'] = array('id'=>$type_id);
              $where_group['where'] = array('deleted'=>'0');

              $info1 = $this->basic->get_data($table,$where_group,$select);

              $str = '';
             foreach ($info1 as  $value1)
              {
                $str.= $value1['group_name'].","; 
              }
                
            $str = trim($str, ",");
            $info[$i]['contact_group_name']= $str;
        }

        $filename="exported_subscriber_list_".time()."_".$this->user_id.".csv";
        $f = fopen('php://memory', 'w');
        fputs( $f, "\xEF\xBB\xBF" );
        $head=array("Subscriber ID","Page ID","Label IDs","Labels","First Name","Last Name","Full Name","Subscribed at","Status");
        fputcsv($f,$head, ",");

        foreach ($info as  $value) 
        {
            $write_info=array();            
            $write_info[] = $value['subscribe_id'];
            $write_info[] = $value['page_id'];
            $write_info[] = $value['contact_group_id'];
            $write_info[] = $value['contact_group_name'];
            $write_info[] = $value['first_name'];
            $write_info[] = $value['last_name'];
            $write_info[] = $value['full_name'];
            // $write_info[] = $value['gender'];
            // $write_info[] = $value['locale'];
            // $write_info[] = $value['timezone'];
            $write_info[] = $value['subscribed_at'];          
            $write_info[] = $value['status'];          
            fputcsv($f, $write_info,',');  
        }

        fseek($f, 0);
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'";');
        fpassthru($f);         
    }

    
    //DEPRECATED FUNCTION FOR QUICK BROADCAST
    public function client_subscribe_unsubscribe_status_change()
    {
        $this->ajax_check();
        if(empty($_POST['client_subscribe_unsubscribe_status'])) die();

        $client_subscribe_unsubscribe = array();
        $post_val=$this->input->post('client_subscribe_unsubscribe_status');
        $subscriber_details_page=$this->input->post('subscriber_details_page'); // if 1 means called from subscriber action page
        $client_subscribe_unsubscribe = explode("-",$post_val);
        $id = isset($client_subscribe_unsubscribe[0]) ? $client_subscribe_unsubscribe[0]: 0;
        $current_status =  isset($client_subscribe_unsubscribe[1]) ? $client_subscribe_unsubscribe[1]: 0;
        
        if($current_status=="1") $permission="0";
        else $permission="1";

        $client_thread_info = $this->basic->get_data('messenger_bot_subscriber',array('where'=>array('id'=>$id,'user_id'=>$this->user_id)));
        $client_thread_id = $client_thread_info[0]['client_thread_id'];
        $page_id = $client_thread_info[0]['page_id'];
        $page_table_id = $client_thread_info[0]['page_table_id'];
        $subscriber_id = $client_thread_info[0]['subscribe_id'];
        $contact_group_id = $client_thread_info[0]['contact_group_id'];


        $where = array
        (
            'id' => $id,
            'user_id' => $this->user_id
        );
        $login_user_id = $this->user_id;
        $data = array('permission' => $permission);
        if($permission=="0") $data["unsubscribed_at"] = date("Y-m-d H:i:s");
        $response='';

        // messenger bot label data block
        $page_access_token = $label_id = $label_auto_id ='';
        $new_label_id = $contact_group_id;
        $new_label_names = "";
        $label_id_names = array(); // assoc array
        $page_info = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_table_id,"bot_enabled"=>"1","user_id"=>$this->user_id)));
        if(isset($page_info[0]))
        {
          $page_access_token = $page_info[0]["page_access_token"];
          $label_info = $this->basic->get_data("messenger_bot_broadcast_contact_group",array("where"=>array("page_id"=>$page_table_id,"user_id"=>$this->user_id)));
          
          foreach ($label_info as $key => $value) 
          {
            if($value['unsubscribe']=='1')
            {
              $label_id = $value['label_id'];
              $label_auto_id = $value['id'];
            }
            $label_id_names[$value['id']] = $value['group_name'];
          }
        }

        if($permission==0)
        {
          $explode=explode(',', $contact_group_id);
          array_push($explode, $label_auto_id);
          $new=array_unique($explode);
          $new_label_id=implode(',', $new);
          $new_label_id=trim($new_label_id,',');
        }
        else
        { 
          $explode=explode(',', $contact_group_id);                
          foreach(array_keys($explode, $label_auto_id) as $key) unset($explode[$key]);
          $new=array_unique($explode);
          $new_label_id=implode(',', $new);
          $new_label_id=trim($new_label_id,',');
        }
        $data["contact_group_id"] = $new_label_id;   

        $temp=array();
        $new_label_id_exp = explode(',', $new_label_id);
    
        foreach ($new_label_id_exp as $key => $value) 
        {
          if(isset($label_id_names[$value])) $temp[] = $label_id_names[$value];
        }
        $new_label_names = implode(',', $temp);    
        // messenger bot label data block

        $response =array('button'=>'','label'=>$new_label_names,'status'=>'0','message'=>$this->lang->line("Something went wrong, please try again."));
        if($this->basic->update_data('messenger_bot_subscriber', $where, $data))
        {    
            if($permission=="0")
            {
                // assign bot label unsubscribe              
                if($page_access_token!="" && $label_id!="")
                {
                  $this->fb_rx_login->assign_label($page_access_token,$subscriber_id,$label_id);    
                }

                $response['button'] = "<a href='' id ='".$id."-".$permission."' title='".$this->lang->line("Subscribe")."' class='client_thread_subscribe_unsubscribe btn btn-circle btn-primary'><i class='fas fa-user-check'></i></a>";
                $response['button2'] ='<span class="subsribe_unsubscribe_container"><a class="text-primary">'.$this->lang->line("Unsubscribed").'</a> <a class="text-muted pointer client_thread_subscribe_unsubscribe" id="'.$id."-".$permission.'">('.$this->lang->line("Subscribe").')</a></span>'; // called from subscriber action page
                $response['message'] = $this->lang->line("Subscriber has been unsubscribed successfully.");
                $response['status'] = "1";
                $this->basic->execute_complex_query("UPDATE facebook_rx_fb_page_info SET current_subscribed_lead_count = current_subscribed_lead_count-1,current_unsubscribed_lead_count = current_unsubscribed_lead_count+1 WHERE user_id = '$login_user_id' AND page_id = '$page_id'");
            }
            else  
            {
                // deassign bot label unsubscribe
                if($page_access_token!="" && $label_id!="")
                {
                  $this->fb_rx_login->deassign_label($page_access_token,$subscriber_id,$label_id);
                }

                $response['button'] = "<a href='' id ='".$id."-".$permission."' title='".$this->lang->line("Unsubscribe")."' class='client_thread_subscribe_unsubscribe btn btn-circle btn-danger'><i class='fas fa-user-times'></i></a>";
                $response['button2'] ='<span class="subsribe_unsubscribe_container"><a class="text-primary">'.$this->lang->line("Subscribed").'</a> <a class="text-muted pointer client_thread_subscribe_unsubscribe" id="'.$id."-".$permission.'">('.$this->lang->line("Unsubscribe").')</a></span>'; // called from subscriber action page
    
                $response['message'] = $this->lang->line("Subscriber has been subscribed back successfully.");
                $response['status'] = "1";
                $this->basic->execute_complex_query("UPDATE facebook_rx_fb_page_info SET current_subscribed_lead_count = current_subscribed_lead_count+1,current_unsubscribed_lead_count = current_unsubscribed_lead_count-1 WHERE user_id = '$login_user_id' AND page_id = '$page_id'");
            }
            echo json_encode($response);
        }
    }
    

    // public function client_subscribe_unsubscribe_status_change()
    // {
    //     $this->ajax_check();
    //     if(empty($_POST['client_subscribe_unsubscribe_status'])) die();
        
    //     $client_subscribe_unsubscribe = array();
    //     $post_val=$this->input->post('client_subscribe_unsubscribe_status');
    //     $subscriber_details_page=$this->input->post('subscriber_details_page'); // if 1 means called from subscriber action page
    //     $client_subscribe_unsubscribe = explode("-",$post_val);
    //     $id = isset($client_subscribe_unsubscribe[0]) ? $client_subscribe_unsubscribe[0]: 0;
    //     $current_status =  isset($client_subscribe_unsubscribe[1]) ? $client_subscribe_unsubscribe[1]: 0;
        
    //     if($current_status=="1") $permission="0";
    //     else $permission="1";  

    //     $client_thread_info = $this->basic->get_data('messenger_bot_subscriber',array('where'=>array('id'=>$id,'user_id'=>$this->user_id)));
    //     $client_thread_id = $client_thread_info[0]['client_thread_id'];
    //     $page_id = $client_thread_info[0]['page_id'];
    //     $page_table_id = $client_thread_info[0]['page_table_id'];

    //     $where = array
    //     (
    //         'id' => $id,
    //         'user_id' => $this->user_id
    //     );
    //     $login_user_id = $this->user_id;
    //     $data = array('permission' => $permission);
    //     if($permission=="0") $data["unsubscribed_at"] = date("Y-m-d H:i:s");
    //     $response='';
    

    //     $response =array('button'=>'','label'=>'','status'=>'0','message'=>$this->lang->line("Something went wrong, please try again."));
    //     if($this->basic->update_data('messenger_bot_subscriber', $where, $data))
    //     {    
    //         if($permission=="0")
    //         {                
    //             $response['button'] = "<a href='' id ='".$id."-".$permission."' title='".$this->lang->line("Subscribe")."' class='client_thread_subscribe_unsubscribe btn btn-circle btn-primary'><i class='fas fa-user-check'></i></a>";
    //             $response['button2'] ='<span class="subsribe_unsubscribe_container"><a class="text-primary">'.$this->lang->line("Unsubscribed").'</a> <a class="text-muted pointer client_thread_subscribe_unsubscribe" id="'.$id."-".$permission.'">('.$this->lang->line("Subscribe").')</a></span>'; // called from subscriber action page
    //             $response['message'] = $this->lang->line("Subscriber has been unsubscribed successfully.");
    //             $response['status'] = "1";
    //             $this->basic->execute_complex_query("UPDATE facebook_rx_fb_page_info SET current_subscribed_lead_count = current_subscribed_lead_count-1,current_unsubscribed_lead_count = current_unsubscribed_lead_count+1 WHERE user_id = '$login_user_id' AND page_id = '$page_id'");
    //         }
    //         else  
    //         {
    //             $response['button'] = "<a href='' id ='".$id."-".$permission."' title='".$this->lang->line("Unsubscribe")."' class='client_thread_subscribe_unsubscribe btn btn-circle btn-danger'><i class='fas fa-user-times'></i></a>";
    //             $response['button2'] ='<span class="subsribe_unsubscribe_container"><a class="text-primary">'.$this->lang->line("Subscribed").'</a> <a class="text-muted pointer client_thread_subscribe_unsubscribe" id="'.$id."-".$permission.'">('.$this->lang->line("Unsubscribe").')</a></span>'; // called from subscriber action page
    
    //             $response['message'] = $this->lang->line("Subscriber has been subscribed back successfully.");
    //             $response['status'] = "1";
    //             $this->basic->execute_complex_query("UPDATE facebook_rx_fb_page_info SET current_subscribed_lead_count = current_subscribed_lead_count+1,current_unsubscribed_lead_count = current_unsubscribed_lead_count-1 WHERE user_id = '$login_user_id' AND page_id = '$page_id'");
    //         }
    //         echo json_encode($response);
    //     }
    // }



    public function enable_disable_auto_sync()
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(78,$this->module_access))
        redirect('home/login_page', 'location'); 
    
        $page_id =  $this->input->post("page_id");
        $operation =  $this->input->post("operation");
        if($page_id=="" || $operation=="") exit();

        $this->basic->update_data("facebook_rx_fb_page_info",array("id"=>$page_id,"user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$this->session->userdata("facebook_rx_fb_user_info")),array("auto_sync_lead"=>$operation,"next_scan_url"=>""));


    }


    public function bot_subscribers($auto_selected_subscriber=0,$auto_selected_page=0)
    {
      $page_info = array();
      $page_list = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$this->session->userdata("facebook_rx_fb_user_info"),"bot_enabled"=>"1")));
   
      foreach($page_list as $value)
      {
          $page_info[$value['id']] = $value['page_name'];
      }
      
      $page_info[''] = $this->lang->line("Page");
      $data['page_info'] = $page_info;

      $data['body'] = 'messenger_tools/bot_subscribers';
      $data['page_title'] = $this->lang->line('Bot Subscribers');
      $data['auto_selected_subscriber'] = $auto_selected_subscriber; // used for showing single subscriber data
      $data['auto_selected_page'] = $auto_selected_page; // used for showing single page data
      if($this->is_webview_exist)
        $data['webview_access'] = 'yes';
      else
        $data['webview_access'] = 'no';

      $this->_viewcontroller($data);
    }


    public function bot_subscribers_data()
    { 
        $this->ajax_check();
        $this->session->unset_userdata("bot_subscribers_sql");

        $search_value = $this->input->post("search_value");
        $page_id = $this->input->post("page_id");
        $label_id = $this->input->post("label_id");
        $display_columns = 
        array(
          "#",
          "CHECKBOX",
          'image_path',
          'page_name',
          'subscribe_id',
          'first_name',
          'last_name',
          'actions',
          'gender',
          'label_names',
          'client_thread_id',
          'subscribed_at'
        );
        $search_columns = array('first_name','last_name','full_name','subscribe_id','gender');

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $sort_index = isset($_POST['order'][0]['column']) ? strval($_POST['order'][0]['column']) : 11;
        $sort = isset($display_columns[$sort_index]) ? $display_columns[$sort_index] : 'subscribed_at';
        $order = isset($_POST['order'][0]['dir']) ? strval($_POST['order'][0]['dir']) : 'desc';
        $order_by=$sort." ".$order;

        $where_custom="messenger_bot_subscriber.user_id = ".$this->user_id." AND is_bot_subscriber='1' AND facebook_rx_fb_user_info_id = ".$this->session->userdata('facebook_rx_fb_user_info');

        if ($search_value != '') 
        {
            foreach ($search_columns as $key => $value) 
            $temp[] = $value." LIKE "."'%$search_value%'";
            $imp = implode(" OR ", $temp);
            $where_custom .=" AND (".$imp.") ";
        }

        if($page_id!="") $this->db->where("page_table_id", $page_id);
        if($label_id!="") $this->db->where("FIND_IN_SET('$label_id',messenger_bot_subscriber.contact_group_id) !=", 0);       

        $join = array('facebook_rx_fb_page_info'=>"facebook_rx_fb_page_info.id=messenger_bot_subscriber.page_table_id,left");          
        $table="messenger_bot_subscriber";
        $select = "messenger_bot_subscriber.*,page_name";
        $this->db->where($where_custom);
        $info=$this->basic->get_data($table,$where='',$select,$join,$limit,$start,$order_by,$group_by='');
         
        // for download result 
        $bot_subscribers_sql = array("table"=>$table,"where_custom"=>$where_custom,"select"=>$select,"join"=>$join,"order_by"=>$order_by);
        $bot_subscribers_sql["where"]="";
        if($page_id!="") $bot_subscribers_sql["page_table_id"] = $page_id;
        if($label_id!="") $bot_subscribers_sql["where"] = array("FIND_IN_SET('$label_id',messenger_bot_subscriber.contact_group_id) !=", 0);
        $this->session->set_userdata("bot_subscribers_sql",$bot_subscribers_sql);
        // for download result 
        
        $this->db->where($where_custom);
        if($page_id!="") $this->db->where("page_table_id", $page_id);
        if($label_id!="") $this->db->where("FIND_IN_SET('$label_id',messenger_bot_subscriber.contact_group_id) !=", 0);
        $total_rows_array=$this->basic->count_row($table,$where='',$count=$table.".id",$join,$group_by='');

        $total_result=$total_rows_array[0]['total_rows'];

        /*This block is commeneted because we are not showing labels in datatable for speed issue*/
        // $table = 'messenger_bot_broadcast_contact_group';
        // $select = array('group_name','id');
        // $where_group['where'] = array('user_id'=>$this->user_id);
        // $contact_group_info = $this->basic->get_data($table,$where_group,$select);
        // $contact_group_info_formatted = array();
        // foreach ($contact_group_info as $key => $value) 
        // {
        //   $contact_group_info_formatted[$value['id']] = $value['group_name'];
        // }

        foreach($info as $key => $value) 
        {
            // $contact_group_id = $info[$key]['contact_group_id'];
            // $exp = explode(",",$contact_group_id);
            // $str = '';
            // foreach ($exp as $value1)
            // {
            //    if(isset($contact_group_info_formatted[$value1]))
            //    $str.= $contact_group_info_formatted[$value1].", ";
            // }               
            // $str = trim($str);
            // $str = trim($str, ",");
            // $info[$key]['label_names']= $str;

            $info[$key]['label_names']= "";

            $info[$key]['subscribed_at']= date("jS M y H:i",strtotime($info[$key]["subscribed_at"]));            
     
            $info[$key]['actions'] = "<a  data-id='".$info[$key]['id']."' data-subscribe-id='".$info[$key]['subscribe_id']."' data-page-id='".$info[$key]['page_table_id']."' class='btn btn-outline-primary btn-circle subscriber_actions_modal'  href=''><i class='fas fa-briefcase'></i></a>";

            $info[$key]['page_name'] = "<a target='_BLANK' href='https://facebook.com/".$info[$key]['page_id']."'>".$info[$key]['page_name'] ."</a>";

            $profile_pic = ($value['profile_pic']!="") ? "<img class='rounded-circle' style='height:40px;width:40px;' src='".$value["profile_pic"]."'>" :  "<img class='rounded-circle' style='height:40px;width:40px;' src='".base_url('assets/img/avatar/avatar-1.png')."'>";
            $info[$key]['image_path']=($value["image_path"]!="") ? "<a  target='_BLANK' href='".base_url($value["image_path"])."'><img class='rounded-circle' style='height:40px;width:40px;' src='".base_url($value["image_path"])."'></a>" : $profile_pic;

            if($info[$key]['gender'] == "male") $info[$key]['gender'] ="<i class='fas fa-male blue' style='font-size:22px;' title='".$this->lang->line('Male')."' data-toggle='tooltip' data-placement='bottom'></i>";
            else if($info[$key]['gender'] == "female") $info[$key]['gender'] ="<i class='fas fa-female purple' style='font-size:22px;' title='".$this->lang->line('Female')."' data-toggle='tooltip' data-placement='bottom'></i>";
        }

        $data['draw'] = (int)$_POST['draw'] + 1;
        $data['recordsTotal'] = $total_result;
        $data['recordsFiltered'] = $total_result;
        $data['data'] = convertDataTableResult($info, $display_columns ,$start,$primary_key="id");
        echo json_encode($data);
    }


    public function get_label_dropdown()
    {
      $this->ajax_check();
      $page_id=$this->input->post('page_id');// database id

      $table_type = 'messenger_bot_broadcast_contact_group';
      $where_type['where'] = array('user_id'=>$this->user_id,"page_id"=>$page_id,"invisible"=>"0");
      $info_type = $this->basic->get_data($table_type,$where_type,$select='', $join='', $limit='', $start='', $order_by='group_name asc');
      $label_info=array(''=>$this->lang->line("Label"));
      foreach($info_type as $value)
      {
          $label_info[$value['id']] = $value['group_name'];
      }
      echo form_dropdown('label_id',$label_info,'','class="form-control select2" id="label_id"');
      echo "<script>$('#label_id').select2();</script>";
    }


    public function download_result()
    {        
        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                echo "<div class='alert alert-danger text-center'><i class='fa fa-ban'></i> This function is disabled from admin account in this demo!!</div>";
                exit();
            }
        }

        // gettings last search data from session to download
        $bot_subscribers_sql = $this->session->userdata("bot_subscribers_sql");
        $join = array('facebook_rx_fb_page_info'=>"facebook_rx_fb_page_info.id=messenger_bot_subscriber.page_table_id,left");          
        $table="messenger_bot_subscriber";
        $select = "messenger_bot_subscriber.*,page_name";
        
        $this->db->where($bot_subscribers_sql["where_custom"]);
        if(isset($bot_subscribers_sql["page_table_id"]) && $bot_subscribers_sql["page_table_id"]!="") $this->db->where("page_table_id",$bot_subscribers_sql["page_table_id"]);        
        if(isset($bot_subscribers_sql["where"]) && $bot_subscribers_sql["where"]!="") $this->db->where($bot_subscribers_sql["where"]);
        
        $info=$this->basic->get_data($bot_subscribers_sql["table"],$where='',$bot_subscribers_sql["select"],$bot_subscribers_sql["join"],'',NULL,$bot_subscribers_sql["order_by"],$group_by='');
        // echo $this->db->last_query(); exit();    

        $info_count = count($info);

        for($i=0; $i<$info_count; $i++)
        {
              $value = $info[$i]['contact_group_id'];
              $type_id = explode(",",$value);

              $table = 'messenger_bot_broadcast_contact_group';
              $select = array('group_name');

              $where_group['where_in'] = array('id'=>$type_id);
              $where_group['where'] = array('deleted'=>'0');

              $info1 = $this->basic->get_data($table,$where_group,$select);

              $str = '';
             foreach ($info1 as  $value1)
              {
                $str.= $value1['group_name'].","; 
              }
                
            $str = trim($str, ",");
            $info[$i]['contact_group_name']= $str;
        }

        $filename="exported_subscriber_list_".time()."_".$this->user_id.".csv";
        $f = fopen('php://memory', 'w');
        fputs( $f, "\xEF\xBB\xBF" );
        $head=array("Subscriber ID","Page ID","Page Name","Label IDs","Labels","First Name","Last Name","Full Name","Gender","Locale","Timezone","Email","Phone","Location","Subscribed at","Status");
        fputcsv($f,$head, ",");

        foreach ($info as  $value) 
        {
            $write_info=array();            
            $write_info[] = $value['subscribe_id'];
            $write_info[] = $value['page_id'];
            $write_info[] = $value['page_name'];
            $write_info[] = $value['contact_group_id'];
            $write_info[] = $value['contact_group_name'];
            $write_info[] = $value['first_name'];
            $write_info[] = $value['last_name'];
            $write_info[] = $value['full_name'];
            $write_info[] = $value['gender'];
            $write_info[] = $value['locale'];
            $write_info[] = $value['timezone'];
            $write_info[] = $value['email'];
            $write_info[] = $value['phone_number'];
            $write_info[] = $value['user_location'];          
            $write_info[] = $value['status'];          
            fputcsv($f, $write_info,',');  
        }

        fseek($f, 0);
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'";');
        fpassthru($f);         
    }


    public function get_label_dropdown_multiple()
    {
       $this->ajax_check();

       $page_auto_id=$this->input->post('selected_page'); // database id
       $where = array();
       $where['where'] = array('user_id'=>$this->user_id,"page_id"=>$page_auto_id,"invisible"=>"0");  
       $group_info=$this->basic->get_data('messenger_bot_broadcast_contact_group', $where, $select='', $join='', $limit='', $start='', $order_by='group_name', $group_by='', $num_rows=0); 
             
        echo '<script>$("#label_ids").select2();</script>
        <label>'.$this->lang->line("Choose Labels").'</label>
        <select name="label_ids" class="form-control" id="label_ids" multiple style="width:100%;">';
            foreach ($group_info as $key => $value) 
            {
               echo '<option value="'. $value['id'].'">'.$value['group_name'].'</option>';
            }
            // echo '<option value="" selected="selected">'.$this->lang->line('Labels').'</option>';            
        echo '</select>';
       

    }

    
    //DEPRECATED FUNCTION FOR QUICK BROADCAST
    public function bulk_group_assign()
    {
        $this->ajax_check();

        $ids = $this->input->post("ids");
        $page_id = $this->input->post("page_id");
        $group_id = $this->input->post("group_id");

        $get_token=$this->basic->get_data('facebook_rx_fb_page_info',array('where'=>array('id'=>$page_id,"user_id"=>$this->user_id)));
        $page_access_token=isset($get_token[0]['page_access_token'])?$get_token[0]['page_access_token']:"";
       
        $get_groupdata=$this->basic->get_data('messenger_bot_broadcast_contact_group',array('where'=>array('page_id'=>$page_id)));

        $label_id=array();
        $unsubscribe_label = "0";
        foreach ($get_groupdata as $key => $value) 
        {
            $label_id[$value['id']]=$value['label_id'];
            if($value['unsubscribe']=='1') $unsubscribe_label = $value['id'];
        }

        $subscriber_data = $this->basic->get_data("messenger_bot_subscriber",array("where_in"=>array("id"=>$ids)));
        
        foreach ($subscriber_data as $key => $value) 
        {
           $id = $value["id"];
           $subscribe_id = $value["subscribe_id"];

           $final_group_str=implode(',', $group_id);
           $final_group_str=trim($final_group_str,',');

           $update_data = array("contact_group_id"=>$final_group_str);
           if(in_array($unsubscribe_label, $group_id) && $unsubscribe_label!="0")           
           {
              $update_data['permission']='0';
              $update_data['unsubscribed_at']=date("Y-m-d H:i:s");
           }
           else $update_data['permission']='1';

           $this->basic->update_data("messenger_bot_subscriber",array("id"=>$id,"user_id"=>$this->user_id),$update_data);

           $prev_group = explode(',',$value["contact_group_id"]); 
           $tobe_subsctibed=array_diff($group_id,$prev_group);
           $tobe_unsubsctibed=array_diff($prev_group,$group_id);

           foreach($tobe_subsctibed as $key2 => $value2) 
           {
              $response=$this->fb_rx_login->assign_label($page_access_token,$subscribe_id,$label_id[$value2]);  
           }
           foreach($tobe_unsubsctibed as $key2 => $value2) 
           {
              $response=$this->fb_rx_login->deassign_label($page_access_token,$subscribe_id,$label_id[$value2]);  
           }
        }

         $sql = "SELECT count(id) as permission_count FROM `messenger_bot_subscriber` WHERE page_table_id='$page_id' AND permission='1' AND user_id=".$this->user_id;
         $count_data = $this->db->query($sql)->row_array();

         $sql2 = "SELECT count(id) as permission_count FROM `messenger_bot_subscriber` WHERE page_table_id='$page_id' AND permission='0' AND user_id=".$this->user_id;
         $count_data2 = $this->db->query($sql2)->row_array();

         // how many are subscribed and how many are unsubscribed
         $subscribed = isset($count_data["permission_count"]) ? $count_data["permission_count"] : 0;
         $unsubscribed = isset($count_data2["permission_count"]) ? $count_data2["permission_count"] : 0;
         $current_lead_count=$subscribed+$unsubscribed;

         $this->basic->update_data("facebook_rx_fb_page_info",array("id"=>$page_id),array("current_subscribed_lead_count"=>$subscribed,"current_unsubscribed_lead_count"=>$unsubscribed,"current_lead_count"=>$current_lead_count));
         echo "1";
    }
    

    // public function bulk_group_assign()
    // {
    //     $this->ajax_check();

    //     $ids = $this->input->post("ids");
    //     $page_id = $this->input->post("page_id");
    //     $group_id = $this->input->post("group_id");

    //     $subscriber_data = $this->basic->get_data("messenger_bot_subscriber",array("where_in"=>array("id"=>$ids)));
        
    //     foreach ($subscriber_data as $key => $value) 
    //     {
    //        $id = $value["id"];
    //        $subscribe_id = $value["subscribe_id"];

    //        $final_group_str=implode(',', $group_id);
    //        $final_group_str=trim($final_group_str,',');

    //        $update_data = array("contact_group_id"=>$final_group_str);           
    //        $this->basic->update_data("messenger_bot_subscriber",array("id"=>$id,"user_id"=>$this->user_id),$update_data);
    //     }
    //     echo "1";
    // }


    public function delete_bulk_subscriber()
    {
        $this->ajax_check();
        $ids = $this->input->post("ids");   
        $page_id = $this->input->post("page_id");   
        $this->db->where_in('id', $ids);
        $this->db->delete("messenger_bot_subscriber");

        $sql = "SELECT count(id) as permission_count FROM `messenger_bot_subscriber` WHERE page_table_id='$page_id' AND permission='1' AND user_id=".$this->user_id;
        $count_data = $this->db->query($sql)->row_array();

        $sql2 = "SELECT count(id) as permission_count FROM `messenger_bot_subscriber` WHERE page_table_id='$page_id' AND permission='0' AND user_id=".$this->user_id;
        $count_data2 = $this->db->query($sql2)->row_array();

        // how many are subscribed and how many are unsubscribed
        $subscribed = isset($count_data["permission_count"]) ? $count_data["permission_count"] : 0;
        $unsubscribed = isset($count_data2["permission_count"]) ? $count_data2["permission_count"] : 0;
        $current_lead_count=$subscribed+$unsubscribed;

        $this->basic->update_data("facebook_rx_fb_page_info",array("id"=>$page_id,"user_id"=>$this->user_id),array("current_subscribed_lead_count"=>$subscribed,"current_unsubscribed_lead_count"=>$unsubscribed,"current_lead_count"=>$current_lead_count));

        echo "success";
    }

    public function get_subscriber_formdata()
    {
      $this->ajax_check();
      $id = $this->input->post("id",true);
      $page_table_id = $this->input->post("page_id",true);
      $subscribe_id = $this->input->post("subscribe_id",true);

      $table_name = "messenger_bot_user_custom_form_webview_data";
      $where = array(
        "where"=>array(
          "messenger_bot_user_custom_form_webview_data.page_id"=>$page_table_id,
          "messenger_bot_user_custom_form_webview_data.subscriber_id"=>$subscribe_id
        )
      );
      $join = array('webview_builder'=>"messenger_bot_user_custom_form_webview_data.web_view_form_canonical_id=webview_builder.canonical_id,left");
      $select = array("webview_builder.form_name","messenger_bot_user_custom_form_webview_data.data as form_data","messenger_bot_user_custom_form_webview_data.inserted_at","messenger_bot_user_custom_form_webview_data.web_view_form_canonical_id as form_id");
      $data = $this->basic->get_data($table_name,$where,$select,$join);

      $content = '
        <div class="col-12 col-md-4">
          <ul class="nav nav-pills flex-column" id="myTab4" role="tablist">';

      $i=1;
      foreach($data as $value)
      {
        $unique_id = 'formdata_tab_'.$i;
        $unique_id2 = 'formdata_tab_content_'.$i;
        if($i == 1) $active = 'active';
        else $active = '';
        $insert_date = date('jS M Y, H:i', strtotime($value['inserted_at']));
        $content .= '
            <li class="nav-item">
              <a class="no_radius nav-link '.$active.'" id="'.$unique_id.'" data-toggle="tab" href="#'.$unique_id2.'" role="tab" aria-controls="'.$unique_id.'" aria-selected="true">'
              .$value['form_name'].

              '<br/><p class="form_id">'.$this->lang->line("Form ID").': '.$value['form_id'].'</p>
               <p class="insert_date">'.$this->lang->line("Submit Date").': '.$insert_date.'</p>
              </a>

            </li>
        ';
        $i++;
      }
            
      $content .='</ul>
        </div>
        <div class="col-12 col-md-8">
          <div class="tab-content no-padding" id="myTab2Content">';

      $i=1;
      foreach($data as $value)
      {
        $unique_id = 'formdata_tab_'.$i;
        $unique_id2 = 'formdata_tab_content_'.$i;
        if($i == 1) $active = 'active show';
        else $active = '';
        $content .= '<div class="tab-pane fade '.$active.'" id="'.$unique_id2.'" role="tabpanel" aria-labelledby="'.$unique_id.'">';
        $content .= '
          <div class="table-responsive">
            <table class="table table-bordered table-hover table-md">
              <thead><tr>
                <th>Field</th>
                <th>Value</th>
              </tr></thead><tbody>
        ';

        $form_data = json_decode($value['form_data'],true);
        foreach($form_data as $key=>$value)
        {
          $content .= '<tr>
                        <td>'.$key.'</td>
                        <td>'.$value.'</td>
                      </tr>';
        }

        $content .= '
            </tbody></table>
          </div>
        ';
        $content .= '</div>';
        $i++;
      }

      $content .='</div>
        </div>
      ';

      if(!empty($data))
        echo $content;
      else
        echo '<div class="col-12 card no_shadow" id="nodata">
                          <div class="card-body">
                            <div class="empty-state">
                              <img class="img-fluid" style="height: 200px" src="'.base_url('assets/img/drawkit/drawkit-nature-man-colour.svg').'" alt="image">
                              <h2 class="mt-0">'.$this->lang->line("We could not find any data.").'</h2>
                            </div>
                          </div>
                        </div>';


    }

    public function subscriber_actions_modal()
    {
      $this->ajax_check();
      $id = $this->input->post("id",true);
      $page_table_id = $this->input->post("page_id",true);
      $subscribe_id = $this->input->post("subscribe_id",true);

      $subscriber_info = $this->basic->get_data("messenger_bot_subscriber",array("where"=>array("id"=>$id,"user_id"=>$this->user_id)));
      if(!isset($subscriber_info[0])) exit();
      $subscriber_data = $subscriber_info[0];

      $default = base_url('assets/images/avatar/avatar-1.png');
      $profile_pic = ($subscriber_data['profile_pic']!="") ? $subscriber_data["profile_pic"] :  $default;
      $subscriber_image =($subscriber_data["image_path"]!="") ? base_url($subscriber_data["image_path"]) : $profile_pic;
      $sdk_locale = $this->sdk_locale();
      $locale = (isset($sdk_locale[$subscriber_data['locale']])) ? $sdk_locale[$subscriber_data['locale']]: $subscriber_data['locale'];
      $timezone="";
      if($subscriber_data["timezone"]!="")
      {
        // $hour = $subscriber_data["timezone"];
        // if($hour>0) $hour = "+".$hour;
        // $hour = $hour." hours";
        // date_default_timezone_set('UTC');
        // $timezone=date('jS M Y g:i a', strtotime($hour));
        if($subscriber_data["timezone"]=='0') $timezone="GMT";
        else $timezone="GMT +".$subscriber_data["timezone"];
      }

      //  label assign block
      $table_type = 'messenger_bot_broadcast_contact_group';
      $where_type['where'] = array('user_id'=>$this->user_id,"page_id"=>$page_table_id,"invisible"=>"0");
      $info_type = $this->basic->get_data($table_type,$where_type,$select='', $join='', $limit='', $start='', $order_by='group_name asc');
      $label_info=array();
      foreach($info_type as $value)
      {
          $label_info[$value['id']] = $value['group_name'];
      }
      $selected_labels = explode(',', $subscriber_data["contact_group_id"]);
      $label_dropdown = form_dropdown('subscriber_labels',$label_info,$selected_labels,'class="form-control select2" id="subscriber_labels" multiple style="width:100% !important;"');

      
      // subscribe unsubscribe blobk
      if($subscriber_data['permission'] == '1')
      $status ='<span class="subsribe_unsubscribe_container"><a class="text-primary">'.$this->lang->line("Subscribed").'</a> <a class="text-muted pointer client_thread_subscribe_unsubscribe" id="'.$subscriber_data['id']."-".$subscriber_data['permission'].'">('.$this->lang->line("Unsubscribe").')</a></span>';
      else $status ='<span class="subsribe_unsubscribe_container"><a class="text-primary">'.$this->lang->line("Unsubscribed").'</a> <a class="text-muted pointer client_thread_subscribe_unsubscribe" id="'.$subscriber_data['id']."-".$subscriber_data['permission'].'">('.$this->lang->line("Subscribe").')</a></span>';
      
      // bot strat stop blbok
      if($subscriber_data['status'] == '1')
      $start_stop = '<span class="client_thread_start_stop_container"><a href="" class="dropdown-item has-icon client_thread_start_stop" button_id="'.$subscriber_data['id']."-".$subscriber_data['status'].'"><i class="far fa-pause-circle"></i> '.$this->lang->line("Pause Bot Reply").'</a></span>';
      else $start_stop = '<span class="client_thread_start_stop_container"><a href="" class="dropdown-item has-icon client_thread_start_stop" button_id="'.$subscriber_data['id']."-".$subscriber_data['status'].'"><i class="far fa-play-circle"></i> '.$this->lang->line("Resume Bot Reply").'</a></span>';
     
      // sequence message block
      $sequence_block='';      
      if($this->is_drip_campaigner_exist)
      {
        $campaign_data=$this->basic->get_data("messenger_bot_drip_campaign",array("where"=>array("page_id"=>$page_table_id)),$select='',$join='',$limit='',$start=NULL,$order_by='created_at DESC');
        $drip_types=$this->get_drip_type();
        $option=array('0'=>$this->lang->line('Choose Message Sequence'));
        foreach ($campaign_data as $key => $value) 
        {
          $option[$value['id']]="";
          if($value['campaign_name']!="") $option[$value['id']].=$value['campaign_name']." : ";
          $option[$value['id']].=$drip_types[$value['drip_type']]." [".date("jS M, y H:i:s",strtotime($value['created_at']))."]";
        }

        $current_sequence_array=array();
        $user_sequence = $this->basic->get_data("messenger_bot_drip_campaign_assign",array("where"=>array("subscribe_id"=>$subscribe_id,"user_id"=>$this->user_id)));
        foreach ($user_sequence as $key => $value) 
        {
          $current_sequence_array[] = $value['messenger_bot_drip_campaign_id'];
        }

        $sequence_dropdwon = form_dropdown('assign_campaign_id', $option, $current_sequence_array,'style="width:100%" class="form-control inline" id="assign_campaign_id" multiple');
        $last_sent_info='';
        // if($subscriber_data['messenger_bot_drip_campaign_id']!=0)
        // {
        //   $last_sent_info = '<small class="last_sent_info float-right" data-toggle="tooltip" title="'.$this->lang->line("Last Sent").'"><i class="fas fa-clock"></i> '.date("jS M Y H:i").' ('.$this->lang->line("Day").'-'.$subscriber_data['messenger_bot_drip_last_completed_day'].')</small>';
        // }
        $sequence_block='
        <br>
        <div class="section">
          <div class="section-title margin-0">
            '.$this->lang->line("Message Sequence").'
            '.$last_sent_info.'                         
          </div>          
          '.$sequence_dropdwon.'
        </div>';
      }

      // optin block
      $optin_ref = '';
      $optin="DIRECT";
      $refferer_id = $subscriber_data['refferer_id'];
      if($subscriber_data['refferer_uri']!='') $refferer_id='<a href="'.$subscriber_data['refferer_uri'].'" target="_BLANK">'.$refferer_id.'</a>';
      if($subscriber_data['refferer_source']!='') $optin = str_replace('_', ' ', $subscriber_data['refferer_source']);
      if($subscriber_data['refferer_id']!='') $optin_ref='<span style="padding-left:45px;"><b>Refference : </b>'.$refferer_id.'</span>';
      $optinpop="";
      if($optin=='FB PAGE') $optin="DIRECT";
      if($optin=='DIRECT')
      $optinpop='<a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="" data-content="'.$this->lang->line("Direct OPT-IN means the subscriber either came from your Facebook page directly or the source is unknown.").'" data-original-title="'.$this->lang->line("OPT-IN").'"><i class="fa fa-info-circle"></i> </a>';

      // broadcast block
      $broadcast_block='';
      $availablility='<span class="blue">'.$this->lang->line("Available").'</span>';
      if($subscriber_data['unavailable_conversation']=='1' || $subscriber_data['permission']=='0') $availablility='<span class="red">'.$this->lang->line("Unavailable").'</span>';
      if($subscriber_data['unavailable_conversation']=='1') 
      {
        $reason=$this->lang->line("Error in last send");
        $reason_deatils=$subscriber_data['last_error_message_conversation'];
      }
      else if($subscriber_data['permission']=='0')
      {
        $reason =$this->lang->line("Unsubscribed");
        $reason_deatils = $this->lang->line("Unsubscribed at")." : ".date("jS M Y H:i:s",strtotime($subscriber_data['unsubscribed_at']));
      }
      $broadcast_block='
        <br>
        <div class="section">
          <div class="section-title margin-0 full_width">
            '.$this->lang->line("Converation Broadcasting ").'  : '.$availablility;

            if($subscriber_data['unavailable_conversation']=='1' || $subscriber_data['permission']=='0') {
              $broadcast_block.='
              <div class="alert alert-light alert-has-icon" style="margin-top: 10px;margin-left:45px;">
                <div class="alert-icon"><i class="far fa-lightbulb"></i></div>
                <div class="alert-body">
                  <div class="alert-title"><small><b>'.$this->lang->line("Reason")." : </b>".$reason.'</small></div>
                  <small>'.$reason_deatils.'</small>
                </div>
              </div>';
            }
        $broadcast_block.='
          </div>
        </div>';
      
      
      echo '<div class="col-12 col-md-5 col-lg-4 collef">
            <div class="card main_card">
              <div class="card-body padding-0">
                <div class="padding-20">
                  <span class="bgimage" style="background-image: url('.$subscriber_image.');"></span>
                </div>  
                <ul class="list-group list-group-flush">
                  <li class="list-group-item">
                    <i class="fas fa-check-circle subscriber_details blue" data-toggle="tooltip" title="'.$this->lang->line('Status').'"></i>
                    '.$status.'                    
                  </li>                  
                  <li class="list-group-item"><i class="fas fa-id-card subscriber_details blue" data-toggle="tooltip" title="'.$this->lang->line('Subscriber ID').'"></i>'.$subscribe_id.'</li>
                  <li class="list-group-item"><i class="fas fa-mars subscriber_details blue" data-toggle="tooltip" title="'.$this->lang->line('Gender').'"></i>'.ucfirst($subscriber_data['gender']).'</li>
                  <li class="list-group-item"><i class="fas fa-language subscriber_details blue" data-toggle="tooltip" title="'.$this->lang->line('Language').'"></i>'.$locale.'</li>
                  <li class="list-group-item"><i class="fas fa-globe subscriber_details blue" data-toggle="tooltip" title="'.$this->lang->line('Timezone').'"></i>'.$timezone.'</li>
                  ';

                  $last_update_time = ($subscriber_data['last_update_time']=='0000-00-00 00:00:00') ? "-" : date('jS M Y g:i a', strtotime($subscriber_data['last_update_time']));
                  $phone_number_entry_time = ($subscriber_data['phone_number_entry_time']=='0000-00-00 00:00:00') ? "-" : date('jS M Y g:i a', strtotime($subscriber_data['phone_number_entry_time']));
                  $birthdate_entry_time = ($subscriber_data['birthdate_entry_time']=='0000-00-00 00:00:00') ? "-" : date('jS M Y g:i a', strtotime($subscriber_data['birthdate_entry_time']));
                  $last_subscriber_interaction_time = ($subscriber_data['last_subscriber_interaction_time']=='0000-00-00 00:00:00') ? "-" : date('jS M Y g:i a', strtotime($subscriber_data['last_subscriber_interaction_time']));

                  if($subscriber_data['email']!='')
                  echo 
                  '<li class="list-group-item"><i class="fas fa-envelope subscriber_details blue" data-toggle="tooltip" title="'.$this->lang->line('Email').' - '.$this->lang->line("Last Updated").' : '.$last_update_time.'"></i>'.$subscriber_data['email'].'</li>';

                  if($subscriber_data['phone_number']!='')
                  echo 
                  '<li class="list-group-item"><i class="fas fa-phone subscriber_details blue" data-toggle="tooltip" title="'.$this->lang->line('Phone').' - '.$this->lang->line("Last Updated").' : '.$phone_number_entry_time.'"></i>'.$subscriber_data['phone_number'].'</li>';

                  if($subscriber_data['user_location']!='')
                  echo 
                  '<li class="list-group-item"><i class="fas fa-map-marker subscriber_details blue" data-toggle="tooltip" title="'.$this->lang->line('Location').' - '.$this->lang->line("Last Updated").' : '.$last_update_time.'"></i><a target="_BLANK" href="'.$subscriber_data['location_map_url'].'">'.$subscriber_data['user_location'].'</a></li>';

                  if($subscriber_data['birthdate']!='0000-00-00')
                  echo 
                  '<li class="list-group-item"><i class="fas fa-birthday-cake subscriber_details blue" data-toggle="tooltip" title="'.$this->lang->line('Birthday').' - '.$this->lang->line("Last Updated").' : '.$birthdate_entry_time.'"></i>'.date('jS M Y', strtotime($subscriber_data['birthdate'])).'</li>';

                  if($subscriber_data['last_subscriber_interaction_time']!='0000-00-00 00:00:00')
                  echo 
                  '<li class="list-group-item"><i class="far fa-clock subscriber_details blue" data-toggle="tooltip" title="'.$this->lang->line("Last Interacted at").'"></i>'.$last_subscriber_interaction_time.'</li>';

              echo    
              '</ul>
                
              </div>
            </div>          
          </div>

          <div class="col-12 col-md-7 col-lg-8 colmid" id="middle_column">
            <div class="card main_card">
              <div class="card-header full_width"  style="display: block;padding-top:25px;">                            
                  <div class="dropleft float-right">
                    <a href="#" data-toggle="dropdown" aria-expanded="false"><i class="fas fa-ellipsis-v" style="font-size:25px"></i></a>
                    <div class="dropdown-menu">
                      <div class="dropdown-title">'.$this->lang->line("Options").'</div>                        
                      '.$start_stop.'
                      <a href="" class="dropdown-item has-icon update_user_details"  button_id ="'.$subscriber_data['id']."-".$subscriber_data['subscribe_id']."-".$subscriber_data['page_table_id'].'"><i class="fas fa-sync-alt"></i> '.$this->lang->line("Sync Subscriber Data").'</a>
                      <div class="dropdown-divider"></div>
                      <a href="" class="dropdown-item has-icon red delete_user_details" button_id ="'.$subscriber_data['id']."-".$subscriber_data['page_table_id'].'"><i class="fas fa-trash"></i> '.$this->lang->line("Delete Subscriber Data").'</a>
                    </div>
                  </div>
                  <h4><a href="https://facebook.com/'.$subscriber_data['link'].'" target="_BLANK">'.$subscriber_data['full_name'].'</a></h4>
              </div>
              <div class="card-body">
                <div class="section">
                  <div class="section-title margin-0 full_width">
                    '.$this->lang->line("Labels").'
                    <a class="blue float-right pointer" data-id="'.$subscriber_data['id'].'"  data-page-id="'.$subscriber_data['page_table_id'].'" id="create_label"><small><i class="fas fa-plus-circle"></i> '.$this->lang->line("Create Label").'</small></a>                              
                  </div>                            
                  <div id="subscriber_labels_container">'.$label_dropdown.'</div>
                </div>

                '.$sequence_block.'

                <br>
                <div class="section">
                  <div class="section-title margin-0 full_width">
                    '.$this->lang->line("OPT-IN Through").'    
                    <span class="float-right text blue">'.$optin.' '.$optinpop.'</span>
                  </div>
                  '.$optin_ref.'  
                </div>

                <div id="broadcast_block">'.$broadcast_block.'</div>

              </div>

              <div class="card-footer">
                <a class="btn btn-primary float-left" href="" data-subscribe-id="'.$subscriber_data['subscribe_id'].'" data-id="'.$subscriber_data['id'].'" data-page-id="'.$subscriber_data['page_table_id'].'" id="save_changes"><i class="fas fa-save"></i> '.$this->lang->line("Save Changes").'</a>
                <a class="btn btn-outline-secondary float-right" data-dismiss="modal"><i class="fas fa-times"></i> '.$this->lang->line("Close").'</a>
              </div>

            </div>               
          </div>

          <script>
          $("#subscriber_labels").select2({
              placeholder: "'.$this->lang->line('Choose Label').'",
              allowClear: true
          });

          $("#assign_campaign_id").select2({
               placeholder: "'.$this->lang->line('Choose Sequence').'",
              allowClear: true
          });
          $(\'[data-toggle="popover"]\').popover(); 
          $(\'[data-toggle="popover"]\').on("click", function(e) {e.preventDefault(); return true;});
          $(\'[data-toggle="tooltip"]\').tooltip({placement: "bottom"});
          </script>';


    }

    public function subscriber_actions_refresh()
    {
      $this->ajax_check();
      $id = $this->input->post("id",true); // subscriber auto id

      $subscriber_info = $this->basic->get_data("messenger_bot_subscriber",array("where"=>array("id"=>$id,"user_id"=>$this->user_id)));
      if(!isset($subscriber_info[0])) exit();

      $subscriber_data = $subscriber_info[0];
      $page_table_id = $subscriber_data['page_table_id'];
      $contact_group_id = $subscriber_data['contact_group_id'];


      $table_type = 'messenger_bot_broadcast_contact_group';
      $where_type['where'] = array('user_id'=>$this->user_id,"page_id"=>$page_table_id,"invisible"=>"0");
      $info_type = $this->basic->get_data($table_type,$where_type,$select='', $join='', $limit='', $start='', $order_by='group_name asc');
      $label_info=array();
      foreach($info_type as $value)
      {
          $label_info[$value['id']] = $value['group_name'];
      }
      $selected_labels = explode(',', $contact_group_id);
      $label_dropdown = form_dropdown('subscriber_labels',$label_info,$selected_labels,'class="form-control select2" id="subscriber_labels" multiple style="width:100% !important;"');
      $label_dropdown.='
      <script>
      $("#subscriber_labels").select2({
          placeholder: "'.$this->lang->line('Choose Label').'",
          allowClear: true
      });
      </script>';

      // broadcast block
      $broadcast_block='';
      if($this->is_drip_campaigner_exist)
      {
        $availablility='<span class="blue">'.$this->lang->line("Available").'</span>';
        if($subscriber_data['unavailable']=='1' || $subscriber_data['permission']=='0') $availablility='<span class="red">'.$this->lang->line("Unavailable").'</span>';
        if($subscriber_data['unavailable']=='1') 
        {
          $reason=$this->lang->line("Error in last send");
          $reason_deatils=$subscriber_data['last_error_message'];
        }
        else if($subscriber_data['permission']=='0')
        {
          $reason =$this->lang->line("Unsubscribed");
          $reason_deatils = $this->lang->line("Unsubscribed at")." : ".date("jS M Y H:i:s",strtotime($subscriber_data['unsubscribed_at']));
        }
        $broadcast_block='
          <br>
          <div class="section">
            <div class="section-title margin-0 full_width">
              '.$this->lang->line("Broadcasting Availablity").'  : '.$availablility;

              if($subscriber_data['unavailable']=='1' || $subscriber_data['permission']=='0') {
                $broadcast_block.='
                <div class="alert alert-light alert-has-icon" style="margin-top: 10px;margin-left:45px;">
                  <div class="alert-icon"><i class="far fa-lightbulb"></i></div>
                  <div class="alert-body">
                    <div class="alert-title"><small><b>'.$this->lang->line("Reason")." : </b>".$reason.'</small></div>
                    <small>'.$reason_deatils.'</small>
                  </div>
                </div>';
              }
          $broadcast_block.='
            </div>
          </div>';
      }

      echo json_encode(array("label_dropdown"=>$label_dropdown,"broadcast_block"=>$broadcast_block));

    }

    // public function create_label_and_assign()
    // {
    //   $this->ajax_check();
    //   $id = $this->input->post("id",true); // subscriber auto id
    //   $page_table_id = $this->input->post("page_id",true);
    //   $label_name = $this->input->post("label_name",true);

    //   $is_exists = $this->basic->get_data("messenger_bot_broadcast_contact_group",array("where"=>array("page_id"=>$page_table_id,"group_name"=>$label_name)));
    //   if(isset($is_exists[0]))
    //   {
    //        $insert_id = $is_exists[0]['id'];
    //        $label_id = '';
    //   }
    //   else
    //   {
    //     $label_id="";
    //     $this->basic->insert_data("messenger_bot_broadcast_contact_group",array("page_id"=>$page_table_id,"group_name"=>$label_name,"user_id"=>$this->user_id,"label_id"=>$label_id));
    //     $insert_id = $this->db->insert_id();
    //   }

    //   echo json_encode(array('id'=>$insert_id,"text"=>$label_name));
    // }

    public function save_subscriber_changes()
    {
      $this->ajax_check();
      $id = $this->input->post("id");
      $page_id = $this->input->post("page_id");
      $group_id = $this->input->post("group_id");
      $campaign_id = $this->input->post("campaign_id"); // array
      if(!isset($group_id)) $group_id=array();

      $get_token=$this->basic->get_data('facebook_rx_fb_page_info',array('where'=>array('id'=>$page_id,"user_id"=>$this->user_id)));
      $page_access_token=isset($get_token[0]['page_access_token'])?$get_token[0]['page_access_token']:"";

      $get_groupdata=$this->basic->get_data('messenger_bot_broadcast_contact_group',array('where'=>array('user_id'=>$this->user_id)));

      $label_id=array();
      $unsubscribe_label = "0";
      foreach ($get_groupdata as $key => $value) 
      {
          $label_id[$value['id']]=$value['label_id'];
          if($value['unsubscribe']=='1') $unsubscribe_label = $value['id'];      
      }

      $subscriber_data = $this->basic->get_data("messenger_bot_subscriber",array("where"=>array("id"=>$id,"user_id"=>$this->user_id)));
      
      foreach ($subscriber_data as $key => $value) // it's a single loop :p
      {
         $id = $value["id"];
         $subscribe_id = $value["subscribe_id"];

         $final_group_str=implode(',', $group_id);
         $final_group_str=trim($final_group_str,',');

         $update_data=array("contact_group_id"=>$final_group_str);

         if(in_array($unsubscribe_label, $group_id) && $unsubscribe_label!="0")
         {
            $update_data['permission']='0';
            $update_data['unsubscribed_at']=date("Y-m-d H:i:s");
         }
         else $update_data['permission']='1';

         $this->basic->update_data("messenger_bot_subscriber",array("id"=>$id,"user_id"=>$this->user_id),$update_data); 

         $drip_data = array();
         if(!empty($campaign_id) && $this->is_drip_campaigner_exist) $drip_data = $this->basic->get_data("messenger_bot_drip_campaign",array("where_in"=>array("id"=>$campaign_id,"user_id"=>$this->user_id)));

         $eligible_drip_ids = array();
         foreach ($drip_data as $key => $value2) 
         {
           $eligible_drip_ids[] = $value2['id'];
           $this->assign_drip_messaging_id($value2["drip_type"],"0",$value2['page_id'],$subscribe_id,$value2['id']);// inside home controller
         }

         if($this->is_drip_campaigner_exist)
         {
          if(!empty($eligible_drip_ids)) $this->db->where_not_in("messenger_bot_drip_campaign_id",$eligible_drip_ids);         
          $this->db->where("subscribe_id",$subscribe_id);
          $this->db->delete("messenger_bot_drip_campaign_assign");
         }

         $prev_group = explode(',',$value["contact_group_id"]); 
         $tobe_subsctibed=array_filter(array_diff($group_id,$prev_group));
         $tobe_unsubsctibed=array_filter(array_diff($prev_group,$group_id));

         foreach($tobe_subsctibed as $key2 => $value2) 
         {
            $response=$this->fb_rx_login->assign_label($page_access_token,$subscribe_id,$label_id[$value2]);  
         }
         foreach($tobe_unsubsctibed as $key2 => $value2) 
         {
            $response=$this->fb_rx_login->deassign_label($page_access_token,$subscribe_id,$label_id[$value2]);  
         }

      }
      echo "1";


    }

    
    //DEPRECATED FUNCTION FOR QUICK BROADCAST
    public function create_label_and_assign()
    {
      $this->ajax_check();
      $id = $this->input->post("id",true); // subscriber auto id
      $page_table_id = $this->input->post("page_id",true);
      $label_name = $this->input->post("label_name",true);
      $subscriber_info = $this->basic->get_data("messenger_bot_subscriber",array("where"=>array("id"=>$id,"user_id"=>$this->user_id)));

      $subscriber_data = $subscriber_info[0];
      $subscribe_id = $subscriber_data['subscribe_id'];
      
      $getdata=$this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_table_id)));      
      $page_access_token=isset($getdata[0]['page_access_token'])?$getdata[0]['page_access_token']:"";
      
      $is_exists = $this->basic->get_data("messenger_bot_broadcast_contact_group",array("where"=>array("page_id"=>$page_table_id,"group_name"=>$label_name)));
      if(isset($is_exists[0]))
      {
           $insert_id = $is_exists[0]['id'];
           $label_id = $is_exists[0]['label_id'];
      }

      else
      {
        $response=$this->fb_rx_login->create_label($page_access_token,$label_name);
        $label_id=isset($response['id']) ? $response['id'] : "";
        $this->basic->insert_data("messenger_bot_broadcast_contact_group",array("page_id"=>$page_table_id,"group_name"=>$label_name,"user_id"=>$this->user_id,"label_id"=>$label_id));
        $insert_id = $this->db->insert_id();
      }

      echo json_encode(array('id'=>$insert_id,"text"=>$label_name));
    }
  
    //DEPRECATED FUNCTION FOR QUICK BROADCAST
    /*
    public function save_subscriber_changes()
    {
      $this->ajax_check();
      $id = $this->input->post("id");
      $page_id = $this->input->post("page_id");
      $group_id = $this->input->post("group_id");
      $campaign_id = $this->input->post("campaign_id");
      if(!isset($group_id)) $group_id=array();

      $get_token=$this->basic->get_data('facebook_rx_fb_page_info',array('where'=>array('id'=>$page_id,"user_id"=>$this->user_id)));
      $page_access_token=isset($get_token[0]['page_access_token'])?$get_token[0]['page_access_token']:"";
      
      $get_groupdata=$this->basic->get_data('messenger_bot_broadcast_contact_group',array('where'=>array('user_id'=>$this->user_id)));

      $label_id=array();
      $unsubscribe_label = "0";
      foreach ($get_groupdata as $key => $value) 
      {
          $label_id[$value['id']]=$value['label_id'];
          if($value['unsubscribe']=='1') $unsubscribe_label = $value['id'];      
      }

      $subscriber_data = $this->basic->get_data("messenger_bot_subscriber",array("where"=>array("id"=>$id,"user_id"=>$this->user_id)));
      
      foreach ($subscriber_data as $key => $value) // it's a single loop :p
      {
         $id = $value["id"];
         $xmessenger_bot_drip_campaign_id = $value["messenger_bot_drip_campaign_id"];
         $subscribe_id = $value["subscribe_id"];

         $final_group_str=implode(',', $group_id);
         $final_group_str=trim($final_group_str,',');

         if($campaign_id!=$xmessenger_bot_drip_campaign_id && $campaign_id!='0')
           $update_data=array
           (
            "messenger_bot_drip_campaign_id"=>$campaign_id,
            "messenger_bot_drip_last_completed_day"=>"0",
            "messenger_bot_drip_is_toatally_complete"=>"0",
            "messenger_bot_drip_last_sent_at"=>"0000-00-00 00:00:00",
            "messenger_bot_drip_initial_date"=>date('Y-m-d H:i:s'),
            "last_processing_started_at"=>"0000-00-00 00:00:00",
            "messenger_bot_drip_processing_status"=>"0",
            "contact_group_id"=>$final_group_str
           );
         else $update_data=array("contact_group_id"=>$final_group_str);

         if(in_array($unsubscribe_label, $group_id) && $unsubscribe_label!="0")
         {
            $update_data['permission']='0';
            $update_data['unsubscribed_at']=date("Y-m-d H:i:s");
         }
         else $update_data['permission']='1';

         $this->basic->update_data("messenger_bot_subscriber",array("id"=>$id,"user_id"=>$this->user_id),$update_data);         

         $prev_group = explode(',',$value["contact_group_id"]); 
         $tobe_subsctibed=array_filter(array_diff($group_id,$prev_group));
         $tobe_unsubsctibed=array_filter(array_diff($prev_group,$group_id));

         foreach($tobe_subsctibed as $key2 => $value2) 
         {
            $response=$this->fb_rx_login->assign_label($page_access_token,$subscribe_id,$label_id[$value2]);  
         }
         foreach($tobe_unsubsctibed as $key2 => $value2) 
         {
            $response=$this->fb_rx_login->deassign_label($page_access_token,$subscribe_id,$label_id[$value2]);  
         }
      }
      echo "1";


    }
    */

 

    public function start_stop_bot_reply()
    {
        $this->ajax_check();
        $client_subscribe_unsubscribe = array();
        $post_val=$this->input->post('client_thread_start_stop');
        $client_subscribe_unsubscribe = explode("-",$post_val);
        $id = isset($client_subscribe_unsubscribe[0]) ? $client_subscribe_unsubscribe[0]: 0;
        $current_status =  isset($client_subscribe_unsubscribe[1]) ? $client_subscribe_unsubscribe[1]: 0;
        
        if($current_status=="1") $permission="0";
        else $permission="1";
        
        $where = array
        (
            'id' => $id,
            'user_id' => $this->user_id
        );
        $data = array('status' => $permission);
        
            
        if($permission=="0")
        {
            $message = $this->lang->line("Bot reply has been paused successfully.");
            $response ='<a href="" class="dropdown-item has-icon client_thread_start_stop" button_id="'.$id."-".$permission.'"><i class="far fa-play-circle"></i> '.$this->lang->line('Resume Bot Reply').'</a>';
            $this->basic->update_data("messenger_bot_subscriber",$where, $data);

        }
        else  
        {  
            $message = $this->lang->line("Bot reply has been resumed successfully.");
            $response ='<a href="" class="dropdown-item has-icon client_thread_start_stop" button_id="'.$id."-".$permission.'"><i class="far fa-pause-circle"></i> '.$this->lang->line('Pause Bot Reply').'</a>';
            $this->basic->update_data("messenger_bot_subscriber",$where, $data);
        }

        echo json_encode(array("message"=>$message,"button"=>$response));
    }

    
    //DEPRECATED FUNCTION FOR QUICK BROADCAST//
    public function sync_subscriber_data()
    {
        $this->ajax_check();
        $value = array();
        $post_val=$this->input->post('post_value');
        $value = explode("-",$post_val);
        $id = isset($value[0]) ? $value[0]: 0; //subscribe auto id
        $client_id = isset($value[1]) ? $value[1]: 0; // subscribe_id
        $page_id = isset($value[2]) ? $value[2]: 0; // page auto id

        $response = array();    
        $facebook_rx_fb_page_info = $this->basic->get_data('facebook_rx_fb_page_info', array('where' => array('id' => $page_id, 'user_id' => $this->user_id)));
        $facebook_rx_fb_page_info = $facebook_rx_fb_page_info[0];

        $update_data = $this->subscriber_info($facebook_rx_fb_page_info['page_access_token'],$client_id);

        if(!isset($update_data['error'])) 
        {

            $first_name = isset($update_data['first_name']) ? $update_data['first_name'] : "";
            $last_name = isset($update_data['last_name']) ? $update_data['last_name'] : "";
            $profile_pic = isset($update_data['profile_pic']) ? $update_data['profile_pic'] : "";
            $gender = isset($update_data['gender']) ? $update_data['gender'] : "";
            $locale = isset($update_data['locale']) ? $update_data['locale'] : "";
            $timezone = isset($update_data['timezone']) ? $update_data['timezone'] : "";
            $full_name = isset($update_data['name']) ? $update_data['name'] : "";
            
            if ($first_name != "") {

                $data = array
                (
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'profile_pic' => $profile_pic,
                    'is_updated_name' => '1',
                    'is_bot_subscriber' => '1',
                    'is_image_download' => '0',
                    'gender'=>$gender,
                    'locale'=>$locale,
                    'timezone'=>$timezone,
                    'last_name_update_time' => date('Y-m-d H:i:s')
                );
                if($full_name!="") $data["full_name"] = $full_name;
            }
            else  $data = array('is_updated_name' => '1','is_bot_subscriber' => '0','last_name_update_time' => date('Y-m-d H:i:s'));

            // getting previous labels if any
          
            $xlabels=$this->fb_rx_login->retrieve_level_of_psid($client_id,$facebook_rx_fb_page_info['page_access_token']);
            $existing_label_str="";
            if(isset($xlabels['data']))
            {
              $get_groupdata=$this->basic->get_data('messenger_bot_broadcast_contact_group',array('where'=>array('page_id'=>$page_id)));
              $label_id=array();
              foreach ($get_groupdata as $key => $value) 
              {
                  $label_id[$value['label_id']]=$value['id'];
              }
              $existing_label_array=array();
              foreach ($xlabels['data'] as $key => $value) 
              {
                if(isset($label_id[$value['id']])) $existing_label_array[]=$label_id[$value['id']];
              }
              $existing_label_str = implode(',', $existing_label_array);
            }
            if($existing_label_str!="") $data["contact_group_id"]=$existing_label_str;

            $this->basic->update_data('messenger_bot_subscriber', array('id' => $id,"user_id"=>$this->user_id), $data);

            $response['status'] = '1';
            $response['message'] = $this->lang->line("Subscriber data has been synced successfully.");
        }
        else 
        {
            $data = array('last_name_update_time' => date('Y-m-d H:i:s'),'is_updated_name' => '1'); 
            $this->basic->update_data('messenger_bot_subscriber', array('id' => $table_id), $data);
            $response['status'] = '0';
            $response['message'] = $this->lang->line($update_data['error']['message']);
        }

        echo json_encode($response);
    }
    

    // public function sync_subscriber_data()
    // {
    //     $this->ajax_check();
    //     $value = array();
    //     $post_val=$this->input->post('post_value');
    //     $value = explode("-",$post_val);
    //     $id = isset($value[0]) ? $value[0]: 0; //subscribe auto id
    //     $client_id = isset($value[1]) ? $value[1]: 0; // subscribe_id
    //     $page_id = isset($value[2]) ? $value[2]: 0; // page auto id

    //     $response = array();
    //     /**
    //      * get page access token using page id from table facebook_rx_fb_page_info
    //      * then call the function for update data
    //      */
    //     $facebook_rx_fb_page_info = $this->basic->get_data('facebook_rx_fb_page_info', array('where' => array('id' => $page_id, 'user_id' => $this->user_id)));
    //     $facebook_rx_fb_page_info = $facebook_rx_fb_page_info[0];

    //     $update_data = $this->subscriber_info($facebook_rx_fb_page_info['page_access_token'],$client_id);

    //     if(!isset($update_data['error'])) 
    //     {

    //         $first_name = isset($update_data['first_name']) ? $update_data['first_name'] : "";
    //         $last_name = isset($update_data['last_name']) ? $update_data['last_name'] : "";
    //         $profile_pic = isset($update_data['profile_pic']) ? $update_data['profile_pic'] : "";
    //         $gender = isset($update_data['gender']) ? $update_data['gender'] : "";
    //         $locale = isset($update_data['locale']) ? $update_data['locale'] : "";
    //         $timezone = isset($update_data['timezone']) ? $update_data['timezone'] : "";
    //         $full_name = isset($update_data['name']) ? $update_data['name'] : "";
            
    //         if ($first_name != "") {

    //             $data = array
    //             (
    //                 'first_name' => $first_name,
    //                 'last_name' => $last_name,
    //                 'profile_pic' => $profile_pic,
    //                 'is_updated_name' => '1',
    //                 'is_bot_subscriber' => '1',
    //                 'is_image_download' => '0',
    //                 'gender'=>$gender,
    //                 'locale'=>$locale,
    //                 'timezone'=>$timezone,
    //                 'last_name_update_time' => date('Y-m-d H:i:s')
    //             );
    //             if($full_name!="") $data["full_name"] = $full_name;
    //         }
    //         else  $data = array('is_updated_name' => '1','is_bot_subscriber' => '0','last_name_update_time' => date('Y-m-d H:i:s'));           

    //         $this->basic->update_data('messenger_bot_subscriber', array('id' => $id,"user_id"=>$this->user_id), $data);
    //         $response['status'] = '1';
    //         $response['message'] = $this->lang->line("Subscriber data has been synced successfully.");
    //     }
    //     else 
    //     {
    //         $data = array('last_name_update_time' => date('Y-m-d H:i:s'),'is_updated_name' => '1'); 
    //         $this->basic->update_data('messenger_bot_subscriber', array('id' => $table_id), $data);
    //         $response['status'] = '0';
    //         $response['message'] = $this->lang->line($update_data['error']['message']);
    //     }

    //     echo json_encode($response);
    // }

    public function delete_subsriber()
    {
        $this->ajax_check();
        $value = array();
        $post_val=$this->input->post('post_value');
        $value = explode("-",$post_val);
        $id = isset($value[0]) ? $value[0]: 0; //subscribe auto id
        $page_id = isset($value[1]) ? $value[1]: 0; //page auto id

        $this->basic->delete_data('messenger_bot_subscriber',array('id'=>$id,"user_id"=>$this->user_id));

        $sql = "SELECT count(id) as permission_count FROM `messenger_bot_subscriber` WHERE page_table_id='$page_id' AND permission='1' AND user_id=".$this->user_id;
        $count_data = $this->db->query($sql)->row_array();

        $sql2 = "SELECT count(id) as permission_count FROM `messenger_bot_subscriber` WHERE page_table_id='$page_id' AND permission='0' AND user_id=".$this->user_id;
        $count_data2 = $this->db->query($sql2)->row_array();

        // how many are subscribed and how many are unsubscribed
        $subscribed = isset($count_data["permission_count"]) ? $count_data["permission_count"] : 0;
        $unsubscribed = isset($count_data2["permission_count"]) ? $count_data2["permission_count"] : 0;
        $current_lead_count=$subscribed+$unsubscribed;

        $this->basic->update_data("facebook_rx_fb_page_info",array("id"=>$page_id),array("current_subscribed_lead_count"=>$subscribed,"current_unsubscribed_lead_count"=>$unsubscribed,"current_lead_count"=>$current_lead_count));

        echo "1";
    }

    
    /**
    * called via ajax and needs user_id and page_id to be executed
    * brings data from table messenger_bot_subscriber and makes them unique
    * and after checking if page exists & bot enabled from table messenger_bot_page_info
    * it insert leads on table messenger_bot_subscriber
    * @return json message describes about results
    */
    public function migrate_lead_to_bot()
    {        
        /**
         * grab data from post
         */
        $this->ajax_check();
        $user_page_id = $this->input->post('user_page_id');
        $user_page_id = explode('-', $user_page_id);
        $user_id = $user_page_id[0];
        $page_table_id = $user_page_id[1];

        $response = array();

        $this->basic->update_data("messenger_bot_subscriber",array("page_table_id"=>$page_table_id,"is_bot_subscriber"=>'0'),array("is_updated_name"=>'0','is_24h_1_sent'=>'1'));   

        $response['status'] = '1';
        $response['message'] = $this->lang->line('Migration Successful, in bot subscriber list, first name & last name will be updated gradually in background.Once any subscriber will be updated, will be available under Bot Subscribers menu.Some subscribers may be removed for BOT subscribers as they are not eligible for messenger BOT Subscribers for various reasons.Migration may take a long time depends on the number of subscribers.');    

        echo json_encode($response);
    }



}