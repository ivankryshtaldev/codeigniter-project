<?php

require_once("Home.php"); // loading home controller

class Social_accounts extends Home
{ 
    
    public function __construct()
    {
        parent::__construct();
        if ($this->session->userdata('logged_in') != 1)
        redirect('home/login_page', 'location');   
        
        if($this->session->userdata('user_type') != 'Admin' && !in_array(65,$this->module_access))
        redirect('home/login_page', 'location'); 

        if($this->session->userdata("facebook_rx_fb_user_info")==0 && $this->config->item("backup_mode")==1 && $this->uri->segment(2)!="app_delete_action")
        redirect('social_apps/index','refresh');

        $this->important_feature();
        $this->member_validity();
        
        $this->load->library("fb_rx_login");       
    }


    public function index()
    {
      $this->account_import();
    }
  
    public function account_import()
    {
        $data['body'] = 'facebook_rx/account_import';
        $data['page_title'] = $this->lang->line('Facebook Account Import');

        $redirect_url = base_url()."social_accounts/manual_renew_account";
        $fb_login_button = $this->fb_rx_login->login_for_user_access_token($redirect_url);
        $data['fb_login_button'] = $fb_login_button;

        $where['where'] = array('user_id'=>$this->user_id);
        $existing_accounts = $this->basic->get_data('facebook_rx_fb_user_info',$where);

        $show_import_account_box = 1;
        $data['show_import_account_box'] = 1;
        if(!empty($existing_accounts))
        {
            $i=0;
            foreach($existing_accounts as $value)
            {
                $existing_account_info[$i]['need_to_delete'] = $value['need_to_delete'];
                if($value['need_to_delete'] == '1')
                {
                   $show_import_account_box = 0; 
                   $data['show_import_account_box'] = $show_import_account_box;
                }

                $existing_account_info[$i]['fb_id'] = $value['fb_id'];
                $existing_account_info[$i]['userinfo_table_id'] = $value['id'];
                $existing_account_info[$i]['name'] = $value['name'];
                $existing_account_info[$i]['email'] = $value['email'];
                $existing_account_info[$i]['user_access_token'] = $value['access_token'];

                $valid_or_invalid = $this->fb_rx_login->access_token_validity_check_for_user($value['access_token']);
                if($valid_or_invalid)
                {
                    $existing_account_info[$i]['validity'] = 'yes';
                }
                else{
                    $existing_account_info[$i]['validity'] = 'no';
                }


                $where = array();
                $where['where'] = array('facebook_rx_fb_user_info_id'=>$value['id']);
                $page_count = $this->basic->get_data('facebook_rx_fb_page_info',$where);
                $existing_account_info[$i]['page_list'] = $page_count;
                if(!empty($page_count))
                {
                    $existing_account_info[$i]['total_pages'] = count($page_count);                    
                }
                else
                    $existing_account_info[$i]['total_pages'] = 0;


                $group_count = $this->basic->get_data('facebook_rx_fb_group_info',$where);
                $existing_account_info[$i]['group_list'] = $group_count;
                if(!empty($group_count))
                {
                    $existing_account_info[$i]['total_groups'] = count($group_count);                    
                }
                else
                    $existing_account_info[$i]['total_groups'] = 0;

                $i++;
            }

            $data['existing_accounts'] = $existing_account_info;
        }
        else
            $data['existing_accounts'] = '0';


        $this->_viewcontroller($data);
    }



