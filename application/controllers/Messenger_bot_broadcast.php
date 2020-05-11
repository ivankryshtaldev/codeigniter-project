<?php

require_once("Home.php"); // loading home controller

class Messenger_bot_broadcast extends Home
{

    public function __construct()
    {
        parent::__construct();
        if ($this->session->userdata('logged_in') != 1)
        redirect('home/login_page', 'location');
        if($this->session->userdata('user_type') != 'Admin' && !in_array(79,$this->module_access))
        redirect('home/login_page', 'location');

        $function_name=$this->uri->segment(2);
        if($function_name!="" && $function_name!="index" && $function_name!="conversation_broadcast_campaign" && $function_name!="conversation_broadcast_campaign_data")
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
        $data['body'] = 'messenger_tools/bulk_message/menu_block';
        $data['page_title'] = $this->lang->line('Messenger Broadcast');
        $this->_viewcontroller($data);
    }

    public function conversation_broadcast_campaign()
    {
      $page_info = array();
      $page_list = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$this->session->userdata("facebook_rx_fb_user_info"),"bot_enabled"=>"1")),$select='',$join='',$limit='',$start=NULL,$order_by='page_name ASC');
    
      foreach($page_list as $value)
      {
          $page_info[$value['id']] = $value['page_name'];
      }
      
      $page_info[''] = $this->lang->line("Page");
      $data['page_list'] = $page_info;

      $data['body'] = 'messenger_tools/bulk_message/campaign_list';
      $data['page_title'] = $this->lang->line('Conversation Broadcast');
      $this->_viewcontroller($data);
    }


    public function conversation_broadcast_campaign_data()
    { 
        $this->ajax_check();
        $this->session->unset_userdata("bot_subscribers_sql");

        $search_value = $this->input->post("search_value");
        $page_id = $this->input->post("search_page_id");
        $status = $this->input->post("search_status");
        $campaign_date_range = $this->input->post("campaign_date_range");


        $display_columns = 
        array(
          "#",
          "CHECKBOX",
          'campaign_name',
          'page_name',
          'posting_status',
          'successfully_sent',
          'actions',
          'schedule_time',
          'added_at',
          'label_names'
        );
        $search_columns = array('campaign_name','label_names');

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $sort_index = isset($_POST['order'][0]['column']) ? strval($_POST['order'][0]['column']) : 8;
        $sort = isset($display_columns[$sort_index]) ? $display_columns[$sort_index] : 'added_at';
        $order = isset($_POST['order'][0]['dir']) ? strval($_POST['order'][0]['dir']) : 'desc';
        $order_by=$sort." ".$order;

        $where_custom="facebook_ex_conversation_campaign.user_id = ".$this->user_id;

        if ($search_value != '') 
        {
            foreach ($search_columns as $key => $value) 
            $temp[] = $value." LIKE "."'%$search_value%'";
            $imp = implode(" OR ", $temp);
            $where_custom .=" AND (".$imp.") ";
        }
        if($campaign_date_range!="")
        {
            $exp = explode('|', $campaign_date_range);
            $from_date = isset($exp[0])?$exp[0]:"";
            $to_date = isset($exp[1])?$exp[1]:"";
            if($from_date!="Invalid date" && $to_date!="Invalid date")
            $where_custom .= " AND added_at >= '{$from_date}' AND added_at <='{$to_date}'";
        }
        $this->db->where($where_custom);

        if($page_id!="") $this->db->where(array("page_id"=>$page_id)); 
        if($status!="") $this->db->where(array("posting_status"=>$status));       
        
        $table="facebook_ex_conversation_campaign";
        $info=$this->basic->get_data($table,$where='',$select='',$join='',$limit,$start,$order_by,$group_by='');
        
        $this->db->where($where_custom);
        if($page_id!="") $this->db->where(array("page_id"=>$page_id)); 
        if($status!="") $this->db->where(array("posting_status"=>$status)); 
        $total_rows_array=$this->basic->count_row($table,$where='',$count=$table.".id",$join,$group_by='');

        $total_result=$total_rows_array[0]['total_rows'];

        foreach($info as $key => $value) 
        {
            $action_count = 3;
            if($info[$key]['schedule_time'] != "0000-00-00 00:00:00")
            $scheduled_at = date("M j, y H:i",strtotime($info[$key]['schedule_time']));
            else $scheduled_at = '<span class="text-muted"><i class="fas fa-exclamation-circle"></i> '.$this->lang->line("Not Scheduled")."<span>";
            $info[$key]['schedule_time'] =  "<div style='min-width:110px;'>".$scheduled_at."</div>";

            if($info[$key]['added_at'] != "0000-00-00 00:00:00")
            $info[$key]['added_at'] = "<div style='min-width:110px;'>".date("M j, y H:i",strtotime($info[$key]['added_at']))."</div>";

            $posting_status = $info[$key]['posting_status'];

            $info[$key]['page_name']="<a target='_BLANK' href='https://facebook.com/".$info[$key]['fb_page_id']."'>".$info[$key]['page_name']."</a>";

            if($posting_status=='1')
            $info[$key]['delete'] =  "<a class='btn btn-circle btn-light pointer text-muted'  data-toggle='tooltip' title='".$this->lang->line("Campaign in processing can not be deleted. You can pause campaign and then delete it.")."'><i class='fas fa-trash-alt'></i></a>";
            else  $info[$key]['delete'] =  "<a class='btn btn-circle btn-outline-danger delete'  id='".$info[$key]['id']."' data-toggle='tooltip' title='".$this->lang->line("Delete Campaign")."' href=''><i class='fas fa-trash-alt'></i></a>";
         
            $is_try_again=$info[$key]["is_try_again"];
            $force_porcess_str="";
            if($this->config->item("number_of_message_to_be_sent_in_try")=="" || $this->config->item("number_of_message_to_be_sent_in_try")=="0")
            {
                $force_porcess_str="";
            }
            else
            {
                $action_count++;
                if($posting_status=='3')$force_porcess_str .= "<a href='' class='btn btn-circle btn-outline-success play_campaign_info' data-toggle='tooltip' title='".$this->lang->line("Resume Campaign")."' table_id='".$info[$key]['id']."'><i class='fas fa-play'></i></a>";
                else if($posting_status!='4') $force_porcess_str .= "<a href='' class='btn btn-circle btn-outline-dark pause_campaign_info' data-toggle='tooltip' title='".$this->lang->line("Pause Campaign")."' table_id='".$info[$key]['id']."'><i class='fas fa-pause'></i></a>";
            }

            $info[$key]["successfully_sent"] =  $info[$key]["successfully_sent"]."/". $info[$key]["total_thread"] ;

            if($posting_status=='1')
            {
                $action_count++;
                $force_porcess_str .= "<a href='' class='btn btn-circle btn-outline-warning force' data-toggle='tooltip' title='".$this->lang->line("Force Re-process Campaign")."' id='".$info[$key]['id']."'><i class='fas fa-sync'></i></a>";
            } 
            $info[$key]['force'] = $force_porcess_str;

            $hold_message = '<a href="#" data-placement="top" data-toggle="popover" data-trigger="focus" title="'.$this->lang->line("Campaign Status : On-hold").'" data-content="'.$this->lang->line("If campaign receive more than 10 error message during broadcast, system hold the campaign to avoid risk. The subscribers those get error, automatically marked as unavailable for future campaign to reduce error rate in future until subscriber send message to your Messenger BOT again. 
In this case, we suggest you to check the error message in report, and if you think it’s not for your message content, but for specific subscribers, you can restart the campaign from where it is left off, by clicking on the option menu & then click Force Resume. ").'"><i class="fas fa-info-circle"></i> </a>';

            if( $posting_status == '2') $info[$key]['posting_status'] = '<div style="min-width:100px"><span class="text-success badge"><i class="fas fa-check-circle"></i> '.$this->lang->line("Completed").'</span></div>';
            else if( $posting_status == '1') $info[$key]['posting_status'] = '<div style="min-width:100px"><span class="text-warning"><i class="fas fa-spinner"></i> '.$this->lang->line("Processing").'</span></div>';
            else if( $posting_status == '3') $info[$key]['posting_status'] = '<div style="min-width:100px"><span class="text-muted"><i class="fas fa-stop"></i> '.$this->lang->line("Paused").'</span></div>';
            else if( $posting_status == '4') $info[$key]['posting_status'] = '<div style="min-width:100px"><span class="text-dark"><i class="fas fa-ban"></i> '.$this->lang->line("On-hold").$hold_message.' </span></div>';
            else $info[$key]['posting_status'] = '<div style="min-width:100px"><span class="text-danger"><i class="far fa-times-circle"></i> '.$this->lang->line("Pending").'</span></div>';

            $info[$key]['posting_status'] = '<div style="min-width:80px;">'.$info[$key]['posting_status'].'</div>';

            $info[$key]["sent_count"] =  $info[$key]["successfully_sent"]."/". $info[$key]["total_thread"] ;

            $info[$key]['report'] =  "<a class='btn btn-circle btn-outline-primary sent_report' data-toggle='tooltip' title='".$this->lang->line("Campaign Report")."' href='' cam-id='".$info[$key]['id']."'><i class='fas fa-eye'></i></a>";

            if($posting_status!='0' || $info[$key]['time_zone']=="") 
            $info[$key]['edit'] =  "<a class='btn btn-circle btn-light text-muted' data-toggle='tooltip' title='".$this->lang->line("Only scheduled pending campaign can be edited.")."'><i class='fas fa-edit'></i></a>";
            else
            {                
                $edit_url = site_url('messenger_bot_broadcast/edit_conversation_campaign/'.$info[$key]['id']);
                $info[$key]['edit'] =  "<a class='btn btn-circle btn-outline-warning' data-toggle='tooltip' title='".$this->lang->line("Edit Campaign")."' href='".$edit_url."'><i class='fas fa-edit'></i></a>";
            }

            $action_width = ($action_count*47)+20;
            $info[$key]['actions'] ='
            <div class="dropdown d-inline dropright">
              <button class="btn btn-outline-primary dropdown-toggle no_caret" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-briefcase"></i>
              </button>
              <div class="dropdown-menu mini_dropdown text-center" style="width:'.$action_width.'px !important">';
                $info[$key]['actions'] .= $info[$key]['report'];
                $info[$key]['actions'] .= $info[$key]['edit'];
                $info[$key]['actions'] .= $force_porcess_str;
                $info[$key]['actions'] .= $info[$key]['delete'];
                $info[$key]['actions'] .="
              </div>
            </div>
            <script>
            $('[data-toggle=\"tooltip\"]').tooltip();
            $('[data-toggle=\"popover\"]').popover(); 
            $('[data-toggle=\"popover\"]').on(\"click\", function(e) {e.preventDefault(); return true;});
            </script>
            ";
        }
        $data['draw'] = (int)$_POST['draw'] + 1;
        $data['recordsTotal'] = $total_result;
        $data['recordsFiltered'] = $total_result;
        $data['data'] = convertDataTableResult($info, $display_columns ,$start,$primary_key="id");
        echo json_encode($data);
    }


    public function ajax_campaign_pause()
    {
        $this->ajax_check();
        $table_id = $this->input->post('table_id');
        $post_info = $this->basic->update_data('facebook_ex_conversation_campaign',array('id'=>$table_id),array('posting_status'=>'3','is_try_again'=>'0'));
        echo '1';
    }

    public function ajax_campaign_play()
    {
        $this->ajax_check();
        $table_id = $this->input->post('table_id');
        $post_info = $this->basic->update_data('facebook_ex_conversation_campaign',array('id'=>$table_id),array('posting_status'=>'1','is_try_again'=>'1'));
        echo '1';
    }


    public function force_reprocess_campaign()
    {
        $this->ajax_check();
        $id=$this->input->post("id");

        $where = array('id'=>$id,'user_id'=>$this->user_id);
        $data = array('is_try_again'=>'1','posting_status'=>'1');
        $this->basic->update_data('facebook_ex_conversation_campaign',$where,$data);
        if($this->db->affected_rows() != 0) echo "1";
        else  echo "0";
    }

    public function restart_campaign()
    {
        $this->ajax_check();
        $id=$this->input->post("table_id");

        $where = array('id'=>$id,'user_id'=>$this->user_id);
        $data = array('is_try_again'=>'1','posting_status'=>'1','last_try_error_count'=>0,'is_spam_caught'=>'0');
        $this->basic->update_data('facebook_ex_conversation_campaign',$where,$data);
        echo '1';
    }

    public function delete_campaign()
    {
        $this->ajax_check();
        $id=$this->input->post("id");

        $xdata = $this->basic->get_data("facebook_ex_conversation_campaign",array("where"=>array("id"=>$id,"user_id"=>$this->user_id)),array("posting_status","total_thread"));

        $current_total_thread_abs  = isset($xdata[0]["total_thread"]) ? $xdata[0]["total_thread"] : 0;
        $posting_status  = isset($xdata[0]["posting_status"]) ? $xdata[0]["posting_status"] : "";

        if($posting_status=="0") // removing usage data if deleted and campaign is pending
        {
            if($current_total_thread_abs>0)
            $this->_delete_usage_log($module_id=79,$request=$current_total_thread_abs);
        }

        if($this->basic->delete_data("facebook_ex_conversation_campaign",array("id"=>$id,"user_id"=>$this->user_id)))
        {
            if($this->basic->delete_data("facebook_ex_conversation_campaign_send",array("campaign_id"=>$id,"user_id"=>$this->user_id)))
            echo "1";
        }
        else echo "0";
    }



    public function create_conversation_campaign()
    {
        $data['body'] = "messenger_tools/bulk_message/create_campaign";
        $data['page_title'] = $this->lang->line("Create Conversation Broadcast");
        $page_info = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("facebook_rx_fb_user_info_id"=>$this->session->userdata("facebook_rx_fb_user_info"),"bot_enabled"=>'1')),$select='',$join='',$limit='',$start=NULL,$order_by='page_name ASC');
        $data["time_zone"]= $this->_time_zone_list();
        $data['page_info'] = $page_info;
        $data['locale_list'] = $this->sdk_locale();
        $data["time_zone_numeric"]= $this->_time_zone_list_numeric();
        $this->_viewcontroller($data);
    }


    public function create_conversation_campaign_action()
    {
        if(!$_POST) exit();
        $this->ajax_check();

        $user_id = $this->user_id;
        $campaign_name = $this->input->post('campaign_name',true);
        $campaign_message = $this->input->post('message',true);       
        $page_auto_id = $this->input->post('page',true); // page auto id
        $label_ids = $this->input->post('label_ids',true);
        $excluded_label_ids = $this->input->post('excluded_label_ids',true);
        $user_gender = $this->input->post('user_gender',true);
        $user_time_zone = $this->input->post('user_time_zone',true);
        $user_locale = $this->input->post('user_locale',true);

        if(!isset($label_ids) || !is_array($label_ids)) $label_ids=array();
        if(!isset($excluded_label_ids) || !is_array($excluded_label_ids)) $excluded_label_ids=array();

        $schedule_type = $this->input->post('schedule_type',true);
        if($schedule_type=='') $schedule_type='now';
        $schedule_time = $this->input->post('schedule_time',true);
        $time_zone = $this->input->post('time_zone',true);

        $delay_time = $this->input->post("delay_time",true);
        if($delay_time=="") $delay_time = 0;

        $posting_status = "0";
        $added_at = date("Y-m-d H:i:s");
        $is_spam_caught = "0";
        $successfully_sent = 0;
        $total_thread = 0;

        $pageinfo = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_auto_id,"user_id"=>$this->user_id)));
        if(!isset($pageinfo[0]))
        {
            echo json_encode(array('status'=>'0','message'=>$this->lang->line("Something went wrong.")));
            exit();
        }
        $fb_page_id  = $pageinfo[0]['page_id'];
        $page_name  = $pageinfo[0]['page_name'];

        $excluded_label_ids_temp=$excluded_label_ids;
        $unsubscribe_labeldata=$this->basic->get_data("messenger_bot_broadcast_contact_group",array("where"=>array("user_id"=>$this->user_id,"page_id"=>$page_auto_id,"unsubscribe"=>"1")));
        foreach ($unsubscribe_labeldata as $key => $value) 
        {
            array_push($excluded_label_ids_temp, $value["id"]);
        }

        if(count($label_ids)>0) $sql_part="("; else $sql_part="";        
        $sql_part_array=array();
        foreach ($label_ids as $key => $value) 
        {
           $sql_part_array[]="FIND_IN_SET('".$value."',contact_group_id) !=0";
        }        
        if(count($label_ids)>0) 
        {
            $sql_part.=implode(' OR ', $sql_part_array);
            $sql_part.=") AND ";
        }

        $sql_part2="";
        $sql_part_array2=array();
        foreach ($excluded_label_ids_temp as $key => $value) 
        {
          $sql_part_array2[]="NOT FIND_IN_SET('".$value."',contact_group_id) !=0";          
        }        
        if(count($excluded_label_ids_temp)>0) 
        {
            $sql_part2=implode(' AND ', $sql_part_array2);
            $sql_part2.=" AND ";
        }

        $sql_part3="";
        $sql_part_array3 = array();
        if($user_gender!='') $sql_part_array3[] = "gender = '{$user_gender}'";
        if($user_time_zone!='') $sql_part_array3[] = "timezone = '{$user_time_zone}'";
        if($user_locale!='') $sql_part_array3[] = "locale = '{$user_locale}'";

        if(count($sql_part_array3)>0) 
        {
            $sql_part3 = implode(' AND ', $sql_part_array3);
            $sql_part3 .=" AND ";
        }

        $sql="SELECT * FROM messenger_bot_subscriber WHERE ".$sql_part." ".$sql_part2." ".$sql_part3." user_id = ".$this->user_id." AND page_table_id = {$page_auto_id} AND permission='1' AND unavailable_conversation='0';";
        $lead_list=$this->basic->execute_query($sql);

        // echo $this->db->last_query(); exit();    

        $report = array();
        foreach ($lead_list as $key => $value)
        {
           if($value['client_thread_id']=="") continue; // dont send bulk message if thread id blank

           // $temp=explode(',', $value['contact_group_id']);
           // if(count($temp) > count($excluded_label_ids_temp))
           // $result=array_intersect($temp, $excluded_label_ids_temp);
           // else $result=array_intersect($excluded_label_ids_temp, $temp);
           // if(count($result)>0) continue;

           $total_thread++;

           $report[$page_auto_id][$value['client_thread_id']] = array
           (
            "client_username"=>$value["full_name"],
            "client_id"=>$value["subscribe_id"],
            "message_sent_id"=>"Pending",
            "sent_time"=>"x",
            "lead_id" =>  $value["id"],
            "page_name" => $page_name,
            "link" => $value["link"],
            "first_name" => $value["first_name"],
            "last_name" => $value["last_name"]
            );

        }

        // 24+1 inactivation becuase he is already sending a promo message
        $sql_24h="UPDATE messenger_bot_subscriber SET is_24h_1_sent='1' WHERE ".$sql_part." user_id = ".$this->user_id." AND page_table_id = {$page_auto_id} AND permission='1'";
        $this->basic->execute_complex_query($sql_24h);        
        // ===============================================================

        if($total_thread==0)
        {
            echo json_encode(array('status'=>'0','message'=>$this->lang->line("Campaign could not target any subscriber to reach message. Please try again with different targeting options.")));
            exit();
        }

        $data = array(
            'user_id' => $user_id,
            'page_id' => $page_auto_id,
            'fb_page_id' => $fb_page_id, 
            'page_name' => $page_name,
            'campaign_name' => $campaign_name,
            'campaign_message' => $campaign_message,
            'schedule_time' => $schedule_time,
            'time_zone' => $time_zone,
            'posting_status' => $posting_status,
            'is_spam_caught' => $is_spam_caught,
            'total_thread' => $total_thread,
            'successfully_sent' => $successfully_sent,
            'added_at' => $added_at,
            'report' => json_encode($report), // page and thread array json
            'delay_time' => $delay_time,
            'user_gender' => $user_gender,
            'user_time_zone' => $user_time_zone,
            'user_locale' => $user_locale
        );

        if(!empty($label_ids)) $data['label_ids'] = implode(',', $label_ids); else $data['label_ids'] ="";
        if(!empty($excluded_label_ids)) $data['excluded_label_ids'] = implode(',', $excluded_label_ids); else $data['excluded_label_ids'] = "";

        $fb_label_names = array();
        if(!empty($label_ids))
        {
            $fb_label_data=$this->basic->get_data("messenger_bot_broadcast_contact_group",array("where_in"=>array("id"=>$label_ids)));
            foreach ($fb_label_data as $key => $value) 
            {
               if($value['invisible']=='0')
               $fb_label_names[]=$value["group_name"];
            }  
        }
        $data['label_names'] = implode(',', $fb_label_names);

        $status=$this->_check_usage($module_id=79,$request=$total_thread);
        if($status=="3")  //monthly limit is exceeded, can not send another ,message this month
        {
            echo json_encode(array('status'=>'0','message'=>$this->lang->line("Sorry, your monthly limit to send conversation campaign message is exceeded.")));
            exit();
        }

        $this->basic->insert_data('facebook_ex_conversation_campaign', $data); // at first campaign is insrted to database , then proccessed
        $campaign_id= $this->db->insert_id();
        
        $report_insert=array();
        foreach($report as $key=>$value) // each report contain several page group of leads
        {   
            $page_id_send  = $key;
            foreach ($value as $key2 => $value2)  // Processing leads under page group
            {
                $client_thread_id_send = $key2;
                $report_insert[]=array
                (
                    "campaign_id"=>$campaign_id,   
                    "user_id"=>$this->user_id,   
                    "page_id"=>$page_id_send,   
                    "client_thread_id"=>$client_thread_id_send,   
                    "client_username"=>$value2["client_username"],
                    "client_id"=>$value2['client_id'],
                    "message_sent_id"=>"Pending",
                    "lead_id" =>  $value2["lead_id"],
                    "processed" =>  "0",
                    "page_name"=>$value2["page_name"],
                    "link"=> $value2["link"],
                    "first_name" => $value2["first_name"],
                    "last_name" => $value2["last_name"]
                );
            }
        }
        $this->db->insert_batch('facebook_ex_conversation_campaign_send', $report_insert); // strong the leads to send message in database

        $this->_insert_usage_log($module_id=79,$request=$total_thread);
        echo json_encode(array('status'=>'1'));
    }


    /********* SAMPLE REPORT FIELD FORMAT********/
    /*array
    (
        'page_id1'=>  // auto id
        array
        (
            thread_id1 = > array("name1","sent_time1","message_id1","client_id1");
            thread_id2 = > array("name2","sent_time2","message_id2","client_id2");
            thread_id3 = > array("name3","sent_time3","message_id3","client_id3");
        ),

        'page_id2'=> array
        (
            thread_id1 = > array("name1","sent_time1","message_id1","client_id1");
            thread_id2 = > array("name2","sent_time2","message_id2","client_id2");
            thread_id3 = > array("name3","sent_time3","message_id3","client_id3");
        ),

        'page_id2'=> array
        (
            thread_id1 = > array("name1","sent_time1","message_id1","client_id1");
            thread_id2 = > array("name2","sent_time2","message_id2","client_id2");
            thread_id3 = > array("name3","sent_time3","message_id3","client_id3");
        )
    );*/
    /********* SAMPLE REPORT FIELD FORMAT********/



    public function edit_conversation_campaign($id=0)
    {
        if($id==0) exit();

        $data['body'] = "messenger_tools/bulk_message/edit_campaign";
        $data['page_title'] = $this->lang->line("Edit Conversation Broadcast");
        $page_info = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("facebook_rx_fb_user_info_id"=>$this->session->userdata("facebook_rx_fb_user_info"),"bot_enabled"=>'1')),$select='',$join='',$limit='',$start=NULL,$order_by='page_name ASC');
        $data["time_zone"]= $this->_time_zone_list();
        $data['page_info'] = $page_info;
        $data["xdata"] = $this->basic->get_data("facebook_ex_conversation_campaign",array("where"=>array("id"=>$id,"user_id"=>$this->user_id)));

        $data['locale_list'] = $this->sdk_locale();
        $data["time_zone_numeric"]= $this->_time_zone_list_numeric();

        // only pending campaigns are editable
        if(!isset($data["xdata"][0]["posting_status"]) || $data["xdata"][0]["posting_status"]!='0' ) exit();

        $previous_exclude = isset($data["xdata"][0]["do_not_send_to"]) ? json_decode($data["xdata"][0]["do_not_send_to"],true) : array();      
        $this->_viewcontroller($data);
    }

    public function edit_conversation_campaign_action()
    {
        if(!$_POST) exit();
        $this->ajax_check();

        $user_id = $this->user_id;
        $campaign_name = $this->input->post('campaign_name',true);
        $campaign_message = $this->input->post('message',true);       
        $page_auto_id = $this->input->post('page',true); // page auto id
        $label_ids = $this->input->post('label_ids',true);
        $excluded_label_ids = $this->input->post('excluded_label_ids',true);        
        $user_gender = $this->input->post('user_gender',true);
        $user_time_zone = $this->input->post('user_time_zone',true);
        $user_locale = $this->input->post('user_locale',true);

        if(!isset($label_ids) || !is_array($label_ids)) $label_ids=array();
        if(!isset($excluded_label_ids) || !is_array($excluded_label_ids)) $excluded_label_ids=array();

        $schedule_type = $this->input->post('schedule_type',true);
        if($schedule_type=='') $schedule_type='now';
        $schedule_time = $this->input->post('schedule_time',true);
        $time_zone = $this->input->post('time_zone',true);

        $delay_time = $this->input->post("delay_time",true);
        if($delay_time=="") $delay_time = 0;

        $campaign_id = $this->input->post("campaign_id");
        $previous_thread = $this->input->post("previous_thread");

        $posting_status = "0";
        $added_at = date("Y-m-d H:i:s");
        $is_spam_caught = "0";
        $successfully_sent = 0;
        $total_thread = 0;

        $pageinfo = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_auto_id,"user_id"=>$this->user_id)));
        if(!isset($pageinfo[0]))
        {
            echo json_encode(array('status'=>'0','message'=>$this->lang->line("Something went wrong.")));
            exit();
        }
        $fb_page_id  = $pageinfo[0]['page_id'];
        $page_name  = $pageinfo[0]['page_name'];

        $excluded_label_ids_temp=$excluded_label_ids;
        $unsubscribe_labeldata=$this->basic->get_data("messenger_bot_broadcast_contact_group",array("where"=>array("user_id"=>$this->user_id,"page_id"=>$page_auto_id,"unsubscribe"=>"1")));
        foreach ($unsubscribe_labeldata as $key => $value) 
        {
            array_push($excluded_label_ids_temp, $value["id"]);
        }  

        if(count($label_ids)>0) $sql_part="("; else $sql_part="";        
        $sql_part_array=array();
        foreach ($label_ids as $key => $value) 
        {
           $sql_part_array[]="FIND_IN_SET('".$value."',contact_group_id) !=0";
        }
        $sql_part.=implode(' OR ', $sql_part_array);
        if(count($label_ids)>0) $sql_part.=") AND ";

        $sql_part_array2=array();
        foreach ($excluded_label_ids_temp as $key => $value) 
        {
          $sql_part_array2[]="NOT FIND_IN_SET('".$value."',contact_group_id) !=0";          
        }        
        if(count($excluded_label_ids_temp)>0) 
        {
            $sql_part2=implode(' AND ', $sql_part_array2);
            $sql_part2.=" AND ";
        }

        $sql_part3="";
        $sql_part_array3 = array();
        if($user_gender!='') $sql_part_array3[] = "gender = '{$user_gender}'";
        if($user_time_zone!='') $sql_part_array3[] = "timezone = '{$user_time_zone}'";
        if($user_locale!='') $sql_part_array3[] = "locale = '{$user_locale}'";

        if(count($sql_part_array3)>0) 
        {
            $sql_part3 = implode(' AND ', $sql_part_array3);
            $sql_part3 .=" AND ";
        }


        $sql="SELECT * FROM messenger_bot_subscriber WHERE ".$sql_part." ".$sql_part2." ".$sql_part3." user_id = ".$this->user_id." AND page_table_id = {$page_auto_id} AND permission='1' AND unavailable_conversation='0';";
        $lead_list=$this->basic->execute_query($sql);     


        $report = array();
        foreach ($lead_list as $key => $value)
        {
           if($value['client_thread_id']=="") continue; // dont send bulk message if thread id blank

           $temp=explode(',', $value['contact_group_id']);
           if(count($temp) > count($excluded_label_ids_temp))
           $result=array_intersect($temp, $excluded_label_ids_temp);
           else $result=array_intersect($excluded_label_ids_temp, $temp);
           if(count($result)>0) continue;

           $total_thread++;

           $report[$page_auto_id][$value['client_thread_id']] = array
           (
            "client_username"=>$value["full_name"],
            "client_id"=>$value["subscribe_id"],
            "message_sent_id"=>"Pending",
            "sent_time"=>"x",
            "lead_id" =>  $value["id"],
            "page_name" => $page_name,
            "link"=> $value["link"],
            "first_name" => $value["first_name"],
            "last_name" => $value["last_name"]
            );

        }

        // 24+1 inactivation becuase he is already sending a promo message
        $sql_24h="UPDATE messenger_bot_subscriber SET is_24h_1_sent='1' WHERE ".$sql_part." user_id = ".$this->user_id." AND page_table_id = {$page_auto_id} AND permission='1'";
        $this->basic->execute_complex_query($sql_24h);        
        // ===============================================================

        if($total_thread==0)
        {
            echo json_encode(array('status'=>'0','message'=>$this->lang->line("Campaign could not target any subscriber to reach message. Please try again with different targeting options.")));
            exit();
        }

        $data = array(
            'user_id' => $user_id,
            'page_id' => $page_auto_id,
            'fb_page_id' => $fb_page_id, 
            'page_name' => $page_name,
            'campaign_name' => $campaign_name,
            'campaign_message' => $campaign_message,
            'schedule_time' => $schedule_time,
            'time_zone' => $time_zone,
            'posting_status' => $posting_status,
            'is_spam_caught' => $is_spam_caught,
            'total_thread' => $total_thread,
            'successfully_sent' => $successfully_sent,
            'report' => json_encode($report), // page and thread array json
            'delay_time' => $delay_time,            
            'user_gender' => $user_gender,
            'user_time_zone' => $user_time_zone,
            'user_locale' => $user_locale
        );

        if(!empty($label_ids)) $data['label_ids'] = implode(',', $label_ids); else $data['label_ids'] ="";
        if(!empty($excluded_label_ids)) $data['excluded_label_ids'] = implode(',', $excluded_label_ids); else $data['excluded_label_ids'] = "";

        $fb_label_names = array();
        if(!empty($label_ids))
        {
            $fb_label_data=$this->basic->get_data("messenger_bot_broadcast_contact_group",array("where_in"=>array("id"=>$label_ids)));
            foreach ($fb_label_data as $key => $value) 
            {
               if($value['invisible']=='0')
               $fb_label_names[]=$value["group_name"];
            }  
        }
        $data['label_names'] = implode(',', $fb_label_names);

        $current_total_thread = $previous_thread - $total_thread;
        $current_total_thread_abs = abs($current_total_thread);
        if($current_total_thread<0)
        {
            $status=$this->_check_usage($module_id=79,$request=$current_total_thread_abs);
             if($status=="3")  //monthly limit is exceeded, can not send another ,message this month
            {
                echo json_encode(array('status'=>'0','message'=>$this->lang->line("Sorry, your monthly limit to send promo message is exceeded.")));
                exit();
            }
        }

        $this->basic->update_data('facebook_ex_conversation_campaign',array("id"=>$campaign_id,"user_id"=>$this->user_id),$data); // at first campaign is insrted to database , then proccessed

        $report_insert=array();
        foreach($report as $key=>$value) // each report contain several page group of leads
        {   
            $page_id_send  = $key;
            foreach ($value as $key2 => $value2)  // Processing leads under page group
            {
                $client_thread_id_send = $key2;
                $report_insert[]=array
                (
                    "campaign_id"=>$campaign_id,   
                    "user_id"=>$this->user_id,   
                    "page_id"=>$page_id_send,   
                    "client_thread_id"=>$client_thread_id_send,   
                    "client_username"=>$value2["client_username"],
                    "client_id"=>$value2['client_id'],
                    "message_sent_id"=>"Pending",
                    "lead_id" =>  $value2["lead_id"],
                    "processed" =>  "0",
                    "page_name"=>$value2["page_name"],
                    "link"=>$value2["link"],
                    "first_name" => $value2["first_name"],
                    "last_name" => $value2["last_name"]
                );
            }
        }
        $this->basic->delete_data("facebook_ex_conversation_campaign_send",array("campaign_id"=>$campaign_id,"user_id"=>$this->user_id)); // delete previosu tread list
        $this->db->insert_batch('facebook_ex_conversation_campaign_send', $report_insert); // strong the leads to send message in database
        
        if($current_total_thread<0)
        $this->_insert_usage_log($module_id=79,$request=$current_total_thread_abs);
        else $this->_delete_usage_log($module_id=79,$request=$current_total_thread_abs);

        echo json_encode(array('status'=>'1'));
    }



    public function send_test_message()
    {

        $this->ajax_check();

        $campaign_message = $this->input->post("message");        
        $thread_ids = $this->input->post("thread_ids");
        $thread_ids = array_unique($thread_ids);
        $total_thread = count($thread_ids);

        $successfully_sent=0;
        $join = array('facebook_rx_fb_page_info'=>"facebook_rx_fb_page_info.id=messenger_bot_subscriber.page_table_id,left");
        $where = array("where_in"=>array("client_thread_id"=>$thread_ids,"messenger_bot_subscriber.user_id"=>array($this->user_id),"facebook_rx_fb_page_info.user_id"=>array($this->user_id),"facebook_rx_fb_page_info.facebook_rx_fb_user_info_id"=>array($this->session->userdata("facebook_rx_fb_user_info"))));
        $info = $this->basic->get_data("messenger_bot_subscriber",$where,array("page_access_token","full_name","client_thread_id","subscribe_id"),$join,'','','full_name asc','client_thread_id');
        $str="";
        $str.= "<table class='table table-condensed table-bordered'>";
        $str.=  "<thread><tr>";
            $str.=  "<th>".$this->lang->line("SL")."</th>";
            $str.=  "<th>".$this->lang->line("Subscriber")."</th>";
            $str.=  "<th>".$this->lang->line("Status")."</th>";
            $str.=  "<th>".$this->lang->line("Response")."</th>";
        $str.=  "</tr></thead><tbody>";
        $sl=0;
        foreach ($info as $key => $value)
        {
            $sl++;
            $client_username_send = $value["full_name"];
            $client_thread_id_send = $value["client_thread_id"];
            $client_id_send = $value["subscribe_id"];
            $page_access_token_send = $value["page_access_token"];

            $campaign_message_send = $campaign_message;
            // added by mostofa at 04/03/2017
            $client_username_send_array = explode(' ', $client_username_send);
            $client_last_name = array_pop($client_username_send_array);
            $client_first_name = implode(' ', $client_username_send_array);

            $campaign_message_send = str_replace('#LEAD_USER_NAME#',$client_username_send,$campaign_message_send);
            // added by mostofa at 04/03/2017
            $campaign_message_send = str_replace('#LEAD_USER_FIRST_NAME#',$client_first_name,$campaign_message_send);
            $campaign_message_send = str_replace('#LEAD_USER_LAST_NAME#',$client_last_name,$campaign_message_send);

            // $campaign_message_send = $campaign_message_send."\n\n"." [ This message is sent for testing purpose using ".$this->config->item('product_short_name')." ]";

            $error_msg="<span class='badge badge-status'><i class='fas fa-check-circle green'></i> ".$this->lang->line('Successful')."</span>";
            $message_sent_id = "";
            try
            {
                $response = $this->fb_rx_login->send_message_to_thread($client_thread_id_send,$campaign_message_send,$page_access_token_send);
                if(isset($response['id']))
                {
                    $message_sent_id = $response['id'];
                    $successfully_sent++;
                }
                else
                {
                    if(isset($response["error"]["message"])) $error_msg = $response["error"]["message"];
                }

            }

            catch(Exception $e)
            {
               $error_msg = $e->getMessage();
            }

            $str.=  "<tr>";
                $str.=  "<td>".$sl."</td>";
                $str.=  "<td>".$client_username_send."</td>";
                $str.=  "<td>".$error_msg."</td>";
                $str.=  "<td>".$message_sent_id."</td>";
            $str.=  "</tr>";

        }
        $str.= "</tbody></table>";
        echo $str;
    }


    public function lead_autocomplete($check_permission=1,$page_ids_str="")
    {
       $this->ajax_check();
       $search_query= $this->input->get('search');

       $page_ids = array();
       if($page_ids_str!="")  //  - seperated fb page ids
       $page_ids = explode('-', $page_ids_str);
       // $page_ids=array(95);

       $this->db->select(array('facebook_rx_fb_page_info.page_name','messenger_bot_subscriber.*'));
       $this->db->from('messenger_bot_subscriber');
       $this->db->like('full_name', $search_query);
       $this->db->order_by('full_name', 'ASC');
       // $this->db->group_by('subscribe_id');
       $this->db->where("messenger_bot_subscriber.user_id",$this->user_id);
       // $this->db->where("facebook_rx_fb_page_info.user_id",$this->user_id);
       // $this->db->where("facebook_rx_fb_page_info.deleted",'0');
       $this->db->where("facebook_rx_fb_page_info.facebook_rx_fb_user_info_id",$this->session->userdata("facebook_rx_fb_user_info"));
       $this->db->join("facebook_rx_fb_page_info","facebook_rx_fb_page_info.id=messenger_bot_subscriber.page_table_id",'left');

       if($check_permission==1)  // if check permission is 1 then it will only grab subscribed users
       {
        $this->db->where("permission","1");
        $this->db->where("unavailable_conversation","0");
       }

       if(count($page_ids)>0)  // if facebook page ids is passed then it will filter by page ids, may be needed later, not used for now
       $this->db->where_in("messenger_bot_subscriber.page_table_id",$page_ids);

       $this->db->limit(30);
       $data=$this->db->get()->result_array();
       $results=array();

       foreach ($data as $key => $value)
       {
          $results[]=array("value"=>$value["client_thread_id"],"text"=>$value["full_name"]." (".$value["page_name"].")");
       }
       echo json_encode($results);
    }



    public function campaign_sent_status()
    {       
        $this->ajax_check();
        $id = $this->input->post("id");

        $campaign_data = $this->basic->get_data("facebook_ex_conversation_campaign",array("where"=>array("id"=>$id,"user_id"=>$this->user_id)));
        $report = isset($campaign_data[0]["report"]) ? json_decode($campaign_data[0]["report"],true) : array();
        $campaign_name  = isset($campaign_data[0]["campaign_name"]) ? $campaign_data[0]["campaign_name"] : "";
        $is_spam_caught  = isset($campaign_data[0]["is_spam_caught"]) ? $campaign_data[0]["is_spam_caught"] : 0;
        $total_thread  = isset($campaign_data[0]["total_thread"]) ? $campaign_data[0]["total_thread"] : 0;
        $successfully_sent  = isset($campaign_data[0]["successfully_sent"]) ? $campaign_data[0]["successfully_sent"] : 0;
        $error_message  = isset($campaign_data[0]["error_message"]) ? $campaign_data[0]["error_message"] : "";
        // $video_url  = isset($campaign_data[0]["attached_video"]) ? $campaign_data[0]["attached_video"] : "";
        // $link  = isset($campaign_data[0][" attached_url"]) ? $campaign_data[0]["  attached_url"] : "";
        $campaign_message  = isset($campaign_data[0]["campaign_message"]) ? $campaign_data[0]["campaign_message"] : "";

        $campaign_message_send = $campaign_message;
        // if($video_url!="") $campaign_message_send = $campaign_message_send."\n"."<a target='_BLANK' href='{$video_url}'>{$video_url}</a>";
        // else if($link!="") $campaign_message_send = $campaign_message_send."\n"."<a target='_BLANK' href='{$link}'>{$link}</a>";

        $edit_url = site_url('messenger_bot_broadcast/edit_conversation_message_content/'.$campaign_data[0]['id']);
      
        $posting_status = $campaign_data[0]['posting_status'];
        if( $posting_status == '2') $posting_status = '<span class="text-success"> ('.$this->lang->line("Completed").')</span>';
        else if( $posting_status == '1') $posting_status = '<span class="text-warning"> ('.$this->lang->line("Processing").')</span>';
        else if( $posting_status == '3') $posting_status = '<span class="text-muted"> ('.$this->lang->line("Paused").')</span>';
        else if( $posting_status == '4') $posting_status = '<span class="text-dark"> ('.$this->lang->line("On-hold").')</span>';
        else $posting_status = '<span class="text-danger"> ('.$this->lang->line("Pending").')</span>';



        $response = "";

        $drop_menu = "";
        $send_where_it_is_left_off = "";

        if($campaign_data[0]['posting_status']=='4')
        {
            $send_where_it_is_left_off ='<a class="dropdown-item has-icon restart_button pointer" title="'.$this->lang->line('If the campaign has been completed due to error but there are still some subscriber to be sent, you can resume it from it was left off by force.').'" data-toggle="tooltip"  table_id="'.$id.'"><i class="fas fa-sync"></i> '.$this->lang->line("Force Resume").'</a>';
        }

        if($campaign_data[0]['posting_status'] != '2')
        $drop_menu = '<div class="btn-group dropleft float-right"><button type="button" class="btn btn-primary btn-lg dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.$this->lang->line("Options").'  </button>  <div class="dropdown-menu dropleft"> <a class="dropdown-item has-icon pointer" href="'.$edit_url.'" ><i class="fas fa-edit"></i> '.$this->lang->line("Edit Content").'</a> '.$send_where_it_is_left_off.'</div> </div>';

        $response .= '<script>
                     $(document).ready(function() {   
                        var drop_menu = \''.$drop_menu.'\';
                        setTimeout(function(){ 
                          $("#campaign_report_filter").append(drop_menu);
                          $("[data-toggle=\'tooltip\']").tooltip();
                        }, 1000);
                                       
                        var perscroll2;
                        var ttt = $("#campaign_report").DataTable({
                            language: 
                            {
                              url: "'.base_url('assets/modules/datatables/language/'.$this->language.'.json').'"
                            },
                            dom: \'<"top"f>rt<"bottom"lip><"clear">\',
                            fnInitComplete:function(){  // when initialization is completed then apply scroll plugin
                                  if(areWeUsingScroll)
                                  {
                                    if (perscroll2) perscroll2.destroy();
                                    perscroll2 = new PerfectScrollbar("#campaign_report_wrapper .dataTables_scrollBody");
                                  }
                              },
                              scrollX: "auto",
                              fnDrawCallback: function( oSettings ) { //on paginition page 2,3.. often scroll shown, so reset it and assign it again 
                                  if(areWeUsingScroll)
                                  { 
                                    if (perscroll2) perscroll2.destroy();
                                    perscroll2 = new PerfectScrollbar("#campaign_report_wrapper .dataTables_scrollBody");
                                  }
                              }
                        });

                        $(".modal").on("hidden.bs.modal", function (e) { 
                            if ($(".modal:visible").length) { 
                                $("body").addClass("modal-open");
                            }
                        });

                      

                    });
                 </script>';

        // $sent_status = "{$this->lang->line("Sent")} {$successfully_sent} / {$total_thread}";
        // $response .= "<h4 class='text-center'>".$campaign_name." : ".$sent_status.$posting_status."</h4><div>";

        if($is_spam_caught==1)
        $spam_text = $this->lang->line("Campaign was marked as spam."). " <br/> ";
        else $spam_text="";

        if($error_message!="")
        $response .= "<div class='alert alert-danger text-center'> {$spam_text} {$this->lang->line("Something went wrong for one or more message. Original error message :")} {$error_message} <br><a class='pointer' style='text-decoration:underline;' href='' data-toggle='modal' data-target='#error_message_learn'>".$this->lang->line("Learn more about common error messages")."</a></div>";

        $response .='
        
        <div class="row">
          <div class="col-md-4 col-12">
            <div class="card card-statistic-1">
              <div class="card-icon bg-primary">
                <i class="fas fa-info-circle"></i>
              </div>
              <div class="card-wrap">
                <div class="card-header">
                  <h4>'. $this->lang->line("Campaign").$posting_status.'</h4>
                </div>
                <div class="card-body">
                  '.$campaign_name.'
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4 col-12">
            <div class="card card-statistic-1">
              <div class="card-icon bg-primary">
                <i class="far fa-newspaper"></i>
              </div>
              <div class="card-wrap">
                <div class="card-header">
                  <h4>'.$this->lang->line("Page Name").'</h4>
                </div>
                <div class="card-body">
                  <a target="_BLANK" href="https://facebook.com/'.$campaign_data[0]["fb_page_id"].'">'.$campaign_data[0]["page_name"].'</a>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4 col-12">
            <div class="card card-statistic-1">
              <div class="card-icon bg-primary">
                <i class="fas fa-paper-plane"></i>
              </div>
              <div class="card-wrap">
                <div class="card-header">
                  <h4>'.$this->lang->line("Sent").'</h4>
                </div>
                <div class="card-body">
                  '.$successfully_sent.'/'.$total_thread.'
                </div>
              </div>
            </div>
          </div>
        </div>
        <style>
        .card-statistic-1{border:.5px solid #dee2e6;border-radius: 4px;}
        .card-statistic-1 .card-icon i{font-size:40px !important;margin-top:20px;}
        </style>';

        $response .="<div class='table-responsive2 data-card' id='campaign_report_con'>";
        $response .="<table id='campaign_report' class='table table-bordered table-condensed nowrap'>";
        $response .= "<thead><tr>";
        $response .= "<th class='text-center'>{$this->lang->line("SL")}</th>";
        $response .= "<th>{$this->lang->line("Subscriber")}</th>";
        $response .= "<th class='text-center'>{$this->lang->line("Sent at")}</th>";
        $response .= "<th>{$this->lang->line("Response")}</th>";
        $response .= "</tr></thead></tbody>";
        $i=0;

        foreach ($report as $key2 => $value2)
        {
          foreach ($value2 as $key => $value)
          {
                if(!isset($value["client_id"])) $value["client_id"] = "";
                if(!isset($value["client_username"])) $value["client_username"] = "";
                if(!isset($value["sent_time"])) $value["sent_time"] = "Pending";
                if(!isset($value["page_name"])) $value["page_name"] = "";
                if(!isset($value["message_sent_id"])) $value["message_sent_id"] = "";
                if(!isset($value["page_id"])) $value["page_id"] = "";

                $message_sent_id_formatted=$value["message_sent_id"];
                if($message_sent_id_formatted=="Pending") $message_sent_id_formatted="<i class='fas fa-times red'></i> ".$this->lang->line('Pending');

                $sent_time_formatted="x";
                if($value["sent_time"]!=="Pending" && $value["sent_time"]!=="x") $sent_time_formatted=date("M j, y H:i",strtotime($value["sent_time"]));

                $page_name_formatted=$value["page_name"];
                if($page_name_formatted=="") $page_name_formatted='';

                $i++;
                $response .= "<tr>";
                $response .= "<td class='text-center'>".$i."</td>";

                 if(strpos($message_sent_id_formatted, '#368'))
                {     
                    $unsubs ="<span class='btn btn-outline-danger btn-sm unsubscribe_me' style='cursor:pointer;' client_id='".$value['client_id']."'><i class='fas fa-times-circle red'></i> ".$this->lang->line('Unsubscribe')."</span>";
                    if(isset($value["link"]) && $value["link"] != '')
                    { 
                        $link = "https://www.facebook.com".$value["link"];                       
                        $response .= "<td>".$unsubs." <a href='".$link."' target='_blank'>".$value["client_username"]."</a></td>";
                    }               
                    else  $response .= "<td>".$unsubs." ".$value["client_username"]."</td>";
                }
                else
                {     
                    if(isset($value["link"]) && $value["link"] != '')
                    {  
                        $link = "https://www.facebook.com".$value["link"];                      
                        $response .= "<td><a href='".$link."' target='_blank'>".$value["client_username"]."</a></td>";
                    }               
                    else $response .= "<td>".$value["client_username"]."</td>";

                }

                $response .= "<td class='text-center'>".$sent_time_formatted."</td>";
                $response .= "<td>".$message_sent_id_formatted."</td>";
                $response .= "</tr>";
          }
        }
        $response .= "</tbody></table></div>";
        $response.="<br/><div class='section'><div class='section-title'>".$this->lang->line("Original Message")."</div><div class='alert alert-light'>".nl2br($campaign_message_send)."</[div]></div>";

        echo $response;
    }


    public function edit_conversation_message_content($id=0)
    {
        if($id==0) exit();

        $data['body'] = "messenger_tools/bulk_message/edit_message_content";
        $data['page_title'] = $this->lang->line("Edit Conversation Broadcast Message");
        $data["xdata"] = $this->basic->get_data("facebook_ex_conversation_campaign",array("where"=>array("id"=>$id,"user_id"=>$this->user_id)));
        $this->_viewcontroller($data);
    }

    public function edit_conversation_message_content_action()
    {
        $this->ajax_check();

        $campaign_id = $this->input->post("campaign_id");

        $user_id = $this->user_id;
        $campaign_message = $this->input->post('message');
        

        $data = array('campaign_message' => $campaign_message);

        $this->basic->update_data('facebook_ex_conversation_campaign',array("id"=>$campaign_id,"user_id"=>$this->user_id),$data); 

    }




}