    public function group_delete_action()
    {
        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                $response['status'] = 0;
                $response['message'] = "You can not delete anything from admin account!!";
                echo json_encode($response);
                exit();
            }
        }


        $table_id = $this->input->post("group_table_id");
        $data = array('deleted' => '1');
        $this->basic->delete_data('facebook_rx_fb_group_info',array('id'=>$table_id,'user_id'=>$this->user_id));
        echo json_encode(array('status'=>1,'message'=>$this->lang->line('Group has been deleted successfully.')));
    }


    public function page_delete_action()
    {
        $this->ajax_check();
        $response = array();
        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                $response['status'] = 0;
                $response['message'] = "You can not delete anything from admin account!!";
                echo json_encode($response);
                exit();
            }
        }

        $table_id = $this->input->post("page_table_id",true);
        $response = $this->delete_data_basedon_page($table_id);
        echo $response;

    }


    public function account_delete_action()
    {
        $response = array();
        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                $response['status'] = 0;
                $response['message'] = "You can't delete anything from admin account!!";
                echo json_encode($response);
                exit();
            }
        }
        
        $facebook_rx_fb_user_info_id = $this->input->post("user_table_id");

        $account_information = $this->basic->get_data('facebook_rx_fb_user_info',array('where'=>array('id'=>$facebook_rx_fb_user_info_id,'user_id'=>$this->user_id)));
        if(empty($account_information)){
            echo json_encode(array('success'=>0,'message'=>$this->lang->line("Account is not found for this user. Something is wrong.")));
            exit();
        }


        $page_list = $this->basic->get_data('facebook_rx_fb_page_info',array('where'=>array('facebook_rx_fb_user_info_id'=>$facebook_rx_fb_user_info_id)),array('id','page_id'));

        foreach($page_list as $value)
        {
        	$this->delete_data_basedon_page($value['id']);
        }

        $response = $this->delete_data_basedon_account($facebook_rx_fb_user_info_id);
        
        echo json_encode($response);
        
    }



    public function app_delete_action()
    {
     if($this->is_demo == '1')
      {
          if($this->session->userdata('user_type') == "Admin")
          {
              $response['status'] = 0;
              $response['message'] = "You can not delete anything from admin account!!";
              echo json_encode($response);
              exit();
          }
      }

      $this->ajax_check();
      $app_table_id = $this->input->post('app_table_id',true);
      $app_info = $this->basic->get_data('facebook_rx_config',array('where'=>array('id'=>$app_table_id,'user_id'=>$this->user_id)));
      if(empty($app_info))
      {
        $response['status'] = 0;
        $response['message'] = $this->lang->line('We could not find any APP with this ID for this account.');  
        echo json_encode($response);
        exit;
      }

      $fb_user_infos = $this->basic->get_data('facebook_rx_fb_user_info',array('where'=>array('facebook_rx_config_id'=>$app_table_id)),array('id'));
      foreach($fb_user_infos as $value)
      {
        $fb_page_infos = $this->basic->get_data('facebook_rx_fb_page_info',array('where'=>array('facebook_rx_fb_user_info_id'=>$value['id'])),array('id'));
        foreach($fb_page_infos as $value2)
          $this->delete_data_basedon_page($value2['id'],'1');

        $this->delete_data_basedon_account($value['id'],'1');
      }

      $this->basic->delete_data('facebook_rx_config',array('id'=>$app_table_id,'user_id'=>$this->user_id));
      $this->session->sess_destroy(); 
      $response['status'] = 1;
      $response['message'] = $this->lang->line("APP and all the data corresponding to this APP has been deleted successfully. Now you'll be redirected to the login page.");  
      echo json_encode($response);
    }



    public function enable_disable_webhook()
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(200,$this->module_access))
        exit();
        if(!$_POST) exit();

        $response = array();
        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                $response['status'] = 0;
                $response['message'] = "This function is disabled from admin account in this demo!!";
                echo json_encode($response);
                exit();
            }
        }

        $user_id = $this->user_id;
        $page_id=$this->input->post('page_id');
        $restart=$this->input->post('restart');
        $enable_disable=$this->input->post('enable_disable');
        $page_data=$this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_id,"user_id"=>$this->user_id)));

        if(empty($page_data)){

            echo json_encode(array('success'=>0,'message'=>$this->lang->line("Page is not found for this user. Something is wrong.")));
            exit();
        }


        $fb_page_id=isset($page_data[0]["page_id"]) ? $page_data[0]["page_id"] : "";
        $page_access_token=isset($page_data[0]["page_access_token"]) ? $page_data[0]["page_access_token"] : "";
        $persistent_enabled=isset($page_data[0]["persistent_enabled"]) ? $page_data[0]["persistent_enabled"] : "0";
        $fb_user_id = $page_data[0]["facebook_rx_fb_user_info_id"];
        $fb_user_info = $this->basic->get_data('facebook_rx_fb_user_info',array('where'=>array('id'=>$fb_user_id)));
        $this->fb_rx_login->app_initialize($fb_user_info[0]['facebook_rx_config_id']); 
        if($enable_disable=='enable')
        {
            $already_enabled = $this->basic->get_data('facebook_rx_fb_page_info',array('where'=>array('page_id'=>$fb_page_id,'bot_enabled !='=>'0')));
            if(!empty($already_enabled))
            {                
                if($already_enabled[0]['user_id'] != $this->user_id || $already_enabled[0]['facebook_rx_fb_user_info_id'] != $fb_user_id )
                {
                    echo json_encode(array('success'=>0,'message'=>$this->lang->line("This page is already enabled by other Admin.")));
                    exit();
                }
            }
            //************************************************//
            if($restart != '1')
            {                
                $status=$this->_check_usage($module_id=200,$request=1);
                if($status=="2") 
                {
                    echo json_encode(array('success'=>0,'message'=>$this->lang->line("Module limit is over.")));
                    exit();
                }
                else if($status=="3") 
                {
                    echo json_encode(array('success'=>0,'message'=>$this->lang->line("Module limit is over.")));
                    exit();
                }
            }
            //************************************************//

            $output=$this->fb_rx_login->enable_bot($fb_page_id,$page_access_token);
            if(!isset($output['error'])) $output['error'] = '';

            if($output['error'] == '')
            {
                $this->basic->update_data("facebook_rx_fb_page_info",array("id"=>$page_id),array("bot_enabled"=>"1"));
                $this->getstarted_enable_disable_onpage($page_id,$started_button_enabled='1',$page_access_token,$fb_user_info[0]['facebook_rx_config_id']);
                $this->check_review_status_broadcaster($page_table_id=$page_id,$fb_page_id,$page_access_token,$fb_user_info[0]['facebook_rx_config_id']);
                $this->add_system_quick_email_reply($page_id,$fb_page_id);
                $this->add_system_quick_phone_reply($page_id,$fb_page_id);
                $this->add_system_quick_location_reply($page_id,$fb_page_id);
                $this->add_system_quick_birthday_reply($page_id,$fb_page_id);
                $this->add_system_postback_entry($page_id,$fb_page_id);
                $this->add_system_getstarted_reply_entry($page_id,$fb_page_id);
                $this->add_system_nomatch_reply_entry($page_id,$fb_page_id);
                if($restart != '1')                    
                    $this->_insert_usage_log($module_id=200,$request=1);
                $response['status'] = 1; 
                $response['message'] = $this->lang->line('Bot Connection has been enabled successfully.');              
            }
            else
            {
                $response['status'] = 0; 
                $response['message'] = $output['error'];
            }
        } 
        else
        {
            $updateData=array("bot_enabled"=>"2");
            if($persistent_enabled=='1') 
            {
                $updateData['persistent_enabled']='0';
                $updateData['started_button_enabled']='0';
                $this->fb_rx_login->delete_persistent_menu($page_access_token); // delete persistent menu
                $this->fb_rx_login->delete_get_started_button($page_access_token); // delete get started button
                $this->basic->delete_data("messenger_bot_persistent_menu",array("page_id"=>$page_id,"user_id"=>$this->user_id));
                $this->_delete_usage_log($module_id=197,$request=1);
            }
            $output=$this->fb_rx_login->disable_bot($fb_page_id,$page_access_token);
            if(!isset($output['error'])) $output['error'] = '';
            if($output['error'] == '')
            {
                $this->basic->update_data("facebook_rx_fb_page_info",array("id"=>$page_id),$updateData);
                $this->getstarted_enable_disable_onpage($page_id,$started_button_enabled='0',$page_access_token,$fb_user_info[0]['facebook_rx_config_id']);
                // $this->_delete_usage_log($module_id=200,$request=1);
                $response['status'] = 1; 
                $response['message'] = $this->lang->line('Bot Connection has been disabled successfully.');
            }
            else
            {
                $response['status'] = 0; 
                $response['message'] = $output['error'];
            }
        } 
        echo json_encode($response);
    }

    private function getstarted_enable_disable_onpage($page_id,$started_button_enabled,$page_access_token,$facebook_rx_config_id)
    {
      $this->load->library("fb_rx_login");
      $this->fb_rx_login->app_initialize($facebook_rx_config_id);
      if($started_button_enabled=='1')
      {
        $response=$this->fb_rx_login->add_get_started_button($page_access_token);
        if(!isset($response['error']))
          $this->basic->update_data("facebook_rx_fb_page_info",array("id"=>$page_id,'user_id'=>$this->user_id),array("started_button_enabled"=>'1'));
      }
      else
      {
        $response=$this->fb_rx_login->delete_get_started_button($page_access_token);
        if(!isset($response['error']))
          $this->basic->update_data("facebook_rx_fb_page_info",array("id"=>$page_id,'user_id'=>$this->user_id),array("started_button_enabled"=>'0'));
      }
    }
    private function check_review_status_broadcaster($page_table_id=0,$fb_page_id=0,$access_token='',$facebook_rx_config_id=0)
    {
        $auto_id=$page_table_id; // database id
        if($auto_id == 0) return false;

        $page_id=$fb_page_id;
        
        $this->load->library('fb_rx_login');
        $this->fb_rx_login->app_initialize($facebook_rx_config_id);

        $get_page_review_status=$this->fb_rx_login->get_page_review_status($access_token);

        $review_status=isset($get_page_review_status["data"][0]["status"]) ? strtoupper($get_page_review_status["data"][0]["status"]) : "NOT SUBMITTED";
        if($review_status=="") $review_status="NOT SUBMITTED";
        $user_id=$this->user_id;

        
        //DEPRECATED FUNCTION FOR QUICK BROADCAST
        $existing_labels=$this->fb_rx_login->retrieve_label($access_token);
        if(isset($existing_labels['error']['message'])) $error=$this->lang->line("During the review status check process system also tries to create default unsubscribe label and retrieve the existing labels as well. We got this error : ")." ".$existing_labels["error"]["message"];

        $group_name="Unsubscribe";
        $group_name2="SystemInvisible01";
        
        if(isset($existing_labels["data"]))
        foreach ($existing_labels["data"] as $key => $value) 
        {
            $existng_name=$value['name'];
            $existng_id=$value['id'];

            $unsbscribed='0';
            if($existng_name==$group_name) $unsbscribed='1';

            $is_invisible='0';
            if($existng_name==$group_name2) $is_invisible='1';

            $existng_name = $this->db->escape($existng_name);

            $sql="INSERT IGNORE INTO messenger_bot_broadcast_contact_group(page_id,group_name,user_id,label_id,unsubscribe,invisible) VALUES('$auto_id',$existng_name,'$user_id','$existng_id','$unsbscribed','$is_invisible')";
            $this->basic->execute_complex_query($sql);
        }

        
        if(!$this->basic->is_exist("messenger_bot_broadcast_contact_group",array("page_id"=>$auto_id,"unsubscribe"=>"1")))
        {
            $response=$this->fb_rx_login->create_label($access_token,$group_name);
            $label_id=isset($response['id']) ? $response['id'] : "";

            $errormessage=isset($response["error"]["error_user_msg"])?$response["error"]["error_user_msg"]:$response["error"]["message"];
            
            if($label_id=="") 
            $error=$this->lang->line("During the review status check process system also tries to create default unsubscribe label and retrieve the existing labels as well. We got this error : ")." ".$errormessage;
            else $this->basic->insert_data("messenger_bot_broadcast_contact_group",array("page_id"=>$auto_id,"group_name"=>$group_name,"user_id"=>$this->user_id,"label_id"=>$label_id,"deleted"=>"0","unsubscribe"=>"1"));
        }

        if(!$this->basic->is_exist("messenger_bot_broadcast_contact_group",array("page_id"=>$auto_id,"invisible"=>"1")))
        {            
            $response=$this->fb_rx_login->create_label($access_token,$group_name2);
            $label_id=isset($response['id']) ? $response['id'] : "";

            $errormessage=isset($response["error"]["error_user_msg"])?$response["error"]["error_user_msg"]:$response["error"]["message"];
            
            if($label_id=="") 
            $error=$this->lang->line("During the review status check process system also tries to create default unsubscribe label and retrieve the existing labels as well. We got this error : ")." ".$errormessage;
            else $this->basic->insert_data("messenger_bot_broadcast_contact_group",array("page_id"=>$auto_id,"group_name"=>$group_name2,"user_id"=>$this->user_id,"label_id"=>$label_id,"deleted"=>"0","unsubscribe"=>"0","invisible"=>"1"));
        } 


        $this->basic->update_data("facebook_rx_fb_page_info",array("id"=>$auto_id,"user_id"=>$this->user_id),array("review_status"=>$review_status,"review_status_last_checked"=>date("Y-m-d H:i:s")));


            
       if(!$this->basic->is_exist("messenger_bot",array("postback_id"=>"UNSUBSCRIBE_QUICK_BOXER","page_id"=>$auto_id)))
       {
            $sql='INSERT INTO messenger_bot (user_id,page_id,fb_page_id,template_type,bot_type,keyword_type,keywords,message,buttons,images,audio,video,file,status,bot_name,postback_id,last_replied_at,is_template) VALUES
            ("'.$user_id.'", "'.$auto_id.'", "'.$page_id.'", "text", "generic", "post-back","", \'{"1":{"recipient":{"id":"replace_id"},"message":{"template_type":"text_with_buttons","typing_on_settings":"off","delay_in_reply":"0","attachment":{"type":"template","payload":{"template_type":"button","text":"You have been successfully unsubscribed from our list. It sad to see you go. It is not the same without you ! You can join back by clicking the button below.","buttons":[{"type":"postback","payload":"RESUBSCRIBE_QUICK_BOXER","title":"Resubscribe"}]}}}}}\', "", "", "", "", "", "1", "UNSUBSCRIBE BOT", "UNSUBSCRIBE_QUICK_BOXER", "", "1");';

            $this->db->query($sql);
            $insert_id=$this->db->insert_id();
            $sql='INSERT INTO messenger_bot_postback(user_id,postback_id,page_id,use_status,status,messenger_bot_table_id,bot_name,is_template,template_jsoncode,template_name,template_for) VALUES
            ("'.$user_id.'","UNSUBSCRIBE_QUICK_BOXER","'.$auto_id.'","0","1","'.$insert_id.'","UNSUBSCRIBE BOT","1",\'{"1":{"recipient":{"id":"replace_id"},"message":{"template_type":"text_with_buttons","typing_on_settings":"off","delay_in_reply":"0","attachment":{"type":"template","payload":{"template_type":"button","text":"You have been successfully unsubscribed from our list. It sad to see you go. It is not the same without you ! You can join back by clicking the button below.","buttons":[{"type":"postback","payload":"RESUBSCRIBE_QUICK_BOXER","title":"Resubscribe"}]}}}}}\',"UNSUBSCRIBE TEMPLATE","unsubscribe")';
            $this->db->query($sql);
       }

       if(!$this->basic->is_exist("messenger_bot",array("postback_id"=>"RESUBSCRIBE_QUICK_BOXER","page_id"=>$auto_id)))
       {
            $sql='INSERT INTO messenger_bot (user_id,page_id,fb_page_id,template_type,bot_type,keyword_type,keywords,message,buttons,images,audio,video,file,status,bot_name,postback_id,last_replied_at,is_template) VALUES
            ("'.$user_id.'", "'.$auto_id.'", "'.$page_id.'", "text", "generic", "post-back","", \'{"1":{"recipient":{"id":"replace_id"},"message":{"template_type":"text_with_buttons","typing_on_settings":"off","delay_in_reply":"0","attachment":{"type":"template","payload":{"template_type":"button","text":"Welcome back ! We have not seen you for a while. You will no longer miss our important updates.","buttons":[{"type":"postback","payload":"UNSUBSCRIBE_QUICK_BOXER","title":"Unsubscribe"}]}}}}}\', "", "", "", "", "", "1", "RESUBSCRIBE BOT", "RESUBSCRIBE_QUICK_BOXER", "", "1");';
            $this->db->query($sql);
            $insert_id=$this->db->insert_id();
            $sql='INSERT INTO messenger_bot_postback(user_id,postback_id,page_id,use_status,status,messenger_bot_table_id,bot_name,is_template,template_jsoncode,template_name,template_for) VALUES
            ("'.$user_id.'","RESUBSCRIBE_QUICK_BOXER","'.$auto_id.'","0","1","'.$insert_id.'","RESUBSCRIBE BOT","1",\'{"1":{"recipient":{"id":"replace_id"},"message":{"template_type":"text_with_buttons","typing_on_settings":"off","delay_in_reply":"0","attachment":{"type":"template","payload":{"template_type":"button","text":"Welcome back ! We have not seen you for a while. You will no longer miss our important updates.","buttons":[{"type":"postback","payload":"UNSUBSCRIBE_QUICK_BOXER","title":"Unsubscribe"}]}}}}}\',"RESUBSCRIBE TEMPLATE","resubscribe")';
            $this->db->query($sql);
       }


       return true;
    }


    private function add_system_quick_email_reply($auto_id="",$page_id="")
    {
       if(!$this->basic->is_exist("messenger_bot",array("postback_id"=>"QUICK_REPLY_EMAIL_REPLY_BOT","page_id"=>$auto_id)))
       {
            $user_id=$this->user_id;
            $sql='INSERT INTO `messenger_bot` ( `user_id`, `page_id`, `fb_page_id`, `template_type`, `bot_type`, `keyword_type`, `keywords`, `message`, `buttons`, `images`, `audio`, `video`, `file`, `status`, `bot_name`, `postback_id`, `last_replied_at`, `is_template`) VALUES
            ("'.$user_id.'", "'.$auto_id.'", "'.$page_id.'", "text", "generic", "email-quick-reply","", \'{"1":{"recipient":{"id":"replace_id"},"message":{"template_type":"text","typing_on_settings":"off","delay_in_reply":"0","text":"Thanks, we have received your email. We will keep you updated. Thank you for being with us."}}}\', "", "", "", "", "", "1", "QUICK REPLY EMAIL REPLY", "QUICK_REPLY_EMAIL_REPLY_BOT", "0000-00-00 00:00:00", "0");';
            $this->db->query($sql);
            $insert_id=$this->db->insert_id();
            $sql='INSERT INTO messenger_bot_postback(user_id,postback_id,page_id,use_status,status,messenger_bot_table_id,bot_name,is_template,template_jsoncode,template_name,template_for) VALUES
            ("'.$user_id.'","QUICK_REPLY_EMAIL_REPLY_BOT","'.$auto_id.'","0","1","'.$insert_id.'","QUICK REPLY EMAIL REPLY","1",\'{"1":{"recipient":{"id":"replace_id"},"message":{"template_type":"text","typing_on_settings":"off","delay_in_reply":"0","text":"Thanks, we have received your email. We will keep you updated. Thank you for being with us."}}}\',"QUICK REPLY EMAIL REPLY","email-quick-reply")';
            $this->db->query($sql);
        }
        return true;
    }

    private function add_system_quick_phone_reply($auto_id="",$page_id="")
    {
       if(!$this->basic->is_exist("messenger_bot",array("postback_id"=>"QUICK_REPLY_PHONE_REPLY_BOT","page_id"=>$auto_id)))
       {
            $user_id=$this->user_id;
            $sql='INSERT INTO `messenger_bot` ( `user_id`, `page_id`, `fb_page_id`, `template_type`, `bot_type`, `keyword_type`, `keywords`, `message`, `buttons`, `images`, `audio`, `video`, `file`, `status`, `bot_name`, `postback_id`, `last_replied_at`, `is_template`) VALUES
            ("'.$user_id.'", "'.$auto_id.'", "'.$page_id.'", "text", "generic", "phone-quick-reply","", \'{"1":{"recipient":{"id":"replace_id"},"message":{"template_type":"text","typing_on_settings":"off","delay_in_reply":"0","text":"Thanks, we have received your phone. Thank you for being with us."}}}\', "", "", "", "", "", "1", "QUICK REPLY PHONE REPLY", "QUICK_REPLY_PHONE_REPLY_BOT", "0000-00-00 00:00:00", "0");';
            $this->db->query($sql);
            $insert_id=$this->db->insert_id();
            $sql='INSERT INTO messenger_bot_postback(user_id,postback_id,page_id,use_status,status,messenger_bot_table_id,bot_name,is_template,template_jsoncode,template_name,template_for) VALUES
            ("'.$user_id.'","QUICK_REPLY_PHONE_REPLY_BOT","'.$auto_id.'","0","1","'.$insert_id.'","QUICK REPLY PHONE REPLY","1",\'{"1":{"recipient":{"id":"replace_id"},"message":{"template_type":"text","typing_on_settings":"off","delay_in_reply":"0","text":"Thanks, we have received your phone. Thank you for being with us."}}}\',"QUICK REPLY PHONE REPLY","phone-quick-reply")';
            $this->db->query($sql);
        }
        return true;
    }

    private function add_system_quick_location_reply($auto_id="",$page_id="")
    {
       if(!$this->basic->is_exist("messenger_bot",array("postback_id"=>"QUICK_REPLY_LOCATION_REPLY_BOT","page_id"=>$auto_id)))
       {
            $user_id=$this->user_id;
            $sql='INSERT INTO `messenger_bot` ( `user_id`, `page_id`, `fb_page_id`, `template_type`, `bot_type`, `keyword_type`, `keywords`, `message`, `buttons`, `images`, `audio`, `video`, `file`, `status`, `bot_name`, `postback_id`, `last_replied_at`, `is_template`) VALUES
            ("'.$user_id.'", "'.$auto_id.'", "'.$page_id.'", "text", "generic", "location-quick-reply","", \'{"1":{"recipient":{"id":"replace_id"},"message":{"template_type":"text","typing_on_settings":"off","delay_in_reply":"0","text":"Thanks, we have received your location. Thank you for being with us."}}}\', "", "", "", "", "", "1", "QUICK REPLY LOCATION REPLY", "QUICK_REPLY_LOCATION_REPLY_BOT", "0000-00-00 00:00:00", "0");';
            $this->db->query($sql);
            $insert_id=$this->db->insert_id();
            $sql='INSERT INTO messenger_bot_postback(user_id,postback_id,page_id,use_status,status,messenger_bot_table_id,bot_name,is_template,template_jsoncode,template_name,template_for) VALUES
            ("'.$user_id.'","QUICK_REPLY_LOCATION_REPLY_BOT","'.$auto_id.'","0","1","'.$insert_id.'","QUICK REPLY LOCATION REPLY","1",\'{"1":{"recipient":{"id":"replace_id"},"message":{"template_type":"text","typing_on_settings":"off","delay_in_reply":"0","text":"Thanks, we have received your location. Thank you for being with us."}}}\',"QUICK REPLY LOCATION REPLY","location-quick-reply")';
            $this->db->query($sql);
        }
        return true;
    }

    private function add_system_quick_birthday_reply($auto_id="",$page_id="")
    {
       if(!$this->basic->is_exist("messenger_bot",array("postback_id"=>"QUICK_REPLY_BIRTHDAY_REPLY_BOT","page_id"=>$auto_id)))
       {
            $user_id=$this->user_id;
            $sql='INSERT INTO `messenger_bot` ( `user_id`, `page_id`, `fb_page_id`, `template_type`, `bot_type`, `keyword_type`, `keywords`, `message`, `buttons`, `images`, `audio`, `video`, `file`, `status`, `bot_name`, `postback_id`, `last_replied_at`, `is_template`) VALUES
            ("'.$user_id.'", "'.$auto_id.'", "'.$page_id.'", "text", "generic", "birthday-quick-reply","", \'{"1":{"recipient":{"id":"replace_id"},"message":{"template_type":"text","typing_on_settings":"off","delay_in_reply":"0","text":"Thanks, we have received your birthday. Thank you for being with us."}}}\', "", "", "", "", "", "1", "QUICK REPLY BIRTHDAY REPLY", "QUICK_REPLY_BIRTHDAY_REPLY_BOT", "0000-00-00 00:00:00", "0");';
            $this->db->query($sql);
            $insert_id=$this->db->insert_id();
            $sql='INSERT INTO messenger_bot_postback(user_id,postback_id,page_id,use_status,status,messenger_bot_table_id,bot_name,is_template,template_jsoncode,template_name,template_for) VALUES
            ("'.$user_id.'","QUICK_REPLY_BIRTHDAY_REPLY_BOT","'.$auto_id.'","0","1","'.$insert_id.'","QUICK REPLY BIRTHDAY REPLY","1",\'{"1":{"recipient":{"id":"replace_id"},"message":{"template_type":"text","typing_on_settings":"off","delay_in_reply":"0","text":"Thanks, we have received your birthday. Thank you for being with us."}}}\',"QUICK REPLY BIRTHDAY REPLY","birthday-quick-reply")';
            $this->db->query($sql);
        }
        return true;
    }


    private function add_system_postback_entry($auto_id="",$page_id="")
    {
       $user_id=$this->user_id;
        
       if(!$this->basic->is_exist("messenger_bot",array("postback_id"=>"YES_START_CHAT_WITH_HUMAN","page_id"=>$auto_id)))
       {
            $sql='INSERT INTO messenger_bot (user_id,page_id,fb_page_id,template_type,bot_type,keyword_type,keywords,message,buttons,images,audio,video,file,status,bot_name,postback_id,last_replied_at,is_template) VALUES
            ("'.$user_id.'", "'.$auto_id.'", "'.$page_id.'", "text", "generic", "post-back","", \'{"1":{"recipient":{"id":"replace_id"},"message":{"template_type":"text_with_buttons","typing_on_settings":"off","delay_in_reply":"0","attachment":{"type":"template","payload":{"template_type":"button","text":"Thanks! It is a pleasure talking you. One of our team member will reply you soon. If you want to chat with me again, just click the button below.","buttons":[{"type":"postback","payload":"YES_START_CHAT_WITH_BOT","title":"Resume Chat with Bot"}]}}}}}\', "", "", "", "", "", "1", "CHAT WITH HUMAN", "YES_START_CHAT_WITH_HUMAN", "", "1");';
            $this->db->query($sql);
            $insert_id=$this->db->insert_id();
            $sql='INSERT INTO messenger_bot_postback(user_id,postback_id,page_id,use_status,status,messenger_bot_table_id,bot_name,is_template,template_jsoncode,template_name,template_for) VALUES
            ("'.$user_id.'","YES_START_CHAT_WITH_HUMAN","'.$auto_id.'","0","1","'.$insert_id.'","CHAT WITH HUMAN","1",\'{"1":{"recipient":{"id":"replace_id"},"message":{"template_type":"text_with_buttons","typing_on_settings":"off","delay_in_reply":"0","attachment":{"type":"template","payload":{"template_type":"button","text":"Thanks! It is a pleasure talking you. One of our team member will reply you soon. If you want to chat with me again, just click the button below.","buttons":[{"type":"postback","payload":"YES_START_CHAT_WITH_BOT","title":"Resume Chat with Bot"}]}}}}}\',"CHAT WITH HUMAN TEMPLATE","chat-with-human")';
            $this->db->query($sql);
       }

       if(!$this->basic->is_exist("messenger_bot",array("postback_id"=>"YES_START_CHAT_WITH_BOT","page_id"=>$auto_id)))
       {
            $sql='INSERT INTO messenger_bot (user_id,page_id,fb_page_id,template_type,bot_type,keyword_type,keywords,message,buttons,images,audio,video,file,status,bot_name,postback_id,last_replied_at,is_template) VALUES
            ("'.$user_id.'", "'.$auto_id.'", "'.$page_id.'", "text", "generic", "post-back","", \'{"1":{"recipient":{"id":"replace_id"},"message":{"template_type":"text_with_buttons","typing_on_settings":"off","delay_in_reply":"0","attachment":{"type":"template","payload":{"template_type":"button","text":"I am gald to have you back. I will try my best to answer all questions. If you want to start chat with human again you can simply click the button below.","buttons":[{"type":"postback","payload":"YES_START_CHAT_WITH_HUMAN","title":"Chat with human"}]}}}}}\', "", "", "", "", "", "1", "CHAT WITH BOT", "YES_START_CHAT_WITH_BOT", "", "1");';
            $this->db->query($sql);
            $insert_id=$this->db->insert_id();
            $sql='INSERT INTO messenger_bot_postback(user_id,postback_id,page_id,use_status,status,messenger_bot_table_id,bot_name,is_template,template_jsoncode,template_name,template_for) VALUES
            ("'.$user_id.'","YES_START_CHAT_WITH_BOT","'.$auto_id.'","0","1","'.$insert_id.'","RESUBSCRIBE BOT","1",\'{"1":{"recipient":{"id":"replace_id"},"message":{"template_type":"text_with_buttons","typing_on_settings":"off","delay_in_reply":"0","attachment":{"type":"template","payload":{"template_type":"button","text":"I am gald to have you back. I will try my best to answer all questions. If you want to start chat with human again you can simply click the button below.","buttons":[{"type":"postback","payload":"YES_START_CHAT_WITH_HUMAN","title":"Chat with human"}]}}}}}\',"CHAT WITH BOT TEMPLATE","chat-with-bot")';
            $this->db->query($sql);
       }
       return true;
    }


    private function add_system_getstarted_reply_entry($auto_id="",$page_id="")
    {
       $user_id=$this->user_id;
        
       if(!$this->basic->is_exist("messenger_bot",array("keyword_type"=>"get-started","page_id"=>$auto_id)))
       {
            $sql='INSERT INTO messenger_bot (user_id,page_id,fb_page_id,template_type,bot_type,keyword_type,keywords,message,buttons,images,audio,video,file,status,bot_name,postback_id,last_replied_at,is_template) VALUES
            ("'.$user_id.'", "'.$auto_id.'", "'.$page_id.'", "text", "generic", "get-started","", \'{"1":{"recipient":{"id":"replace_id"},"message":{"template_type":"text","typing_on_settings":"off","delay_in_reply":"0","text":"Hi #LEAD_USER_FIRST_NAME#, Welcome to our page."}}}\', "", "", "", "", "", "1", "GET STARTED", "", "", "0");';
            $this->db->query($sql);
       }
       return true;
    }

    private function add_system_nomatch_reply_entry($auto_id="",$page_id="")
    {
       $user_id=$this->user_id;
        
       if(!$this->basic->is_exist("messenger_bot",array("keyword_type"=>"no match","page_id"=>$auto_id)))
       {
            $sql='INSERT INTO messenger_bot (user_id,page_id,fb_page_id,template_type,bot_type,keyword_type,keywords,message,buttons,images,audio,video,file,status,bot_name,postback_id,last_replied_at,is_template) VALUES
            ("'.$user_id.'", "'.$auto_id.'", "'.$page_id.'", "text", "generic", "no match","", \'{"1":{"recipient":{"id":"replace_id"},"message":{"template_type":"text","typing_on_settings":"off","delay_in_reply":"0","text":"Sorry, we could not find any content to show. One of our team member will reply you soon."}}}\', "", "", "", "", "", "1", "NO MATCH FOUND", "", "", "0");';
            $this->db->query($sql);
       }
       return true;
    }



    public function delete_full_bot()
    {
        $this->ajax_check();
        if($this->session->userdata('user_type') != 'Admin' && !in_array(200,$this->module_access)) exit();

        $response = array();
        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                $response['status'] = 0;
                $response['message'] = "This function is disabled from admin account in this demo!!";
                echo json_encode($response);
                exit();
            }
        }

        $user_id = $this->user_id;
        $page_id=$this->input->post('page_id');
        $already_disabled=$this->input->post('already_disabled');       

        $page_data=$this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_id,"user_id"=>$this->user_id)));

        if(empty($page_data)){
            echo json_encode(array('success'=>0,'message'=>$this->lang->line("Page is not found for this user. Something is wrong.")));
            exit();
        }

        $fb_page_id=isset($page_data[0]["page_id"]) ? $page_data[0]["page_id"] : "";
        $page_access_token=isset($page_data[0]["page_access_token"]) ? $page_data[0]["page_access_token"] : "";
        $persistent_enabled=isset($page_data[0]["persistent_enabled"]) ? $page_data[0]["persistent_enabled"] : "0";
        $fb_user_id = $page_data[0]["facebook_rx_fb_user_info_id"];
        $fb_user_info = $this->basic->get_data('facebook_rx_fb_user_info',array('where'=>array('id'=>$fb_user_id)));
        $this->fb_rx_login->app_initialize($fb_user_info[0]['facebook_rx_config_id']);

        $updateData=array("bot_enabled"=>"0");
        if($already_disabled == 'no')
        {            
            if($persistent_enabled=='1') 
            {
                $updateData['persistent_enabled']='0';
                $updateData['started_button_enabled']='0';
                $this->fb_rx_login->delete_persistent_menu($page_access_token); // delete persistent menu
                $this->fb_rx_login->delete_get_started_button($page_access_token); // delete get started button
                $this->basic->delete_data("messenger_bot_persistent_menu",array("page_id"=>$page_id,"user_id"=>$this->user_id));                
            }
            $response=$this->fb_rx_login->disable_bot($fb_page_id,$page_access_token);
        }
        $this->basic->update_data("facebook_rx_fb_page_info",array("id"=>$page_id),$updateData);
        $this->_delete_usage_log($module_id=200,$request=1);

        $this->delete_bot_data($page_id,$fb_page_id);

        $response['status'] = 1;
        $response['message'] = $this->lang->line("Bot Connection and all of the settings and campaigns of this page has been deleted successfully.");

        echo json_encode($response);

    }


    private function delete_bot_data($page_id,$fb_page_id)
    {

        if($this->db->table_exists('messenger_bot_engagement_checkbox'))
        {            
            $get_checkbox=$this->basic->get_data("messenger_bot_engagement_checkbox",array("where"=>array("page_id"=>$page_id)));
            $checkbox_ids=array();
            foreach ($get_checkbox as $key => $value) 
            {
                $checkbox_ids[]=$value['id'];
            }

            $this->basic->delete_data("messenger_bot_engagement_checkbox",array("page_id"=>$page_id));
        
            if(!empty($checkbox_ids))
            {
                $this->db->where_in('checkbox_plugin_id', $checkbox_ids);
                $this->db->delete('messenger_bot_engagement_checkbox_reply');
            }
        }

        $del_list=array (
          0 => 
          array 
          (
            'table_name' => 'messenger_bot',
            'where_field' => 'page_id',
            'value' =>$page_id,
          ),
          1 => 
          array (
            'table_name' => 'messenger_bot_persistent_menu',
            'where_field' => 'page_id',
            'value' =>$page_id,
          ),
          2 => 
          array (
            'table_name' => 'messenger_bot_postback',
            'where_field' => 'page_id',
            'value' =>$page_id,
          ),
          3 => 
          array (
            'table_name' => 'messenger_bot_quick_reply_email',
            'where_field' => 'fb_page_id',
            'value' => $fb_page_id,
          ),
          4 => 
          array (
            'table_name' => 'messenger_bot_reply_error_log',
            'where_field' => 'page_id',
            'value' =>$page_id,
          ),
          5 => 
          array (
            'table_name' => 'messenger_bot_subscriber',
            'where_field' => 'page_id',
            'value' =>$fb_page_id,
          ),
          7 => 
          array (
            'table_name' => 'fb_chat_plugin_2way',
            'where_field' => 'page_auto_id',
            'value' =>$page_id,
            'where_field2' => 'core_or_bot',
            'value2' =>'0',
          ),
          8 => 
          array (
            'table_name' => 'messenger_bot_domain_whitelist',
            'where_field' => 'page_id',
            'value' =>$page_id,
          ),
          9 => 
          array (
            'table_name' => 'messenger_bot_engagement_2way_chat_plugin',
            'where_field' => 'page_auto_id',
            'value' =>$page_id,
          ),
          10 => 
          array (
            'table_name' => 'messenger_bot_engagement_messenger_codes',
            'where_field' => 'page_id',
            'value' =>$page_id,
          ),
          11 => 
          array (
            'table_name' => 'messenger_bot_engagement_mme',
            'where_field' => 'page_id',
            'value' =>$page_id,
          ),
          12 => 
          array (
            'table_name' => 'messenger_bot_engagement_send_to_msg',
            'where_field' => 'page_id',
            'value' =>$page_id,
          ),
          13 => 
          array (
            'table_name' => 'messenger_bot_drip_campaign',
            'where_field' => 'page_id',
            'value' =>$page_id,
          ),
          14 => 
          array (
            'table_name' => 'messenger_bot_drip_report',
            'where_field' => 'page_id',
            'value' =>$page_id,
          ),
          15 => 
          array (
            'table_name' => 'messenger_bot_broadcast',
            'where_field' => 'page_id',
            'value' =>$page_id,
          ),
          16 => 
          array (
            'table_name' => 'messenger_bot_broadcast_contact_group',
            'where_field' => 'page_id',
            'value' =>$page_id,
          ),
          17 => 
          array (
            'table_name' => 'messenger_bot_broadcast_serial',
            'where_field' => 'page_id',
            'value' =>$page_id,
          ),
          18 => 
          array (
            'table_name' => 'messenger_bot_broadcast_serial_send',
            'where_field' => 'page_id',
            'value' =>$page_id,
          ),
        );

        foreach ($del_list as $key => $value) 
        {
            if($this->db->table_exists($value['table_name']))
            {
                $where=array($value['where_field']=>$value['value']);
                if(isset($value['where_field2'])) $where[$value['where_field2']]=$value['value2'];
                $this->basic->delete_data($value['table_name'],$where);
            }
        }

        return true;
    } 



    public function manual_renew_account()
    {
        $id = $this->session->userdata('fb_rx_login_database_id');
        $redirect_url = base_url()."social_accounts/manual_renew_account";

        $user_info = array();
        $user_info = $this->fb_rx_login->login_callback($redirect_url);   
                
        if( isset($user_info['status']) && $user_info['status'] == '0')
        {
            $data['error'] = 1;
            $data['message'] = $this->lang->line("something went wrong")." : ".$user_info['message'];
            $data['body'] = "facebook_rx/user_login";
            $this->_viewcontroller($data);
        } 
        else 
        {
            $access_token=$user_info['access_token_set'];

            //checking permission given by the users            
            $permission = $this->fb_rx_login->debug_access_token($access_token);

            $given_permission = array();
            if(isset($permission['data']['scopes']))
            {
                $permission_checking = array();
                $needed_permission = array('manage_pages','publish_pages','pages_messaging');
                $given_permission = $permission['data']['scopes'];
                $permission_checking = array_intersect($needed_permission,$given_permission);
                if(empty($permission_checking))
                {
                    $documentation_link = base_url('documentation/#!/sm_import_account');
                    // $text = "'".$this->lang->line("sorry, you didn't confirm the request yet. please login to your fb account and accept the request. for more")."' <a href='".$documentation_link."'>'".$this->lang->line("visit here.")."'</a>";
                    $text = $this->lang->line("All needed permissions are not approved for your app")." [".implode(',', $needed_permission)."]";
                    $this->session->set_userdata('limit_cross', $text);
                    redirect('social_accounts/index','location');                
                    exit();
                }
            }
            
            if(isset($access_token))
            {
                $data = array(
                    'user_id' => $this->user_id,
                    'facebook_rx_config_id' => $id,
                    'access_token' => $access_token,
                    'name' => $user_info['name'],
                    'email' => isset($user_info['email']) ? $user_info['email'] : "",
                    'fb_id' => $user_info['id'],
                    'add_date' => date('Y-m-d'),
                    'deleted' => '0'
                    );

                $where=array();
                $where['where'] = array('user_id'=>$this->user_id,'fb_id'=>$user_info['id']);
                $exist_or_not = array();
                $exist_or_not = $this->basic->get_data('facebook_rx_fb_user_info',$where,$select='',$join='',$limit='',$start=NULL,$order_by='',$group_by='',$num_rows=0,$csv='',$delete_overwrite=1);

                if(empty($exist_or_not))
                {
                    //************************************************//
                    $status=$this->_check_usage($module_id=65,$request=1);
                    if($status=="2") 
                    {
                        $this->session->set_userdata('limit_cross', $this->lang->line("Module limit is over."));
                        redirect('social_accounts/index','location');                
                        exit();
                    }
                    else if($status=="3") 
                    {
                        $this->session->set_userdata('limit_cross', $this->lang->line("Module limit is over."));
                        redirect('social_accounts/index','location');                
                        exit();
                    }
                    //************************************************//
                    $this->basic->insert_data('facebook_rx_fb_user_info',$data);
                    $facebook_table_id = $this->db->insert_id();

                    //insert data to useges log table
                    $this->_insert_usage_log($module_id=65,$request=1);
                }
                else
                {
                    $facebook_table_id = $exist_or_not[0]['id'];
                    $where = array('user_id'=>$this->user_id,'id'=>$facebook_table_id);
                    $this->basic->update_data('facebook_rx_fb_user_info',$where,$data);
                }

                $this->session->set_userdata("facebook_rx_fb_user_info",$facebook_table_id);  

                $page_list = array();
                $page_list = $this->fb_rx_login->get_page_list($access_token);

                if(isset($page_list['error']) && $page_list['error'] == '1')
                {
                    $data['error'] = 1;
                    $data['message'] = $this->lang->line("Something went wrong")." : ".$page_list['message'];
                    $data['body'] = "facebook_rx/user_login";
                    return $this->_viewcontroller($data);                    
                }

                if(!empty($page_list))
                {
                    foreach($page_list as $page)
                    {
                        $user_id = $this->user_id;
                        $page_id = $page['id'];
                        $page_cover = '';
                        if(isset($page['cover']['source'])) $page_cover = $page['cover']['source'];
                        $page_profile = '';
                        if(isset($page['picture']['url'])) $page_profile = $page['picture']['url'];
                        $page_name = '';
                        if(isset($page['name'])) $page_name = $page['name'];
                        $page_access_token = '';
                        if(isset($page['access_token'])) $page_access_token = $page['access_token'];
                        $page_email = '';
                        if(isset($page['emails'][0])) $page_email = $page['emails'][0];
                        $page_username = '';
                        if(isset($page['username'])) $page_username = $page['username'];

                        $data = array(
                            'user_id' => $user_id,
                            'facebook_rx_fb_user_info_id' => $facebook_table_id,
                            'page_id' => $page_id,
                            'page_cover' => $page_cover,
                            'page_profile' => $page_profile,
                            'page_name' => $page_name,
                            'username' => $page_username,
                            'page_access_token' => $page_access_token,
                            'page_email' => $page_email,
                            'add_date' => date('Y-m-d'),
                            'deleted' => '0'
                            );

                        $where=array();
                        $where['where'] = array('facebook_rx_fb_user_info_id'=>$facebook_table_id,'page_id'=>$page['id']);
                        $exist_or_not = array();
                        $exist_or_not = $this->basic->get_data('facebook_rx_fb_page_info',$where,$select='',$join='',$limit='',$start=NULL,$order_by='',$group_by='',$num_rows=0,$csv='',$delete_overwrite=1);

                        if(empty($exist_or_not))
                        {
                            $this->basic->insert_data('facebook_rx_fb_page_info',$data);
                        }
                        else
                        {
                            $where = array('facebook_rx_fb_user_info_id'=>$facebook_table_id,'page_id'=>$page['id']);
                            $this->basic->update_data('facebook_rx_fb_page_info',$where,$data);
                        }

                    }
                }

                $group_list = array();
                $group_list = $this->fb_rx_login->get_group_list($access_token);


                if(!empty($group_list))
                {
                    foreach($group_list as $group)
                    {
                        $user_id = $this->user_id;
                        $group_access_token = $access_token; // group uses user access token
                        $group_id = $group['id'];
                        $group_cover = '';
                        if(isset($group['cover']['source'])) $group_cover = $group['cover']['source'];
                        $group_profile = '';
                        if(isset($group['picture']['url'])) $group_profile = $group['picture']['url'];
                        $group_name = '';
                        if(isset($group['name'])) $group_name = $group['name'];

                        $data = array(
                            'user_id' => $user_id,
                            'facebook_rx_fb_user_info_id' => $facebook_table_id,
                            'group_id' => $group_id,
                            'group_cover' => $group_cover,
                            'group_profile' => $group_profile,
                            'group_name' => $group_name,
                            'group_access_token' => $group_access_token,
                            'add_date' => date('Y-m-d'),
                            'deleted' => '0'
                            );

                        $where=array();
                        $where['where'] = array('facebook_rx_fb_user_info_id'=>$facebook_table_id,'group_id'=>$group['id']);
                        $exist_or_not = array();
                        $exist_or_not = $this->basic->get_data('facebook_rx_fb_group_info',$where,$select='',$join='',$limit='',$start=NULL,$order_by='',$group_by='',$num_rows=0,$csv='',$delete_overwrite=1);

                        if(empty($exist_or_not))
                        {
                            $this->basic->insert_data('facebook_rx_fb_group_info',$data);
                        }
                        else
                        {
                            $where = array('facebook_rx_fb_user_info_id'=>$facebook_table_id,'group_id'=>$group['id']);
                            $this->basic->update_data('facebook_rx_fb_group_info',$where,$data);
                        }
                    }
                }

                $this->session->set_userdata('success_message', 'success');
                redirect('social_accounts/index','location');                
                exit();
            }
            else
            {
                $data['error'] = 1;
                $data['message'] = "'".$this->lang->line("something went wrong,please")."' <a href='".base_url("social_accounts/account_import")."'>'".$this->lang->line("try again")."'</a>";
                $data['body'] = "facebook_rx/user_login";
                $this->_viewcontroller($data);
            }
        }
    }

    public function fb_rx_account_switch()
    {
        $this->ajax_check();
        $id=$this->input->post("id");
        
        $this->session->set_userdata("facebook_rx_fb_user_info",$id); 

        $get_user_data = $this->basic->get_data("facebook_rx_fb_user_info",array("where"=>array("id"=>$id,"user_id"=>$this->user_id)));
        $config_id = isset($get_user_data[0]["facebook_rx_config_id"]) ? $get_user_data[0]["facebook_rx_config_id"] : 0;
        $this->session->set_userdata("fb_rx_login_database_id",$config_id);   
    }




    public function pages_messaging()
    {
        $page_info = $this->basic->get_data('facebook_rx_fb_page_info', array('where' => array('user_id' => $this->user_id,'facebook_rx_fb_user_info_id'=>$this->session->userdata('facebook_rx_fb_user_info'))), array('id', 'page_id', 'page_name', 'webhook_enabled'));

        $page_dropdown = array();
        $is_any_page_enabled = false;
        $enabled_page = '';

        $page_dropdown[-1] = $this->lang->line('Please select a page');
        foreach ($page_info as $key => $single_page) {
            
            if ($single_page['webhook_enabled'] == '1') {

                $is_any_page_enabled = true;
                $enabled_page = $single_page['id'];
            }

            $page_dropdown[$single_page['id']] = $single_page['page_name'];
        }

        $data['page_dropdown'] = $page_dropdown;
        $data['is_any_page_enabled'] = $is_any_page_enabled;
        $data['enabled_page'] = $enabled_page;
        $data['page_info'] = $page_info;

        if($this->db->table_exists('messenger_bot'))
            $data['has_messenger_bot'] = 'yes';
        else
            $data['has_messenger_bot'] = 'no';

        $page_messaging_info = $this->basic->get_data('page_messaging_information');
        $data['page_messaging_info'] = $page_messaging_info;

        $data['body'] = 'facebook_rx/pages_messaging';
        $data['title'] = 'Page Messaging Settings';
        $this->_viewcontroller($data);
    }


    public function enableDisableWebHook()
    {
        if (!isset($_POST)) exit;

        $page_id = $this->input->post('page_id');
        $page_table_id = $page_id;
        $enable_or_disable = $this->input->post('enable_or_disable');

        if ($enable_or_disable == "disabled")
            $webhook_enabled = '1';
        else
            $webhook_enabled = '0';

        $page_info = $this->basic->get_data('facebook_rx_fb_page_info',array('where'=>array('id'=>$page_id)),array('page_access_token','page_id'));
        $page_access_token = $page_info[0]['page_access_token'];
        $page_id = $page_info[0]['page_id'];

        $this->load->library('fb_rx_login');
        $this->fb_rx_login->app_initialize($this->session->userdata('fb_rx_login_database_id'));

        if($webhook_enabled == '1')
            $response = $this->fb_rx_login->enable_webhook($page_id,$page_access_token);
        else
            $response = $this->fb_rx_login->disable_webhook($page_id,$page_access_token);

        if($response['error'] != '')
        {
            echo json_encode($response);
            exit;
        }

        if($response['error'] == '' && $page_table_id != '-1')
        {
            $this->basic->update_data('facebook_rx_fb_page_info', array('id' => $page_table_id), array('webhook_enabled' => $webhook_enabled));
            $response['error'] = '';
            echo json_encode($response);
        }
    }


    public function submitPagesMessageInfo()
    {
        if (!isset($_POST)) exit;

        $post=$_POST;
        foreach ($post as $key => $value) 
        {
            $$key=$value;
        }

        $page_info = $this->basic->get_data('facebook_rx_fb_page_info',array('where'=>array('id'=>$enabled_page)),array('page_id'));
        $page_id = $page_info[0]['page_id'];

        $this->basic->execute_complex_query("TRUNCATE TABLE page_messaging_information");

        for($i=1;$i<=3;$i++)
        {
            $reply_bot = array();
            $reply_variable = 'reply_'.$i;
            $reply_variable = isset($$reply_variable) ? $$reply_variable : '';

            $keyword = 'keyword_'.$i;
            $keyword = isset($$keyword) ? $$keyword : '';

            $reply_bot['text'] = $reply_variable;

            $json_data = array();
            $json_data['recipient'] = array('id'=>'replace_id');
            $json_data['messaging_type'] = 'RESPONSE';
            $json_data['message'] = $reply_bot;
            $json_encode_data = json_encode($json_data);

            $insert_data = array();
            $insert_data['keywords'] = $keyword;
            $insert_data['message'] = $json_encode_data;
            $insert_data['reply_message'] = $reply_variable;
            $insert_data['user_id'] = $this->user_id;
            $insert_data['page_id'] = $enabled_page;
            $insert_data['fb_page_id'] = $page_id;

            $this->basic->insert_data('page_messaging_information',$insert_data);
                        
        }

        echo 'success';

    }




}