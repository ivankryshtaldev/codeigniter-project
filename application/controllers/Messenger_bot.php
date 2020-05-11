<?php
require_once("application/controllers/Home.php"); // loading home controller
class Messenger_bot extends Home
{
    public $addon_data=array();
    public $postback_info;
    public $postback_array=array();
    public $postback_done=array();
    public function __construct()
    {
        parent::__construct();
        $this->user_id=$this->session->userdata('user_id'); // user_id of logged in user, we may need it
        $function_name=$this->uri->segment(2);
        if($function_name!="webhook_callback" && $function_name!="webhook_callback_main" && $function_name!="update_first_name_last_name") 
        {
             // all addon must be login protected
              //------------------------------------------------------------------------------------------
              if ($this->session->userdata('logged_in')!= 1) redirect('home/login', 'location');          
              // if you want the addon to be accessed by admin and member who has permission to this addon
              //-------------------------------------------------------------------------------------------
              if(isset($addondata['module_id']) && is_numeric($addondata['module_id']) && $addondata['module_id']>0)
              {
                   if($this->session->userdata('user_type') != 'Admin' && !in_array($addondata['module_id'],$this->module_access))
                   {
                        redirect('home/login_page', 'location');
                        exit();
                   }
              }
        } 
        $this->member_validity();
    }


    public function api_member_validity($user_id='')
    {
        if($user_id!='') {
            $where['where'] = array('id'=>$user_id);
            $user_expire_date = $this->basic->get_data('users',$where,$select=array('expired_date'));
            $expire_date = strtotime($user_expire_date[0]['expired_date']);
            $current_date = strtotime(date("Y-m-d"));
            $package_data=$this->basic->get_data("users",$where=array("where"=>array("users.id"=>$user_id)),$select="package.price as price, users.user_type",$join=array('package'=>"users.package_id=package.id,left"));

            if(is_array($package_data) && array_key_exists(0, $package_data) && $package_data[0]['user_type'] == 'Admin' )
                return true;

            $price = '';
            if(is_array($package_data) && array_key_exists(0, $package_data))
            $price=$package_data[0]["price"];
            if($price=="Trial") $price=1;

            
            if ($expire_date < $current_date && ($price>0 && $price!=""))
            return false;
            else return true;
            

        }
    } 
  

    private function package_list()
    {
        $payment_package=$this->basic->get_data("package",$where='',$select='',$join='',$limit='',$start=NULL,$order_by='price');
        $return_val=array();
        $config_data=$this->basic->get_data("payment_config");
        $currency=isset($config_data[0]["currency"])?$config_data[0]["currency"]:"USD";
        foreach ($payment_package as $row)
        {
            $return_val[$row['id']]=$row['package_name']." : Only @".$currency." ".$row['price']." for ".$row['validity']." days";
        }
        return $return_val;
    }

    public function get_label_dropdown()
    {
        if(!$_POST) exit();
        $page_id=$this->input->post('page_id');// database id

        $table_type = 'messenger_bot_broadcast_contact_group';
        $where_type['where'] = array('user_id'=>$this->user_id,"page_id"=>$page_id,"unsubscribe"=>"0","invisible"=>"0");
        $info_type = $this->basic->get_data($table_type,$where_type,$select='', $join='', $limit='', $start='', $order_by='group_name');
        $result = array();
        $group_name =array();

        $dropdown=array();
        $str='<script>$("#label_ids").select2();</script> ';
        $str .='<select multiple=""  class="form-control select2" id="label_ids" name="label_ids[]">';
        $str .= '<option value="">'.$this->lang->line('Select Labels').'</option>';
        foreach ($info_type as  $value)
        {
            $search_key = $value['id'];
            $search_type = $value['group_name'];
            $str.=  "<option value='{$search_key}'>".$search_type."</option>";            

        }
        $str.= '</select>';

        echo json_encode(array('first_dropdown'=>$str));
    }

    public function get_drip_campaign_dropdown()
    {
        if(!$_POST) exit();
        $page_id=$this->input->post('page_id');// database id

        $table_type = 'messenger_bot_drip_campaign';
        $where_type['where'] = array('user_id'=>$this->user_id,"page_id"=>$page_id);
        $info_type = $this->basic->get_data($table_type,$where_type,$select='');
        $result = array();
        $group_name =array();

        $dropdown=array();
        $str='<script>$("#drip_campaign_id").select2();</script> ';
        $str .='<select class="form-control select2" id="drip_campaign_id" name="drip_campaign_id[]">';
        $str .= '<option value="">'.$this->lang->line('Select').'</option>';
        foreach ($info_type as  $value)
        {
            $search_key = $value['id'];
            $search_value = $value['campaign_name'];
            $str.=  "<option value='{$search_key}'>".$search_value."</option>";
        }
        $str.= '</select>';

        echo json_encode(array('dropdown_value'=>$str));
    }


    public function get_postback_dropdown()
    {
        if(!$_POST) exit();
        $page_auto_id=$this->input->post('page_auto_id');// database id
        $default_child_postback_id=$this->input->post('default_child_postback_id');// this will be auto selected

        $postback_id_list = $this->basic->get_data('messenger_bot_postback',array('where'=>array('user_id'=>$this->user_id,'page_id'=>$page_auto_id,'template_for'=>'reply_message','is_template'=>'0','use_status'=>'0')),array('postback_id','bot_name'));

        $str='';
        if(!empty($postback_id_list))
        {            
            $str='<script>$("#template_postback_id").select2();</script> ';
            $str .='<label>'.$this->lang->line("Select PostBack ID").'</label>
                    <select class="form-control select2" id="template_postback_id" name="template_postback_id">';
            foreach ($postback_id_list as  $value)
            {
                $array_key = $value['postback_id'];
                $array_value = $value['postback_id']." (".$value['bot_name'].")";
                $selected = ($array_key==$default_child_postback_id) ? 'selected' : '';
                $str .="<option value='{$array_key}' {$selected}>{$array_value}</option>";            

            }
            $str.= '</select>';
        }

        echo json_encode(array('first_dropdown'=>$str));
    }

    public function get_postback_dropdown_child()
    {
        if(!$_POST) exit();
        $page_auto_id=$this->input->post('page_auto_id');// database id

        $postback_id_list = $this->basic->get_data('messenger_bot_postback',array('where'=>array('user_id'=>$this->user_id,'page_id'=>$page_auto_id,'template_for'=>'reply_message','is_template'=>'1')),array('postback_id','bot_name'));

        $str='';
        $str .="<option value=''>".$this->lang->line("Select")."</option>";
        if(!empty($postback_id_list))
        {            
            foreach ($postback_id_list as  $value)
            {
                $array_key = $value['postback_id'];
                $array_value = $value['postback_id']." (".$value['bot_name'].")";
                $str .="<option value='{$array_key}'>{$array_value}</option>";            

            }
        }

        echo json_encode(array('dropdown'=>$str));
    }
    
    public function create_subscriber($sender_id='', $page_id='')
    {

        $this->db->db_debug = FALSE; //disable debugging for queries
        $table = "messenger_bot_subscriber";
        $where['where'] = array('messenger_bot_subscriber.subscribe_id' => $sender_id);
        $subscriber_info = $this->basic->get_data($table,$where);

        $response=array();
        $response['is_new']=FALSE;
        if(!empty($subscriber_info) && $subscriber_info[0]['is_bot_subscriber']=='1'){
            $response['subscriber_info']=$subscriber_info[0];
            return $response; 
        }

        else{

            if(empty($subscriber_info))
                $response['is_new']=TRUE;

            $table = "facebook_rx_fb_page_info";
            $where['where'] = array('page_id' => $page_id,'bot_enabled'=>'1');
            $page_access_token_array = $this->basic->get_data($table,$where,"page_access_token,user_id,id");
            $page_access_token = $page_access_token_array[0]['page_access_token'];
            $user_id = $page_access_token_array[0]['user_id'];
            $user_data = $this->subscriber_info($page_access_token,$sender_id);
            $this->db->db_debug = FALSE; //disable debugging for queries

            //Insert or update subscriber information 

                $subscribe_id = $sender_id;
                $first_name = isset($user_data['first_name']) ? $this->db->escape($user_data['first_name']):"";
                $last_name = isset($user_data['last_name']) ? $this->db->escape($user_data['last_name']):"";
                $profile_pic = isset($user_data['profile_pic']) ? $this->db->escape($user_data['profile_pic']):"";
                $locale = isset($user_data['locale']) ? $user_data['locale']:"";
                $timezone = isset($user_data['timezone']) ? $user_data['timezone']:"";
                $gender = isset($user_data['gender']) ? $user_data['gender']:"";
                $subscribed_at = date('Y-m-d H:i:s');
                $page_table_id=$page_access_token_array[0]['id'];

                $sql="INSERT INTO messenger_bot_subscriber (user_id,page_id,page_table_id,subscribe_id,first_name,last_name,profile_pic,locale,timezone,gender,subscribed_at,is_imported,is_bot_subscriber) 
                VALUES ('$user_id','$page_id','$page_table_id','$subscribe_id',$first_name,$last_name,$profile_pic,'$locale','$timezone','$gender','$subscribed_at','0','1')
                ON DUPLICATE KEY UPDATE first_name=$first_name,last_name=$last_name,profile_pic=$profile_pic,locale='$locale',timezone='$timezone',gender='$gender',is_bot_subscriber='1'; ";

                $this->basic->execute_complex_query($sql);

                $last_insert_id=$this->db->insert_id();

                if($last_insert_id=='' || $last_insert_id==0)
                    $last_insert_id=$subscriber_info[0]['id'];


                $data = array(
                    'id'=>$last_insert_id,    // Get the table id of the subscriber for assigning drip campaing later. 
                    'user_id' => $user_id,
                    'page_id' => $page_id,
                    'subscribe_id' => $sender_id,
                    'first_name' => $user_data['first_name'],
                    'last_name' => $user_data['last_name'],
                    'profile_pic' => $user_data['profile_pic'],
                    'locale' => $user_data['locale'],
                    'timezone' => $user_data['timezone'],
                    'gender' => $user_data['gender'],
                    'subscribed_at' => date('Y-m-d H:i:s'),
                    'status' =>'1'
                );

                $response['subscriber_info']=$data;


               return $response;

        }

        
    }
        
    
    /**Sender action added 19.03.2018 by Konok**/
    
    public function sender_action($sender_id,$action_type,$post_access_token='')
    {
    
        $url = "https://graph.facebook.com/v2.6/me/messages?access_token={$post_access_token}";
        
        $post_data_array['recipient']['id']=$sender_id;
        $post_data_array['sender_action']=$action_type;
        $post_data=json_encode($post_data_array);
        $ch = curl_init();
        $headers = array("Content-type: application/json");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_COOKIEJAR,'cookie.txt'); 
        curl_setopt($ch, CURLOPT_COOKIEFILE,'cookie.txt'); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3"); 
        $st=curl_exec($ch);  
        $result=json_decode($st,TRUE);
        return $result;
    }

    public function is_email($email)
    {
        $email=trim($email);
        $is_valid=0;
        /***Validation check***/
        $pattern = '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD';
        if (preg_match($pattern, $email) === 1) {
            $is_valid=1;
        }
        return $is_valid;
    }

    public function is_phone_number($phone)
    {    
        $is_valid=0;
        if(preg_match("#\+\d{7}#",$phone)===1)
            $is_valid=1; 
            
        return $is_valid;
            
    }


    public function webhook_callback_main()
    {
        
        $currenTime=date("Y-m-d H:i:s");
        $response_raw=$this->input->post("response_raw");   

        /*file_put_contents("fb.txt",$response_raw, FILE_APPEND | LOCK_EX);        
        exit();*/ 
        
        $response = json_decode($response_raw,TRUE);
        if(isset($response['entry']['0']['messaging'][0]['delivery'])) exit();

        // for package expired users bot will not work section
        $page_id = $response['entry']['0']['messaging'][0]['recipient']['id'];
        $table_name = "facebook_rx_fb_page_info";
        $where['where'] = array('facebook_rx_fb_page_info.page_id' => $page_id,'facebook_rx_fb_page_info.bot_enabled' => '1');
        $join = array('users'=>"users.id=facebook_rx_fb_page_info.user_id,left");   
        $users_expiry_info = $this->basic->get_data($table_name,$where,array("users.id as user_id","users.expired_date","users.user_type","users.deleted","users.status","facebook_rx_fb_page_info.id as page_auto_id","chat_human_email","page_name"),$join);
        
        $PAGE_AUTO_ID= isset($users_expiry_info[0]['page_auto_id']) ? $users_expiry_info[0]['page_auto_id'] : "0"; // Page's Database ID
        
        if($PAGE_AUTO_ID=="0") exit(); 
        
        if(isset($users_expiry_info[0]['user_type']) && $users_expiry_info[0]['user_type'] != 'Admin')
        {
            $user_status = $users_expiry_info[0]['status'];
            $user_deleted = $users_expiry_info[0]['deleted'];
            if($user_deleted == '1' || $user_status == '0') exit();
            
            if(!$this->api_member_validity($users_expiry_info[0]['user_id'])) exit();            
        }
        // end of for package expired users bot will not work section
        
        if(isset($response['entry']['0']['messaging'][0]['read'])) 
        {           
            $receipent_id_read=isset($response['entry']['0']['messaging'][0]['sender']['id'])?$response['entry']['0']['messaging'][0]['sender']['id']:"";
            $where_array=array("subscribe_id"=>$receipent_id_read,"opened"=>"0","processed"=>'1',"error_message"=>"");
            $campaign_info=$this->basic->get_data("messenger_bot_broadcast_serial_send",array("where"=>$where_array));
            $campaign_id_read=array();
            foreach($campaign_info as $read_info)
            {
                $campaign_id_read[]= $read_info['campaign_id']; 
            }
            if(!empty($campaign_id_read))   
            {
                $campaign_info_multiple=$this->basic->get_data("messenger_bot_broadcast_serial",array("where_in"=>array("id"=>$campaign_id_read)));
                foreach ($campaign_info_multiple as $key => $value) 
                {
                   $cam_id=$value["id"];
                   $successfully_opened=$value["successfully_opened"];
                   $report_temp=json_decode($value["report"],true);
                   $report_temp[$receipent_id_read]["opened"]="1";
                   $report_temp[$receipent_id_read]["open_time"]=$currenTime;
                   $report_json=json_encode($report_temp);
                   $successfully_opened++;
                   $this->basic->update_data("messenger_bot_broadcast_serial",array("id"=>$cam_id),array("report"=>$report_json,"successfully_opened"=>$successfully_opened));
                }
                $update_data_read= array("opened"=>"1","open_time"=>$currenTime);
                $this->basic->update_data('messenger_bot_broadcast_serial_send',$where_array,$update_data_read); 
            }
            

            // drip message open update

            if($this->db->table_exists('messenger_bot_drip_campaign'))
            {
                $drip_subscriber_data=$this->basic->get_data("messenger_bot_subscriber",array("where"=>array("subscribe_id"=>$receipent_id_read)));
                if(!isset($drip_subscriber_data[0])) exit();
                $driptime=date("Y-m-d H:i:s");
                $drip_insert_data=array
                (
                    "is_opened"=>"1",
                    "opened_at"=>$driptime,
                    "last_updated_at"=>$driptime
                );
                $this->basic->update_data("messenger_bot_drip_report",array("subscribe_id"=>$drip_subscriber_data[0]["subscribe_id"],"is_opened"=>"0"),$drip_insert_data);
            }
               
            exit();                         
        }
       
       
       //if it's optin from checkbox plugin, then tese action is not needed. As not information can be found for that. 
       
       $page_id = $response['entry']['0']['messaging'][0]['recipient']['id'];
       
       if(!isset($response['entry'][0]['messaging'][0]['optin']['user_ref'])) 
       {       
            $sender_id= $response['entry']['0']['messaging'][0]['sender']['id'];
            
            //subscriber status
            $create_subscriber_get_info=$this->create_subscriber($sender_id, $page_id);

            $subscriber_new_old_info['is_new']= $create_subscriber_get_info['is_new'];
            $subscriber_info[0] = $create_subscriber_get_info['subscriber_info'];
        
        }
     
        /***   Check if it coming from after subscribing by checkbox plugin    ***/
        
        if($this->db->table_exists('messenger_bot_engagement_2way_chat_plugin'))
        {
            if(isset($response['entry'][0]['messaging'][0]['prior_message']['source']) && $response['entry'][0]['messaging'][0]['prior_message']['source']=="checkbox_plugin")
            {
            
                $user_identifier= isset($response['entry'][0]['messaging'][0]['prior_message']['identifier']) ? $response['entry'][0]['messaging'][0]['prior_message']['identifier']:"";
                
                if($user_identifier!="")
                {                
                    //Get check_box plugin id searching with user_identifier.                 
                    $check_box_plugin_info= $this->basic->get_data("messenger_bot_engagement_checkbox_reply",array("where"=>array("user_ref"=>$user_identifier)));
                    
                    $check_box_plugin_id=isset($check_box_plugin_info[0]['checkbox_plugin_id']) ? $check_box_plugin_info[0]['checkbox_plugin_id']:"";
                    $check_box_plugin_reference=isset($check_box_plugin_info[0]['reference']) ? $check_box_plugin_info[0]['reference']:"";
                                        
                    if($check_box_plugin_id!="")
                    {
                     // Update subscriber if new, then source is from checkbox plugin & also reffernce updated. 
                        if($subscriber_new_old_info['is_new'])
                        {
                            $plugin_name=$response['entry'][0]['messaging'][0]['prior_message']['source'];
                            $subscriber_id_update=$subscriber_info[0]['id'];
                            $update_data=array("refferer_id"=>$check_box_plugin_reference,"refferer_source"=>$plugin_name,"refferer_uri"=>"N/A");
                            $this->basic->update_data("messenger_bot_subscriber",array("id"=>$subscriber_id_update),$update_data);
                        }
                        
                    /****Assign Drip Messaging Campaing ID ***/
                    $drip_type="messenger_bot_engagement_checkbox";
                    $this->assign_drip_messaging_id($drip_type,$check_box_plugin_id,$PAGE_AUTO_ID,$subscriber_info[0]['subscribe_id']);   
                        
                        
                        $engagementer_info= $this->basic->get_data("messenger_bot_engagement_checkbox",array("where"=>array("id"=>$check_box_plugin_id)));
                
                        $label_ids=isset($engagementer_info[0]['label_ids']) ? $engagementer_info[0]['label_ids']:"";
                        
                        if($label_ids!="" )
                        {                 
                            // $this->assign_label_webhook_call($sender_id,$page_id,$label_ids);

                            //DEPRECATED FUNCTION FOR QUICK BROADCAST
                            $post_data_label_assign=array("psid"=>$sender_id,"fb_page_id"=>$page_id,"label_auto_ids"=>$label_ids);
                            $url=base_url()."home/assign_label_webhook_call";
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch,CURLOPT_POST,1);
                            curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data_label_assign);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                            // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
                            $reply_response=curl_exec($ch);                  
                        }                        
                        
                    }   
                    
                }
            
            }
        }
     
     
     
        
        if(isset($response['entry'][0]['messaging'][0]['message']['text']) 
        && !isset($response['entry'][0]['messaging'][0]['message']['quick_reply']) 
        && !isset($response['entry'][0]['messaging'][0]['postback']) 
        && !isset($response['entry'][0]['messaging'][0]['optin'])) //message for all
        {
            $messages = $response['entry']['0']['messaging'][0]['message']['text'];
            $table_name = "messenger_bot";
            $where['where'] = array('messenger_bot.fb_page_id' => $page_id,'facebook_rx_fb_page_info.bot_enabled' => '1');
            $join = array('facebook_rx_fb_page_info'=>"facebook_rx_fb_page_info.page_id=messenger_bot.fb_page_id,left");   
            $messenger_bot_info = $this->basic->get_data($table_name,$where,array("messenger_bot.*","facebook_rx_fb_page_info.page_access_token as page_access_token","facebook_rx_fb_page_info.enable_mark_seen as enable_mark_seen","facebook_rx_fb_page_info.enbale_type_on as enbale_type_on","facebook_rx_fb_page_info.reply_delay_time as reply_delay_time"),$join,'','','messenger_bot.id asc');
            
            $enable_mark_seen=$messenger_bot_info[0]['enable_mark_seen'];

            if($enable_mark_seen)
                $this->sender_action($sender_id,"mark_seen",$messenger_bot_info[0]['page_access_token']);                 
            
            foreach ($messenger_bot_info as $key => $value) {
                $cam_keywords_str = $value['keywords'];
                $cam_keywords_array = explode(",", $cam_keywords_str);
                foreach ($cam_keywords_array as $cam_keywords) {
                    if(function_exists('iconv') && function_exists('mb_detect_encoding')){
                        $encoded_word =  mb_detect_encoding($cam_keywords);
                        if(isset($encoded_word)){
                            $cam_keywords = iconv( $encoded_word, "UTF-8//TRANSLIT", $cam_keywords );
                        }
                    }
                    $pos= stripos($messages,trim($cam_keywords));
                    if($pos!==FALSE){
                        $message_str = $value['message'];
                        $message_array = json_decode($message_str,true);
                        // if(!isset($message_array[1])) $message_array[1]=$message_array;
                        if(!isset($message_array[1])){
                            $message_array_org=$message_array;
                            $message_array=array();
                            $message_array[1]=$message_array_org;
                        }
                        foreach($message_array as $msg)
                        {
                            $template_type_file_track=$msg['message']['template_type'];
                            unset($msg['message']['template_type']);

                            // typing on and typing on delay [alamin]
                            $enable_typing_on = $msg['message']['typing_on_settings'];
                            $enable_typing_on = ($enable_typing_on=='on')  ? 1 : 0;
                            unset($msg['message']['typing_on_settings']);
                            $typing_on_delay_time = $msg['message']['delay_in_reply'];
                            if($typing_on_delay_time=="") $typing_on_delay_time = 0;
                            unset($msg['message']['delay_in_reply']);

                            
                            /** Spintax **/
                            if(isset($msg['message']['text']))
                                $msg['message']['text']=spintax_process($msg['message']['text']);
                                
                            $msg['messaging_type'] = "RESPONSE";
                            $reply = json_encode($msg);     

                            $replace_search=array('{"id":"replace_id"}','#SUBSCRIBER_ID_REPLACE#');
                            $replace_with=array('{"id":"'.$sender_id.'"}',$sender_id);
                            $reply=str_replace($replace_search, $replace_with, $reply);
                            

                            if(isset($subscriber_info[0]['first_name']))
                                $reply=str_replace('#LEAD_USER_FIRST_NAME#', $subscriber_info[0]['first_name'], $reply);
                            if(isset($subscriber_info[0]['last_name']))
                                $reply=str_replace('#LEAD_USER_LAST_NAME#', $subscriber_info[0]['last_name'], $reply);
                            $access_token = $value['page_access_token'];
                            if(isset($subscriber_info[0]['status']) && $subscriber_info[0]['status']=="1")
                            {
                                // typing on and typing on delay [alamin]
                                if($enable_typing_on) $this->sender_action($sender_id,"typing_on",$access_token);                                
                                if($typing_on_delay_time>0) sleep($typing_on_delay_time);
                            
                                if($template_type_file_track=='video' || $template_type_file_track=='file' || $template_type_file_track=='audio')
                                {
                                    $post_data=array("access_token"=>$access_token,"reply"=>$reply);
                                    $url=base_url()."home/send_reply_curl_call";
                                    $ch = curl_init();
                                    curl_setopt($ch, CURLOPT_URL, $url);
                                    curl_setopt($ch, CURLOPT_POST,1);
                                    curl_setopt($ch, CURLOPT_POSTFIELDS,$post_data);
                                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
                                    $reply_response=curl_exec($ch);  
                                }
                                else
                                    $reply_response= $this->send_reply($access_token,$reply);
                             
                             /*****Insert into database messenger_bot_reply_error_log if get error****/
                             if(isset($reply_response['error']['message'])){
                                $bot_settings_id= $value['id'];
                                $reply_error_message= $reply_response['error']['message'];
                                $error_time= date("Y-m-d H:i:s");
                                $page_table_id=$value['page_id'];
                                $user_id=$value['user_id'];
                                
                                $error_insert_data=array("page_id"=>$page_table_id,"fb_page_id"=>$page_id,"user_id"=>$user_id,
                                                    "error_message"=>$reply_error_message,"bot_settings_id"=>$bot_settings_id,
                                                    "error_time"=>$error_time);
                                $this->basic->insert_data('messenger_bot_reply_error_log',$error_insert_data);
                                
                             }
                             
                             
                            }
                            
                            
                        }

                        /** Assign Drip Messaging Campaign ID ****/
                     /*   $drip_type="default";
                        $this->assign_drip_messaging_id($drip_type,"0",$PAGE_AUTO_ID,$subscriber_info[0]['subscribe_id']);  */

                        //update Subscriber Last Interaction time. 
                        $this->update_subscriber_last_interaction($sender_id,$currenTime);

                    /***Update Source if user send text message just after click to messenger ads action ***/

                    $reference_id = isset($response['entry'][0]['messaging'][0]['referral']['ref']) ? $response['entry'][0]['messaging'][0]['referral']['ref']:"";
                    $reference_source=isset($response['entry'][0]['messaging'][0]['referral']['source']) ? $response['entry'][0]['messaging'][0]['referral']['source']:"";

                    if($reference_source=='ADS'){

                        $reference_ad_id=isset($response['entry'][0]['messaging'][0]['referral']['ad_id']) ? $response['entry'][0]['messaging'][0]['referral']['ad_id']:"";
                        $reference_ad_id="ad_id: ".$refence_ad_id;

                        if($subscriber_new_old_info['is_new']){
                            $subscriber_id_update=$subscriber_info[0]['id'];
                            $update_data=array("refferer_id"=>$reference_id,"refferer_source"=>"ADS","refferer_uri"=>$reference_ad_id);
                            $this->basic->update_data("messenger_bot_subscriber",array("id"=>$subscriber_id_update),$update_data);
                        }

                    }


                        die();
                    }
                }
            }
            $table_name = "messenger_bot";
            $where['where'] = array('messenger_bot.fb_page_id' => $page_id, 'messenger_bot.keyword_type' => 'no match','facebook_rx_fb_page_info.bot_enabled' => '1','facebook_rx_fb_page_info.no_match_found_reply'=>'enabled');
            $join = array('facebook_rx_fb_page_info'=>"facebook_rx_fb_page_info.page_id=messenger_bot.fb_page_id,left");   
            $messenger_bot_info = $this->basic->get_data($table_name,$where,array("messenger_bot.*","facebook_rx_fb_page_info.page_access_token as page_access_token","facebook_rx_fb_page_info.enable_mark_seen as enable_mark_seen","facebook_rx_fb_page_info.enbale_type_on as enbale_type_on","facebook_rx_fb_page_info.reply_delay_time as reply_delay_time"),$join,'1','','messenger_bot.id asc');
            
            $enable_mark_seen=$messenger_bot_info[0]['enable_mark_seen'];
            
            if(isset($messenger_bot_info[0]) && !empty($messenger_bot_info)){
                $message_str = $messenger_bot_info[0]['message'];
                $message_array = json_decode($message_str,true);
                // if(!isset($message_array[1])) $message_array[1]=$message_array;
                if(!isset($message_array[1])){
                    $message_array_org=$message_array;
                    $message_array=array();
                    $message_array[1]=$message_array_org;
                }
                foreach($message_array as $msg)
                {
                    $template_type_file_track=$msg['message']['template_type'];
                    unset($msg['message']['template_type']);

                    // typing on and typing on delay [alamin]
                    $enable_typing_on = $msg['message']['typing_on_settings'];
                    $enable_typing_on = ($enable_typing_on=='on')  ? 1 : 0;
                    unset($msg['message']['typing_on_settings']);
                    $typing_on_delay_time = $msg['message']['delay_in_reply'];
                    if($typing_on_delay_time=="") $typing_on_delay_time = 0;
                    unset($msg['message']['delay_in_reply']);


                    /** Spintax **/
                    if(isset($msg['message']['text']))
                        $msg['message']['text']=spintax_process($msg['message']['text']);
                                
                    $msg['messaging_type'] = "RESPONSE";
                    $reply = json_encode($msg);         

                    $replace_search=array('{"id":"replace_id"}','#SUBSCRIBER_ID_REPLACE#');
                    $replace_with=array('{"id":"'.$sender_id.'"}',$sender_id);
                    $reply=str_replace($replace_search, $replace_with, $reply);

                    if(isset($subscriber_info[0]['first_name']))
                        $reply=str_replace('#LEAD_USER_FIRST_NAME#', $subscriber_info[0]['first_name'], $reply);
                    if(isset($subscriber_info[0]['last_name']))
                        $reply=str_replace('#LEAD_USER_LAST_NAME#', $subscriber_info[0]['last_name'], $reply);
                    $access_token = $messenger_bot_info[0]['page_access_token'];
                    if(isset($subscriber_info[0]['status']) && $subscriber_info[0]['status']=="1")
                    {
                        // typing on and typing on delay [alamin]
                        if($enable_typing_on) $this->sender_action($sender_id,"typing_on",$access_token);                                
                        if($typing_on_delay_time>0) sleep($typing_on_delay_time);
    
                        if($template_type_file_track=='video' || $template_type_file_track=='file' || $template_type_file_track=='audio')
                        {
                            $post_data=array("access_token"=>$access_token,"reply"=>$reply);
                            $url=base_url()."home/send_reply_curl_call";
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch,CURLOPT_POST,1);
                            curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
                            $reply_response=curl_exec($ch);  
                    
                        }
                        else
                            $reply_response=$this->send_reply($access_token,$reply);
                            /*****Insert into database messenger_bot_reply_error_log if get error****/
                             if(isset($reply_response['error']['message'])){
                                $bot_settings_id= $value['id'];
                                $reply_error_message= $reply_response['error']['message'];
                                $error_time= date("Y-m-d H:i:s");
                                $page_table_id=$value['page_id'];
                                $user_id=$value['user_id'];
                                
                                $error_insert_data=array("page_id"=>$page_table_id,"fb_page_id"=>$page_id,"user_id"=>$user_id,
                                                    "error_message"=>$reply_error_message,"bot_settings_id"=>$bot_settings_id,
                                                    "error_time"=>$error_time);
                                $this->basic->insert_data('messenger_bot_reply_error_log',$error_insert_data);
                                
                             }  
                    }
                }

                 //update Subscriber Last Interaction time. 
                 $this->update_subscriber_last_interaction($sender_id,$currenTime);


                die();
            }
        }

        elseif(isset($response['entry'][0]['messaging'][0]['optin'])) //Optins from Send to messengers 
        {
        
            if($this->db->table_exists('messenger_bot_engagement_2way_chat_plugin')){
        
            $reference_id = isset($response['entry'][0]['messaging'][0]['optin']['ref'])?$response['entry'][0]['messaging'][0]['optin']['ref']:"";
            $user_reference_id = isset($response['entry'][0]['messaging'][0]['optin']['user_ref'])?$response['entry'][0]['messaging'][0]['optin']['user_ref']:"";
            
            if($user_reference_id!="")
                $table_name="messenger_bot_engagement_checkbox";
                
            else
            {
            
                $table_name="messenger_bot_engagement_send_to_msg";
                
                if($subscriber_new_old_info['is_new'])
                {
                
                    $plugin_name="SEND-TO-MESSENGER-PLUGIN";
                    $subscriber_id_update=$subscriber_info[0]['id'];
                    
                    $update_data=array("refferer_id"=>$reference_id,"refferer_source"=>$plugin_name,"refferer_uri"=>"N/A");
                    $this->basic->update_data("messenger_bot_subscriber",array("id"=>$subscriber_id_update),$update_data);
                }
                
            }
                
            
            $engagementer_info= $this->basic->get_data($table_name,array("where"=>array("reference"=>$reference_id)));
            
            $label_ids=isset($engagementer_info[0]['label_ids']) ? $engagementer_info[0]['label_ids']:"";
            
            $template_id=isset($engagementer_info[0]['template_id']) ? $engagementer_info[0]['template_id']:"";
            
            $plugin_auto_id=isset($engagementer_info[0]['id']) ? $engagementer_info[0]['id']:"";
            
            
            if($template_id!=""){
                
                $postback_id_info= $this->basic->get_data("messenger_bot_postback",array("where"=>array("id"=>$template_id)));
                $postback_id= isset($postback_id_info[0]['postback_id']) ? $postback_id_info[0]['postback_id'] :"";
            }
            
            $table_name = "messenger_bot";
            
            if($template_id=="")
            
                $where['where'] = array('messenger_bot.fb_page_id' => $page_id,'keyword_type'=>'get-started','facebook_rx_fb_page_info.bot_enabled' => '1');
                
                else    
                
                    $where['where'] = array('messenger_bot.fb_page_id' => $page_id,'facebook_rx_fb_page_info.bot_enabled' => '1',"postback_id"=>$postback_id);
                    
            }
            
            else{
            
                $where['where'] = array('messenger_bot.fb_page_id' => $page_id,'keyword_type'=>'get-started','facebook_rx_fb_page_info.bot_enabled' => '1');
                 /** Assign Drip Messaging Campaign ID ****/
                $drip_type="default";
                $this->assign_drip_messaging_id($drip_type,"0",$PAGE_AUTO_ID,$subscriber_info[0]['subscribe_id']);
            }
            
            
            $join = array('facebook_rx_fb_page_info'=>"facebook_rx_fb_page_info.page_id=messenger_bot.fb_page_id,left");
            $messenger_bot_info = $this->basic->get_data($table_name,$where,array("messenger_bot.*","facebook_rx_fb_page_info.page_access_token as page_access_token","facebook_rx_fb_page_info.enable_mark_seen as enable_mark_seen","facebook_rx_fb_page_info.enbale_type_on as enbale_type_on","facebook_rx_fb_page_info.reply_delay_time as reply_delay_time"),$join,'','','messenger_bot.id asc');
            
            $enable_mark_seen=$messenger_bot_info[0]['enable_mark_seen'];            
            
            if($enable_mark_seen && $user_reference_id=="")
                $this->sender_action($sender_id,"mark_seen",$messenger_bot_info[0]['page_access_token']);
                
            
            foreach ($messenger_bot_info as $key => $value) {
                $message_str = $value['message'];
                $message_array = json_decode($message_str,true);
                // if(!isset($message_array[1])) $message_array[1]=$message_array;
                if(!isset($message_array[1])){
                    $message_array_org=$message_array;
                    $message_array=array();
                    $message_array[1]=$message_array_org;
                }
                foreach($message_array as $msg)
                {
                    $template_type_file_track=$msg['message']['template_type'];
                    unset($msg['message']['template_type']);

                    // typing on and typing on delay [alamin]
                    $enable_typing_on = $msg['message']['typing_on_settings'];
                    $enable_typing_on = ($enable_typing_on=='on')  ? 1 : 0;
                    unset($msg['message']['typing_on_settings']);
                    $typing_on_delay_time = $msg['message']['delay_in_reply'];
                    if($typing_on_delay_time=="") $typing_on_delay_time = 0;
                    unset($msg['message']['delay_in_reply']);
                    
                    /** Spintax **/
                    if(isset($msg['message']['text']))
                        $msg['message']['text']=spintax_process($msg['message']['text']);
                        
                    $msg['messaging_type'] = "RESPONSE";
                    $reply = json_encode($msg);       
                    
                    if($user_reference_id=="") {  // if comes from send-to-messenger rather than checkbox plugin  
                        $replace_search=array('{"id":"replace_id"}','#SUBSCRIBER_ID_REPLACE#');
                        $replace_with=array('{"id":"'.$sender_id.'"}',$sender_id);
                        $reply=str_replace($replace_search, $replace_with, $reply);
                    }                 
                     
                      
                    else // if comes from checkbox plugin, then it's different message structure. 
                        $reply=str_replace('{"id":"replace_id"}', '{"user_ref":"'.$user_reference_id.'"}', $reply);
                    
                    if(isset($subscriber_info[0]['first_name']))
                        $reply=str_replace('#LEAD_USER_FIRST_NAME#', $subscriber_info[0]['first_name'], $reply);
                    if(isset($subscriber_info[0]['last_name']))
                        $reply=str_replace('#LEAD_USER_LAST_NAME#', $subscriber_info[0]['last_name'], $reply);
                    $access_token = $value['page_access_token'];
                    
                    if((isset($subscriber_info[0]['status']) && $subscriber_info[0]['status']=="1") || $user_reference_id!=""){                   
                     
                        // typing on and typing on delay [alamin]
                        // User_reference id means comes from checkbox plugin and did not set typing on display for it
                        if($enable_typing_on && $user_reference_id=="") $this->sender_action($sender_id,"typing_on",$access_token);
                        if($typing_on_delay_time>0 && $user_reference_id=="") sleep($typing_on_delay_time);
                        
                        if($template_type_file_track=='video' || $template_type_file_track=='file' || $template_type_file_track=='audio'){
                            $post_data=array("access_token"=>$access_token,"reply"=>$reply);
                            $url=base_url()."home/send_reply_curl_call";
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch,CURLOPT_POST,1);
                            curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
                            $reply_response=curl_exec($ch);  
                    
                        }
                        else
                             $reply_response=$this->send_reply($access_token,$reply);
                                
                         /*****Insert into database messenger_bot_reply_error_log if get error****/
                             if(isset($reply_response['error']['message'])){
                                $bot_settings_id= $value['id'];
                                $reply_error_message= $reply_response['error']['message'];
                                $error_time= date("Y-m-d H:i:s");
                                $page_table_id=$value['page_id'];
                                $user_id=$value['user_id'];
                                
                                $error_insert_data=array("page_id"=>$page_table_id,"fb_page_id"=>$page_id,"user_id"=>$user_id,
                                                    "error_message"=>$reply_error_message,"bot_settings_id"=>$bot_settings_id,
                                                    "error_time"=>$error_time);
                                $this->basic->insert_data('messenger_bot_reply_error_log',$error_insert_data);
                                
                             }
                    }
                }

                /*** Assign Drip Campaing & also Label ***/
             if($this->db->table_exists('messenger_bot_engagement_2way_chat_plugin')){

                /***    Assign Drip Messaging Campaign ID *****/
                if($user_reference_id==""){
                    $drip_type="messenger_bot_engagement_send_to_msg";
                    $this->assign_drip_messaging_id($drip_type,$plugin_auto_id,$PAGE_AUTO_ID,$subscriber_info[0]['subscribe_id']);    
                }       
            
                 /** Insert into messenger_bot_engagement_checkbox_reply if it comes from checkbox plugin ***/
                 if($user_reference_id!="")
                 {
                    $reference_data_checkbox['user_ref']=$user_reference_id;
                    $reference_data_checkbox['checkbox_plugin_id']=$plugin_auto_id;
                    $reference_data_checkbox['reference']=$reference_id;
                    $reference_data_checkbox['optin_time']=date("Y-m-d H:i:s");
                    $this->basic->insert_data("messenger_bot_engagement_checkbox_reply",$reference_data_checkbox);
                    
                 }
            
                if($label_ids!="" && $user_reference_id==""){   // Update Label if only send-to-messenger. Don't for checkbox for first time. As we can't infromation
                
                    // $this->assign_label_webhook_call($sender_id,$page_id,$label_ids);

                    //DEPRECATED FUNCTION FOR QUICK BROADCAST
                    $post_data_label_assign=array("psid"=>$sender_id,"fb_page_id"=>$page_id,"label_auto_ids"=>$label_ids);
                    $url=base_url()."home/assign_label_webhook_call";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch,CURLOPT_POST,1);
                    curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data_label_assign);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
                    $reply_response=curl_exec($ch); 
                     
                } 
            }

             //update Subscriber Last Interaction time. 
             $this->update_subscriber_last_interaction($sender_id,$currenTime);

            die();

            }
        }
        
        
         elseif((isset($response['entry'][0]['messaging'][0]['postback']['referral']['type']) && $response['entry'][0]['messaging'][0]['postback']['referral']['type']=="OPEN_THREAD" && isset($response['entry'][0]['messaging'][0]['postback']['referral']['ref'])) || 
        
        (isset($response['entry'][0]['messaging'][0]['postback']['payload']) && $response['entry'][0]['messaging'][0]['postback']['payload']=="GET_STARTED_PAYLOAD" ) ||
        (isset($response['entry'][0]['messaging'][0]['referral']['source']) && $response['entry'][0]['messaging'][0]['referral']['type']=="OPEN_THREAD"))
        
        //When not any conversation and get started button is added
        {
        
            /**Check If the Engagement add-on is installed or not. Check a table of this addon is exist or not**/
            
            if($this->db->table_exists('messenger_bot_engagement_2way_chat_plugin')){
        
        
            /* If get started not set, then get the refferal means already have the conversation */
            $reference_id = isset($response['entry'][0]['messaging'][0]['postback']['referral']['ref'])?$response['entry'][0]['messaging'][0]['postback']['referral']['ref']:$response['entry'][0]['messaging'][0]['referral']['ref'];
            
            $reference_source=isset($response['entry'][0]['messaging'][0]['postback']['referral']['source'])?$response['entry'][0]['messaging'][0]['postback']['referral']['source']:$response['entry'][0]['messaging'][0]['referral']['source'];
            
            
            if($reference_source=="CUSTOMER_CHAT_PLUGIN"){ // If from Custom CHat
                $table_name="messenger_bot_engagement_2way_chat_plugin";
                $plugin_name=$reference_source;
                $refferer_uri=isset($response['entry'][0]['messaging'][0]['postback']['referral']['referer_uri'])?$response['entry'][0]['messaging'][0]['postback']['referral']['referer_uri']:"";
                $drip_type="messenger_bot_engagement_2way_chat_plugin";
            }
                
            else if($reference_source=="SHORTLINK"){ // If from custom link
            
                $table_name="messenger_bot_engagement_mme";
                $plugin_name=$reference_source;
                $refferer_uri="N/A";
                $drip_type="messenger_bot_engagement_mme";
                
            }
            else if($reference_source=="MESSENGER_CODE"){ //if messenger codes
            
                $table_name="messenger_bot_engagement_messenger_codes";
                $plugin_name=$reference_source;
                $refferer_uri="N/A";
                $drip_type="messenger_bot_engagement_messenger_codes";
                
            }

            else if($reference_source=="ADS"){  // if come after Click to Messenger ads Action. 

                $reference_ad_id=isset($response['entry'][0]['messaging'][0]['referral']['ad_id']) ? $response['entry'][0]['messaging'][0]['referral']['ad_id']:"";
                $reference_ad_id="ad_id: ".$refence_ad_id;
                $plugin_name=$reference_source;
                $refferer_uri=$reference_ad_id;
            }

            else{  // If come from page directly
                $table_name="";
                $plugin_name="FB PAGE";
                $refferer_uri="N/A";
                $drip_type="default";
                $this->assign_drip_messaging_id($drip_type,"0",$PAGE_AUTO_ID,$sender_id);
            }
            
            if($subscriber_new_old_info['is_new']){
                    $subscriber_id_update=$subscriber_info[0]['id'];
                    $update_data=array("refferer_id"=>$reference_id,"refferer_source"=>$plugin_name,"refferer_uri"=>$refferer_uri);
                    $this->basic->update_data("messenger_bot_subscriber",array("id"=>$subscriber_id_update),$update_data);
                }


            //If come after click to messengers ads , then if it's postback click or quick reply click, then need to jump to postback or quick reply section for replying. Because this part is only for get started button click & other engagment plugin . 

            if($reference_source=="ADS"){

                if(isset($response['entry'][0]['messaging'][0]['message']['quick_reply'])) goto QUICK_REPLY_BLOCK;
                else if (isset($response['entry'][0]['messaging'][0]['postback'])) goto POST_BACK_BLOCK;

            }

            
            
            $postback_id="";
            
            if($table_name!=""){
            
            $engagementer_info= $this->basic->get_data($table_name,array("where"=>array("reference"=>$reference_id)));
            
            $plugin_auto_id=isset($engagementer_info[0]['id']) ? $engagementer_info[0]['id']:"";

            $label_ids=isset($engagementer_info[0]['label_ids']) ? $engagementer_info[0]['label_ids']:"";
            $template_id=isset($engagementer_info[0]['template_id']) ? $engagementer_info[0]['template_id']:"";
            
            if($template_id!=""){
                $postback_id_info= $this->basic->get_data("messenger_bot_postback",array("where"=>array("id"=>$template_id)));
                $postback_id= isset($postback_id_info[0]['postback_id']) ? $postback_id_info[0]['postback_id'] :"";
                
            }
            
        }
            
            
            if($postback_id=="")
            
                $where['where'] = array('messenger_bot.fb_page_id' => $page_id,'keyword_type'=>'get-started','facebook_rx_fb_page_info.bot_enabled' => '1');
                
                else    
                    $where['where'] = array('messenger_bot.fb_page_id' => $page_id,'facebook_rx_fb_page_info.bot_enabled' => '1',"postback_id"=>$postback_id);
        
        }
        
        else{  // if engagement add-on not installed, then default query for get started. 
        
            $where['where'] = array('messenger_bot.fb_page_id' => $page_id,'keyword_type'=>'get-started','facebook_rx_fb_page_info.bot_enabled' => '1');
             /** Assign Drip Messaging Campaign ID ****/
            $drip_type="default";
            $this->assign_drip_messaging_id($drip_type,"0",$PAGE_AUTO_ID,$sender_id);
        }
            
                    
            
            $messages = $response['entry'][0]['messaging'][0]['message']['text'];
            $table_name = "messenger_bot";
            
            $join = array('facebook_rx_fb_page_info'=>"facebook_rx_fb_page_info.page_id=messenger_bot.fb_page_id,left");
            $messenger_bot_info = $this->basic->get_data($table_name,$where,array("messenger_bot.*","facebook_rx_fb_page_info.page_access_token as page_access_token","facebook_rx_fb_page_info.enable_mark_seen as enable_mark_seen","facebook_rx_fb_page_info.enbale_type_on as enbale_type_on","facebook_rx_fb_page_info.reply_delay_time as reply_delay_time"),$join,'','','messenger_bot.id asc');
            
            $enable_mark_seen=$messenger_bot_info[0]['enable_mark_seen'];
            
            if($enable_mark_seen) // mark ass seen action
                $this->sender_action($sender_id,"mark_seen",$messenger_bot_info[0]['page_access_token']);
            
            foreach ($messenger_bot_info as $key => $value) {
                $message_str = $value['message'];
                $message_array = json_decode($message_str,true);
                // if(!isset($message_array[1])) $message_array[1]=$message_array;
                if(!isset($message_array[1])){
                    $message_array_org=$message_array;
                    $message_array=array();
                    $message_array[1]=$message_array_org;
                }
                foreach($message_array as $msg)
                {
                    $template_type_file_track=$msg['message']['template_type'];
                    unset($msg['message']['template_type']);

                    // typing on and typing on delay [alamin]
                    $enable_typing_on = $msg['message']['typing_on_settings'];
                    $enable_typing_on = ($enable_typing_on=='on')  ? 1 : 0;
                    unset($msg['message']['typing_on_settings']);
                    $typing_on_delay_time = $msg['message']['delay_in_reply'];
                    if($typing_on_delay_time=="") $typing_on_delay_time = 0;
                    unset($msg['message']['delay_in_reply']);
                    
                    /** Spintax **/
                    if(isset($msg['message']['text']))
                        $msg['message']['text']=spintax_process($msg['message']['text']);               
                    
                    $msg['messaging_type'] = "RESPONSE";
                    $reply = json_encode($msg);     

                    $replace_search=array('{"id":"replace_id"}','#SUBSCRIBER_ID_REPLACE#');
                    $replace_with=array('{"id":"'.$sender_id.'"}',$sender_id);
                    $reply=str_replace($replace_search, $replace_with, $reply);

                    if(isset($subscriber_info[0]['first_name']))
                        $reply=str_replace('#LEAD_USER_FIRST_NAME#', $subscriber_info[0]['first_name'], $reply);
                    if(isset($subscriber_info[0]['last_name']))
                        $reply=str_replace('#LEAD_USER_LAST_NAME#', $subscriber_info[0]['last_name'], $reply);
                    $access_token = $value['page_access_token'];
                    if(isset($subscriber_info[0]['status']) && $subscriber_info[0]['status']=="1"){
                    
                        // typing on and typing on delay [alamin]
                        if($enable_typing_on) $this->sender_action($sender_id,"typing_on",$access_token);                                
                        if($typing_on_delay_time>0) sleep($typing_on_delay_time);

                        if($template_type_file_track=='video' || $template_type_file_track=='file' || $template_type_file_track=='audio'){
                            $post_data=array("access_token"=>$access_token,"reply"=>$reply);
                            $url=base_url()."home/send_reply_curl_call";
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch,CURLOPT_POST,1);
                            curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
                            $reply_response=curl_exec($ch);  
                    
                        }
                        else
                             $reply_response=$this->send_reply($access_token,$reply);
                            /*****Insert into database messenger_bot_reply_error_log if get error****/
                             if(isset($reply_response['error']['message'])){
                                $bot_settings_id= $value['id'];
                                $reply_error_message= $reply_response['error']['message'];
                                $error_time= date("Y-m-d H:i:s");
                                $page_table_id=$value['page_id'];
                                $user_id=$value['user_id'];
                                
                                $error_insert_data=array("page_id"=>$page_table_id,"fb_page_id"=>$page_id,"user_id"=>$user_id,
                                                    "error_message"=>$reply_error_message,"bot_settings_id"=>$bot_settings_id,
                                                    "error_time"=>$error_time);
                                $this->basic->insert_data('messenger_bot_reply_error_log',$error_insert_data);
                                
                             }
                    }
                }

                
                if($this->db->table_exists('messenger_bot_engagement_2way_chat_plugin')){

                    /****   Update Drip Messaging Campaign ID ****/
                    $this->assign_drip_messaging_id($drip_type,$plugin_auto_id,$PAGE_AUTO_ID,$sender_id);    
               
                    if(!empty($label_ids)){
                    
                        // $this->assign_label_webhook_call($sender_id,$page_id,$label_ids);

                        //DEPRECATED FUNCTION FOR QUICK BROADCAST
                        $post_data_label_assign=array("psid"=>$sender_id,"fb_page_id"=>$page_id,"label_auto_ids"=>$label_ids);
                        $url=base_url()."home/assign_label_webhook_call";
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch,CURLOPT_POST,1);
                        curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data_label_assign);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                        // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
                        $reply_response=curl_exec($ch); 
                         
                    } 
                }
                
                 //update Subscriber Last Interaction time. 
                $this->update_subscriber_last_interaction($sender_id,$currenTime);

                die();
            }
        }
        elseif (isset($response['entry'][0]['messaging'][0]['message']['quick_reply'])) //quick_reply
        {

            QUICK_REPLY_BLOCK: 

            //catch payload_id from response
            $payload_id = $response['entry'][0]['messaging'][0]['message']['quick_reply']['payload'];
            $messages = $response['entry'][0]['messaging'][0]['message']['text'];
            $table_name = "messenger_bot";
            $where['where'] = array('messenger_bot.fb_page_id' => $page_id,'facebook_rx_fb_page_info.bot_enabled' => '1');
            $this->db->where("FIND_IN_SET('$payload_id',messenger_bot.postback_id) !=", 0);
            $join = array('facebook_rx_fb_page_info'=>"facebook_rx_fb_page_info.page_id=messenger_bot.fb_page_id,left");
            $messenger_bot_info = $this->basic->get_data($table_name,$where,array("messenger_bot.*","facebook_rx_fb_page_info.page_access_token as page_access_token","facebook_rx_fb_page_info.enable_mark_seen as enable_mark_seen","facebook_rx_fb_page_info.enbale_type_on as enbale_type_on","facebook_rx_fb_page_info.reply_delay_time as reply_delay_time"),$join,'','','messenger_bot.id asc');
            
            $enable_mark_seen=$messenger_bot_info[0]['enable_mark_seen'];
            $enable_typing_on=$messenger_bot_info[0]['enbale_type_on'];
            $typing_on_delay_time = $messenger_bot_info[0]['reply_delay_time'];
            if($typing_on_delay_time=="0") $typing_on_delay_time=1;
            
            /***    Insert email into database if it's email from quick reply ***/
            
            if($this->is_email($payload_id)){
                
                $fb_page_id=$subscriber_info[0]['page_id'];
                $user_id=$subscriber_info[0]['user_id'];
                $fb_user_id=$subscriber_info[0]['subscribe_id'];
                $fb_user_first_name=$subscriber_info[0]['first_name'];
                $fb_user_last_name=$subscriber_info[0]['last_name'];
                $profile_pic=$subscriber_info[0]['profile_pic'];
                $update_time=date("Y-m-d H:i:s");
                $email=$payload_id;                
               
                $sql="UPDATE messenger_bot_subscriber SET email='$email',entry_time='$update_time',last_update_time='$update_time' WHERE subscribe_id='$fb_user_id';";  

                $this->basic->execute_complex_query($sql);
                $where['where'] = array('messenger_bot.fb_page_id' => $page_id,'facebook_rx_fb_page_info.bot_enabled' => '1',"keyword_type"=>"email-quick-reply");
                $join = array('facebook_rx_fb_page_info'=>"facebook_rx_fb_page_info.page_id=messenger_bot.fb_page_id,left");
                $messenger_bot_info = $this->basic->get_data($table_name,$where,array("messenger_bot.*","facebook_rx_fb_page_info.page_access_token as page_access_token","facebook_rx_fb_page_info.enable_mark_seen as enable_mark_seen","facebook_rx_fb_page_info.enbale_type_on as enbale_type_on","facebook_rx_fb_page_info.reply_delay_time as reply_delay_time"),$join,'','','messenger_bot.id asc');
                $enable_mark_seen=$messenger_bot_info[0]['enable_mark_seen'];
                $enable_typing_on=$messenger_bot_info[0]['enbale_type_on'];
                
                $typing_on_delay_time = $messenger_bot_info[0]['reply_delay_time'];
                if($typing_on_delay_time=="0") $typing_on_delay_time=1;
            }
            elseif($this->is_phone_number($payload_id)){
            
                $fb_page_id=$subscriber_info[0]['page_id'];
                $user_id=$subscriber_info[0]['user_id'];
                $fb_user_id=$subscriber_info[0]['subscribe_id'];
                $fb_user_first_name=$subscriber_info[0]['first_name'];
                $fb_user_last_name=$subscriber_info[0]['last_name'];
                $profile_pic=$subscriber_info[0]['profile_pic'];
                $update_time=date("Y-m-d H:i:s");
                $phone_number=$payload_id;
                
                $sql="UPDATE messenger_bot_subscriber SET phone_number='$phone_number',phone_number_entry_time='$update_time',phone_number_last_update='$update_time' WHERE subscribe_id='$fb_user_id';";              
                    
                $this->basic->execute_complex_query($sql);
                $where['where'] = array('messenger_bot.fb_page_id' => $page_id,'facebook_rx_fb_page_info.bot_enabled' => '1',"keyword_type"=>"phone-quick-reply");
                $join = array('facebook_rx_fb_page_info'=>"facebook_rx_fb_page_info.page_id=messenger_bot.fb_page_id,left");
                $messenger_bot_info = $this->basic->get_data($table_name,$where,array("messenger_bot.*","facebook_rx_fb_page_info.page_access_token as page_access_token","facebook_rx_fb_page_info.enable_mark_seen as enable_mark_seen","facebook_rx_fb_page_info.enbale_type_on as enbale_type_on","facebook_rx_fb_page_info.reply_delay_time as reply_delay_time"),$join,'','','messenger_bot.id asc');
                
                $enable_mark_seen=$messenger_bot_info[0]['enable_mark_seen'];
            }
            
            
            
            if($enable_mark_seen)
                $this->sender_action($sender_id,"mark_seen",$messenger_bot_info[0]['page_access_token']);    
                
            foreach ($messenger_bot_info as $key => $value) {
                $message_str = $value['message'];
                $message_array = json_decode($message_str,true);
                // if(!isset($message_array[1])) $message_array[1]=$message_array;
                if(!isset($message_array[1])){
                    $message_array_org=$message_array;
                    $message_array=array();
                    $message_array[1]=$message_array_org;
                }
                foreach($message_array as $msg)
                {
                    $template_type_file_track=$msg['message']['template_type'];
                    unset($msg['message']['template_type']);

                    // typing on and typing on delay [alamin]
                    $enable_typing_on = $msg['message']['typing_on_settings'];
                    $enable_typing_on = ($enable_typing_on=='on')  ? 1 : 0;
                    unset($msg['message']['typing_on_settings']);
                    $typing_on_delay_time = $msg['message']['delay_in_reply'];
                    if($typing_on_delay_time=="") $typing_on_delay_time = 0;
                    unset($msg['message']['delay_in_reply']);
                    
                    /** Spintax **/
                    if(isset($msg['message']['text']))
                        $msg['message']['text']=spintax_process($msg['message']['text']);
                                
                    $msg['messaging_type'] = "RESPONSE";
                    $reply = json_encode($msg);        

                   $replace_search=array('{"id":"replace_id"}','#SUBSCRIBER_ID_REPLACE#');
                   $replace_with=array('{"id":"'.$sender_id.'"}',$sender_id);
                   $reply=str_replace($replace_search, $replace_with, $reply);

                    if(isset($subscriber_info[0]['first_name']))
                        $reply=str_replace('#LEAD_USER_FIRST_NAME#', $subscriber_info[0]['first_name'], $reply);
                    if(isset($subscriber_info[0]['last_name']))
                        $reply=str_replace('#LEAD_USER_LAST_NAME#', $subscriber_info[0]['last_name'], $reply);
                    $access_token = $value['page_access_token'];
                    if(isset($subscriber_info[0]['status']) && $subscriber_info[0]['status']=="1"){
                    
                       // typing on and typing on delay [alamin]
                       if($enable_typing_on) $this->sender_action($sender_id,"typing_on",$access_token);                                
                       if($typing_on_delay_time>0) sleep($typing_on_delay_time);

                        if($template_type_file_track=='video' || $template_type_file_track=='file' || $template_type_file_track=='audio'){
                            $post_data=array("access_token"=>$access_token,"reply"=>$reply);
                            $url=base_url()."home/send_reply_curl_call";
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch,CURLOPT_POST,1);
                            curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
                            $reply_response=curl_exec($ch);  
                    
                        }
                        else
                             $reply_response=$this->send_reply($access_token,$reply);
                         /*****Insert into database messenger_bot_reply_error_log if get error****/
                             if(isset($reply_response['error']['message'])){
                                $bot_settings_id= $value['id'];
                                $reply_error_message= $reply_response['error']['message'];
                                $error_time= date("Y-m-d H:i:s");
                                $page_table_id=$value['page_id'];
                                $user_id=$value['user_id'];
                                
                                $error_insert_data=array("page_id"=>$page_table_id,"fb_page_id"=>$page_id,"user_id"=>$user_id,
                                                    "error_message"=>$reply_error_message,"bot_settings_id"=>$bot_settings_id,
                                                    "error_time"=>$error_time);
                                $this->basic->insert_data('messenger_bot_reply_error_log',$error_insert_data);
                                
                             }
                    }                    
                }

                  /** Assign Drip Messaging Campaign ID ****/

                $drip_assign_id=isset($messenger_bot_info[0]['drip_campaign_id']) ? $messenger_bot_info[0]['drip_campaign_id']:"";

                 if($drip_assign_id!="" && $drip_assign_id!="0"){
                    
                    $drip_type="custom";
                    $this->assign_drip_messaging_id($drip_type,"0",$PAGE_AUTO_ID,$sender_id,$drip_assign_id);  
                }
                


                /***Set labels if any setup available for this postback for quickReply ***/

                // if($this->db->table_exists('messenger_bot_broadcast')){

                $label_ids=isset($messenger_bot_info[0]['broadcaster_labels']) ? $messenger_bot_info[0]['broadcaster_labels']:"";
           
                if(!empty($label_ids)){
               
                    // $this->assign_label_webhook_call($sender_id,$page_id,$label_ids);

                    //DEPRECATED FUNCTION FOR QUICK BROADCAST
                    $post_data_label_assign=array("psid"=>$sender_id,"fb_page_id"=>$page_id,"label_auto_ids"=>$label_ids);
                    $url=base_url()."home/assign_label_webhook_call";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch,CURLOPT_POST,1);
                    curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data_label_assign);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
                    $reply_response=curl_exec($ch); 
                     
                } 
                // }

                if($this->db->table_exists('messenger_bot_thirdparty_webhook') && $this->basic->is_exist("messenger_bot_thirdparty_webhook",array("page_id"=>$page_id)))
                {
                    if($this->is_email($payload_id))
                    $this->thirdparty_webhook_trigger($page_id,$sender_id,"trigger_email");
                    else if($this->is_phone_number($payload_id))
                    $this->thirdparty_webhook_trigger($page_id,$sender_id,"trigger_phone_number");
                    else
                    $this->thirdparty_webhook_trigger($page_id,$sender_id,"trigger_postback",$payload_id);
                }

                 //update Subscriber Last Interaction time. 
                 $this->update_subscriber_last_interaction($sender_id,$currenTime);

                die();
            }
        }
        elseif(isset($response['entry'][0]['messaging'][0]['postback']))//Clicking on Payload Button like Start Chatting
        {

            POST_BACK_BLOCK:

            $payload_id = $response['entry'][0]['messaging'][0]['postback']['payload'];
            $messages = $response['entry'][0]['messaging'][0]['message']['text'];
            $table_name = "messenger_bot";
            $where['where'] = array('messenger_bot.fb_page_id' => $page_id,'facebook_rx_fb_page_info.bot_enabled' => '1');
            $this->db->where("FIND_IN_SET('$payload_id',messenger_bot.postback_id) !=", 0);
            $join = array('facebook_rx_fb_page_info'=>"facebook_rx_fb_page_info.page_id=messenger_bot.fb_page_id,left");
            $messenger_bot_info = $this->basic->get_data($table_name,$where,array("messenger_bot.*","facebook_rx_fb_page_info.page_access_token as page_access_token","facebook_rx_fb_page_info.enable_mark_seen as enable_mark_seen","facebook_rx_fb_page_info.enbale_type_on as enbale_type_on","facebook_rx_fb_page_info.reply_delay_time as reply_delay_time"),$join,'','','messenger_bot.id asc');
            
            $enable_mark_seen=$messenger_bot_info[0]['enable_mark_seen'];            
            
            if($enable_mark_seen)
                $this->sender_action($sender_id,"mark_seen",$messenger_bot_info[0]['page_access_token']);

            if($payload_id=="UNSUBSCRIBE_QUICK_BOXER")
            {  
                //$this->unsubscribe_webhook_call($sender_id,$page_id);
                
                // DEPRECATED FUNCTION FOR QUICK BROADCAST
                $post_data_unsubscribe=array("psid"=>$sender_id,"fb_page_id"=>$page_id);
                $url=base_url()."home/unsubscribe_webhook_call";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch,CURLOPT_POST,1);
                curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data_unsubscribe);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
                $reply_response=curl_exec($ch);  
            }
            elseif($payload_id=="RESUBSCRIBE_QUICK_BOXER")
            {
                // $this->resubscribe_webhook_call($sender_id,$page_id);

                //DEPRECATED FUNCTION FOR QUICK BROADCAST
                $post_data_unsubscribe=array("psid"=>$sender_id,"fb_page_id"=>$page_id);
                $url=base_url()."home/resubscribe_webhook_call";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch,CURLOPT_POST,1);
                curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data_unsubscribe);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
                $reply_response=curl_exec($ch);  
            }
            elseif($payload_id=="YES_START_CHAT_WITH_HUMAN")
            {
                if($this->basic->update_data("messenger_bot_subscriber",array("page_id"=>$page_id,"subscribe_id"=>$sender_id),array("status"=>"0")))
                {
                    $pagename= isset($users_expiry_info[0]['page_name']) ? $users_expiry_info[0]['page_name'] : "";
                    $chat_human_email=isset($users_expiry_info[0]['chat_human_email']) ? $users_expiry_info[0]['chat_human_email'] : "";

                    if($chat_human_email!="")
                    {
                        $message = "Hello,<br/> One of your messenger bot subscriber has stoped robot chat and wants to chat with human a agent.<br/><br/>";
                        $message.="Page : <a target='_BLANK' href='https://www.facebook.com/".$page_id."/inbox'>".$pagename."</a><br>";
                        $message.="Subscriber ID : ".$sender_id."<br>";
                        if(isset($subscriber_info[0]['first_name']))
                        $message.="Subscriber Name : ".$subscriber_info[0]['first_name'];
                        if(isset($subscriber_info[0]['last_name']))
                        $message.=" ".$subscriber_info[0]['last_name'];
                        $message.="<br/><br> Thank you";
                        
                        $mask="";
                        if($this->config->item("product_short_name")!="")
                        {
                            $message.=",".$this->config->item("product_short_name");
                            $mask=$this->config->item("product_short_name");
                        }

                        $subject="Want to chat with a human agent";
                        $this->_mail_sender($from, $chat_human_email, $subject, $message,$mask);
                    }
                }
            }
            elseif($payload_id=="YES_START_CHAT_WITH_BOT")
            {
                $this->basic->update_data("messenger_bot_subscriber",array("page_id"=>$page_id,"subscribe_id"=>$sender_id),array("status"=>"1"));                
            }

            foreach ($messenger_bot_info as $key => $value) {
                
                $message_str = $value['message'];
                $message_array = json_decode($message_str,true);
                // if(!isset($message_array[1])) $message_array[1]=$message_array;
                if(!isset($message_array[1])){
                    $message_array_org=$message_array;
                    $message_array=array();
                    $message_array[1]=$message_array_org;
                }
                foreach($message_array as $msg)
                {
                    $template_type_file_track=$msg['message']['template_type'];
                    unset($msg['message']['template_type']);

                    // typing on and typing on delay [alamin]
                    $enable_typing_on = $msg['message']['typing_on_settings'];
                    $enable_typing_on = ($enable_typing_on=='on')  ? 1 : 0;
                    unset($msg['message']['typing_on_settings']);
                    $typing_on_delay_time = $msg['message']['delay_in_reply'];
                    if($typing_on_delay_time=="") $typing_on_delay_time = 0;
                    unset($msg['message']['delay_in_reply']);
                    
                    /** Spintax **/
                    if(isset($msg['message']['text']))
                        $msg['message']['text']=spintax_process($msg['message']['text']);
                    
                    $msg['messaging_type'] = "RESPONSE";
                    $reply = json_encode($msg);    

                    $replace_search=array('{"id":"replace_id"}','#SUBSCRIBER_ID_REPLACE#');
                    $replace_with=array('{"id":"'.$sender_id.'"}',$sender_id);
                    $reply=str_replace($replace_search, $replace_with, $reply);

                    if(isset($subscriber_info[0]['first_name']))
                        $reply=str_replace('#LEAD_USER_FIRST_NAME#', $subscriber_info[0]['first_name'], $reply);
                    if(isset($subscriber_info[0]['last_name']))
                        $reply=str_replace('#LEAD_USER_LAST_NAME#', $subscriber_info[0]['last_name'], $reply);
                    $access_token = $value['page_access_token'];
                    if((isset($subscriber_info[0]['status']) && $subscriber_info[0]['status']=="1") || $payload_id=="YES_START_CHAT_WITH_BOT"){
                        
                        // typing on and typing on delay [alamin]
                        if($enable_typing_on) $this->sender_action($sender_id,"typing_on",$access_token);                                
                        if($typing_on_delay_time>0) sleep($typing_on_delay_time);
        
                        if($template_type_file_track=='video' || $template_type_file_track=='file' || $template_type_file_track=='audio'){
                            $post_data=array("access_token"=>$access_token,"reply"=>$reply);
                            $url=base_url()."home/send_reply_curl_call";
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch,CURLOPT_POST,1);
                            curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
                            $reply_response=curl_exec($ch);  
                    
                        }
                        else
                            $reply_response=$this->send_reply($access_token,$reply);
                         /*****Insert into database messenger_bot_reply_error_log if get error****/
                             if(isset($reply_response['error']['message'])){
                                $bot_settings_id= $value['id'];
                                $reply_error_message= $reply_response['error']['message'];
                                $error_time= date("Y-m-d H:i:s");
                                $page_table_id=$value['page_id'];
                                $user_id=$value['user_id'];
                                
                                $error_insert_data=array("page_id"=>$page_table_id,"fb_page_id"=>$page_id,"user_id"=>$user_id,
                                                    "error_message"=>$reply_error_message,"bot_settings_id"=>$bot_settings_id,
                                                    "error_time"=>$error_time);
                                $this->basic->insert_data('messenger_bot_reply_error_log',$error_insert_data);
                                
                             }
                    }                                       
                }


                $drip_assign_id=isset($messenger_bot_info[0]['drip_campaign_id']) ? $messenger_bot_info[0]['drip_campaign_id']:"";
                
                if($drip_assign_id!="" && $drip_assign_id!="0"){
                    $drip_type="custom";
                    $this->assign_drip_messaging_id($drip_type,"0",$PAGE_AUTO_ID,$sender_id,$drip_assign_id);  
                }


               /***Set labels if any setup available for this postback for quickReply ***/

                // if($this->db->table_exists('messenger_bot_broadcast')){

                $label_ids=isset($messenger_bot_info[0]['broadcaster_labels']) ? $messenger_bot_info[0]['broadcaster_labels']:"";
           
                if(!empty($label_ids)){
               
                    ///$this->assign_label_webhook_call($sender_id,$page_id,$label_ids);

                    //DEPRECATED FUNCTION FOR QUICK BROADCAST
                    $post_data_label_assign=array("psid"=>$sender_id,"fb_page_id"=>$page_id,"label_auto_ids"=>$label_ids);
                    $post_data_label_assign=array("psid"=>$sender_id,"fb_page_id"=>$page_id,"label_auto_ids"=>$label_ids);
                    $url=base_url()."home/assign_label_webhook_call";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch,CURLOPT_POST,1);
                    curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data_label_assign);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
                    $reply_response=curl_exec($ch); 
                     
                } 
                // }


                if($this->db->table_exists('messenger_bot_thirdparty_webhook') && $this->basic->is_exist("messenger_bot_thirdparty_webhook",array("page_id"=>$page_id)))                
                 $this->thirdparty_webhook_trigger($page_id,$sender_id,"trigger_postback",$payload_id);

                //update Subscriber Last Interaction time. 
                $this->update_subscriber_last_interaction($sender_id,$currenTime);

                die();
            }
        } 
        elseif(isset($response['entry'][0]['messaging'][0]['message']['attachments'][0]['payload']['coordinates']['lat'])){ 
            
            $lattitued= $response['entry'][0]['messaging'][0]['message']['attachments'][0]['payload']['coordinates']['lat'];
            $longitude= $response['entry'][0]['messaging'][0]['message']['attachments'][0]['payload']['coordinates']['long'];
            $location_bing_map=$response['entry'][0]['messaging'][0]['message']['attachments'][0]['url'];
            $user_location=$lattitued.",".$longitude;
            
            
            $fb_page_id=$subscriber_info[0]['page_id'];
            $user_id=$subscriber_info[0]['user_id'];
            $fb_user_id=$subscriber_info[0]['subscribe_id'];
            $fb_user_first_name=$subscriber_info[0]['first_name'];
            $fb_user_last_name=$subscriber_info[0]['last_name'];
            $profile_pic=$subscriber_info[0]['profile_pic'];
            $update_time=date("Y-m-d H:i:s");

                $sql="UPDATE messenger_bot_subscriber SET user_location='$user_location',location_map_url='$location_bing_map',last_update_time='$update_time' WHERE subscribe_id='$fb_user_id';";                      
                $this->basic->execute_complex_query($sql);
                $table_name = "messenger_bot";
                $where['where'] = array('messenger_bot.fb_page_id' => $page_id,'facebook_rx_fb_page_info.bot_enabled' => '1',"keyword_type"=>"location-quick-reply");
                $join = array('facebook_rx_fb_page_info'=>"facebook_rx_fb_page_info.page_id=messenger_bot.fb_page_id,left");
                $messenger_bot_info = $this->basic->get_data($table_name,$where,array("messenger_bot.*","facebook_rx_fb_page_info.page_access_token as page_access_token","facebook_rx_fb_page_info.enable_mark_seen as enable_mark_seen","facebook_rx_fb_page_info.enbale_type_on as enbale_type_on","facebook_rx_fb_page_info.reply_delay_time as reply_delay_time"),$join,'','','messenger_bot.id asc');
                
                $enable_mark_seen=$messenger_bot_info[0]['enable_mark_seen'];
            
            
            if($enable_mark_seen)
                $this->sender_action($sender_id,"mark_seen",$messenger_bot_info[0]['page_access_token']);
            foreach ($messenger_bot_info as $key => $value) {
                $message_str = $value['message'];
                $message_array = json_decode($message_str,true);
                // if(!isset($message_array[1])) $message_array[1]=$message_array;
                if(!isset($message_array[1])){
                    $message_array_org=$message_array;
                    $message_array=array();
                    $message_array[1]=$message_array_org;
                }
                foreach($message_array as $msg)
                {
                    $template_type_file_track=$msg['message']['template_type'];
                    unset($msg['message']['template_type']);

                    // typing on and typing on delay [alamin]
                    $enable_typing_on = $msg['message']['typing_on_settings'];
                    $enable_typing_on = ($enable_typing_on=='on')  ? 1 : 0;
                    unset($msg['message']['typing_on_settings']);
                    $typing_on_delay_time = $msg['message']['delay_in_reply'];
                    if($typing_on_delay_time=="") $typing_on_delay_time = 0;
                    unset($msg['message']['delay_in_reply']);
                    
                    /** Spintax **/
                    if(isset($msg['message']['text']))
                        $msg['message']['text']=spintax_process($msg['message']['text']);
                    
                    $msg['messaging_type'] = "RESPONSE";
                    $reply = json_encode($msg);                           

                   $replace_search=array('{"id":"replace_id"}','#SUBSCRIBER_ID_REPLACE#');
                   $replace_with=array('{"id":"'.$sender_id.'"}',$sender_id);
                   $reply=str_replace($replace_search, $replace_with, $reply);

                    if(isset($subscriber_info[0]['first_name']))
                        $reply=str_replace('#LEAD_USER_FIRST_NAME#', $subscriber_info[0]['first_name'], $reply);
                    if(isset($subscriber_info[0]['last_name']))
                        $reply=str_replace('#LEAD_USER_LAST_NAME#', $subscriber_info[0]['last_name'], $reply);
                    $access_token = $value['page_access_token'];
                    if(isset($subscriber_info[0]['status']) && $subscriber_info[0]['status']=="1"){
                    
                        // typing on and typing on delay [alamin]
                        if($enable_typing_on) $this->sender_action($sender_id,"typing_on",$access_token);                                
                        if($typing_on_delay_time>0) sleep($typing_on_delay_time);

                        if($template_type_file_track=='video' || $template_type_file_track=='file' || $template_type_file_track=='audio'){
                            $post_data=array("access_token"=>$access_token,"reply"=>$reply);
                            $url=base_url()."home/send_reply_curl_call";
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch,CURLOPT_POST,1);
                            curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
                            $reply_response=curl_exec($ch);  
                    
                        }
                        else
                             $reply_response=$this->send_reply($access_token,$reply);
                         /*****Insert into database messenger_bot_reply_error_log if get error****/
                             if(isset($reply_response['error']['message'])){
                                $bot_settings_id= $value['id'];
                                $reply_error_message= $reply_response['error']['message'];
                                $error_time= date("Y-m-d H:i:s");
                                $page_table_id=$value['page_id'];
                                $user_id=$value['user_id'];
                                
                                $error_insert_data=array("page_id"=>$page_table_id,"fb_page_id"=>$page_id,"user_id"=>$user_id,
                                                    "error_message"=>$reply_error_message,"bot_settings_id"=>$bot_settings_id,
                                                    "error_time"=>$error_time);
                                $this->basic->insert_data('messenger_bot_reply_error_log',$error_insert_data);
                                
                             }
                    }                    
                }

                if($this->db->table_exists('messenger_bot_thirdparty_webhook') && $this->basic->is_exist("messenger_bot_thirdparty_webhook",array("page_id"=>$page_id)))                
                $this->thirdparty_webhook_trigger($page_id,$sender_id,"trigger_location");

                //update Subscriber Last Interaction time. 
                $this->update_subscriber_last_interaction($sender_id,$currenTime);
                
                die();
            }
            
        }     
        else
        {   
            $table_name = "messenger_bot";
            $where['where'] = array('messenger_bot.fb_page_id' => $page_id, 'messenger_bot.keyword_type' => 'no match','facebook_rx_fb_page_info.bot_enabled' => '1','facebook_rx_fb_page_info.no_match_found_reply'=>'enabled');
            $join = array('facebook_rx_fb_page_info'=>"facebook_rx_fb_page_info.page_id=messenger_bot.fb_page_id,left");   
            $messenger_bot_info = $this->basic->get_data($table_name,$where,array("messenger_bot.*","facebook_rx_fb_page_info.page_access_token as page_access_token","facebook_rx_fb_page_info.enable_mark_seen as enable_mark_seen","facebook_rx_fb_page_info.enbale_type_on as enbale_type_on","facebook_rx_fb_page_info.reply_delay_time as reply_delay_time"),$join,'1','','messenger_bot.id asc');
            
            $enable_mark_seen=$messenger_bot_info[0]['enable_mark_seen'];
            
    
            if($enable_mark_seen)
                $this->sender_action($sender_id,"mark_seen",$messenger_bot_info[0]['page_access_token']);
            if(isset($messenger_bot_info[0]) && !empty($messenger_bot_info)){
                $message_str = $messenger_bot_info[0]['message'];
                $message_array = json_decode($message_str,true);
                // if(!isset($message_array[1])) $message_array[1]=$message_array;
                if(!isset($message_array[1])){
                    $message_array_org=$message_array;
                    $message_array=array();
                    $message_array[1]=$message_array_org;
                }
                foreach($message_array as $msg)
                {
                    $template_type_file_track=$msg['message']['template_type'];
                    unset($msg['message']['template_type']);

                    // typing on and typing on delay [alamin]
                    $enable_typing_on = $msg['message']['typing_on_settings'];
                    $enable_typing_on = ($enable_typing_on=='on')  ? 1 : 0;
                    unset($msg['message']['typing_on_settings']);
                    $typing_on_delay_time = $msg['message']['delay_in_reply'];
                    if($typing_on_delay_time=="") $typing_on_delay_time = 0;
                    unset($msg['message']['delay_in_reply']);
                    
                    /** Spintax **/
                    if(isset($msg['message']['text']))
                        $msg['message']['text']=spintax_process($msg['message']['text']);
            
                    $msg['messaging_type'] = "RESPONSE";
                    $reply = json_encode($msg);   
                                             
                    $replace_search=array('{"id":"replace_id"}','#SUBSCRIBER_ID_REPLACE#');
                    $replace_with=array('{"id":"'.$sender_id.'"}',$sender_id);
                    $reply=str_replace($replace_search, $replace_with, $reply);

                    if(isset($subscriber_info[0]['first_name']))
                        $reply=str_replace('#LEAD_USER_FIRST_NAME#', $subscriber_info[0]['first_name'], $reply);
                    if(isset($subscriber_info[0]['last_name']))
                        $reply=str_replace('#LEAD_USER_LAST_NAME#', $subscriber_info[0]['last_name'], $reply);
                    $access_token = $messenger_bot_info[0]['page_access_token'];
                    if(isset($subscriber_info[0]['status']) && $subscriber_info[0]['status']=="1"){
                    
                        // typing on and typing on delay [alamin]
                        if($enable_typing_on) $this->sender_action($sender_id,"typing_on",$access_token);                                
                        if($typing_on_delay_time>0) sleep($typing_on_delay_time);
                        
                        if($template_type_file_track=='video' || $template_type_file_track=='file' || $template_type_file_track=='audio'){
                            $post_data=array("access_token"=>$access_token,"reply"=>$reply);
                            $url=base_url()."home/send_reply_curl_call";
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch,CURLOPT_POST,1);
                            curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
                            $reply_response=curl_exec($ch);  
                    
                        }
                        else
                             $reply_response=$this->send_reply($access_token,$reply);
                         /*****Insert into database messenger_bot_reply_error_log if get error****/
                             if(isset($reply_response['error']['message'])){
                                $bot_settings_id= $value['id'];
                                $reply_error_message= $reply_response['error']['message'];
                                $error_time= date("Y-m-d H:i:s");
                                $page_table_id=$value['page_id'];
                                $user_id=$value['user_id'];
                                
                                $error_insert_data=array("page_id"=>$page_table_id,"fb_page_id"=>$page_id,"user_id"=>$user_id,
                                                    "error_message"=>$reply_error_message,"bot_settings_id"=>$bot_settings_id,
                                                    "error_time"=>$error_time);
                                $this->basic->insert_data('messenger_bot_reply_error_log',$error_insert_data);
                                
                             }
                    }                                     
                }

                //update Subscriber Last Interaction time. 
                $this->update_subscriber_last_interaction($sender_id,$currenTime);
                
                die();
            }
        }
    }


   

    public function index()
    {
        $this->bot_menu_section();
    }
    


    //=================================BOT SETTINGS===============================
    public function bot_list()
    {   
        if($this->session->userdata('user_type') != 'Admin' && !in_array(200,$this->module_access))
        redirect('home/login_page', 'location'); 
        $data['body'] = 'messenger_tools/bot_list';
        $data['page_title'] = $this->lang->line('Bot Settings');  
        $table_name = "facebook_rx_fb_page_info";
        $where['where'] = array('bot_enabled' => "1",'facebook_rx_fb_page_info.facebook_rx_fb_user_info_id'=> $this->session->userdata('facebook_rx_fb_user_info'));
        $join = array('facebook_rx_fb_user_info'=>"facebook_rx_fb_user_info.id=facebook_rx_fb_page_info.facebook_rx_fb_user_info_id,left");   
        $page_info = $this->basic->get_data($table_name,$where,array("facebook_rx_fb_page_info.*","facebook_rx_fb_user_info.name as account_name","facebook_rx_fb_user_info.fb_id"),$join,'','','page_name asc');
        $error_record = $this->basic->get_data('messenger_bot_reply_error_log',array('where'=>array('user_id'=>$this->user_id)),$select=array('page_id','count(id) as total_error'),$join='',$limit='',$start=NULL,$order_by='',$group_by='page_id');
        $error_record_array = array();
        foreach($error_record as $value)
        {
            $error_record_array[$value['page_id']] = $value['total_error'];
        }
        $data['error_record'] = $error_record_array;
        $len_page_info = count($page_info); 

        $page_list = array();
        if(!empty($page_info))
        {
            $i = 1;
            $selected_page_id = $this->session->userdata('bot_list_get_page_details_page_table_id');
            foreach($page_info as $value)
            {
                if($value['id'] == $selected_page_id) $page_list[0] = $value;
                else $page_list[$i] = $value;
                $i++;
            }
        }
        ksort($page_list);
        $data['page_info'] = $page_list;

        $data['package_list'] = $this->package_list(); // get user package

        // get eligible saved templates
        if($this->db->table_exists('messenger_bot_saved_templates')) 
        {
            if ($this->session->userdata("user_type")=="Member") 
            {
                $package_info=$this->session->userdata('package_info');
                $search_package_id=isset($package_info['id'])?$package_info['id']:'0';
                $where_custom="((FIND_IN_SET('".$search_package_id."',allowed_package_ids) <> 0 AND template_access='public') OR (template_access='private' AND user_id='".$this->user_id."'))";
            }
            else $where_custom="user_id='".$this->user_id."'";        

            $this->db->select('*');
            $this->db->where( $where_custom );
            $this->db->order_by("saved_at DESC");
            $query = $this->db->get('messenger_bot_saved_templates');
            $template_data=$query->result_array();
            $data["saved_template_list"]=$template_data;
        }
        else $data["saved_template_list"]=array();
        // ----------------------------------
        $this->_viewcontroller($data);   
    }


    public function get_page_details()
    {
        $this->ajax_check();
        $page_table_id = $this->input->post('page_table_id',true);
        $facebook_rx_fb_user_info_id  =  $this->session->userdata('facebook_rx_fb_user_info');
        $this->session->set_userdata('bot_list_get_page_details_page_table_id',$page_table_id);

        $where = array();
        $table_name = "facebook_rx_fb_page_info";
        $where['where'] = array('facebook_rx_fb_user_info_id' => $facebook_rx_fb_user_info_id,'id'=>$page_table_id);
        $page_info = $this->basic->get_data($table_name,$where,'','','','','page_name asc');

        $subscription_messaging_permission_str = '';
        if($page_info[0]['review_status'] == 'PENDING')
            $subscription_messaging_permission_str = '<a class="badge badge-status orange"><i class="fas fa-hourglass-start"></i> '.$this->lang->line('Pending').'</a>';
        else if($page_info[0]['review_status'] == 'REJECTED')
            $subscription_messaging_permission_str = '<a class="badge badge-status red"><i class="fas fa-ban"></i> '.$this->lang->line('Rejected').'</a>';
        else if($page_info[0]['review_status'] == 'APPROVED')
            $subscription_messaging_permission_str = '<a class="badge badge-status blue"><i class="fas fa-check-circle"></i> '.$this->lang->line("Approved").'</a>';
        else if($page_info[0]['review_status'] == 'LIMITED')
            $subscription_messaging_permission_str = '<a class="badge badge-status blue"><i class="fas fa-lock"></i> '.$this->lang->line("Limited").'</a>';
        else
            $subscription_messaging_permission_str = '<a class="badge badge-status"><i class="fas fa-times-circle"></i> '.$this->lang->line('Not Submitted').'</a>';

        if($page_info[0]['estimated_reach'] != '')
            $estimated_reach = custom_number_format($page_info[0]['estimated_reach']);
        else
            $estimated_reach = 0;

        $where = array();
        $table_name = "messenger_bot_subscriber";
        $where['where'] = array('user_id' => $this->user_id, 'page_table_id' => $page_info[0]['id']);
        $sub_count = $this->basic->get_data($table_name,$where,'id');
        $subscriber_count = count($sub_count);

        $error_record = $this->basic->get_data('messenger_bot_reply_error_log',array('where'=>array('user_id' => $this->user_id, 'page_id' => $page_info[0]['id'])),$select=array('id'));
        $error_count = count($error_record);

        
        $getstarted_info = $this->basic->get_data("messenger_bot",array("where"=>array("keyword_type"=>"get-started","user_id"=>$this->user_id,"page_id"=>$page_table_id)));
        $gid=$gurl='';
        if(isset($getstarted_info[0]['id'])) $gid = $getstarted_info[0]['id'];
        if($gid!='') $gurl = base_url("messenger_bot/edit_bot/").$gid."/1/getstart";

        $response['getstarted_button_edit_url'] = $gurl;

        $nomatch_info = $this->basic->get_data("messenger_bot",array("where"=>array("keyword_type"=>"no match","user_id"=>$this->user_id,"page_id"=>$page_table_id)));
        $nid=$nurl='';
        if(isset($nomatch_info[0]['id'])) $nid = $nomatch_info[0]['id'];
        if($nid!='') $nurl = base_url("messenger_bot/edit_bot/").$nid."/1/nomatch";

        $action_button_info = $this->basic->get_data("messenger_bot_postback",array("where"=>array("page_id"=>$page_table_id,"template_for !="=>'reply_message')));
        $uurl=$rurl=$eurl=$purl=$lurl=$hurl=$burl=$jurl='';
        foreach($action_button_info as $key => $value) 
        {
          if($value['template_for']=='unsubscribe') $uurl=base_url('messenger_bot/edit_template/').$value['id'].'/1/default';
          else if($value['template_for']=='resubscribe') $rurl=base_url('messenger_bot/edit_template/').$value['id'].'/1/default';
          else if($value['template_for']=='email-quick-reply') $eurl=base_url('messenger_bot/edit_template/').$value['id'].'/1/default';          
          else if($value['template_for']=='phone-quick-reply') $purl=base_url('messenger_bot/edit_template/').$value['id'].'/1/default';          
          else if($value['template_for']=='location-quick-reply') $lurl=base_url('messenger_bot/edit_template/').$value['id'].'/1/default';          
          else if($value['template_for']=='birthday-quick-reply') $jurl=base_url('messenger_bot/edit_template/').$value['id'].'/1/default';          
          else if($value['template_for']=='chat-with-human') $hurl=base_url('messenger_bot/edit_template/').$value['id'].'/1/default';          
          else if($value['template_for']=='chat-with-bot') $burl=base_url('messenger_bot/edit_template/').$value['id'].'/1/default';          
        }

        $murl = base_url('messenger_bot/persistent_menu_list/').$page_table_id.'/1';
        $durl = base_url('messenger_bot_enhancers/sequence_message_campaign/').$page_table_id.'/1';
        $surl = base_url('subscriber_manager/bot_subscribers/0/').$page_table_id;

        $middle_column_content='
        <div class="card main_card">
          <div class="card-header padding-left-10">
            <h4 class="put_page_name_url"><i class="fab fa-facebook-square"></i> <a target="_BLANK" href="https://facebook.com/'.$page_info[0]['page_id'].'">'.$page_info[0]['page_name'].'</a></h4>
          </div>
          <div class="card-body padding-10">

            <div class="row">

              <div class="col-12">
                <div class="card card-large-icons card-condensed active">
                  <div class="card-icon">
                    <i class="fas fa-comments"></i>
                  </div>
                  <div class="card-body">
                    <h4>'.$this->lang->line("Bot Reply Settings").'</h4>                    
                    <a href="'.base_url("messenger_bot/bot_settings/").$page_info[0]['id'].'/1" data-page-id="'.$page_info[0]['id'].'" data-height="795" class="card-cta iframed" id="reply_settings">'.$this->lang->line("Change Settings").'</a>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <div class="card card-large-icons card-condensed">
                  <div class="card-icon">
                    <i class="fas fa-check-circle"></i>
                  </div>
                  <div class="card-body">
                    <h4>'.$this->lang->line("Get Started Settings").'</h4>                    
                    <a href="" id="get_started_settings" data-page-id="'.$page_table_id.'" class="card-cta enable_start_button" welcome-message="'.htmlspecialchars($page_info[0]['welcome_message']).'" sbutton-enable="'.$page_info[0]['id'].'" sbutton-status="'.$page_info[0]['started_button_enabled'].'" >'.$this->lang->line("Change Settings").'</a>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <div class="card card-large-icons card-condensed">
                  <div class="card-icon">
                    <i class="fas fa-cog"></i>
                  </div>
                  <div class="card-body">
                    <h4>'.$this->lang->line("General Settings").'</h4>                    
                    <a href="" id="general_settings" class="card-cta enable_general_settings" chat_human_email="'.$page_info[0]['chat_human_email'].'" table_id="'.$page_info[0]['id'].'" mark_seen_status="'.$page_info[0]['enable_mark_seen'].'" no_match_found_reply="'.$page_info[0]['no_match_found_reply'].'" >'.$this->lang->line("Change Settings").'</a>

                  </div>
                </div>
              </div>
              
              <div class="col-12">
                <div class="card card-large-icons card-condensed">
                  <div class="card-icon">
                    <i class="far fa-hand-pointer"></i>
                  </div>
                  <div class="card-body">
                    <h4>'.$this->lang->line("Action Button Settings").'</h4>                    
                    <div class="dropdown avatar-badge">
                      <span class="dropdown-toggle pointer" data-toggle="dropdown">
                         '.$this->lang->line("Change Settings").'
                      </span>
                      <div class="dropdown-menu large">
                          <a class="pointer dropdown-item has-icon iframed" data-height="795" href="'.$gurl.'"><i class="fas fa-check-circle"></i> '.$this->lang->line("Get-started Template").'</a>
                          <a class="pointer dropdown-item has-icon iframed" data-height="795" href="'.$nurl.'"><i class="fas fa-comment-slash"></i> '.$this->lang->line("No Match Template").'</a>
                          <a class="pointer dropdown-item has-icon iframed" data-height="795" href="'.$uurl.'"><i class="fas fa-user-slash"></i> '.$this->lang->line("Un-subscribe Template").'</a>
                          <a class="pointer dropdown-item has-icon iframed" data-height="795" href="'.$rurl.'"><i class="fas fa-user-circle"></i> '.$this->lang->line("Re-subscribe Template").'</a>
                          <a class="pointer dropdown-item has-icon iframed" data-height="795" href="'.$eurl.'"><i class="fas fa-envelope"></i> '.$this->lang->line("Email Quick Reply Template").'</a>
                          <a class="pointer dropdown-item has-icon iframed" data-height="795" href="'.$purl.'"><i class="fas fa-phone"></i> '.$this->lang->line("Phone Quick Reply Template").'</a>
                          <!--<a class="pointer dropdown-item has-icon iframed" data-height="795" href="'.$lurl.'"><i class="fas fa-map"></i> '.$this->lang->line("Location Quick Reply Template").'</a>-->
                          <a class="pointer dropdown-item has-icon iframed" data-height="795" href="'.$jurl.'"><i class="fas fa-birthday-cake"></i> '.$this->lang->line("Birthday Quick Reply Template").'</a>
                          <a class="pointer dropdown-item has-icon iframed" data-height="795" href="'.$hurl.'"><i class="fas fa-headset"></i> '.$this->lang->line("Chat with Human Template").'</a>
                          <a class="pointer dropdown-item has-icon iframed" data-height="795" href="'.$burl.'"><i class="fas fa-robot"></i> '.$this->lang->line("Chat with Robot Template").'</a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>';
        if($this->session->userdata('user_type') == 'Admin' || in_array(197,$this->module_access)) : 
            $pm_str="";
            if($page_info[0]['persistent_enabled']=='1') $pm_str='<small class="badge badge-status green">'.$this->lang->line("Published").'</small>';
            $middle_column_content .='
              <div class="col-12">
                <div class="card card-large-icons card-condensed">
                  <div class="card-icon">
                    <i class="fas fa-bars"></i>
                  </div>
                  <div class="card-body">
                    <h4>'.$this->lang->line("Persistent Menu Settings").'</h4>                    
                    <a href="'.$murl.'" class="card-cta iframed" data-height="795">'.$this->lang->line("Change Settings").' '.$pm_str.'</a>
                  </div>
                </div>
              </div>';
        endif;
        if($this->is_drip_campaigner_exist) :
            $middle_column_content .='
              <div class="col-12">
                <div class="card card-large-icons card-condensed">
                  <div class="card-icon">
                    <i class="fas fa-tint"></i>
                  </div>
                  <div class="card-body">
                    <h4>'.$this->lang->line("Sequence Message Settings").'</h4>                    
                    <a href="'.$durl.'" class="card-cta iframed" data-height="795">'.$this->lang->line("Change Settings").'</a>
                  </div>
                </div>
              </div>';
        endif;
        
        $middle_column_content .='</div>';
            
        if($this->is_drip_campaigner_exist) :
            $middle_column_content .='
            <div class="row custom-top-margin">
              <!--
              <div class="col-12">
                  <div class="product-item pb-3">
                    <div class="product-image">
                      <img src="../assets/img/icon/access.png" class="img-fluid rounded">
                    </div>
                    <div class="product-details">
                      <div class="product-name">'.$this->lang->line("Subscription Messaging Permission").'</div>                                        
                      '.$subscription_messaging_permission_str.'
                    </div>
                    <div class="product-cta">
                      <a href="#" class="btn btn-sm small btn-info check_review_status_class" data-id="'.$page_info[0]['id'].'"><i class="fas fa-check-circle"></i> '.$this->lang->line("Check Status").'</a>
                    </div>
                  </div>
              </div>
              <div class="col-12 col-md-12 col-lg-6">
                  <div class="product-item pb-3">
                    <div class="product-image">
                      <img src="../assets/img/icon/paper-plane.png" class="img-fluid rounded">
                    </div>
                    <div class="product-details">
                      <div class="product-name">'.$this->lang->line("Quick Broadcast Estimated Reach").'</div>  
                      <a class="badge badge-status blue"><i class="fas fa-circle"></i> '.$this->lang->line("Estimate").' : '.$estimated_reach.'</a>
                    </div>
                    <div class="product-cta">
                      <a href="#" class="btn btn-sm small btn-primary estimate_now_class" data-id="'.$page_info[0]['id'].'" ><i class="fas fa-user-friends"></i> '.$this->lang->line("Estimate Now").'</a>
                    </div>
                  </div>
              </div>
              -->
            </div>';
        endif;

        $middle_column_content .='
          </div>
          <div class="card-footer text-center">
            <a href="'.$surl.'" class="btn btn-sm btn-outline-primary float-left"><i class="fas fa-user-friends"></i> '.$this->lang->line("Subscribers").' <span class="badge badge-primary">'.custom_number_format($subscriber_count,2).'</span></a>
            <a href="" class="btn btn-sm btn-outline-danger float-right error_log_report" id="error_log" table_id="'.$page_table_id.'"><i class="fas fa-bug"></i> '.$this->lang->line("Errors").' <span class="badge badge-danger">'.$error_count.'</span></a>
          </div>
        </div>
        
        <script>
        $(\'[data-toggle="popover"]\').popover(); 
        $(\'[data-toggle="popover"]\').on("click", function(e) {e.preventDefault(); return true;});
        </script>
        ';     

        $response['middle_column_content'] = $middle_column_content;
        echo json_encode($response);
    }


    public function edit_bot($bot_id='0',$iframe='0',$default_template='0')
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(200, $this->module_access))
        redirect ('home/login_page','location');
        if($bot_id == 0)
        die();
        $table_name = "messenger_bot";
        $where_bot['where'] = array('id' => $bot_id, 'status' => '1');
        $bot_info = $this->basic->get_data($table_name, $where_bot);
        if(!isset($bot_info[0]))
        redirect('messenger_bot/bot_list', 'location');
        $table_name = "facebook_rx_fb_page_info";
        $where['where'] = array('bot_enabled' => "1", "facebook_rx_fb_page_info.id"=>$bot_info[0]["page_id"], "facebook_rx_fb_page_info.user_id"=>$this->user_id);
        $join = array('facebook_rx_fb_user_info'=>"facebook_rx_fb_user_info.id=facebook_rx_fb_page_info.facebook_rx_fb_user_info_id,left");
        $page_info = $this->basic->get_data($table_name,$where, array("facebook_rx_fb_page_info.*","facebook_rx_fb_user_info.name as account_name","facebook_rx_fb_user_info.fb_id"),$join);
        if(!isset($page_info[0]))
        redirect('messenger_bot/bot_list','location'); 
        $data["templates"]=$this->basic->get_enum_values("messenger_bot","template_type");
        $data["keyword_types"]=$this->basic->get_enum_values("messenger_bot","keyword_type");
        $data['body'] = 'messenger_tools/edit_bot_settings';
        $data['page_title'] = $this->lang->line('Edit Bot Settings');  
        $data['page_info'] = isset($page_info[0]) ? $page_info[0] : array();  
        $data['bot_info'] = isset($bot_info[0]) ? $bot_info[0] : array();
        $postback_id_list = $this->basic->get_data('messenger_bot_postback',array('where'=>array('user_id'=>$this->user_id,'page_id'=>$bot_info[0]["page_id"])));
        $current_postbacks = array();
        foreach ($postback_id_list as $value) {
            if($value['messenger_bot_table_id'] == $bot_id)
            $current_postbacks[] = $value['postback_id'];
        }
        $data['postback_ids'] = $postback_id_list;
        $data['current_postbacks'] = $current_postbacks;
        $page_id=$page_info[0]['id'];// database id      
        $postback_data=$this->basic->get_data("messenger_bot_postback",array("where"=>array("page_id"=>$page_id,"is_template"=>"1",'template_for'=>'reply_message'),"or_where"=>array("messenger_bot_table_id"=>$bot_id)),'','','',$start=NULL,$order_by='template_name ASC');
        
        $poption=array();
        foreach ($postback_data as $key => $value) 
        {
            // if($value["template_for"]=="unsubscribe" || $value["template_for"]=="resubscribe" || $value["template_for"]=="email-quick-reply" || $value["template_for"]=="phone-quick-reply" || $value["template_for"]=="location-quick-reply" || $value["template_for"]=="chat-with-human" || $value["template_for"]=="chat-with-bot") continue;
            $poption[$value["postback_id"]]=$value['template_name'].' ['.$value['postback_id'].']';
        }
        $data['poption']=$poption;

        if($this->basic->is_exist("add_ons",array("project_id"=>16)))
            $data['has_broadcaster_addon'] = 1;
        else
            $data['has_broadcaster_addon'] = 0;
        
        $data['default_template'] = $default_template;
        $data['iframe']=$iframe;
        $this->_viewcontroller($data); 
    }

    public function bot_settings($page_auto_id='0',$iframe='0')
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(200,$this->module_access))
        redirect('home/login_page', 'location'); 
        if($page_auto_id==0) exit();
        $table_name = "facebook_rx_fb_page_info";
        $where['where'] = array('bot_enabled' => "1","facebook_rx_fb_page_info.id"=>$page_auto_id,"facebook_rx_fb_page_info.user_id"=>$this->user_id);
        $join = array('facebook_rx_fb_user_info'=>"facebook_rx_fb_user_info.id=facebook_rx_fb_page_info.facebook_rx_fb_user_info_id,left");   
        $page_info = $this->basic->get_data($table_name,$where,array("facebook_rx_fb_page_info.*","facebook_rx_fb_user_info.name as account_name","facebook_rx_fb_user_info.fb_id"),$join);
        if(!isset($page_info[0]))
        redirect('messenger_bot/bot_list', 'location'); 
        $bot_settings=$this->basic->get_data("messenger_bot",array("where"=>array("page_id"=>$page_auto_id,"is_template"=>"0")),'','','','','bot_name asc');
        
        $data["templates"]=$this->basic->get_enum_values("messenger_bot","template_type");
        $data["keyword_types"]=$this->basic->get_enum_values("messenger_bot","keyword_type");
        $data['body'] = 'messenger_tools/bot_settings';
        $data['page_title'] = $this->lang->line('Bot Settings');  
        $data['page_info'] = isset($page_info[0]) ? $page_info[0] : array();  
        $data['bot_settings'] = $bot_settings;

        $postback_id_list = $this->basic->get_data('messenger_bot_postback',array('where'=>array('user_id'=>$this->user_id,'page_id'=>$page_auto_id)));
        $data['postback_ids'] = $postback_id_list;

        if($this->basic->is_exist("add_ons",array("project_id"=>16)))
            $data['has_broadcaster_addon'] = 1;
        else  $data['has_broadcaster_addon'] = 0;

        $data['iframe']=$iframe;
        $this->_viewcontroller($data);  
    }

    public function get_postback()
    {
        if(!$_POST) exit();
        $is_from_add_button=$this->input->post('is_from_add_button');
        $page_id=$this->input->post('page_id');// database id      
        $order_by=$this->input->post('order_by');     
        if($order_by=="") $order_by="id DESC";
        else $order_by=$order_by." ASC";
        $postback_data=$this->basic->get_data("messenger_bot_postback",array("where"=>array("page_id"=>$page_id,"is_template"=>"1",'template_for'=>'reply_message')),'','','',$start=NULL,$order_by);
        $push_postback="";

        if($is_from_add_button=='0')
        {
            $push_postback.="<option value=''>".$this->lang->line("Select")."</option>";
        }
        
        foreach ($postback_data as $key => $value) 
        {
            // if($value["template_for"]=="unsubscribe" || $value["template_for"]=="resubscribe" || $value["template_for"]=="email-quick-reply" || $value["template_for"]=="phone-quick-reply" || $value["template_for"]=="location-quick-reply" || $value["template_for"]=="chat-with-human" || $value["template_for"]=="chat-with-bot" || $value["template_for"]=="birthday-quick-reply") continue;
            $push_postback.="<option value='".$value['postback_id']."'>".$value['template_name'].' ['.$value['postback_id'].']'."</option>";
        }

        if($is_from_add_button=='1' || $is_from_add_button=='')
        {
            $push_postback.="<option value=''>".$this->lang->line("Select")."</option>";
        }

        echo $push_postback;   
    }

    public function get_postback_for_persistent_menu()
    {
        if(!$_POST) exit();
        $page_id=$this->input->post('page_id');// database id      
        $order_by=$this->input->post('order_by');     
        if($order_by=="") $order_by="id DESC";
        else $order_by=$order_by." ASC";
        $postback_data=$this->basic->get_data("messenger_bot_postback",array("where"=>array("page_id"=>$page_id,'is_template'=>'1','template_for'=>'reply_message')),'','','',$start=NULL,$order_by);
        $push_postback="";
        foreach ($postback_data as $key => $value) 
        {
            // if($value["template_for"]=="email-quick-reply" || $value["template_for"]=="phone-quick-reply" || $value["template_for"]=="location-quick-reply") continue;
            $push_postback.="<option value='".$value['postback_id']."'>".$value['template_name'].' ['.$value['postback_id'].']'."</option>";
        }
        echo $push_postback;   
    }
    //=================================BOT SETTINGS===============================
    public function edit_generate_messenger_bot()
    {
        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                echo "<div class='alert alert-danger text-center'><i class='fa fa-ban'></i> This function is disabled from admin account in this demo!!</div>";
                exit();
            }
        }

        $post=$_POST;
        foreach ($post as $key => $value) 
        {
            $$key=$value;
        }
        // $template_type = trim($template_type);
        $insert_data = array();
        $insert_data['bot_name'] = $bot_name;
        $insert_data['fb_page_id'] = $page_id;
        $insert_data['keywords'] = trim($keywords_list);
        $insert_data['page_id'] = $page_table_id;
        // $insert_data['template_type'] = $template_type;
        $insert_data['keyword_type'] = $keyword_type;
        if($keyword_type == 'post-back')
            $insert_data['postback_id'] = implode(',', $keywordtype_postback_id);

        // $template_type = str_replace(' ', '_', $template_type);
        // domain white list section
        $facebook_rx_fb_user_info_id = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_table_id)),array("facebook_rx_fb_user_info_id","page_access_token"));
        $page_access_token = $facebook_rx_fb_user_info_id[0]['page_access_token'];
        $facebook_rx_fb_user_info_id = $facebook_rx_fb_user_info_id[0]["facebook_rx_fb_user_info_id"];
        $white_listed_domain = $this->basic->get_data("messenger_bot_domain_whitelist",array("where"=>array("user_id"=>$this->user_id,"messenger_bot_user_info_id"=>$facebook_rx_fb_user_info_id,"page_id"=>$page_table_id)),"domain");
        $white_listed_domain_array = array();
        foreach ($white_listed_domain as $value) {
            $white_listed_domain_array[] = $value['domain'];
        }
        $need_to_whitelist_array = array();
        // domain white list section

        $postback_insert_data = array();
        $reply_bot = array();
        $bot_message = array();

        for ($k=1; $k <=3 ; $k++) 
        {    
            $template_type = 'template_type_'.$k;
            if(!isset($$template_type)) continue;
            $template_type = $$template_type;
            // $insert_data['template_type'] = $template_type;
            $template_type = str_replace(' ', '_', $template_type);

            if($template_type == 'text')
            {
                $text_reply = 'text_reply_'.$k;
                $text_reply = isset($$text_reply) ? $$text_reply : '';
                if($text_reply != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['text'] = $text_reply;
                    
                }
            }
            if($template_type == 'image')
            {
                $image_reply_field = 'image_reply_field_'.$k;
                $image_reply_field = isset($$image_reply_field) ? $$image_reply_field : '';
                if($image_reply_field != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'image';
                    $reply_bot[$k]['attachment']['payload']['url'] = $image_reply_field;
                    $reply_bot[$k]['attachment']['payload']['is_reusable'] = true;                    
                }
            }
            if($template_type == 'audio')
            {
                $audio_reply_field = 'audio_reply_field_'.$k;
                $audio_reply_field = isset($$audio_reply_field) ? $$audio_reply_field : '';
                if($audio_reply_field != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'audio';
                    $reply_bot[$k]['attachment']['payload']['url'] = $audio_reply_field;
                    $reply_bot[$k]['attachment']['payload']['is_reusable'] = true;
                }
                
            }
            if($template_type == 'video')
            {
                $video_reply_field = 'video_reply_field_'.$k;
                $video_reply_field = isset($$video_reply_field) ? $$video_reply_field : '';
                if($video_reply_field != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'video';
                    $reply_bot[$k]['attachment']['payload']['url'] = $video_reply_field;
                    $reply_bot[$k]['attachment']['payload']['is_reusable'] = true;                    
                }
            }
            if($template_type == 'file')
            {
                $file_reply_field = 'file_reply_field_'.$k;
                $file_reply_field = isset($$file_reply_field) ? $$file_reply_field : '';
                if($file_reply_field != '')
                {       
                    $reply_bot[$k]['template_type'] = $template_type;             
                    $reply_bot[$k]['attachment']['type'] = 'file';
                    $reply_bot[$k]['attachment']['payload']['url'] = $file_reply_field;
                    $reply_bot[$k]['attachment']['payload']['is_reusable'] = true;
                }
            }



        
            if($template_type == 'media')
            {
                $media_input = 'media_input_'.$k;
                $media_input = isset($$media_input) ? $$media_input : '';
                if($media_input != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'template';
                    $reply_bot[$k]['attachment']['payload']['template_type'] = 'media';
                    $template_media_type = '';
                    if (strpos($media_input, '/videos/') !== false) {
                        $template_media_type = 'video';
                    }
                    else
                        $template_media_type = 'image';
                    $reply_bot[$k]['attachment']['payload']['elements'][0]['media_type'] = $template_media_type;
                    $reply_bot[$k]['attachment']['payload']['elements'][0]['url'] = $media_input;                    
                }

                for ($i=1; $i <= 3 ; $i++) 
                { 
                    $button_text = 'media_text_'.$i.'_'.$k;
                    $button_text = isset($$button_text) ? $$button_text : '';
                    $button_type = 'media_type_'.$i.'_'.$k;
                    $button_type = isset($$button_type) ? $$button_type : '';
                    $button_postback_id = 'media_post_id_'.$i.'_'.$k;
                    $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                    $button_web_url = 'media_web_url_'.$i.'_'.$k;
                    $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                    //add an extra query parameter for tracking the subscriber to whom send 
                    if($button_web_url!='')
                      $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");

                    $button_call_us = 'media_call_us_'.$i.'_'.$k;
                    $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                    if($button_type == 'post_back')
                    {
                        if($button_text != '' && $button_type != '' && $button_postback_id != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'postback';
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['payload'] = $button_postback_id;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;
                            $single_postback_insert_data = array();
                            $single_postback_insert_data['user_id'] = $this->user_id;
                            $single_postback_insert_data['postback_id'] = $button_postback_id;
                            $single_postback_insert_data['page_id'] = $page_table_id;
                            $single_postback_insert_data['bot_name'] = $bot_name;
                            $postback_insert_data[] = $single_postback_insert_data; 
                        }
                    }
                    if(strpos($button_type,'web_url') !== FALSE)
                    {
                        $button_type_array = explode('_', $button_type);
                        if(isset($button_type_array[2]))
                        {
                            $button_extension = trim($button_type_array[2],'_'); 
                            array_pop($button_type_array);
                        }            
                        else $button_extension = '';
                        $button_type = implode('_', $button_type_array);

                        if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'web_url';
                            if($button_extension != '' && $button_extension == 'birthday'){
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['webview_height_ratio'] = 'compact';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                                $button_web_url = base_url('webview_builder/get_birthdate');
                            }
                            else
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['url'] = $button_web_url;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;

                            if($button_extension != '' && $button_extension != 'birthday')
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['webview_height_ratio'] = $button_extension;
                                // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                            }

                            if(!in_array($button_web_url, $white_listed_domain_array))
                            {
                                $need_to_whitelist_array[] = $button_web_url;
                            }
                        }
                    }
                    if($button_type == 'phone_number')
                    {
                        if($button_text != '' && $button_type != '' && $button_call_us != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'phone_number';
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['payload'] = $button_call_us;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;
                        }
                    }
                }
            }



            if($template_type == 'text_with_buttons')
            {
                $text_with_buttons_input = 'text_with_buttons_input_'.$k;
                $text_with_buttons_input = isset($$text_with_buttons_input) ? $$text_with_buttons_input : '';
                if($text_with_buttons_input != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'template';
                    $reply_bot[$k]['attachment']['payload']['template_type'] = 'button';
                    $reply_bot[$k]['attachment']['payload']['text'] = $text_with_buttons_input;                    
                }

                for ($i=1; $i <= 3 ; $i++) 
                { 
                    $button_text = 'text_with_buttons_text_'.$i.'_'.$k;
                    $button_text = isset($$button_text) ? $$button_text : '';
                    $button_type = 'text_with_button_type_'.$i.'_'.$k;
                    $button_type = isset($$button_type) ? $$button_type : '';
                    $button_postback_id = 'text_with_button_post_id_'.$i.'_'.$k;
                    $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                    $button_web_url = 'text_with_button_web_url_'.$i.'_'.$k;
                    $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                    //add an extra query parameter for tracking the subscriber to whom send 
                    if($button_web_url!='')
                        $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");


                    $button_call_us = 'text_with_button_call_us_'.$i.'_'.$k;
                    $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                    if($button_type == 'post_back')
                    {
                        if($button_text != '' && $button_type != '' && $button_postback_id != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['type'] = 'postback';
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['payload'] = $button_postback_id;
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['title'] = $button_text;
                            $single_postback_insert_data = array();
                            $single_postback_insert_data['user_id'] = $this->user_id;
                            $single_postback_insert_data['postback_id'] = $button_postback_id;
                            $single_postback_insert_data['page_id'] = $page_table_id;
                            $single_postback_insert_data['bot_name'] = $bot_name;
                            $postback_insert_data[] = $single_postback_insert_data; 
                        }
                    }
                    if(strpos($button_type,'web_url') !== FALSE)
                    {
                        $button_type_array = explode('_', $button_type);
                        if(isset($button_type_array[2]))
                        {
                            $button_extension = trim($button_type_array[2],'_'); 
                            array_pop($button_type_array);
                        }            
                        else $button_extension = '';
                        $button_type = implode('_', $button_type_array);

                        if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                        {
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['type'] = 'web_url';

                            if($button_extension != '' && $button_extension == 'birthday'){
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['webview_height_ratio'] = 'compact';
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                                $button_web_url = base_url('webview_builder/get_birthdate');
                            }
                            else
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['url'] = $button_web_url;
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['title'] = $button_text;

                            if($button_extension != '' && $button_extension != 'birthday')
                            {
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['webview_height_ratio'] = $button_extension;
                                // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                            }

                            if(!in_array($button_web_url, $white_listed_domain_array))
                            {
                                $need_to_whitelist_array[] = $button_web_url;
                            }
                        }
                    }
                    if($button_type == 'phone_number')
                    {
                        if($button_text != '' && $button_type != '' && $button_call_us != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['type'] = 'phone_number';
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['payload'] = $button_call_us;
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['title'] = $button_text;
                        }
                    }
                }
            }

            if($template_type == 'quick_reply')
            {
                $quick_reply_text = 'quick_reply_text_'.$k;
                $quick_reply_text = isset($$quick_reply_text) ? $$quick_reply_text : '';
                if($quick_reply_text != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['text'] = $quick_reply_text;                    
                }

                for ($i=1; $i <= 11 ; $i++) 
                { 
                    $button_text = 'quick_reply_button_text_'.$i.'_'.$k;
                    $button_text = isset($$button_text) ? $$button_text : '';
                    $button_postback_id = 'quick_reply_post_id_'.$i.'_'.$k;
                    $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                    $button_type = 'quick_reply_button_type_'.$i.'_'.$k;
                    $button_type = isset($$button_type) ? $$button_type : '';
                    if($button_type=='post_back')
                    {
                        if($button_text != '' && $button_postback_id != '')
                        {
                            $reply_bot[$k]['quick_replies'][$i-1]['content_type'] = 'text';
                            $reply_bot[$k]['quick_replies'][$i-1]['payload'] = $button_postback_id;
                            $reply_bot[$k]['quick_replies'][$i-1]['title'] = $button_text;
                            $single_postback_insert_data = array();
                            $single_postback_insert_data['user_id'] = $this->user_id;
                            $single_postback_insert_data['postback_id'] = $button_postback_id;
                            $single_postback_insert_data['page_id'] = $page_table_id;
                            $single_postback_insert_data['bot_name'] = $bot_name;
                            $postback_insert_data[] = $single_postback_insert_data; 
                        }                    
                    }
                    if($button_type=='phone_number')
                    {
                        $reply_bot[$k]['quick_replies'][$i-1]['content_type'] = 'user_phone_number';
                    }
                    if($button_type=='user_email')
                    {
                        $reply_bot[$k]['quick_replies'][$i-1]['content_type'] = 'user_email';
                    }
                    if($button_type=='location')
                    {
                        $reply_bot[$k]['quick_replies'][$i-1]['content_type'] = 'location';
                    }

                }
            }

            if($template_type == 'generic_template')
            {
                $generic_template_title = 'generic_template_title_'.$k;
                $generic_template_title = isset($$generic_template_title) ? $$generic_template_title : '';
                $generic_template_image = 'generic_template_image_'.$k;
                $generic_template_image = isset($$generic_template_image) ? $$generic_template_image : '';
                $generic_template_subtitle = 'generic_template_subtitle_'.$k;
                $generic_template_subtitle = isset($$generic_template_subtitle) ? $$generic_template_subtitle : '';
                $generic_template_image_destination_link = 'generic_template_image_destination_link_'.$k;
                $generic_template_image_destination_link = isset($$generic_template_image_destination_link) ? $$generic_template_image_destination_link : '';

                if($generic_template_title != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'template';
                    $reply_bot[$k]['attachment']['payload']['template_type'] = 'generic';
                    $reply_bot[$k]['attachment']['payload']['elements'][0]['title'] = $generic_template_title;                   
                }

                if($generic_template_subtitle != '')
                $reply_bot[$k]['attachment']['payload']['elements'][0]['subtitle'] = $generic_template_subtitle;

                if($generic_template_image!="")
                {
                    $reply_bot[$k]['attachment']['payload']['elements'][0]['image_url'] = $generic_template_image;
                    if($generic_template_image_destination_link!="")
                    {
                        $reply_bot[$k]['attachment']['payload']['elements'][0]['default_action']['type'] = 'web_url';
                        $reply_bot[$k]['attachment']['payload']['elements'][0]['default_action']['url'] = $generic_template_image_destination_link;
                    }

                    if(function_exists('getimagesize') && $generic_template_image!='') 
                    {
                        list($width, $height, $type, $attr) = getimagesize($generic_template_image);
                        if($width==$height)
                            $reply_bot[$k]['attachment']['payload']['image_aspect_ratio'] = 'square';
                    }

                }
                
                // $reply_bot['attachment']['payload']['elements'][0]['default_action']['messenger_extensions'] = true;
                // $reply_bot['attachment']['payload']['elements'][0]['default_action']['webview_height_ratio'] = 'tall';
                // $reply_bot['attachment']['payload']['elements'][0]['default_action']['fallback_url'] = $generic_template_image_destination_link;

                for ($i=1; $i <= 3 ; $i++) 
                { 
                    $button_text = 'generic_template_button_text_'.$i.'_'.$k;
                    $button_text = isset($$button_text) ? $$button_text : '';
                    $button_type = 'generic_template_button_type_'.$i.'_'.$k;
                    $button_type = isset($$button_type) ? $$button_type : '';
                    $button_postback_id = 'generic_template_button_post_id_'.$i.'_'.$k;
                    $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                    $button_web_url = 'generic_template_button_web_url_'.$i.'_'.$k;
                    $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                    //add an extra query parameter for tracking the subscriber to whom send 
                    if($button_web_url!='')
                        $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");

                    $button_call_us = 'generic_template_button_call_us_'.$i.'_'.$k;
                    $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                    if($button_type == 'post_back')
                    {
                        if($button_text != '' && $button_type != '' && $button_postback_id != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'postback';
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['payload'] = $button_postback_id;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;
                            $single_postback_insert_data = array();
                            $single_postback_insert_data['user_id'] = $this->user_id;
                            $single_postback_insert_data['postback_id'] = $button_postback_id;
                            $single_postback_insert_data['page_id'] = $page_table_id;
                            $single_postback_insert_data['bot_name'] = $bot_name;
                            $postback_insert_data[] = $single_postback_insert_data; 
                        }
                    }
                    if(strpos($button_type,'web_url') !== FALSE)
                    {
                        $button_type_array = explode('_', $button_type);
                        if(isset($button_type_array[2]))
                        {
                            $button_extension = trim($button_type_array[2],'_'); 
                            array_pop($button_type_array);
                        }            
                        else $button_extension = '';
                        $button_type = implode('_', $button_type_array);

                        if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'web_url';
                            if($button_extension != '' && $button_extension == 'birthday'){                                
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['webview_height_ratio'] = 'compact';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                                $button_web_url = base_url('webview_builder/get_birthdate');
                            }
                            else
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['url'] = $button_web_url;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;

                            if($button_extension != '' && $button_extension != 'birthday')
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['webview_height_ratio'] = $button_extension;
                                // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                            }

                            if(!in_array($button_web_url, $white_listed_domain_array))
                            {
                                $need_to_whitelist_array[] = $button_web_url;
                            }
                        }
                    }
                    if($button_type == 'phone_number')
                    {
                        if($button_text != '' && $button_type != '' && $button_call_us != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'phone_number';
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['payload'] = $button_call_us;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;
                        }
                    }
                }
            }

            if($template_type == 'carousel')
            {
                $reply_bot[$k]['template_type'] = $template_type;
                $reply_bot[$k]['attachment']['type'] = 'template';
                $reply_bot[$k]['attachment']['payload']['template_type'] = 'generic';
                for ($j=1; $j <=10 ; $j++) 
                {                                 
                    $carousel_image = 'carousel_image_'.$j.'_'.$k;
                    $carousel_title = 'carousel_title_'.$j.'_'.$k;

                    if(!isset($$carousel_title) || $$carousel_title == '') continue;

                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['title'] = $$carousel_title;
                    $carousel_subtitle = 'carousel_subtitle_'.$j.'_'.$k;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['subtitle'] = $$carousel_subtitle;

                    if(isset($$carousel_image) && $$carousel_image!="")
                    {
                        $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['image_url'] = $$carousel_image;                    
                        $carousel_image_destination_link = 'carousel_image_destination_link_'.$j.'_'.$k;
                        if($$carousel_image_destination_link!="") 
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['default_action']['type'] = 'web_url';
                            $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['default_action']['url'] = $$carousel_image_destination_link;
                        }

                        if(function_exists('getimagesize') && $$carousel_image!='') 
                        {
                            list($width, $height, $type, $attr) = getimagesize($$carousel_image);
                            if($width==$height)
                                $reply_bot[$k]['attachment']['payload']['image_aspect_ratio'] = 'square';
                        }

                    }
                    // $reply_bot['attachment']['payload']['elements'][$j-1]['default_action']['messenger_extensions'] = true;
                    // $reply_bot['attachment']['payload']['elements'][$j-1]['default_action']['webview_height_ratio'] = 'tall';
                    // $reply_bot['attachment']['payload']['elements'][$j-1]['default_action']['fallback_url'] = $$carousel_image_destination_link;

                    for ($i=1; $i <= 3 ; $i++) 
                    { 
                        $button_text = 'carousel_button_text_'.$j."_".$i.'_'.$k;
                        $button_text = isset($$button_text) ? $$button_text : '';
                        $button_type = 'carousel_button_type_'.$j."_".$i.'_'.$k;
                        $button_type = isset($$button_type) ? $$button_type : '';
                        $button_postback_id = 'carousel_button_post_id_'.$j."_".$i.'_'.$k;
                        $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                        $button_web_url = 'carousel_button_web_url_'.$j."_".$i.'_'.$k;
                        $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                        //add an extra query parameter for tracking the subscriber to whom send 
                        if($button_web_url!='')
                            $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");

                        $button_call_us = 'carousel_button_call_us_'.$j."_".$i.'_'.$k;
                        $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                        if($button_type == 'post_back')
                        {
                            if($button_text != '' && $button_type != '' && $button_postback_id != '')
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['type'] = 'postback';
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['payload'] = $button_postback_id;
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['title'] = $button_text;
                                $single_postback_insert_data = array();
                                $single_postback_insert_data['user_id'] = $this->user_id;
                                $single_postback_insert_data['postback_id'] = $button_postback_id;
                                $single_postback_insert_data['page_id'] = $page_table_id;
                                $single_postback_insert_data['bot_name'] = $bot_name;
                                $postback_insert_data[] = $single_postback_insert_data; 
                            }
                        }
                        if(strpos($button_type,'web_url') !== FALSE)
                        {
                            $button_type_array = explode('_', $button_type);
                            if(isset($button_type_array[2]))
                            {
                                $button_extension = trim($button_type_array[2],'_'); 
                                array_pop($button_type_array);
                            }            
                            else $button_extension = '';
                            $button_type = implode('_', $button_type_array);

                            if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['type'] = 'web_url';
                                if($button_extension != '' && $button_extension == 'birthday'){
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['webview_height_ratio'] = 'compact';
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                                    $button_web_url = base_url('webview_builder/get_birthdate');
                                }
                                else
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['url'] = $button_web_url;
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['title'] = $button_text;

                                if($button_extension != '' && $button_extension != 'birthday')
                                {
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['webview_height_ratio'] = $button_extension;
                                    // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                                }

                                if(!in_array($button_web_url, $white_listed_domain_array))
                                {
                                    $need_to_whitelist_array[] = $button_web_url;
                                }
                            }
                        }
                        if($button_type == 'phone_number')
                        {
                            if($button_text != '' && $button_type != '' && $button_call_us != '')
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['type'] = 'phone_number';
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['payload'] = $button_call_us;
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['title'] = $button_text;
                            }
                        }
                    }
                }
            }

            if($template_type == 'list')
            {
                $reply_bot[$k]['template_type'] = $template_type;
                $reply_bot[$k]['attachment']['type'] = 'template';
                $reply_bot[$k]['attachment']['payload']['template_type'] = 'list';

                for ($j=1; $j <=4 ; $j++) 
                {                                 
                    $list_image = 'list_image_'.$j.'_'.$k;
                    if(!isset($$list_image) || $$list_image == '') continue;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['image_url'] = $$list_image;
                    $list_title = 'list_title_'.$j.'_'.$k;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['title'] = $$list_title;
                    $list_subtitle = 'list_subtitle_'.$j.'_'.$k;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['subtitle'] = $$list_subtitle;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['default_action']['type'] = 'web_url';
                    $list_image_destination_link = 'list_image_destination_link_'.$j.'_'.$k;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['default_action']['url'] = $$list_image_destination_link;
                    
                }

                $button_text = 'list_with_buttons_text_'.$k;
                $button_text = isset($$button_text) ? $$button_text : '';
                $button_type = 'list_with_button_type_'.$k;
                $button_type = isset($$button_type) ? $$button_type : '';
                $button_postback_id = 'list_with_button_post_id_'.$k;
                $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                $button_web_url = 'list_with_button_web_url_'.$k;
                $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                 //add an extra query parameter for tracking the subscriber to whom send 
                if($button_web_url!='')
                    $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");

                $button_call_us = 'list_with_button_call_us_'.$k;
                $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                if($button_type == 'post_back')
                {
                    if($button_text != '' && $button_type != '' && $button_postback_id != '')
                    {
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['type'] = 'postback';
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['payload'] = $button_postback_id;
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['title'] = $button_text;
                        $single_postback_insert_data = array();
                        $single_postback_insert_data['user_id'] = $this->user_id;
                        $single_postback_insert_data['postback_id'] = $button_postback_id;
                        $single_postback_insert_data['page_id'] = $page_table_id;
                        $single_postback_insert_data['bot_name'] = $bot_name;
                        $postback_insert_data[] = $single_postback_insert_data; 
                    }
                }
                if(strpos($button_type,'web_url') !== FALSE)
                {
                    $button_type_array = explode('_', $button_type);
                    if(isset($button_type_array[2]))
                    {
                        $button_extension = trim($button_type_array[2],'_'); 
                        array_pop($button_type_array);
                    }            
                    else $button_extension = '';
                    $button_type = implode('_', $button_type_array);

                    if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                    {
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['type'] = 'web_url';
                        if($button_extension != '' && $button_extension == 'birthday'){
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['messenger_extensions'] = 'true';
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['webview_height_ratio'] = 'compact';
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                            $button_web_url = base_url('webview_builder/get_birthdate');
                        }
                        else
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['url'] = $button_web_url;
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['title'] = $button_text;

                        if($button_extension != '' && $button_extension != 'birthday')
                        {
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['messenger_extensions'] = 'true';
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['webview_height_ratio'] = $button_extension;
                            // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                        }

                        if(!in_array($button_web_url, $white_listed_domain_array))
                        {
                            $need_to_whitelist_array[] = $button_web_url;
                        }
                    }
                }
                if($button_type == 'phone_number')
                {
                    if($button_text != '' && $button_type != '' && $button_call_us != '')
                    {
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['type'] = 'phone_number';
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['payload'] = $button_call_us;
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['title'] = $button_text;
                    }
                }


            }

            if(isset($reply_bot[$k]))
            {     
                $typing_on_settings = 'typing_on_enable_'.$k;
                if(!isset($$typing_on_settings)) $typing_on_settings = 'off';
                else $typing_on_settings = $$typing_on_settings;

                $delay_in_reply = 'delay_in_reply_'.$k;
                if(!isset($$delay_in_reply)) $delay_in_reply = 0;
                else $delay_in_reply = $$delay_in_reply;

                $reply_bot[$k]['typing_on_settings'] = $typing_on_settings;
                $reply_bot[$k]['delay_in_reply'] = $delay_in_reply;

                $bot_message[$k]['recipient'] = array('id'=>'replace_id');
                $bot_message[$k]['message'] = $reply_bot[$k];
            }

        }
        
        $reply_bot_filtered = array();
        $m=0;
        foreach ($bot_message as $value) {
            $m++;
            $reply_bot_filtered[$m] = $value;
        }

        // domain white list section start
        $this->load->library("fb_rx_login"); 
        $domain_whitelist_insert_data = array();
        foreach($need_to_whitelist_array as $value)
        {
            
            $domain_only_whitelist= get_domain_only_with_http($value);
            if(in_array($domain_only_whitelist, $white_listed_domain_array)) continue; 

            $response=$this->fb_rx_login->domain_whitelist($page_access_token,$domain_only_whitelist);
            if($response['status'] != '0')
            {
                $temp_data = array();
                $temp_data['user_id'] = $this->user_id;
                $temp_data['messenger_bot_user_info_id'] = $facebook_rx_fb_user_info_id; 
                $temp_data['page_id'] = $page_table_id;
                $temp_data['domain'] = $domain_only_whitelist;
                $temp_data['created_at'] = date("Y-m-d H:i:s");
                $domain_whitelist_insert_data[] = $temp_data;
            }
        }
        if(!empty($domain_whitelist_insert_data))
            $this->db->insert_batch('messenger_bot_domain_whitelist',$domain_whitelist_insert_data);
        // domain white list section end

        $insert_data['message'] = json_encode($reply_bot_filtered,true);
        $insert_data['user_id'] = $this->user_id;
        $this->basic->update_data('messenger_bot',array("id" => $id),$insert_data);
        // $this->basic->delete_data('messenger_bot_postback',array('messenger_bot_table_id'=> $id));
        $messenger_bot_table_id = $id;
        
        $existing_postback_ids_array = array();
        $existing_postback_ids = $this->basic->get_data('messenger_bot_postback',array('where'=>array('messenger_bot_table_id'=>$messenger_bot_table_id)),array('postback_id'));
        if(!empty($existing_postback_ids))
        {
            foreach($existing_postback_ids as $value)
            {
                array_push($existing_postback_ids_array, strtoupper($value['postback_id']));
            }
        }

        $postback_insert_data_modified = array();
        $m=0;
        foreach($postback_insert_data as $value)
        {
            if(in_array(strtoupper($value['postback_id']), $existing_postback_ids_array)) continue;
            $postback_insert_data_modified[$m]['user_id'] = $value['user_id'];
            $postback_insert_data_modified[$m]['postback_id'] = $value['postback_id'];
            $postback_insert_data_modified[$m]['page_id'] = $value['page_id'];
            $postback_insert_data_modified[$m]['bot_name'] = $value['bot_name'];
            $postback_insert_data_modified[$m]['messenger_bot_table_id'] = $messenger_bot_table_id;
            $m++;
        }

        if($keyword_type == 'post-back' && !empty($keywordtype_postback_id))
        {   
            $this->db->where("page_id",$page_table_id);         
            $this->db->where_in("postback_id", $keywordtype_postback_id);
            $this->db->update('messenger_bot_postback', array('use_status' => '1'));
        }
        
        // if(!empty($postback_insert_data_modified))
        // $this->db->insert_batch('messenger_bot_postback',$postback_insert_data_modified);

        // $this->session->set_flashdata('bot_update_success',1);
        echo json_encode(array("status" => "1", "message" =>$this->lang->line("Bot settings has been updated successfully.")));        

    }


    public function ajax_generate_messenger_bot()
    {
        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                echo "<div class='alert alert-danger text-center'><i class='fa fa-ban'></i> This function is disabled from admin account in this demo!!</div>";
                exit();
            }
        }


        $post=$_POST;
        foreach ($post as $key => $value) 
        {
            $$key=$value;
        }
        // $template_type = trim($template_type);
        $insert_data = array();
        $insert_data['bot_name'] = $bot_name;
        $insert_data['fb_page_id'] = $page_id;
        $insert_data['keywords'] = trim($keywords_list);
        $insert_data['page_id'] = $page_table_id;
        // $insert_data['template_type'] = $template_type;
        $insert_data['keyword_type'] = $keyword_type;
        if($keyword_type == 'post-back')
            $insert_data['postback_id'] = implode(',', $keywordtype_postback_id);

        // $template_type = str_replace(' ', '_', $template_type);
        // domain white list section
        $facebook_rx_fb_user_info_id = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_table_id)),array("facebook_rx_fb_user_info_id","page_access_token"));
        $page_access_token = $facebook_rx_fb_user_info_id[0]['page_access_token'];
        $facebook_rx_fb_user_info_id = $facebook_rx_fb_user_info_id[0]["facebook_rx_fb_user_info_id"];
        $white_listed_domain = $this->basic->get_data("messenger_bot_domain_whitelist",array("where"=>array("user_id"=>$this->user_id,"messenger_bot_user_info_id"=>$facebook_rx_fb_user_info_id,"page_id"=>$page_table_id)),"domain");
        $white_listed_domain_array = array();
        foreach ($white_listed_domain as $value) {
            $white_listed_domain_array[] = $value['domain'];
        }
        $need_to_whitelist_array = array();
        // domain white list section

        $postback_insert_data = array();
        $reply_bot = array();
        $bot_message = array();

        for ($k=1; $k <=3 ; $k++) 
        {    
            $template_type = 'template_type_'.$k;
            if(!isset($$template_type)) continue;
            $template_type = $$template_type;
            // $insert_data['template_type'] = $template_type;
            $template_type = str_replace(' ', '_', $template_type);

            if($template_type == 'text')
            {
                $text_reply = 'text_reply_'.$k;
                $text_reply = isset($$text_reply) ? $$text_reply : '';
                if($text_reply != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['text'] = $text_reply;
                    
                }
            }
            if($template_type == 'image')
            {
                $image_reply_field = 'image_reply_field_'.$k;
                $image_reply_field = isset($$image_reply_field) ? $$image_reply_field : '';
                if($image_reply_field != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'image';
                    $reply_bot[$k]['attachment']['payload']['url'] = $image_reply_field;
                    $reply_bot[$k]['attachment']['payload']['is_reusable'] = true;                    
                }
            }
            if($template_type == 'audio')
            {
                $audio_reply_field = 'audio_reply_field_'.$k;
                $audio_reply_field = isset($$audio_reply_field) ? $$audio_reply_field : '';
                if($audio_reply_field != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'audio';
                    $reply_bot[$k]['attachment']['payload']['url'] = $audio_reply_field;
                    $reply_bot[$k]['attachment']['payload']['is_reusable'] = true;
                }
                
            }
            if($template_type == 'video')
            {
                $video_reply_field = 'video_reply_field_'.$k;
                $video_reply_field = isset($$video_reply_field) ? $$video_reply_field : '';
                if($video_reply_field != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'video';
                    $reply_bot[$k]['attachment']['payload']['url'] = $video_reply_field;
                    $reply_bot[$k]['attachment']['payload']['is_reusable'] = true;                    
                }
            }
            if($template_type == 'file')
            {
                $file_reply_field = 'file_reply_field_'.$k;
                $file_reply_field = isset($$file_reply_field) ? $$file_reply_field : '';
                if($file_reply_field != '')
                {       
                    $reply_bot[$k]['template_type'] = $template_type;             
                    $reply_bot[$k]['attachment']['type'] = 'file';
                    $reply_bot[$k]['attachment']['payload']['url'] = $file_reply_field;
                    $reply_bot[$k]['attachment']['payload']['is_reusable'] = true;
                }
            }



 
            if($template_type == 'media')
            {
                $media_input = 'media_input_'.$k;
                $media_input = isset($$media_input) ? $$media_input : '';
                if($media_input != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'template';
                    $reply_bot[$k]['attachment']['payload']['template_type'] = 'media';
                    $template_media_type = '';
                    if (strpos($media_input, '/videos/') !== false) {
                        $template_media_type = 'video';
                    }
                    else
                        $template_media_type = 'image';
                    $reply_bot[$k]['attachment']['payload']['elements'][0]['media_type'] = $template_media_type;
                    $reply_bot[$k]['attachment']['payload']['elements'][0]['url'] = $media_input;                    
                }

                for ($i=1; $i <= 3 ; $i++) 
                { 
                    $button_text = 'media_text_'.$i.'_'.$k;
                    $button_text = isset($$button_text) ? $$button_text : '';
                    $button_type = 'media_type_'.$i.'_'.$k;
                    $button_type = isset($$button_type) ? $$button_type : '';
                    $button_postback_id = 'media_post_id_'.$i.'_'.$k;
                    $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                    $button_web_url = 'media_web_url_'.$i.'_'.$k;
                    $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                     //add an extra query parameter for tracking the subscriber to whom send 
                    if($button_web_url!='')
                        $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");

                    $button_call_us = 'media_call_us_'.$i.'_'.$k;
                    $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                    if($button_type == 'post_back')
                    {
                        if($button_text != '' && $button_type != '' && $button_postback_id != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'postback';
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['payload'] = $button_postback_id;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;
                            $single_postback_insert_data = array();
                            $single_postback_insert_data['user_id'] = $this->user_id;
                            $single_postback_insert_data['postback_id'] = $button_postback_id;
                            $single_postback_insert_data['page_id'] = $page_table_id;
                            $single_postback_insert_data['bot_name'] = $bot_name;
                            $postback_insert_data[] = $single_postback_insert_data; 
                        }
                    }
                    if(strpos($button_type,'web_url') !== FALSE)
                    {
                        $button_type_array = explode('_', $button_type);
                        if(isset($button_type_array[2]))
                        {
                            $button_extension = trim($button_type_array[2],'_'); 
                            array_pop($button_type_array);
                        }            
                        else $button_extension = '';
                        $button_type = implode('_', $button_type_array);

                        if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'web_url';
                            if($button_extension != '' && $button_extension == 'birthday'){
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['webview_height_ratio'] = 'compact';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                                $button_web_url = base_url('webview_builder/get_birthdate');
                            }
                            else
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['url'] = $button_web_url;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;

                            if($button_extension != '' && $button_extension != 'birthday')
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['webview_height_ratio'] = $button_extension;
                                // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                            }

                            if(!in_array($button_web_url, $white_listed_domain_array))
                            {
                                $need_to_whitelist_array[] = $button_web_url;
                            }
                        }
                    }
                    if($button_type == 'phone_number')
                    {
                        if($button_text != '' && $button_type != '' && $button_call_us != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'phone_number';
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['payload'] = $button_call_us;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;
                        }
                    }
                }
            }


            if($template_type == 'text_with_buttons')
            {
                $text_with_buttons_input = 'text_with_buttons_input_'.$k;
                $text_with_buttons_input = isset($$text_with_buttons_input) ? $$text_with_buttons_input : '';
                if($text_with_buttons_input != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'template';
                    $reply_bot[$k]['attachment']['payload']['template_type'] = 'button';
                    $reply_bot[$k]['attachment']['payload']['text'] = $text_with_buttons_input;                    
                }

                for ($i=1; $i <= 3 ; $i++) 
                { 
                    $button_text = 'text_with_buttons_text_'.$i.'_'.$k;
                    $button_text = isset($$button_text) ? $$button_text : '';
                    $button_type = 'text_with_button_type_'.$i.'_'.$k;
                    $button_type = isset($$button_type) ? $$button_type : '';
                    $button_postback_id = 'text_with_button_post_id_'.$i.'_'.$k;
                    $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                    $button_web_url = 'text_with_button_web_url_'.$i.'_'.$k;
                    $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                    //add an extra query parameter for tracking the subscriber to whom send 
                    if($button_web_url!='')
                        $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");

                    $button_call_us = 'text_with_button_call_us_'.$i.'_'.$k;
                    $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                    if($button_type == 'post_back')
                    {
                        if($button_text != '' && $button_type != '' && $button_postback_id != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['type'] = 'postback';
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['payload'] = $button_postback_id;
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['title'] = $button_text;
                            $single_postback_insert_data = array();
                            $single_postback_insert_data['user_id'] = $this->user_id;
                            $single_postback_insert_data['postback_id'] = $button_postback_id;
                            $single_postback_insert_data['page_id'] = $page_table_id;
                            $single_postback_insert_data['bot_name'] = $bot_name;
                            $postback_insert_data[] = $single_postback_insert_data; 
                        }
                    }

                    if(strpos($button_type,'web_url') !== FALSE)
                    {
                        $button_type_array = explode('_', $button_type);
                        if(isset($button_type_array[2]))
                        {
                            $button_extension = trim($button_type_array[2],'_'); 
                            array_pop($button_type_array);
                        }            
                        else $button_extension = '';
                        $button_type = implode('_', $button_type_array);

                        if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                        {
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['type'] = 'web_url';

                            if($button_extension != '' && $button_extension == 'birthday'){
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['webview_height_ratio'] = 'compact';
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                                $button_web_url = base_url('webview_builder/get_birthdate');
                            }
                            else
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['url'] = $button_web_url;
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['title'] = $button_text;

                            if($button_extension != '' && $button_extension != 'birthday')
                            {
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['webview_height_ratio'] = $button_extension;
                                // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                            }

                            if(!in_array($button_web_url, $white_listed_domain_array))
                            {
                                $need_to_whitelist_array[] = $button_web_url;
                            }
                        }
                    }
                    if($button_type == 'phone_number')
                    {
                        if($button_text != '' && $button_type != '' && $button_call_us != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['type'] = 'phone_number';
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['payload'] = $button_call_us;
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['title'] = $button_text;
                        }
                    }
                }
            }

            if($template_type == 'quick_reply')
            {
                $quick_reply_text = 'quick_reply_text_'.$k;
                $quick_reply_text = isset($$quick_reply_text) ? $$quick_reply_text : '';
                if($quick_reply_text != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['text'] = $quick_reply_text;                    
                }

                for ($i=1; $i <= 11 ; $i++) 
                { 
                    $button_text = 'quick_reply_button_text_'.$i.'_'.$k;
                    $button_text = isset($$button_text) ? $$button_text : '';
                    $button_postback_id = 'quick_reply_post_id_'.$i.'_'.$k;
                    $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                    $button_type = 'quick_reply_button_type_'.$i.'_'.$k;
                    $button_type = isset($$button_type) ? $$button_type : '';
                    if($button_type=='post_back')
                    {
                        if($button_text != '' && $button_postback_id != '')
                        {
                            $reply_bot[$k]['quick_replies'][$i-1]['content_type'] = 'text';
                            $reply_bot[$k]['quick_replies'][$i-1]['payload'] = $button_postback_id;
                            $reply_bot[$k]['quick_replies'][$i-1]['title'] = $button_text;
                            $single_postback_insert_data = array();
                            $single_postback_insert_data['user_id'] = $this->user_id;
                            $single_postback_insert_data['postback_id'] = $button_postback_id;
                            $single_postback_insert_data['page_id'] = $page_table_id;
                            $single_postback_insert_data['bot_name'] = $bot_name;
                            $postback_insert_data[] = $single_postback_insert_data; 
                        }                    
                    }
                    if($button_type=='phone_number')
                    {
                        $reply_bot[$k]['quick_replies'][$i-1]['content_type'] = 'user_phone_number';
                    }
                    if($button_type=='user_email')
                    {
                        $reply_bot[$k]['quick_replies'][$i-1]['content_type'] = 'user_email';
                    }
                    if($button_type=='location')
                    {
                        $reply_bot[$k]['quick_replies'][$i-1]['content_type'] = 'location';
                    }

                }
            }

            if($template_type == 'generic_template')
            {
                $generic_template_title = 'generic_template_title_'.$k;
                $generic_template_title = isset($$generic_template_title) ? $$generic_template_title : '';
                $generic_template_image = 'generic_template_image_'.$k;
                $generic_template_image = isset($$generic_template_image) ? $$generic_template_image : '';
                $generic_template_subtitle = 'generic_template_subtitle_'.$k;
                $generic_template_subtitle = isset($$generic_template_subtitle) ? $$generic_template_subtitle : '';
                $generic_template_image_destination_link = 'generic_template_image_destination_link_'.$k;
                $generic_template_image_destination_link = isset($$generic_template_image_destination_link) ? $$generic_template_image_destination_link : '';

                if($generic_template_title != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'template';
                    $reply_bot[$k]['attachment']['payload']['template_type'] = 'generic';
                    $reply_bot[$k]['attachment']['payload']['elements'][0]['title'] = $generic_template_title;                   
                }

                if($generic_template_subtitle != '')
                $reply_bot[$k]['attachment']['payload']['elements'][0]['subtitle'] = $generic_template_subtitle;

                if($generic_template_image!="")
                {
                    $reply_bot[$k]['attachment']['payload']['elements'][0]['image_url'] = $generic_template_image;
                    if($generic_template_image_destination_link!="")
                    {
                        $reply_bot[$k]['attachment']['payload']['elements'][0]['default_action']['type'] = 'web_url';
                        $reply_bot[$k]['attachment']['payload']['elements'][0]['default_action']['url'] = $generic_template_image_destination_link;
                    }

                    if(function_exists('getimagesize') && $generic_template_image!='') 
                    {
                        list($width, $height, $type, $attr) = getimagesize($generic_template_image);
                        if($width==$height)
                            $reply_bot[$k]['attachment']['payload']['image_aspect_ratio'] = 'square';
                    }

                }
                

                for ($i=1; $i <= 3 ; $i++) 
                { 
                    $button_text = 'generic_template_button_text_'.$i.'_'.$k;
                    $button_text = isset($$button_text) ? $$button_text : '';
                    $button_type = 'generic_template_button_type_'.$i.'_'.$k;
                    $button_type = isset($$button_type) ? $$button_type : '';
                    $button_postback_id = 'generic_template_button_post_id_'.$i.'_'.$k;
                    $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                    $button_web_url = 'generic_template_button_web_url_'.$i.'_'.$k;
                    $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                    //add an extra query parameter for tracking the subscriber to whom send 
                    if($button_web_url!='')
                        $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");

                    $button_call_us = 'generic_template_button_call_us_'.$i.'_'.$k;
                    $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                    if($button_type == 'post_back')
                    {
                        if($button_text != '' && $button_type != '' && $button_postback_id != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'postback';
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['payload'] = $button_postback_id;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;
                            $single_postback_insert_data = array();
                            $single_postback_insert_data['user_id'] = $this->user_id;
                            $single_postback_insert_data['postback_id'] = $button_postback_id;
                            $single_postback_insert_data['page_id'] = $page_table_id;
                            $single_postback_insert_data['bot_name'] = $bot_name;
                            $postback_insert_data[] = $single_postback_insert_data; 
                        }
                    }
                    if(strpos($button_type,'web_url') !== FALSE)
                    {
                        $button_type_array = explode('_', $button_type);
                        if(isset($button_type_array[2]))
                        {
                            $button_extension = trim($button_type_array[2],'_'); 
                            array_pop($button_type_array);
                        }            
                        else $button_extension = '';
                        $button_type = implode('_', $button_type_array);

                        if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'web_url';
                            if($button_extension != '' && $button_extension == 'birthday'){                                
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['webview_height_ratio'] = 'compact';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                                $button_web_url = base_url('webview_builder/get_birthdate');
                            }
                            else
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['url'] = $button_web_url;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;

                            if($button_extension != '' && $button_extension != 'birthday')
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['webview_height_ratio'] = $button_extension;
                                // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                            }

                            if(!in_array($button_web_url, $white_listed_domain_array))
                            {
                                $need_to_whitelist_array[] = $button_web_url;
                            }
                        }
                    }
                    if($button_type == 'phone_number')
                    {
                        if($button_text != '' && $button_type != '' && $button_call_us != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'phone_number';
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['payload'] = $button_call_us;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;
                        }
                    }
                }
            }

            if($template_type == 'carousel')
            {
                $reply_bot[$k]['template_type'] = $template_type;
                $reply_bot[$k]['attachment']['type'] = 'template';
                $reply_bot[$k]['attachment']['payload']['template_type'] = 'generic';
                for ($j=1; $j <=10 ; $j++) 
                {                                 
                    $carousel_image = 'carousel_image_'.$j.'_'.$k;
                    $carousel_title = 'carousel_title_'.$j.'_'.$k;

                    if(!isset($$carousel_title) || $$carousel_title == '') continue;

                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['title'] = $$carousel_title;
                    $carousel_subtitle = 'carousel_subtitle_'.$j.'_'.$k;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['subtitle'] = $$carousel_subtitle;

                    if(isset($$carousel_image) && $$carousel_image!="")
                    {
                        $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['image_url'] = $$carousel_image;                    
                        $carousel_image_destination_link = 'carousel_image_destination_link_'.$j.'_'.$k;
                        if($$carousel_image_destination_link!="") 
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['default_action']['type'] = 'web_url';
                            $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['default_action']['url'] = $$carousel_image_destination_link;
                        }

                        if(function_exists('getimagesize') && $$carousel_image!='') 
                        {
                            list($width, $height, $type, $attr) = getimagesize($$carousel_image);
                            if($width==$height)
                                $reply_bot[$k]['attachment']['payload']['image_aspect_ratio'] = 'square';
                        }

                    }
                    
                    for ($i=1; $i <= 3 ; $i++) 
                    { 
                        $button_text = 'carousel_button_text_'.$j."_".$i.'_'.$k;
                        $button_text = isset($$button_text) ? $$button_text : '';
                        $button_type = 'carousel_button_type_'.$j."_".$i.'_'.$k;
                        $button_type = isset($$button_type) ? $$button_type : '';
                        $button_postback_id = 'carousel_button_post_id_'.$j."_".$i.'_'.$k;
                        $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                        $button_web_url = 'carousel_button_web_url_'.$j."_".$i.'_'.$k;
                        $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                        //add an extra query parameter for tracking the subscriber to whom send 
                        if($button_web_url!='')
                          $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");

                        $button_call_us = 'carousel_button_call_us_'.$j."_".$i.'_'.$k;
                        $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                        if($button_type == 'post_back')
                        {
                            if($button_text != '' && $button_type != '' && $button_postback_id != '')
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['type'] = 'postback';
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['payload'] = $button_postback_id;
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['title'] = $button_text;
                                $single_postback_insert_data = array();
                                $single_postback_insert_data['user_id'] = $this->user_id;
                                $single_postback_insert_data['postback_id'] = $button_postback_id;
                                $single_postback_insert_data['page_id'] = $page_table_id;
                                $single_postback_insert_data['bot_name'] = $bot_name;
                                $postback_insert_data[] = $single_postback_insert_data; 
                            }
                        }
                        if(strpos($button_type,'web_url') !== FALSE)
                        {
                            $button_type_array = explode('_', $button_type);
                            if(isset($button_type_array[2]))
                            {
                                $button_extension = trim($button_type_array[2],'_'); 
                                array_pop($button_type_array);
                            }            
                            else $button_extension = '';
                            $button_type = implode('_', $button_type_array);

                            if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['type'] = 'web_url';
                                if($button_extension != '' && $button_extension == 'birthday'){
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['webview_height_ratio'] = 'compact';
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                                    $button_web_url = base_url('webview_builder/get_birthdate');
                                }
                                else
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['url'] = $button_web_url;
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['title'] = $button_text;

                                if($button_extension != '' && $button_extension != 'birthday')
                                {
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['webview_height_ratio'] = $button_extension;
                                    // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                                }

                                if(!in_array($button_web_url, $white_listed_domain_array))
                                {
                                    $need_to_whitelist_array[] = $button_web_url;
                                }
                            }
                        }
                        if($button_type == 'phone_number')
                        {
                            if($button_text != '' && $button_type != '' && $button_call_us != '')
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['type'] = 'phone_number';
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['payload'] = $button_call_us;
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['title'] = $button_text;
                            }
                        }
                    }
                }
            }

            if($template_type == 'list')
            {
                $reply_bot[$k]['template_type'] = $template_type;
                $reply_bot[$k]['attachment']['type'] = 'template';
                $reply_bot[$k]['attachment']['payload']['template_type'] = 'list';

                for ($j=1; $j <=4 ; $j++) 
                {                                 
                    $list_image = 'list_image_'.$j.'_'.$k;
                    if(!isset($$list_image) || $$list_image == '') continue;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['image_url'] = $$list_image;
                    $list_title = 'list_title_'.$j.'_'.$k;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['title'] = $$list_title;
                    $list_subtitle = 'list_subtitle_'.$j.'_'.$k;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['subtitle'] = $$list_subtitle;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['default_action']['type'] = 'web_url';
                    $list_image_destination_link = 'list_image_destination_link_'.$j.'_'.$k;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['default_action']['url'] = $$list_image_destination_link;
                    
                }

                $button_text = 'list_with_buttons_text_'.$k;
                $button_text = isset($$button_text) ? $$button_text : '';
                $button_type = 'list_with_button_type_'.$k;
                $button_type = isset($$button_type) ? $$button_type : '';
                $button_postback_id = 'list_with_button_post_id_'.$k;
                $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                $button_web_url = 'list_with_button_web_url_'.$k;
                $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                //add an extra query parameter for tracking the subscriber to whom send 
                if($button_web_url!='')
                  $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");

                $button_call_us = 'list_with_button_call_us_'.$k;
                $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                if($button_type == 'post_back')
                {
                    if($button_text != '' && $button_type != '' && $button_postback_id != '')
                    {
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['type'] = 'postback';
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['payload'] = $button_postback_id;
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['title'] = $button_text;
                        $single_postback_insert_data = array();
                        $single_postback_insert_data['user_id'] = $this->user_id;
                        $single_postback_insert_data['postback_id'] = $button_postback_id;
                        $single_postback_insert_data['page_id'] = $page_table_id;
                        $single_postback_insert_data['bot_name'] = $bot_name;
                        $postback_insert_data[] = $single_postback_insert_data; 
                    }
                }
                if(strpos($button_type,'web_url') !== FALSE)
                {
                    $button_type_array = explode('_', $button_type);
                    if(isset($button_type_array[2]))
                    {
                        $button_extension = trim($button_type_array[2],'_'); 
                        array_pop($button_type_array);
                    }            
                    else $button_extension = '';
                    $button_type = implode('_', $button_type_array);

                    if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                    {
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['type'] = 'web_url';
                        if($button_extension != '' && $button_extension == 'birthday'){
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['messenger_extensions'] = 'true';
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['webview_height_ratio'] = 'compact';
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                            $button_web_url = base_url('webview_builder/get_birthdate');
                        }
                        else
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['url'] = $button_web_url;
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['title'] = $button_text;

                        if($button_extension != '' && $button_extension != 'birthday')
                        {
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['messenger_extensions'] = 'true';
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['webview_height_ratio'] = $button_extension;
                            // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                        }

                        if(!in_array($button_web_url, $white_listed_domain_array))
                        {
                            $need_to_whitelist_array[] = $button_web_url;
                        }
                    }
                }
                if($button_type == 'phone_number')
                {
                    if($button_text != '' && $button_type != '' && $button_call_us != '')
                    {
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['type'] = 'phone_number';
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['payload'] = $button_call_us;
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['title'] = $button_text;
                    }
                }


            }


            if(isset($reply_bot[$k]))
            {   
                $typing_on_settings = 'typing_on_enable_'.$k;
                if(!isset($$typing_on_settings)) $typing_on_settings = 'off';
                else $typing_on_settings = $$typing_on_settings;

                $delay_in_reply = 'delay_in_reply_'.$k;
                if(!isset($$delay_in_reply)) $delay_in_reply = 0;
                else $delay_in_reply = $$delay_in_reply;

                $reply_bot[$k]['typing_on_settings'] = $typing_on_settings;
                $reply_bot[$k]['delay_in_reply'] = $delay_in_reply;
                       
                $bot_message[$k]['recipient'] = array('id'=>'replace_id');
                $bot_message[$k]['message'] = $reply_bot[$k];
            }

        }

        $reply_bot_filtered = array();
        $m=0;
        foreach ($bot_message as $value) {
            $m++;
            $reply_bot_filtered[$m] = $value;
        }

        // domain white list section start
        $this->load->library("fb_rx_login"); 
        $domain_whitelist_insert_data = array();
        foreach($need_to_whitelist_array as $value)
        {
             $domain_only_whitelist= get_domain_only_with_http($value);
             if(in_array($domain_only_whitelist, $white_listed_domain_array)) continue; 

            $response=$this->fb_rx_login->domain_whitelist($page_access_token,$domain_only_whitelist);
            if($response['status'] != '0')
            {
                $temp_data = array();
                $temp_data['user_id'] = $this->user_id;
                $temp_data['messenger_bot_user_info_id'] = $facebook_rx_fb_user_info_id;
                $temp_data['page_id'] = $page_table_id;
                $temp_data['domain'] = $domain_only_whitelist;
                $temp_data['created_at'] = date("Y-m-d H:i:s");
                $domain_whitelist_insert_data[] = $temp_data;
            }
        }


        if(!empty($domain_whitelist_insert_data))
            $this->db->insert_batch('messenger_bot_domain_whitelist',$domain_whitelist_insert_data);
        // domain white list section end
        
        $insert_data['message'] = json_encode($reply_bot_filtered,true);
        $insert_data['user_id'] = $this->user_id;        
        $this->basic->insert_data('messenger_bot',$insert_data);
        $messenger_bot_table_id = $this->db->insert_id();
        $postback_insert_data_modified = array();
        $m=0;
        foreach($postback_insert_data as $value)
        {
            $postback_insert_data_modified[$m]['user_id'] = $value['user_id'];
            $postback_insert_data_modified[$m]['postback_id'] = $value['postback_id'];
            $postback_insert_data_modified[$m]['page_id'] = $value['page_id'];
            $postback_insert_data_modified[$m]['bot_name'] = $value['bot_name'];
            $postback_insert_data_modified[$m]['messenger_bot_table_id'] = $messenger_bot_table_id;
            $m++;
        }

        if($keyword_type == 'post-back' && !empty($keywordtype_postback_id))
        {    
            $this->db->where("page_id",$page_table_id);        
            $this->db->where_in("postback_id", $keywordtype_postback_id);
            $this->db->update('messenger_bot_postback', array('use_status' => '1'));
        }
        
        // if(!empty($postback_insert_data_modified))
        // $this->db->insert_batch('messenger_bot_postback',$postback_insert_data_modified);
        // $this->session->set_flashdata('bot_success',1); 
        echo json_encode(array("status" => "1", "message" =>$this->lang->line("new bot settings has been stored successfully.")));
        
    }

    public function template_manager()
    {
        $page_list = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("user_id"=>$this->user_id,'bot_enabled'=>'1')),array('page_name','id'));
        $data['page_info'] = $page_list;
        $data['body'] = 'messenger_tools/template_manager';
        $data['page_title'] = $this->lang->line('Template Manager');
        $this->_viewcontroller($data);
    }
    public function template_manager_data()
    {
        $this->ajax_check();
        $page_id = $this->input->post('page_id',true);
        $postback_id = $this->input->post('postback_id',true);
        $display_columns = array("#","CHECKBOX",'id', 'page_name', 'bot_name', 'postback_id', 'action');

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $sort_index = isset($_POST['order'][0]['column']) ? strval($_POST['order'][0]['column']) : 2;
        $sort = isset($display_columns[$sort_index]) ? $display_columns[$sort_index] : 'id';
        $order = isset($_POST['order'][0]['dir']) ? strval($_POST['order'][0]['dir']) : 'desc';
        $order_by=$sort." ".$order;


        $where_simple = array();
        $where_simple['messenger_bot_postback.user_id'] = $this->user_id;
        if($page_id != '') $where_simple['messenger_bot_postback.page_id'] = $page_id;
        if($postback_id != '') $where_simple['postback_id like'] = "%".$postback_id."%";
        $where_simple['messenger_bot_postback.is_template'] = '1';
        $where_simple['messenger_bot_postback.template_for'] = 'reply_message';
        $table="messenger_bot_postback";
        $where = array('where'=>$where_simple);
        
        $join = array('facebook_rx_fb_page_info'=>'messenger_bot_postback.page_id=facebook_rx_fb_page_info.id,left');
        $select = array('messenger_bot_postback.*','page_name');

        $info=$this->basic->get_data($table,$where,$select,$join,$limit,$start,$order_by,$group_by='');

        $total_rows_array=$this->basic->count_row($table,$where,$count=$table.".id",$join,$group_by='');
        $total_result=$total_rows_array[0]['total_rows'];

        $i=0;
        $base_url=base_url();
        foreach ($info as $key => $value) 
        {
            $info[$i]["action"] = "<div style='min-width:90px'><a href='#' class='btn btn-circle btn-outline-info get_json_code' title='Get JSON Code' table_id='".$value['id']."'><i class='fas fa-code'></i></a>&nbsp;<a class='btn btn-circle btn-outline-warning' title='Edit' href='".base_url('messenger_bot/edit_template/').$value['id']."'><i class='fas fa-edit'></i></a>&nbsp;<a href='#' class='btn btn-circle btn-outline-danger delete_template' title='Delete' table_id='".$value['id']."'><i class='fa fa-trash'></i></a></div>";
            $i++;
        }

        $data['draw'] = (int)$_POST['draw'] + 1;
        $data['recordsTotal'] = $total_result;
        $data['recordsFiltered'] = $total_result;
        $data['data'] = convertDataTableResult($info, $display_columns ,$start,$primary_key="id");

        echo json_encode($data);


    }
    public function create_new_template($is_iframe="0",$default_page="",$default_child_postback_id="")
    {
        $data['body'] = 'messenger_tools/add_new_template';
        $data['page_title'] = $this->lang->line('Create new template');
        $data["templates"]=$this->basic->get_enum_values("messenger_bot","template_type");
        $data["keyword_types"]=$this->basic->get_enum_values("messenger_bot","keyword_type");
        $join = array('facebook_rx_fb_user_info'=>'facebook_rx_fb_page_info.facebook_rx_fb_user_info_id=facebook_rx_fb_user_info.id,left');
        $page_info = $this->basic->get_data('facebook_rx_fb_page_info',array('where'=>array('facebook_rx_fb_page_info.user_id'=>$this->user_id,'bot_enabled'=>'1')),array('facebook_rx_fb_page_info.id','page_name','name'),$join);
        $page_list = array();
        foreach($page_info as $value)
        {
            $page_list[$value['id']] = $value['page_name']." [".$value['name']."]";
        }
        $data['page_list'] = $page_list;
        $data['is_iframe'] = $is_iframe;
        $data['iframe'] = $is_iframe;
        $data['default_page'] = $default_page;
        $data['default_child_postback_id'] = $default_child_postback_id;
        $postback_id_list = $this->basic->get_data('messenger_bot_postback',array('where'=>array('user_id'=>$this->user_id)));

        $data['postback_ids'] = $postback_id_list;
        if($this->basic->is_exist("add_ons",array("project_id"=>16))) $data['has_broadcaster_addon'] = 1;
        else  $data['has_broadcaster_addon'] = 0;

        $this->_viewcontroller($data); 
    }
    
    public function create_template_action()
    {
        $post=$_POST;
        foreach ($post as $key => $value) 
        {
            $$key=$value;
        }

        $user_all_postback = array();
        $postback_id_list = $this->basic->get_data('messenger_bot_postback',array('where'=>array('user_id'=>$this->user_id,'page_id'=>$page_table_id)));

        foreach($postback_id_list as $value)
        {
            array_push($user_all_postback, $value['postback_id']);
        }

        // $template_type = trim($template_type);
        $insert_data = array();
        $insert_data_to_bot = array();
        $insert_data['bot_name'] = $bot_name;
        $insert_data_to_bot['bot_name'] = $bot_name;
        $insert_data['template_name'] = $bot_name;
        $insert_data['postback_id'] = $template_postback_id;
        $insert_data_to_bot['postback_id'] = $template_postback_id;
        $insert_data['page_id'] = $page_table_id;
        $insert_data_to_bot['page_id'] = $page_table_id;
        $insert_data['is_template'] = '1';
        $insert_data_to_bot['is_template'] = '1';
        $insert_data['use_status'] = '1';

        if(!isset($label_ids) || !is_array($label_ids)) $label_ids=array();
        $label_ids=array_filter($label_ids);
        $new_label_ids=implode(',', $label_ids);
        $insert_data["broadcaster_labels"]=$new_label_ids;
        $insert_data_to_bot["broadcaster_labels"]=$new_label_ids;

        if(!isset($drip_campaign_id) || !is_array($drip_campaign_id)) $drip_campaign_id=array();
        $drip_campaign_id=array_filter($drip_campaign_id);
        $new_drip_campaign_id=implode(',', $drip_campaign_id);
        $insert_data["drip_campaign_id"]=$new_drip_campaign_id;
        $insert_data_to_bot["drip_campaign_id"]=$new_drip_campaign_id;
        
        $facebook_rx_fb_user_info_id = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_table_id)),array("facebook_rx_fb_user_info_id","page_access_token","page_id"));
        $insert_data_to_bot['fb_page_id'] = $facebook_rx_fb_user_info_id[0]['page_id'];
        
        $page_access_token = $facebook_rx_fb_user_info_id[0]['page_access_token'];
        $facebook_rx_fb_user_info_id = $facebook_rx_fb_user_info_id[0]["facebook_rx_fb_user_info_id"];
        $white_listed_domain = $this->basic->get_data("messenger_bot_domain_whitelist",array("where"=>array("user_id"=>$this->user_id,"messenger_bot_user_info_id"=>$facebook_rx_fb_user_info_id,"page_id"=>$page_table_id)),"domain");

        $white_listed_domain_array = array();
        foreach ($white_listed_domain as $value) {
            $white_listed_domain_array[] = $value['domain'];
        }
        $need_to_whitelist_array = array();
        // domain white list section

        $postback_insert_data = array();
        $reply_bot = array();
        $bot_message = array();
        for ($k=1; $k <=3 ; $k++) 
        {    
            $template_type = 'template_type_'.$k;
            if(!isset($$template_type)) continue;
            $template_type = $$template_type;
            $template_type = str_replace(' ', '_', $template_type);

            if($template_type == 'text')
            {
                $text_reply = 'text_reply_'.$k;
                $text_reply = isset($$text_reply) ? $$text_reply : '';
                if($text_reply != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['text'] = $text_reply;
                    
                }
            }
            if($template_type == 'image')
            {
                $image_reply_field = 'image_reply_field_'.$k;
                $image_reply_field = isset($$image_reply_field) ? $$image_reply_field : '';
                if($image_reply_field != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'image';
                    $reply_bot[$k]['attachment']['payload']['url'] = $image_reply_field;
                    $reply_bot[$k]['attachment']['payload']['is_reusable'] = true;                    
                }
            }
            if($template_type == 'audio')
            {
                $audio_reply_field = 'audio_reply_field_'.$k;
                $audio_reply_field = isset($$audio_reply_field) ? $$audio_reply_field : '';
                if($audio_reply_field != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'audio';
                    $reply_bot[$k]['attachment']['payload']['url'] = $audio_reply_field;
                    $reply_bot[$k]['attachment']['payload']['is_reusable'] = true;
                }
                
            }
            if($template_type == 'video')
            {
                $video_reply_field = 'video_reply_field_'.$k;
                $video_reply_field = isset($$video_reply_field) ? $$video_reply_field : '';
                if($video_reply_field != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'video';
                    $reply_bot[$k]['attachment']['payload']['url'] = $video_reply_field;
                    $reply_bot[$k]['attachment']['payload']['is_reusable'] = true;                    
                }
            }
            if($template_type == 'file')
            {
                $file_reply_field = 'file_reply_field_'.$k;
                $file_reply_field = isset($$file_reply_field) ? $$file_reply_field : '';
                if($file_reply_field != '')
                {       
                    $reply_bot[$k]['template_type'] = $template_type;             
                    $reply_bot[$k]['attachment']['type'] = 'file';
                    $reply_bot[$k]['attachment']['payload']['url'] = $file_reply_field;
                    $reply_bot[$k]['attachment']['payload']['is_reusable'] = true;
                }
            }

 
            if($template_type == 'media')
            {
                $media_input = 'media_input_'.$k;
                $media_input = isset($$media_input) ? $$media_input : '';
                if($media_input != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'template';
                    $reply_bot[$k]['attachment']['payload']['template_type'] = 'media';
                    $template_media_type = '';
                    if (strpos($media_input, '/videos/') !== false) {
                        $template_media_type = 'video';
                    }
                    else
                        $template_media_type = 'image';
                    $reply_bot[$k]['attachment']['payload']['elements'][0]['media_type'] = $template_media_type;
                    $reply_bot[$k]['attachment']['payload']['elements'][0]['url'] = $media_input;                    
                }

                for ($i=1; $i <= 3 ; $i++) 
                { 
                    $button_text = 'media_text_'.$i.'_'.$k;
                    $button_text = isset($$button_text) ? $$button_text : '';
                    $button_type = 'media_type_'.$i.'_'.$k;
                    $button_type = isset($$button_type) ? $$button_type : '';
                    $button_postback_id = 'media_post_id_'.$i.'_'.$k;
                    $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                    $button_web_url = 'media_web_url_'.$i.'_'.$k;
                    $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                     //add an extra query parameter for tracking the subscriber to whom send 
                    if($button_web_url!='')
                        $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");

                    $button_call_us = 'media_call_us_'.$i.'_'.$k;
                    $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                    if($button_type == 'post_back')
                    {
                        if($button_text != '' && $button_type != '' && $button_postback_id != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'postback';
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['payload'] = $button_postback_id;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;
                            $single_postback_insert_data = array();
                            $single_postback_insert_data['user_id'] = $this->user_id;
                            $single_postback_insert_data['postback_id'] = $button_postback_id;
                            $single_postback_insert_data['page_id'] = $page_table_id;
                            $single_postback_insert_data['bot_name'] = $bot_name;
                            $postback_insert_data[] = $single_postback_insert_data; 
                        }
                    }
                    if(strpos($button_type,'web_url') !== FALSE)
                    {
                        $button_type_array = explode('_', $button_type);
                        if(isset($button_type_array[2]))
                        {
                            $button_extension = trim($button_type_array[2],'_'); 
                            array_pop($button_type_array);
                        }            
                        else $button_extension = '';
                        $button_type = implode('_', $button_type_array);

                        if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'web_url';
                            if($button_extension != '' && $button_extension == 'birthday'){
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['webview_height_ratio'] = 'compact';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                                $button_web_url = base_url('webview_builder/get_birthdate');
                            }
                            else
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['url'] = $button_web_url;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;

                            if($button_extension != '' && $button_extension != 'birthday')
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['webview_height_ratio'] = $button_extension;
                                // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                            }

                            if(!in_array($button_web_url, $white_listed_domain_array))
                            {
                                $need_to_whitelist_array[] = $button_web_url;
                            }
                        }
                    }
                    if($button_type == 'phone_number')
                    {
                        if($button_text != '' && $button_type != '' && $button_call_us != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'phone_number';
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['payload'] = $button_call_us;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;
                        }
                    }
                }
            }


            if($template_type == 'text_with_buttons')
            {
                $text_with_buttons_input = 'text_with_buttons_input_'.$k;
                $text_with_buttons_input = isset($$text_with_buttons_input) ? $$text_with_buttons_input : '';
                if($text_with_buttons_input != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'template';
                    $reply_bot[$k]['attachment']['payload']['template_type'] = 'button';
                    $reply_bot[$k]['attachment']['payload']['text'] = $text_with_buttons_input;                    
                }

                for ($i=1; $i <= 3 ; $i++) 
                { 
                    $button_text = 'text_with_buttons_text_'.$i.'_'.$k;
                    $button_text = isset($$button_text) ? $$button_text : '';
                    $button_type = 'text_with_button_type_'.$i.'_'.$k;
                    $button_type = isset($$button_type) ? $$button_type : '';
                    $button_postback_id = 'text_with_button_post_id_'.$i.'_'.$k;
                    $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                    $button_web_url = 'text_with_button_web_url_'.$i.'_'.$k;
                    $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                    //add an extra query parameter for tracking the subscriber to whom send 
                    if($button_web_url!='')
                        $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");

                    $button_call_us = 'text_with_button_call_us_'.$i.'_'.$k;
                    $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                    if($button_type == 'post_back')
                    {
                        if($button_text != '' && $button_type != '' && $button_postback_id != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['type'] = 'postback';
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['payload'] = $button_postback_id;
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['title'] = $button_text;
                            $single_postback_insert_data = array();
                            $single_postback_insert_data['user_id'] = $this->user_id;
                            $single_postback_insert_data['postback_id'] = $button_postback_id;
                            $single_postback_insert_data['page_id'] = $page_table_id;
                            $single_postback_insert_data['bot_name'] = $bot_name;
                            $postback_insert_data[] = $single_postback_insert_data; 
                        }
                    }

                    if(strpos($button_type,'web_url') !== FALSE)
                    {
                        $button_type_array = explode('_', $button_type);
                        if(isset($button_type_array[2]))
                        {
                            $button_extension = trim($button_type_array[2],'_'); 
                            array_pop($button_type_array);
                        }            
                        else $button_extension = '';
                        $button_type = implode('_', $button_type_array);

                        if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                        {
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['type'] = 'web_url';

                            if($button_extension != '' && $button_extension == 'birthday'){
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['webview_height_ratio'] = 'compact';
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                                $button_web_url = base_url('webview_builder/get_birthdate');
                            }
                            else
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['url'] = $button_web_url;
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['title'] = $button_text;

                            if($button_extension != '' && $button_extension != 'birthday')
                            {
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['webview_height_ratio'] = $button_extension;
                                // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                            }

                            if(!in_array($button_web_url, $white_listed_domain_array))
                            {
                                $need_to_whitelist_array[] = $button_web_url;
                            }
                        }
                    }
                    if($button_type == 'phone_number')
                    {
                        if($button_text != '' && $button_type != '' && $button_call_us != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['type'] = 'phone_number';
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['payload'] = $button_call_us;
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['title'] = $button_text;
                        }
                    }
                }
            }

            if($template_type == 'quick_reply')
            {
                $quick_reply_text = 'quick_reply_text_'.$k;
                $quick_reply_text = isset($$quick_reply_text) ? $$quick_reply_text : '';
                if($quick_reply_text != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['text'] = $quick_reply_text;                    
                }

                for ($i=1; $i <= 11 ; $i++) 
                { 
                    $button_text = 'quick_reply_button_text_'.$i.'_'.$k;
                    $button_text = isset($$button_text) ? $$button_text : '';
                    $button_postback_id = 'quick_reply_post_id_'.$i.'_'.$k;
                    $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                    $button_type = 'quick_reply_button_type_'.$i.'_'.$k;
                    $button_type = isset($$button_type) ? $$button_type : '';
                    if($button_type=='post_back')
                    {
                        if($button_text != '' && $button_postback_id != '')
                        {
                            $reply_bot[$k]['quick_replies'][$i-1]['content_type'] = 'text';
                            $reply_bot[$k]['quick_replies'][$i-1]['payload'] = $button_postback_id;
                            $reply_bot[$k]['quick_replies'][$i-1]['title'] = $button_text;
                            $single_postback_insert_data = array();
                            $single_postback_insert_data['user_id'] = $this->user_id;
                            $single_postback_insert_data['postback_id'] = $button_postback_id;
                            $single_postback_insert_data['page_id'] = $page_table_id;
                            $single_postback_insert_data['bot_name'] = $bot_name;
                            $postback_insert_data[] = $single_postback_insert_data; 
                        }                    
                    }
                    if($button_type=='phone_number')
                    {
                        $reply_bot[$k]['quick_replies'][$i-1]['content_type'] = 'user_phone_number';
                    }
                    if($button_type=='user_email')
                    {
                        $reply_bot[$k]['quick_replies'][$i-1]['content_type'] = 'user_email';
                    }
                    if($button_type=='location')
                    {
                        $reply_bot[$k]['quick_replies'][$i-1]['content_type'] = 'location';
                    }

                }
            }

            if($template_type == 'generic_template')
            {
                $generic_template_title = 'generic_template_title_'.$k;
                $generic_template_title = isset($$generic_template_title) ? $$generic_template_title : '';
                $generic_template_image = 'generic_template_image_'.$k;
                $generic_template_image = isset($$generic_template_image) ? $$generic_template_image : '';
                $generic_template_subtitle = 'generic_template_subtitle_'.$k;
                $generic_template_subtitle = isset($$generic_template_subtitle) ? $$generic_template_subtitle : '';
                $generic_template_image_destination_link = 'generic_template_image_destination_link_'.$k;
                $generic_template_image_destination_link = isset($$generic_template_image_destination_link) ? $$generic_template_image_destination_link : '';

                if($generic_template_title != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'template';
                    $reply_bot[$k]['attachment']['payload']['template_type'] = 'generic';
                    $reply_bot[$k]['attachment']['payload']['elements'][0]['title'] = $generic_template_title;                   
                }

                if($generic_template_subtitle != '')
                $reply_bot[$k]['attachment']['payload']['elements'][0]['subtitle'] = $generic_template_subtitle;

                if($generic_template_image!="")
                {
                    $reply_bot[$k]['attachment']['payload']['elements'][0]['image_url'] = $generic_template_image;
                    if($generic_template_image_destination_link!="")
                    {
                        $reply_bot[$k]['attachment']['payload']['elements'][0]['default_action']['type'] = 'web_url';
                        $reply_bot[$k]['attachment']['payload']['elements'][0]['default_action']['url'] = $generic_template_image_destination_link;
                    }

                    if(function_exists('getimagesize') && $generic_template_image!='') 
                    {
                        list($width, $height, $type, $attr) = getimagesize($generic_template_image);
                        if($width==$height)
                            $reply_bot[$k]['attachment']['payload']['image_aspect_ratio'] = 'square';
                    }

                }
                

                for ($i=1; $i <= 3 ; $i++) 
                { 
                    $button_text = 'generic_template_button_text_'.$i.'_'.$k;
                    $button_text = isset($$button_text) ? $$button_text : '';
                    $button_type = 'generic_template_button_type_'.$i.'_'.$k;
                    $button_type = isset($$button_type) ? $$button_type : '';
                    $button_postback_id = 'generic_template_button_post_id_'.$i.'_'.$k;
                    $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                    $button_web_url = 'generic_template_button_web_url_'.$i.'_'.$k;
                    $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                    //add an extra query parameter for tracking the subscriber to whom send 
                    if($button_web_url!='')
                        $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");

                    $button_call_us = 'generic_template_button_call_us_'.$i.'_'.$k;
                    $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                    if($button_type == 'post_back')
                    {
                        if($button_text != '' && $button_type != '' && $button_postback_id != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'postback';
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['payload'] = $button_postback_id;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;
                            $single_postback_insert_data = array();
                            $single_postback_insert_data['user_id'] = $this->user_id;
                            $single_postback_insert_data['postback_id'] = $button_postback_id;
                            $single_postback_insert_data['page_id'] = $page_table_id;
                            $single_postback_insert_data['bot_name'] = $bot_name;
                            $postback_insert_data[] = $single_postback_insert_data; 
                        }
                    }
                    if(strpos($button_type,'web_url') !== FALSE)
                    {
                        $button_type_array = explode('_', $button_type);
                        if(isset($button_type_array[2]))
                        {
                            $button_extension = trim($button_type_array[2],'_'); 
                            array_pop($button_type_array);
                        }            
                        else $button_extension = '';
                        $button_type = implode('_', $button_type_array);

                        if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'web_url';
                            if($button_extension != '' && $button_extension == 'birthday'){                                
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['webview_height_ratio'] = 'compact';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                                $button_web_url = base_url('webview_builder/get_birthdate');
                            }
                            else
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['url'] = $button_web_url;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;

                            if($button_extension != '' && $button_extension != 'birthday')
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['webview_height_ratio'] = $button_extension;
                                // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                            }

                            if(!in_array($button_web_url, $white_listed_domain_array))
                            {
                                $need_to_whitelist_array[] = $button_web_url;
                            }
                        }
                    }
                    if($button_type == 'phone_number')
                    {
                        if($button_text != '' && $button_type != '' && $button_call_us != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'phone_number';
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['payload'] = $button_call_us;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;
                        }
                    }
                }
            }

            if($template_type == 'carousel')
            {
                $reply_bot[$k]['template_type'] = $template_type;
                $reply_bot[$k]['attachment']['type'] = 'template';
                $reply_bot[$k]['attachment']['payload']['template_type'] = 'generic';
                for ($j=1; $j <=10 ; $j++) 
                {                                 
                    $carousel_image = 'carousel_image_'.$j.'_'.$k;
                    $carousel_title = 'carousel_title_'.$j.'_'.$k;

                    if(!isset($$carousel_title) || $$carousel_title == '') continue;

                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['title'] = $$carousel_title;
                    $carousel_subtitle = 'carousel_subtitle_'.$j.'_'.$k;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['subtitle'] = $$carousel_subtitle;

                    if(isset($$carousel_image) && $$carousel_image!="")
                    {
                        $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['image_url'] = $$carousel_image;                    
                        $carousel_image_destination_link = 'carousel_image_destination_link_'.$j.'_'.$k;
                        if($$carousel_image_destination_link!="") 
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['default_action']['type'] = 'web_url';
                            $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['default_action']['url'] = $$carousel_image_destination_link;
                        }

                        if(function_exists('getimagesize') && $$carousel_image!='') 
                        {
                            list($width, $height, $type, $attr) = getimagesize($$carousel_image);
                            if($width==$height)
                                $reply_bot[$k]['attachment']['payload']['image_aspect_ratio'] = 'square';
                        }

                    }
                    
                    for ($i=1; $i <= 3 ; $i++) 
                    { 
                        $button_text = 'carousel_button_text_'.$j."_".$i.'_'.$k;
                        $button_text = isset($$button_text) ? $$button_text : '';
                        $button_type = 'carousel_button_type_'.$j."_".$i.'_'.$k;
                        $button_type = isset($$button_type) ? $$button_type : '';
                        $button_postback_id = 'carousel_button_post_id_'.$j."_".$i.'_'.$k;
                        $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                        $button_web_url = 'carousel_button_web_url_'.$j."_".$i.'_'.$k;
                        $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                        //add an extra query parameter for tracking the subscriber to whom send 
                        if($button_web_url!='')
                          $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");

                        $button_call_us = 'carousel_button_call_us_'.$j."_".$i.'_'.$k;
                        $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                        if($button_type == 'post_back')
                        {
                            if($button_text != '' && $button_type != '' && $button_postback_id != '')
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['type'] = 'postback';
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['payload'] = $button_postback_id;
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['title'] = $button_text;
                                $single_postback_insert_data = array();
                                $single_postback_insert_data['user_id'] = $this->user_id;
                                $single_postback_insert_data['postback_id'] = $button_postback_id;
                                $single_postback_insert_data['page_id'] = $page_table_id;
                                $single_postback_insert_data['bot_name'] = $bot_name;
                                $postback_insert_data[] = $single_postback_insert_data; 
                            }
                        }
                        if(strpos($button_type,'web_url') !== FALSE)
                        {
                            $button_type_array = explode('_', $button_type);
                            if(isset($button_type_array[2]))
                            {
                                $button_extension = trim($button_type_array[2],'_'); 
                                array_pop($button_type_array);
                            }            
                            else $button_extension = '';
                            $button_type = implode('_', $button_type_array);

                            if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['type'] = 'web_url';
                                if($button_extension != '' && $button_extension == 'birthday'){
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['webview_height_ratio'] = 'compact';
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                                    $button_web_url = base_url('webview_builder/get_birthdate');
                                }
                                else
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['url'] = $button_web_url;
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['title'] = $button_text;

                                if($button_extension != '' && $button_extension != 'birthday')
                                {
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['webview_height_ratio'] = $button_extension;
                                    // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                                }

                                if(!in_array($button_web_url, $white_listed_domain_array))
                                {
                                    $need_to_whitelist_array[] = $button_web_url;
                                }
                            }
                        }
                        if($button_type == 'phone_number')
                        {
                            if($button_text != '' && $button_type != '' && $button_call_us != '')
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['type'] = 'phone_number';
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['payload'] = $button_call_us;
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['title'] = $button_text;
                            }
                        }
                    }
                }
            }

            if($template_type == 'list')
            {
                $reply_bot[$k]['template_type'] = $template_type;
                $reply_bot[$k]['attachment']['type'] = 'template';
                $reply_bot[$k]['attachment']['payload']['template_type'] = 'list';

                for ($j=1; $j <=4 ; $j++) 
                {                                 
                    $list_image = 'list_image_'.$j.'_'.$k;
                    if(!isset($$list_image) || $$list_image == '') continue;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['image_url'] = $$list_image;
                    $list_title = 'list_title_'.$j.'_'.$k;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['title'] = $$list_title;
                    $list_subtitle = 'list_subtitle_'.$j.'_'.$k;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['subtitle'] = $$list_subtitle;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['default_action']['type'] = 'web_url';
                    $list_image_destination_link = 'list_image_destination_link_'.$j.'_'.$k;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['default_action']['url'] = $$list_image_destination_link;
                    
                }

                $button_text = 'list_with_buttons_text_'.$k;
                $button_text = isset($$button_text) ? $$button_text : '';
                $button_type = 'list_with_button_type_'.$k;
                $button_type = isset($$button_type) ? $$button_type : '';
                $button_postback_id = 'list_with_button_post_id_'.$k;
                $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                $button_web_url = 'list_with_button_web_url_'.$k;
                $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                //add an extra query parameter for tracking the subscriber to whom send 
                if($button_web_url!='')
                  $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");

                $button_call_us = 'list_with_button_call_us_'.$k;
                $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                if($button_type == 'post_back')
                {
                    if($button_text != '' && $button_type != '' && $button_postback_id != '')
                    {
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['type'] = 'postback';
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['payload'] = $button_postback_id;
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['title'] = $button_text;
                        $single_postback_insert_data = array();
                        $single_postback_insert_data['user_id'] = $this->user_id;
                        $single_postback_insert_data['postback_id'] = $button_postback_id;
                        $single_postback_insert_data['page_id'] = $page_table_id;
                        $single_postback_insert_data['bot_name'] = $bot_name;
                        $postback_insert_data[] = $single_postback_insert_data; 
                    }
                }
                if(strpos($button_type,'web_url') !== FALSE)
                {
                    $button_type_array = explode('_', $button_type);
                    if(isset($button_type_array[2]))
                    {
                        $button_extension = trim($button_type_array[2],'_'); 
                        array_pop($button_type_array);
                    }            
                    else $button_extension = '';
                    $button_type = implode('_', $button_type_array);

                    if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                    {
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['type'] = 'web_url';
                        if($button_extension != '' && $button_extension == 'birthday'){
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['messenger_extensions'] = 'true';
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['webview_height_ratio'] = 'compact';
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                            $button_web_url = base_url('webview_builder/get_birthdate');
                        }
                        else
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['url'] = $button_web_url;
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['title'] = $button_text;

                        if($button_extension != '' && $button_extension != 'birthday')
                        {
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['messenger_extensions'] = 'true';
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['webview_height_ratio'] = $button_extension;
                            // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                        }

                        if(!in_array($button_web_url, $white_listed_domain_array))
                        {
                            $need_to_whitelist_array[] = $button_web_url;
                        }
                    }
                }
                if($button_type == 'phone_number')
                {
                    if($button_text != '' && $button_type != '' && $button_call_us != '')
                    {
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['type'] = 'phone_number';
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['payload'] = $button_call_us;
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['title'] = $button_text;
                    }
                }


            }

            if(isset($reply_bot[$k]))
            {       
                $typing_on_settings = 'typing_on_enable_'.$k;
                if(!isset($$typing_on_settings)) $typing_on_settings = 'off';
                else $typing_on_settings = $$typing_on_settings;

                $delay_in_reply = 'delay_in_reply_'.$k;
                if(!isset($$delay_in_reply)) $delay_in_reply = 0;
                else $delay_in_reply = $$delay_in_reply;

                $reply_bot[$k]['typing_on_settings'] = $typing_on_settings;
                $reply_bot[$k]['delay_in_reply'] = $delay_in_reply;

                $bot_message[$k]['recipient'] = array('id'=>'replace_id');
                $bot_message[$k]['message'] = $reply_bot[$k];
            }

        }

        $reply_bot_filtered = array();
        $m=0;
        foreach ($bot_message as $value) {
            $m++;
            $reply_bot_filtered[$m] = $value;
        }

        // domain white list section start
        $this->load->library("fb_rx_login"); 
        $domain_whitelist_insert_data = array();
        foreach($need_to_whitelist_array as $value)
        {
            $domain_only_whitelist= get_domain_only_with_http($value);
            if(in_array($domain_only_whitelist, $white_listed_domain_array)) continue; 

            $response=$this->fb_rx_login->domain_whitelist($page_access_token,$domain_only_whitelist);
            if($response['status'] != '0')
            {
                $temp_data = array();
                $temp_data['user_id'] = $this->user_id;
                $temp_data['messenger_bot_user_info_id'] = $facebook_rx_fb_user_info_id;
                $temp_data['page_id'] = $page_table_id;
                $temp_data['domain'] = $domain_only_whitelist;
                $temp_data['created_at'] = date("Y-m-d H:i:s");
                $domain_whitelist_insert_data[] = $temp_data;
            }
        }
        if(!empty($domain_whitelist_insert_data))
            $this->db->insert_batch('messenger_bot_domain_whitelist',$domain_whitelist_insert_data);
        // domain white list section end
        
        $insert_data['template_jsoncode'] = json_encode($reply_bot_filtered,true);
        $insert_data_to_bot['message'] = json_encode($reply_bot_filtered,true);
        $insert_data['user_id'] = $this->user_id;        
        $insert_data_to_bot['user_id'] = $this->user_id;        
        $this->basic->insert_data('messenger_bot',$insert_data_to_bot);
        $messenger_bot_table_id = $this->db->insert_id();


        if($postback_type == 'child')
        {
            $template_json = json_encode($reply_bot_filtered,true);
            $postback_update_data = array('use_status'=>'1','messenger_bot_table_id'=>$messenger_bot_table_id,'template_jsoncode'=>$template_json,'is_template'=>'1','bot_name'=>$bot_name,'template_name'=>$bot_name);
            $this->basic->update_data('messenger_bot_postback',array('postback_id'=>$template_postback_id,'page_id'=>$page_table_id,'user_id'=>$this->user_id),$postback_update_data);
            $template_info = $this->basic->get_data('messenger_bot_postback',array('where'=>array('postback_id'=>$template_postback_id,'page_id'=>$page_table_id,'user_id'=>$this->user_id)));
            if(!empty($template_info)) $template_id = $template_info[0]['id'];
            else $template_id = 0;
        }
        else
        {
            $insert_data['messenger_bot_table_id'] = $messenger_bot_table_id;
            $this->basic->insert_data('messenger_bot_postback',$insert_data);
            $template_id = $this->db->insert_id();            
        }
 

        $postback_insert_data_modified = array();

        $m=0;

        $unique_postbacks = array();

        foreach($postback_insert_data as $value)
        {
            if(in_array($value['postback_id'], $user_all_postback)) continue;
            if(in_array($value['postback_id'], $unique_postbacks)) continue;

            $postback_insert_data_modified[$m]['user_id'] = $value['user_id'];
            $postback_insert_data_modified[$m]['postback_id'] = $value['postback_id'];
            $postback_insert_data_modified[$m]['page_id'] = $value['page_id'];
            $postback_insert_data_modified[$m]['bot_name'] = $value['bot_name'];
            $postback_insert_data_modified[$m]['template_id'] = $template_id;
            $postback_insert_data_modified[$m]['inherit_from_template'] = '1';
            array_push($unique_postbacks, $value['postback_id']);
            $m++;
        }

        // if($keyword_type == 'post-back' && !empty($keywordtype_postback_id))
        // {            
        //     $this->db->where_in("postback_id", $keywordtype_postback_id);
        //     $this->db->update('messenger_bot_postback', array('use_status' => '1'));
        // }
        
        if(!empty($postback_insert_data_modified))
        $this->db->insert_batch('messenger_bot_postback',$postback_insert_data_modified);
        $this->session->set_flashdata('bot_success',1);
        echo json_encode(array("status" => "1", "message" =>$this->lang->line("New template has been stored successfully.")));
        
    }

    public function edit_template($postback_table_id=0,$iframe='0',$is_default='0')
    {
        if($postback_table_id == 0) exit();
        $table_name = "messenger_bot_postback";
        $where_bot['where'] = array('id' => $postback_table_id, 'status' => '1', 'user_id'=>$this->user_id);
        $bot_info = $this->basic->get_data($table_name, $where_bot);
        if(empty($bot_info)) redirect('messenger_bot/template_manager', 'location');
        $data['body'] = 'messenger_tools/edit_template';
        $data['page_title'] = $this->lang->line('Edit template');
        $data["templates"]=$this->basic->get_enum_values("messenger_bot","template_type");
        $data["keyword_types"]=$this->basic->get_enum_values("messenger_bot","keyword_type");
        $join = array('facebook_rx_fb_user_info'=>'facebook_rx_fb_page_info.facebook_rx_fb_user_info_id=facebook_rx_fb_user_info.id,left');
        $page_info = $this->basic->get_data('facebook_rx_fb_page_info',array('where'=>array('facebook_rx_fb_page_info.user_id'=>$this->user_id,'bot_enabled'=>'1')),array('facebook_rx_fb_page_info.id','page_name','name'),$join);
        $page_list = array();
        foreach($page_info as $value)
        {
            $page_list[$value['id']] = $value['page_name']." [".$value['name']."]";
        }
        $data['page_list'] = $page_list;
        $data['bot_info'] = isset($bot_info[0]) ? $bot_info[0] : array();

        $postback_id_list = $this->basic->get_data('messenger_bot_postback',array('where'=>array('user_id'=>$this->user_id,'page_id'=>$bot_info[0]["page_id"]),'where_not_in'=>array('postback_id'=>array('UNSUBSCRIBE_QUICK_BOXER','RESUBSCRIBE_QUICK_BOXER','YES_START_CHAT_WITH_HUMAN','YES_START_CHAT_WITH_BOT'))));

        $current_postbacks = array();
        foreach ($postback_id_list as $value) {
            if($value['template_id'] == $postback_table_id || $value['id'] == $postback_table_id)
            $current_postbacks[] = $value['postback_id'];
        }
        $data['postback_ids'] = $postback_id_list;
        $data['current_postbacks'] = $current_postbacks;

        $table_type = 'messenger_bot_broadcast_contact_group';
        $where_type['where'] = array('user_id'=>$this->user_id,"page_id"=>$bot_info[0]["page_id"],"unsubscribe"=>"0","invisible"=>"0");
        $data['info_type'] = $this->basic->get_data($table_type,$where_type,$select='', $join='', $limit='', $start='', $order_by='group_name');

        if($this->is_broadcaster_exist)
        {          

            $table_type = 'messenger_bot_drip_campaign';
            $where_type['where'] = array('user_id'=>$this->user_id,"page_id"=>$bot_info[0]["page_id"]);
            $data['dripcampaign_list'] = $this->basic->get_data($table_type,$where_type,$select='');
        }
        else 
        {
            $data['dripcampaign_list']=array();
        }


        if($this->is_broadcaster_exist)
            $data['has_broadcaster_addon'] = 1;
        else
            $data['has_broadcaster_addon'] = 0;

        $postback_id_list = $this->basic->get_data('messenger_bot_postback',array('where'=>array('user_id'=>$this->user_id,'page_id'=>$bot_info[0]["page_id"],'template_for'=>'reply_message','is_template'=>'1'),'or_where'=>array('template_id'=>$postback_table_id)),array('postback_id','bot_name'));
        $postback_dropdown = array();
        if(!empty($postback_id_list))
        {
            foreach($postback_id_list as $value)
                array_push($postback_dropdown, $value['postback_id']);
        }
        $data['postback_dropdown'] = $postback_dropdown;
        $data['iframe'] = $iframe;
        $data['is_default'] = $is_default;

        $data['iframe']=$iframe;
        $this->_viewcontroller($data);  
    }

    public function edit_template_action()
    {  
        $post=$_POST;
        foreach ($post as $key => $value) 
        {
            $$key=$value;
        }

        // $template_type = trim($template_type);
        $insert_data = array();
        $insert_data['bot_name'] = $bot_name;
        $insert_data['template_name'] = $bot_name;
        $insert_data['postback_id'] = $template_postback_id;
        $insert_data['page_id'] = $page_table_id;
        $insert_data['is_template'] = '1';

        if(!isset($label_ids) || !is_array($label_ids)) $label_ids=array();
        $label_ids=array_filter($label_ids);
        $new_label_ids=implode(',', $label_ids);
        $insert_data["broadcaster_labels"]=$new_label_ids;

        if(!isset($drip_campaign_id) || !is_array($drip_campaign_id)) $drip_campaign_id=array();
        $drip_campaign_id=array_filter($drip_campaign_id);
        $new_drip_campaign_id=implode(',', $drip_campaign_id);
        $insert_data["drip_campaign_id"]=$new_drip_campaign_id;

        // domain white list section
        $facebook_rx_fb_user_info_id = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_table_id)),array("facebook_rx_fb_user_info_id","page_access_token"));
        $page_access_token = $facebook_rx_fb_user_info_id[0]['page_access_token'];
        $facebook_rx_fb_user_info_id = $facebook_rx_fb_user_info_id[0]["facebook_rx_fb_user_info_id"];
        $white_listed_domain = $this->basic->get_data("messenger_bot_domain_whitelist",array("where"=>array("user_id"=>$this->user_id,"messenger_bot_user_info_id"=>$facebook_rx_fb_user_info_id,"page_id"=>$page_table_id)),"domain");
        $white_listed_domain_array = array();
        foreach ($white_listed_domain as $value) {
            $white_listed_domain_array[] = $value['domain'];
        }
        $need_to_whitelist_array = array();
        // domain white list section

        $postback_insert_data = array();
        $reply_bot = array();
        $bot_message = array();

        for ($k=1; $k <=3 ; $k++) 
        {    
            $template_type = 'template_type_'.$k;
            if(!isset($$template_type)) continue;
            $template_type = $$template_type;
            // $insert_data['template_type'] = $template_type;
            $template_type = str_replace(' ', '_', $template_type);

            if($template_type == 'text')
            {
                $text_reply = 'text_reply_'.$k;
                $text_reply = isset($$text_reply) ? $$text_reply : '';
                if($text_reply != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['text'] = $text_reply;
                    
                }
            }
            if($template_type == 'image')
            {
                $image_reply_field = 'image_reply_field_'.$k;
                $image_reply_field = isset($$image_reply_field) ? $$image_reply_field : '';
                if($image_reply_field != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'image';
                    $reply_bot[$k]['attachment']['payload']['url'] = $image_reply_field;
                    $reply_bot[$k]['attachment']['payload']['is_reusable'] = true;                    
                }
            }
            if($template_type == 'audio')
            {
                $audio_reply_field = 'audio_reply_field_'.$k;
                $audio_reply_field = isset($$audio_reply_field) ? $$audio_reply_field : '';
                if($audio_reply_field != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'audio';
                    $reply_bot[$k]['attachment']['payload']['url'] = $audio_reply_field;
                    $reply_bot[$k]['attachment']['payload']['is_reusable'] = true;
                }
                
            }
            if($template_type == 'video')
            {
                $video_reply_field = 'video_reply_field_'.$k;
                $video_reply_field = isset($$video_reply_field) ? $$video_reply_field : '';
                if($video_reply_field != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'video';
                    $reply_bot[$k]['attachment']['payload']['url'] = $video_reply_field;
                    $reply_bot[$k]['attachment']['payload']['is_reusable'] = true;                    
                }
            }
            if($template_type == 'file')
            {
                $file_reply_field = 'file_reply_field_'.$k;
                $file_reply_field = isset($$file_reply_field) ? $$file_reply_field : '';
                if($file_reply_field != '')
                {       
                    $reply_bot[$k]['template_type'] = $template_type;             
                    $reply_bot[$k]['attachment']['type'] = 'file';
                    $reply_bot[$k]['attachment']['payload']['url'] = $file_reply_field;
                    $reply_bot[$k]['attachment']['payload']['is_reusable'] = true;
                }
            }



 
            if($template_type == 'media')
            {
                $media_input = 'media_input_'.$k;
                $media_input = isset($$media_input) ? $$media_input : '';
                if($media_input != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'template';
                    $reply_bot[$k]['attachment']['payload']['template_type'] = 'media';
                    $template_media_type = '';
                    if (strpos($media_input, '/videos/') !== false) {
                        $template_media_type = 'video';
                    }
                    else
                        $template_media_type = 'image';
                    $reply_bot[$k]['attachment']['payload']['elements'][0]['media_type'] = $template_media_type;
                    $reply_bot[$k]['attachment']['payload']['elements'][0]['url'] = $media_input;                    
                }

                for ($i=1; $i <= 3 ; $i++) 
                { 
                    $button_text = 'media_text_'.$i.'_'.$k;
                    $button_text = isset($$button_text) ? $$button_text : '';
                    $button_type = 'media_type_'.$i.'_'.$k;
                    $button_type = isset($$button_type) ? $$button_type : '';
                    $button_postback_id = 'media_post_id_'.$i.'_'.$k;
                    $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                    $button_web_url = 'media_web_url_'.$i.'_'.$k;
                    $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                    //add an extra query parameter for tracking the subscriber to whom send 
                    if($button_web_url!='')
                      $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");

                    $button_call_us = 'media_call_us_'.$i.'_'.$k;
                    $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                    if($button_type == 'post_back')
                    {
                        if($button_text != '' && $button_type != '' && $button_postback_id != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'postback';
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['payload'] = $button_postback_id;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;
                            $single_postback_insert_data = array();
                            $single_postback_insert_data['user_id'] = $this->user_id;
                            $single_postback_insert_data['postback_id'] = $button_postback_id;
                            $single_postback_insert_data['page_id'] = $page_table_id;
                            $single_postback_insert_data['bot_name'] = $bot_name;
                            $postback_insert_data[] = $single_postback_insert_data; 
                        }
                    }
                    if(strpos($button_type,'web_url') !== FALSE)
                    {
                        $button_type_array = explode('_', $button_type);
                        if(isset($button_type_array[2]))
                        {
                            $button_extension = trim($button_type_array[2],'_'); 
                            array_pop($button_type_array);
                        }            
                        else $button_extension = '';
                        $button_type = implode('_', $button_type_array);

                        if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'web_url';
                            if($button_extension != '' && $button_extension == 'birthday'){
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['webview_height_ratio'] = 'compact';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                                $button_web_url = base_url('webview_builder/get_birthdate');
                            }
                            else
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['url'] = $button_web_url;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;

                            if($button_extension != '' && $button_extension != 'birthday')
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['webview_height_ratio'] = $button_extension;
                                // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                            }

                            if(!in_array($button_web_url, $white_listed_domain_array))
                            {
                                $need_to_whitelist_array[] = $button_web_url;
                            }
                        }
                    }
                    if($button_type == 'phone_number')
                    {
                        if($button_text != '' && $button_type != '' && $button_call_us != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'phone_number';
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['payload'] = $button_call_us;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;
                        }
                    }
                }
            }



            if($template_type == 'text_with_buttons')
            {
                $text_with_buttons_input = 'text_with_buttons_input_'.$k;
                $text_with_buttons_input = isset($$text_with_buttons_input) ? $$text_with_buttons_input : '';
                if($text_with_buttons_input != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'template';
                    $reply_bot[$k]['attachment']['payload']['template_type'] = 'button';
                    $reply_bot[$k]['attachment']['payload']['text'] = $text_with_buttons_input;                    
                }

                for ($i=1; $i <= 3 ; $i++) 
                { 
                    $button_text = 'text_with_buttons_text_'.$i.'_'.$k;
                    $button_text = isset($$button_text) ? $$button_text : '';
                    $button_type = 'text_with_button_type_'.$i.'_'.$k;
                    $button_type = isset($$button_type) ? $$button_type : '';
                    $button_postback_id = 'text_with_button_post_id_'.$i.'_'.$k;
                    $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                    $button_web_url = 'text_with_button_web_url_'.$i.'_'.$k;
                    $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                    //add an extra query parameter for tracking the subscriber to whom send 
                    if($button_web_url!='')
                        $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");


                    $button_call_us = 'text_with_button_call_us_'.$i.'_'.$k;
                    $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                    if($button_type == 'post_back')
                    {
                        if($button_text != '' && $button_type != '' && $button_postback_id != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['type'] = 'postback';
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['payload'] = $button_postback_id;
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['title'] = $button_text;
                            $single_postback_insert_data = array();
                            $single_postback_insert_data['user_id'] = $this->user_id;
                            $single_postback_insert_data['postback_id'] = $button_postback_id;
                            $single_postback_insert_data['page_id'] = $page_table_id;
                            $single_postback_insert_data['bot_name'] = $bot_name;
                            $postback_insert_data[] = $single_postback_insert_data; 
                        }
                    }
                    if(strpos($button_type,'web_url') !== FALSE)
                    {
                        $button_type_array = explode('_', $button_type);
                        if(isset($button_type_array[2]))
                        {
                            $button_extension = trim($button_type_array[2],'_'); 
                            array_pop($button_type_array);
                        }            
                        else $button_extension = '';
                        $button_type = implode('_', $button_type_array);

                        if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                        {
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['type'] = 'web_url';

                            if($button_extension != '' && $button_extension == 'birthday'){
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['webview_height_ratio'] = 'compact';
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                                $button_web_url = base_url('webview_builder/get_birthdate');
                            }
                            else
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['url'] = $button_web_url;
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['title'] = $button_text;

                            if($button_extension != '' && $button_extension != 'birthday')
                            {
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['webview_height_ratio'] = $button_extension;
                                // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                            }

                            if(!in_array($button_web_url, $white_listed_domain_array))
                            {
                                $need_to_whitelist_array[] = $button_web_url;
                            }
                        }
                    }
                    if($button_type == 'phone_number')
                    {
                        if($button_text != '' && $button_type != '' && $button_call_us != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['type'] = 'phone_number';
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['payload'] = $button_call_us;
                            $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['title'] = $button_text;
                        }
                    }
                }
            }

            if($template_type == 'quick_reply')
            {
                $quick_reply_text = 'quick_reply_text_'.$k;
                $quick_reply_text = isset($$quick_reply_text) ? $$quick_reply_text : '';
                if($quick_reply_text != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['text'] = $quick_reply_text;                    
                }

                for ($i=1; $i <= 11 ; $i++) 
                { 
                    $button_text = 'quick_reply_button_text_'.$i.'_'.$k;
                    $button_text = isset($$button_text) ? $$button_text : '';
                    $button_postback_id = 'quick_reply_post_id_'.$i.'_'.$k;
                    $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                    $button_type = 'quick_reply_button_type_'.$i.'_'.$k;
                    $button_type = isset($$button_type) ? $$button_type : '';
                    if($button_type=='post_back')
                    {
                        if($button_text != '' && $button_postback_id != '')
                        {
                            $reply_bot[$k]['quick_replies'][$i-1]['content_type'] = 'text';
                            $reply_bot[$k]['quick_replies'][$i-1]['payload'] = $button_postback_id;
                            $reply_bot[$k]['quick_replies'][$i-1]['title'] = $button_text;
                            $single_postback_insert_data = array();
                            $single_postback_insert_data['user_id'] = $this->user_id;
                            $single_postback_insert_data['postback_id'] = $button_postback_id;
                            $single_postback_insert_data['page_id'] = $page_table_id;
                            $single_postback_insert_data['bot_name'] = $bot_name;
                            $postback_insert_data[] = $single_postback_insert_data; 
                        }                    
                    }
                    if($button_type=='phone_number')
                    {
                        $reply_bot[$k]['quick_replies'][$i-1]['content_type'] = 'user_phone_number';
                    }
                    if($button_type=='user_email')
                    {
                        $reply_bot[$k]['quick_replies'][$i-1]['content_type'] = 'user_email';
                    }
                    if($button_type=='location')
                    {
                        $reply_bot[$k]['quick_replies'][$i-1]['content_type'] = 'location';
                    }

                }
            }

            if($template_type == 'generic_template')
            {
                $generic_template_title = 'generic_template_title_'.$k;
                $generic_template_title = isset($$generic_template_title) ? $$generic_template_title : '';
                $generic_template_image = 'generic_template_image_'.$k;
                $generic_template_image = isset($$generic_template_image) ? $$generic_template_image : '';
                $generic_template_subtitle = 'generic_template_subtitle_'.$k;
                $generic_template_subtitle = isset($$generic_template_subtitle) ? $$generic_template_subtitle : '';
                $generic_template_image_destination_link = 'generic_template_image_destination_link_'.$k;
                $generic_template_image_destination_link = isset($$generic_template_image_destination_link) ? $$generic_template_image_destination_link : '';

                if($generic_template_title != '')
                {
                    $reply_bot[$k]['template_type'] = $template_type;
                    $reply_bot[$k]['attachment']['type'] = 'template';
                    $reply_bot[$k]['attachment']['payload']['template_type'] = 'generic';
                    $reply_bot[$k]['attachment']['payload']['elements'][0]['title'] = $generic_template_title;                   
                }

                if($generic_template_subtitle != '')
                $reply_bot[$k]['attachment']['payload']['elements'][0]['subtitle'] = $generic_template_subtitle;

                if($generic_template_image!="")
                {
                    $reply_bot[$k]['attachment']['payload']['elements'][0]['image_url'] = $generic_template_image;
                    if($generic_template_image_destination_link!="")
                    {
                        $reply_bot[$k]['attachment']['payload']['elements'][0]['default_action']['type'] = 'web_url';
                        $reply_bot[$k]['attachment']['payload']['elements'][0]['default_action']['url'] = $generic_template_image_destination_link;
                    }

                    if(function_exists('getimagesize') && $generic_template_image!='') 
                    {
                        list($width, $height, $type, $attr) = getimagesize($generic_template_image);
                        if($width==$height)
                            $reply_bot[$k]['attachment']['payload']['image_aspect_ratio'] = 'square';
                    }

                }
                
                // $reply_bot['attachment']['payload']['elements'][0]['default_action']['messenger_extensions'] = true;
                // $reply_bot['attachment']['payload']['elements'][0]['default_action']['webview_height_ratio'] = 'tall';
                // $reply_bot['attachment']['payload']['elements'][0]['default_action']['fallback_url'] = $generic_template_image_destination_link;

                for ($i=1; $i <= 3 ; $i++) 
                { 
                    $button_text = 'generic_template_button_text_'.$i.'_'.$k;
                    $button_text = isset($$button_text) ? $$button_text : '';
                    $button_type = 'generic_template_button_type_'.$i.'_'.$k;
                    $button_type = isset($$button_type) ? $$button_type : '';
                    $button_postback_id = 'generic_template_button_post_id_'.$i.'_'.$k;
                    $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                    $button_web_url = 'generic_template_button_web_url_'.$i.'_'.$k;
                    $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                    //add an extra query parameter for tracking the subscriber to whom send 
                    if($button_web_url!='')
                        $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");

                    $button_call_us = 'generic_template_button_call_us_'.$i.'_'.$k;
                    $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                    if($button_type == 'post_back')
                    {
                        if($button_text != '' && $button_type != '' && $button_postback_id != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'postback';
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['payload'] = $button_postback_id;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;
                            $single_postback_insert_data = array();
                            $single_postback_insert_data['user_id'] = $this->user_id;
                            $single_postback_insert_data['postback_id'] = $button_postback_id;
                            $single_postback_insert_data['page_id'] = $page_table_id;
                            $single_postback_insert_data['bot_name'] = $bot_name;
                            $postback_insert_data[] = $single_postback_insert_data; 
                        }
                    }
                    if(strpos($button_type,'web_url') !== FALSE)
                    {
                        $button_type_array = explode('_', $button_type);
                        if(isset($button_type_array[2]))
                        {
                            $button_extension = trim($button_type_array[2],'_'); 
                            array_pop($button_type_array);
                        }            
                        else $button_extension = '';
                        $button_type = implode('_', $button_type_array);

                        if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'web_url';
                            if($button_extension != '' && $button_extension == 'birthday'){                                
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['webview_height_ratio'] = 'compact';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                                $button_web_url = base_url('webview_builder/get_birthdate');
                            }
                            else
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['url'] = $button_web_url;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;

                            if($button_extension != '' && $button_extension != 'birthday')
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['webview_height_ratio'] = $button_extension;
                                // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                            }

                            if(!in_array($button_web_url, $white_listed_domain_array))
                            {
                                $need_to_whitelist_array[] = $button_web_url;
                            }
                        }
                    }
                    if($button_type == 'phone_number')
                    {
                        if($button_text != '' && $button_type != '' && $button_call_us != '')
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['type'] = 'phone_number';
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['payload'] = $button_call_us;
                            $reply_bot[$k]['attachment']['payload']['elements'][0]['buttons'][$i-1]['title'] = $button_text;
                        }
                    }
                }
            }

            if($template_type == 'carousel')
            {
                $reply_bot[$k]['template_type'] = $template_type;
                $reply_bot[$k]['attachment']['type'] = 'template';
                $reply_bot[$k]['attachment']['payload']['template_type'] = 'generic';
                for ($j=1; $j <=10 ; $j++) 
                {                                 
                    $carousel_image = 'carousel_image_'.$j.'_'.$k;
                    $carousel_title = 'carousel_title_'.$j.'_'.$k;

                    if(!isset($$carousel_title) || $$carousel_title == '') continue;

                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['title'] = $$carousel_title;
                    $carousel_subtitle = 'carousel_subtitle_'.$j.'_'.$k;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['subtitle'] = $$carousel_subtitle;

                    if(isset($$carousel_image) && $$carousel_image!="")
                    {
                        $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['image_url'] = $$carousel_image;                    
                        $carousel_image_destination_link = 'carousel_image_destination_link_'.$j.'_'.$k;
                        if($$carousel_image_destination_link!="") 
                        {
                            $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['default_action']['type'] = 'web_url';
                            $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['default_action']['url'] = $$carousel_image_destination_link;
                        }

                        if(function_exists('getimagesize') && $$carousel_image!='') 
                        {
                            list($width, $height, $type, $attr) = getimagesize($$carousel_image);
                            if($width==$height)
                                $reply_bot[$k]['attachment']['payload']['image_aspect_ratio'] = 'square';
                        }

                    }
                    // $reply_bot['attachment']['payload']['elements'][$j-1]['default_action']['messenger_extensions'] = true;
                    // $reply_bot['attachment']['payload']['elements'][$j-1]['default_action']['webview_height_ratio'] = 'tall';
                    // $reply_bot['attachment']['payload']['elements'][$j-1]['default_action']['fallback_url'] = $$carousel_image_destination_link;

                    for ($i=1; $i <= 3 ; $i++) 
                    { 
                        $button_text = 'carousel_button_text_'.$j."_".$i.'_'.$k;
                        $button_text = isset($$button_text) ? $$button_text : '';
                        $button_type = 'carousel_button_type_'.$j."_".$i.'_'.$k;
                        $button_type = isset($$button_type) ? $$button_type : '';
                        $button_postback_id = 'carousel_button_post_id_'.$j."_".$i.'_'.$k;
                        $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                        $button_web_url = 'carousel_button_web_url_'.$j."_".$i.'_'.$k;
                        $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                        //add an extra query parameter for tracking the subscriber to whom send 
                        if($button_web_url!='')
                            $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");

                        $button_call_us = 'carousel_button_call_us_'.$j."_".$i.'_'.$k;
                        $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                        if($button_type == 'post_back')
                        {
                            if($button_text != '' && $button_type != '' && $button_postback_id != '')
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['type'] = 'postback';
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['payload'] = $button_postback_id;
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['title'] = $button_text;
                                $single_postback_insert_data = array();
                                $single_postback_insert_data['user_id'] = $this->user_id;
                                $single_postback_insert_data['postback_id'] = $button_postback_id;
                                $single_postback_insert_data['page_id'] = $page_table_id;
                                $single_postback_insert_data['bot_name'] = $bot_name;
                                $postback_insert_data[] = $single_postback_insert_data; 
                            }
                        }
                        if(strpos($button_type,'web_url') !== FALSE)
                        {
                            $button_type_array = explode('_', $button_type);
                            if(isset($button_type_array[2]))
                            {
                                $button_extension = trim($button_type_array[2],'_'); 
                                array_pop($button_type_array);
                            }            
                            else $button_extension = '';
                            $button_type = implode('_', $button_type_array);

                            if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['type'] = 'web_url';
                                if($button_extension != '' && $button_extension == 'birthday'){
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['webview_height_ratio'] = 'compact';
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                                    $button_web_url = base_url('webview_builder/get_birthdate');
                                }
                                else
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['url'] = $button_web_url;
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['title'] = $button_text;

                                if($button_extension != '' && $button_extension != 'birthday')
                                {
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['messenger_extensions'] = 'true';
                                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['webview_height_ratio'] = $button_extension;
                                    // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                                }

                                if(!in_array($button_web_url, $white_listed_domain_array))
                                {
                                    $need_to_whitelist_array[] = $button_web_url;
                                }
                            }
                        }
                        if($button_type == 'phone_number')
                        {
                            if($button_text != '' && $button_type != '' && $button_call_us != '')
                            {
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['type'] = 'phone_number';
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['payload'] = $button_call_us;
                                $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['title'] = $button_text;
                            }
                        }
                    }
                }
            }

            if($template_type == 'list')
            {
                $reply_bot[$k]['template_type'] = $template_type;
                $reply_bot[$k]['attachment']['type'] = 'template';
                $reply_bot[$k]['attachment']['payload']['template_type'] = 'list';

                for ($j=1; $j <=4 ; $j++) 
                {                                 
                    $list_image = 'list_image_'.$j.'_'.$k;
                    if(!isset($$list_image) || $$list_image == '') continue;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['image_url'] = $$list_image;
                    $list_title = 'list_title_'.$j.'_'.$k;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['title'] = $$list_title;
                    $list_subtitle = 'list_subtitle_'.$j.'_'.$k;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['subtitle'] = $$list_subtitle;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['default_action']['type'] = 'web_url';
                    $list_image_destination_link = 'list_image_destination_link_'.$j.'_'.$k;
                    $reply_bot[$k]['attachment']['payload']['elements'][$j-1]['default_action']['url'] = $$list_image_destination_link;
                    
                }

                $button_text = 'list_with_buttons_text_'.$k;
                $button_text = isset($$button_text) ? $$button_text : '';
                $button_type = 'list_with_button_type_'.$k;
                $button_type = isset($$button_type) ? $$button_type : '';
                $button_postback_id = 'list_with_button_post_id_'.$k;
                $button_postback_id = isset($$button_postback_id) ? $$button_postback_id : '';
                $button_web_url = 'list_with_button_web_url_'.$k;
                $button_web_url = isset($$button_web_url) ? $$button_web_url : '';

                 //add an extra query parameter for tracking the subscriber to whom send 
                if($button_web_url!='')
                    $button_web_url=add_query_string_to_url($button_web_url,"subscriber_id","#SUBSCRIBER_ID_REPLACE#");

                $button_call_us = 'list_with_button_call_us_'.$k;
                $button_call_us = isset($$button_call_us) ? $$button_call_us : '';
                if($button_type == 'post_back')
                {
                    if($button_text != '' && $button_type != '' && $button_postback_id != '')
                    {
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['type'] = 'postback';
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['payload'] = $button_postback_id;
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['title'] = $button_text;
                        $single_postback_insert_data = array();
                        $single_postback_insert_data['user_id'] = $this->user_id;
                        $single_postback_insert_data['postback_id'] = $button_postback_id;
                        $single_postback_insert_data['page_id'] = $page_table_id;
                        $single_postback_insert_data['bot_name'] = $bot_name;
                        $postback_insert_data[] = $single_postback_insert_data; 
                    }
                }
                if(strpos($button_type,'web_url') !== FALSE)
                {
                    $button_type_array = explode('_', $button_type);
                    if(isset($button_type_array[2]))
                    {
                        $button_extension = trim($button_type_array[2],'_'); 
                        array_pop($button_type_array);
                    }            
                    else $button_extension = '';
                    $button_type = implode('_', $button_type_array);

                    if($button_text != '' && $button_type != '' && ($button_web_url != '' || $button_extension != ''))
                    {
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['type'] = 'web_url';
                        if($button_extension != '' && $button_extension == 'birthday'){
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['messenger_extensions'] = 'true';
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['webview_height_ratio'] = 'compact';
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['url'] = base_url('webview_builder/get_birthdate?subscriber_id=#SUBSCRIBER_ID_REPLACE#');
                            $button_web_url = base_url('webview_builder/get_birthdate');
                        }
                        else
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['url'] = $button_web_url;
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['title'] = $button_text;

                        if($button_extension != '' && $button_extension != 'birthday')
                        {
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['messenger_extensions'] = 'true';
                            $reply_bot[$k]['attachment']['payload']['buttons'][0]['webview_height_ratio'] = $button_extension;
                            // $reply_bot[$k]['attachment']['payload']['buttons'][$i-1]['fallback_url'] = $button_web_url;
                        }

                        if(!in_array($button_web_url, $white_listed_domain_array))
                        {
                            $need_to_whitelist_array[] = $button_web_url;
                        }
                    }
                }
                if($button_type == 'phone_number')
                {
                    if($button_text != '' && $button_type != '' && $button_call_us != '')
                    {
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['type'] = 'phone_number';
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['payload'] = $button_call_us;
                        $reply_bot[$k]['attachment']['payload']['buttons'][0]['title'] = $button_text;
                    }
                }


            }

            if(isset($reply_bot[$k]))
            {     
                $typing_on_settings = 'typing_on_enable_'.$k;
                if(!isset($$typing_on_settings)) $typing_on_settings = 'off';
                else $typing_on_settings = $$typing_on_settings;

                $delay_in_reply = 'delay_in_reply_'.$k;
                if(!isset($$delay_in_reply)) $delay_in_reply = 0;
                else $delay_in_reply = $$delay_in_reply;

                $reply_bot[$k]['typing_on_settings'] = $typing_on_settings;
                $reply_bot[$k]['delay_in_reply'] = $delay_in_reply;

                $bot_message[$k]['recipient'] = array('id'=>'replace_id');
                $bot_message[$k]['message'] = $reply_bot[$k];
            }

        }

        $reply_bot_filtered = array();
        $m=0;
        foreach ($bot_message as $value) {
            $m++;
            $reply_bot_filtered[$m] = $value;
        }

        // domain white list section start
        $this->load->library("fb_rx_login"); 
        $domain_whitelist_insert_data = array();
        foreach($need_to_whitelist_array as $value)
        {
            
            $domain_only_whitelist= get_domain_only_with_http($value);
            if(in_array($domain_only_whitelist, $white_listed_domain_array)) continue; 

            $response=$this->fb_rx_login->domain_whitelist($page_access_token,$domain_only_whitelist);
            if($response['status'] != '0')
            {
                $temp_data = array();
                $temp_data['user_id'] = $this->user_id;
                $temp_data['messenger_bot_user_info_id'] = $facebook_rx_fb_user_info_id;
                $temp_data['page_id'] = $page_table_id;
                $temp_data['domain'] = $domain_only_whitelist;
                $temp_data['created_at'] = date("Y-m-d H:i:s");
                $domain_whitelist_insert_data[] = $temp_data;
            }
        }
        if(!empty($domain_whitelist_insert_data))
            $this->db->insert_batch('messenger_bot_domain_whitelist',$domain_whitelist_insert_data);
        // domain white list section end

        $insert_data['template_jsoncode'] = json_encode($reply_bot_filtered,true);
        $insert_data['user_id'] = $this->user_id;
        $this->basic->update_data('messenger_bot_postback',array("id" => $id),$insert_data);

        $existing_data = $this->basic->get_data('messenger_bot_postback',array('where'=>array('id'=>$id)));
        $this->basic->update_data('messenger_bot',array('id'=>$existing_data[0]['messenger_bot_table_id']),array('message'=>$existing_data[0]['template_jsoncode'],"broadcaster_labels"=>$new_label_ids,'bot_name'=>$existing_data[0]['template_name'],'drip_campaign_id'=>$existing_data[0]['drip_campaign_id']));

        $messenger_bot_table_id = $existing_data[0]['messenger_bot_table_id'];  

        $existing_postback_ids_array = array();
        $existing_postback_ids = $this->basic->get_data('messenger_bot_postback',array('where'=>array('page_id'=>$page_table_id,'use_status'=>'1')),array('postback_id'));


        $this->basic->delete_data('messenger_bot_postback',array('page_id'=>$page_table_id,'template_id'=>$id,'use_status'=>'0','is_template'=>'0','inherit_from_template'=>'1'));
        if(!empty($existing_postback_ids))
        {
            foreach($existing_postback_ids as $value)
            {
                array_push($existing_postback_ids_array, strtoupper($value['postback_id']));
            }
        }


        $postback_insert_data_modified = array();
        $m=0;
        $unique_postbacks = array();
        foreach($postback_insert_data as $value)
        {
            if(in_array(strtoupper($value['postback_id']), $unique_postbacks)) continue;
            if(in_array(strtoupper($value['postback_id']), $existing_postback_ids_array)) continue;
            if($value['postback_id'] == 'UNSUBSCRIBE_QUICK_BOXER' || $value['postback_id'] == 'RESUBSCRIBE_QUICK_BOXER' || $value['postback_id'] == 'YES_START_CHAT_WITH_HUMAN' || $value['postback_id'] == 'YES_START_CHAT_WITH_BOT') continue;
            $postback_insert_data_modified[$m]['user_id'] = $value['user_id'];
            $postback_insert_data_modified[$m]['postback_id'] = $value['postback_id'];
            $postback_insert_data_modified[$m]['page_id'] = $value['page_id'];
            $postback_insert_data_modified[$m]['bot_name'] = $value['bot_name'];
            $postback_insert_data_modified[$m]['messenger_bot_table_id'] = $messenger_bot_table_id;
            $postback_insert_data_modified[$m]['inherit_from_template'] = '1';
            $postback_insert_data_modified[$m]['template_id'] = $id;
            array_push($unique_postbacks, $value['postback_id']);
            $m++;
        }


        // if($keyword_type == 'post-back' && !empty($keywordtype_postback_id))
        // {            
        //     $this->db->where_in("postback_id", $keywordtype_postback_id);
        //     $this->db->update('messenger_bot_postback', array('use_status' => '1'));
        // }
        
        if(!empty($postback_insert_data_modified))
        $this->db->insert_batch('messenger_bot_postback',$postback_insert_data_modified);

        $this->session->set_flashdata('bot_update_success',1);
        echo json_encode(array("status" => "1", "message" =>$this->lang->line("Template been updated successfully.")));        

    }

    public function ajax_delete_template_info()
    {
        $id = $this->input->post('table_id',true);
        $postback_info = $this->basic->get_data('messenger_bot_postback',array('where'=>array('id'=>$id,'user_id'=>$this->user_id)));
        if(empty($postback_info))
        {
            echo "no_match";
            exit;
        }
        $postback_id = $postback_info[0]['postback_id'];
        $search_content = '%"payload":"'.$postback_id.'"%';
        $bot_info = $this->basic->get_data('messenger_bot',array('where'=>array('message like'=>$search_content)));
        
        if(!empty($bot_info))
        {
            $response = "<div class='text-center alert alert-danger'>".$this->lang->line('You can not delete this template because it is being used in the following bots. First make sure that these templates are free to delete. You can do this by editing or deleting the following bots.')."</div><br>";
            $response.= '
                 <script>
                     $(document).ready(function() {
                         $("#need_to_delete_bots").DataTable();
                     }); 
                  </script>
                  <style>
                     .dataTables_filter
                      {
                         float : right;
                      }
                  </style>
                 <div class="table-responsive">
                 <table id="need_to_delete_bots" class="table table-bordered">
                     <thead>
                         <tr>
                             <th>'.$this->lang->line("SN.").'</th>
                             <th>'.$this->lang->line("Bot Name").'</th>
                             <th>'.$this->lang->line("Kyeword").'</th>
                             <th>'.$this->lang->line("Keyword Type").'</th>
                             <th class="text-center">'.$this->lang->line("Actions").'</th>
                         </tr>
                     </thead>
                     <tbody>';
            $sn = 0;
            $value = array();
            foreach($bot_info as $value)
            {
                $sn++;
                $bot_id = $value['id'];
                $url = '#';
                if($value['is_template'] == '1')
                {
                    $child_postback_info = $this->basic->get_data('messenger_bot_postback',array('where'=>array('messenger_bot_table_id'=>$value['id'])));

                    $postback_table_id = 0;
                    if(isset($child_postback_info[0]['id'])) $postback_table_id = $child_postback_info[0]['id'];
                    $url = base_url('messenger_bot/edit_template/').$postback_table_id;
                }
                else
                    $url = base_url('messenger_bot/edit_bot/').$bot_id.'/postback';
                $response .= '<tr>
                            <td>'.$sn.'</td>
                            <td>'.$value['bot_name'].'</td>
                            <td>'.$value['keywords'].'</td>
                            <td>'.$value['keyword_type'].'</td>
                            <td class="text-center"><a class="btn btn-outline-warning" title="'.$this->lang->line("edit").'" target="_BLANK" href="'.$url.'"><i class="fa fa-edit"></i></a></td>
                        </tr>';
            }
            $response .= '</tbody>
                 </table></div>';
            echo $response;
        }
        else
        {

            $this->basic->delete_data('messenger_bot_postback',array('id'=>$id));
            $this->basic->delete_data('messenger_bot_postback',array('template_id'=>$id,'is_template'=>'0'));
            $this->basic->delete_data('messenger_bot',array('postback_id'=>$postback_id));
            
            // not needed now as child postback also became template
            // $child_postback_info = $this->basic->get_data("messenger_bot_postback",array("where"=>array("template_id"=>$id)),array('postback_id'));
            // foreach($child_postback_info as $value)
            // {
            //     $this->basic->delete_data('messenger_bot',array('postback_id'=>$value['postback_id'])); 
            // }
            echo "success";
        }
    }
    
    public function upload_image_only()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') exit();
        $ret=array();
        $folder_path = FCPATH."upload/image";
        if (!file_exists($folder_path)) {
            mkdir($folder_path, 0777, true);
        }
        $output_dir = FCPATH."upload/image/".$this->user_id;
        if (!file_exists($output_dir)) {
            mkdir($output_dir, 0777, true);
        }
        if (isset($_FILES["myfile"])) {
            $error =$_FILES["myfile"]["error"];
            $post_fileName =$_FILES["myfile"]["name"];
            $post_fileName_array=explode(".", $post_fileName);
            $ext=array_pop($post_fileName_array);
            $filename=implode('.', $post_fileName_array);
            $filename="image_".$this->user_id."_".time().substr(uniqid(mt_rand(), true), 0, 6).".".$ext;
            $allow=".jpg,.jpeg,.png,.gif";
            $allow=str_replace('.', '', $allow);
            $allow=explode(',', $allow);
            if(!in_array(strtolower($ext), $allow)) 
            {
                echo json_encode("Are you kidding???");
                exit();
            }

            move_uploaded_file($_FILES["myfile"]["tmp_name"], $output_dir.'/'.$filename);
            $ret[]= $filename;
            echo json_encode($filename);
        }
    }

    public function delete_uploaded_file() // deletes the uploaded video to upload another one
    {
        if(!$_POST) exit();
        $output_dir = FCPATH."upload/image/".$this->user_id."/";
        if(isset($_POST["op"]) && $_POST["op"] == "delete" && isset($_POST['name']))
        {
             $fileName =$_POST['name'];
             $fileName=str_replace("..",".",$fileName); //required. if somebody is trying parent folder files 
             $filePath = $output_dir. $fileName;
             if (file_exists($filePath)) 
             {
                unlink($filePath);
             }
        }
    }
    public function upload_live_video()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') exit();
        $ret=array();
        $output_dir = FCPATH."upload/video";
        $folder_path = FCPATH."upload/video";
        if (!file_exists($folder_path)) {
            mkdir($folder_path, 0777, true);
        }
        if (isset($_FILES["myfile"])) {
            $error =$_FILES["myfile"]["error"];
            $post_fileName =$_FILES["myfile"]["name"];
            $post_fileName_array=explode(".", $post_fileName);
            $ext=array_pop($post_fileName_array);
            $filename=implode('.', $post_fileName_array);
            $filename="video_".$this->user_id."_".time().substr(uniqid(mt_rand(), true), 0, 6).".".$ext;
            $allow=".mov,.mpeg4,.mp4,.avi,.wmv,.mpegps,.flv,.3gpp,.webm";
            $allow=str_replace('.', '', $allow);
            $allow=explode(',', $allow);
            if(!in_array(strtolower($ext), $allow)) 
            {
                echo json_encode("Are you kidding???");
                exit();
            }
            move_uploaded_file($_FILES["myfile"]["tmp_name"], $output_dir.'/'.$filename);
            $ret[]= $filename;
            $this->session->set_userdata("go_live_video_file_path_name", $output_dir.'/'.$filename);
            $this->session->set_userdata("go_live_video_filename", $filename); 
            echo json_encode($filename);
        }
    }

    public function delete_uploaded_live_file() // deletes the uploaded video to upload another one
    {
        if(!$_POST) exit();
        $output_dir = FCPATH."upload/video/";
        if(isset($_POST["op"]) && $_POST["op"] == "delete" && isset($_POST['name']))
        {
             $fileName =$_POST['name'];
             $fileName=str_replace("..",".",$fileName); //required. if somebody is trying parent folder files 
             $filePath = $output_dir. $fileName;
             if (file_exists($filePath)) 
             {
                unlink($filePath);
             }
        }
    }
    
    // audio/pdf/doc file upload section
    public function upload_audio_file()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') exit();
        $ret=array();
        $output_dir = FCPATH."upload/audio";
        $folder_path = FCPATH."upload/audio";
        if (!file_exists($folder_path)) {
            mkdir($folder_path, 0777, true);
        }
        if (isset($_FILES["myfile"])) {
            $error =$_FILES["myfile"]["error"];
            $post_fileName =$_FILES["myfile"]["name"];
            $post_fileName_array=explode(".", $post_fileName);
            $ext=array_pop($post_fileName_array);
            $filename=implode('.', $post_fileName_array);
            $filename="audio_".$this->user_id."_".time().substr(uniqid(mt_rand(), true), 0, 6).".".$ext;
            $allow=".amr,.mp3,.wav,.WAV,.MP3,.AMR";
            $allow=str_replace('.', '', $allow);
            $allow=explode(',', $allow);
            if(!in_array(strtolower($ext), $allow)) 
            {
                echo json_encode("Are you kidding???");
                exit();
            }
            move_uploaded_file($_FILES["myfile"]["tmp_name"], $output_dir.'/'.$filename);
            $ret[]= $filename;
            $this->session->set_userdata("go_live_video_file_path_name", $output_dir.'/'.$filename);
            $this->session->set_userdata("go_live_video_filename", $filename); 
            echo json_encode($filename);
        }
    }

    public function delete_audio_file() // deletes the uploaded video to upload another one
    {
        if(!$_POST) exit();
        $output_dir = FCPATH."upload/audio/";
        if(isset($_POST["op"]) && $_POST["op"] == "delete" && isset($_POST['name']))
        {
             $fileName =$_POST['name'];
             $fileName=str_replace("..",".",$fileName); //required. if somebody is trying parent folder files 
             $filePath = $output_dir. $fileName;
             if (file_exists($filePath)) 
             {
                unlink($filePath);
             }
        }
    }

    public function upload_general_file()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') exit();
        $ret=array();
        $output_dir = FCPATH."upload/file";
        $folder_path = FCPATH."upload/file";
        if (!file_exists($folder_path)) {
            mkdir($folder_path, 0777, true);
        }
        if (isset($_FILES["myfile"])) {
            $error =$_FILES["myfile"]["error"];
            $post_fileName =$_FILES["myfile"]["name"];
            $post_fileName_array=explode(".", $post_fileName);
            $ext=array_pop($post_fileName_array);
            $filename=implode('.', $post_fileName_array);
            $filename="file_".$this->user_id."_".time().substr(uniqid(mt_rand(), true), 0, 6).".".$ext;
            $allow=".doc,.docx,.pdf,.txt,.ppt,.pptx,.xls,.xlsx";
            $allow=str_replace('.', '', $allow);
            $allow=explode(',', $allow);
            if(!in_array(strtolower($ext), $allow)) 
            {
                echo json_encode("Are you kidding???");
                exit();
            }
            move_uploaded_file($_FILES["myfile"]["tmp_name"], $output_dir.'/'.$filename);
            $ret[]= $filename;
            $this->session->set_userdata("go_live_video_file_path_name", $output_dir.'/'.$filename);
            $this->session->set_userdata("go_live_video_filename", $filename); 
            echo json_encode($filename);
        }
    }

    public function delete_general_file() // deletes the uploaded video to upload another one
    {
        if(!$_POST) exit();
        $output_dir = FCPATH."upload/file/";
        if(isset($_POST["op"]) && $_POST["op"] == "delete" && isset($_POST['name']))
        {
             $fileName =$_POST['name'];
             $fileName=str_replace("..",".",$fileName); //required. if somebody is trying parent folder files 
             $filePath = $output_dir. $fileName;
             if (file_exists($filePath)) 
             {
                unlink($filePath);
             }
        }
    }
    //===========================ENABLE DISABLE STARTED Button====================


    public function get_started_welcome_message()
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(200,$this->module_access)) exit();
        if(!$_POST) exit();

        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                echo json_encode(array('status'=>'0','message'=>'This function is disabled from admin account in this demo!!'));
                exit();
            }
        }

        $page_id=$this->input->post('table_id');
        $welcome_message=$this->input->post('welcome_message');
        $started_button_enabled=$this->input->post('started_button_enabled');
        $this->load->library("fb_rx_login");

        $page_data=$this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_id)));
        $page_access_token=isset($page_data[0]["page_access_token"]) ? $page_data[0]["page_access_token"] : "";
       
        if($started_button_enabled=='1')
        {
            $response=$this->fb_rx_login->add_get_started_button($page_access_token);
            if(!isset($response['error']))
            {
                $response2=$this->fb_rx_login->set_welcome_message($page_access_token,$welcome_message);
                if(!isset($response2['error']))
                {
                   $this->basic->update_data("facebook_rx_fb_page_info",array("id"=>$page_id),array("started_button_enabled"=>$started_button_enabled,"welcome_message"=>$welcome_message));
                   echo json_encode(array('status'=>'1','message'=>$this->lang->line("Get started button has been enabled successfully.")));
                }
                else
                {
                    $error_msg2=isset($response2['error']['message'])?$response2['error']['message']:$this->lang->line("something went wrong, please try again.");
                    echo json_encode(array('status'=>'0','message'=>$error_msg2));
                }
            }
            else
            {
                $error_msg=isset($response['error']['message'])?$response['error']['message']:$this->lang->line("something went wrong, please try again.");
                echo json_encode(array('status'=>'0','message'=>$error_msg));
            }
        }
        else
        {
            $response=$this->fb_rx_login->delete_get_started_button($page_access_token);
            if(!isset($response['error']))
            {
                $response2=$this->fb_rx_login->unset_welcome_message($page_access_token);
                if(!isset($response2['error']))
                {
                   $this->basic->update_data("facebook_rx_fb_page_info",array("id"=>$page_id),array("started_button_enabled"=>$started_button_enabled,"welcome_message"=>""));
                   echo json_encode(array('status'=>'1','message'=>$this->lang->line("Get started button has been disabled successfully.")));
                }
                else
                {
                    $error_msg2=isset($response2['error']['message'])?$response2['error']['message']:$this->lang->line("something went wrong, please try again.");
                    echo json_encode(array('status'=>'0','message'=>$error_msg2));
                }
            }
            else
            {
                $error_msg=isset($response['error']['message'])?$response['error']['message']:$this->lang->line("something went wrong, please try again.");
                echo json_encode(array('status'=>'0','message'=>$error_msg));
            }

        }
    }

    public function export_bot()
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(257,$this->module_access)) exit();
        if(!$_POST) exit();
        
        $page_id=$this->input->post('export_id');
        $template_name=$this->input->post('template_name',true);
        $template_description=$this->input->post('template_description',true);
        $template_preview_image=$this->input->post('template_preview_image',true);
        $template_access=$this->input->post('template_access',true);
        $allowed_package_ids=$this->input->post('allowed_package_ids',true);

        $template_preview_image=str_replace(base_url('upload/image/'.$this->user_id.'/'), '', $template_preview_image);

        if(!is_array($allowed_package_ids) || $template_access=='private')  $allowed_package_ids=array();

        $get_bot_settings=$this->get_bot_settings($page_id);
        $savedata=json_encode($get_bot_settings);

        if($this->session->userdata('user_type') != 'Admin') $template_access='private';

        $this->basic->insert_data("messenger_bot_saved_templates",array("template_name"=>$template_name,"savedata"=>$savedata,"saved_at"=>date("Y-m-d H:i:s"),"user_id"=>$this->user_id,"template_access"=>$template_access,"description"=>$template_description,"preview_image"=>$template_preview_image,"allowed_package_ids"=>implode(',', $allowed_package_ids)));
        $insert_id=$this->db->insert_id();

        $message="<div class='alert alert-info text-center'><i class='fa fa-check-circle'></i> ".$this->lang->line("Bot template has been saved to database successfully.")."</div><br><a class='btn-block btn btn-outline-info' target='_BLANK' href='".base_url('messenger_bot_export_import/saved_templates')."'><i class='fa fa-save'></i> ".$this->lang->line("My Saved Templates")."</a><a target='_BLANK' class='btn-block btn btn-outline-primary' href='".base_url('messenger_bot/export_bot_download/').$insert_id."'><i class='fa fa-file-download'></i> ".$this->lang->line("Download Template")."</a>";
        echo json_encode(array('status'=>'0','message'=>$message));
    }

    public function export_bot_download($id=0)
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(257,$this->module_access)) exit();
        if($id==0) exit();

        $save_data=$this->basic->get_data("messenger_bot_saved_templates",array("where"=>array("id"=>$id)));
        if(!isset($save_data[0])) exit();

        $template_name=isset($save_data[0]['template_name'])?$save_data[0]['template_name']:"";
        $savedata=isset($save_data[0]['savedata'])?$save_data[0]['savedata']:"";

        $template_name = preg_replace("/[^a-z0-9]+/i", "", $template_name);
        $filename=$template_name.".json";
        $f = fopen('php://memory', 'w'); 
        fwrite($f, $savedata);
        fseek($f, 0);
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="'.$filename.'";');
        fpassthru($f);  
    }

    public function upload_json_template()
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(257,$this->module_access)) exit();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') exit();

        $output_dir = FCPATH."upload";
        if (!file_exists($output_dir)) {
            mkdir($output_dir, 0755, true);
        }
        if (isset($_FILES["myfile"])) 
        {
            $error =$_FILES["myfile"]["error"];
            $post_fileName =$_FILES["myfile"]["name"];
            $post_fileName_array=explode(".", $post_fileName);
            $ext=array_pop($post_fileName_array);
            $filename=implode('.', $post_fileName_array);
            $filename="json_template_".$this->user_id."_".time().substr(uniqid(mt_rand(), true), 0, 6).".".$ext;


            $allow=".json";
            $allow=str_replace('.', '', $allow);
            $allow=explode(',', $allow);
            if(!in_array(strtolower($ext), $allow)) 
            {
                echo json_encode("Are you kidding???");
                exit();
            }
            move_uploaded_file($_FILES["myfile"]["tmp_name"], $output_dir.'/'.$filename);
            echo json_encode($filename);
        }
    }

    public function upload_json_template_delete() // deletes the uploaded video to upload another one
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(257,$this->module_access)) exit();
        if(!$_POST) exit();
        $output_dir = FCPATH."upload/";
        if(isset($_POST["op"]) && $_POST["op"] == "delete" && isset($_POST['name']))
        {
             $fileName =$_POST['name'];
             $fileName=str_replace("..",".",$fileName); //required. if somebody is trying parent folder files 
             $filePath = $output_dir. $fileName;
             if (file_exists($filePath)) 
             {
                unlink($filePath);
             }
        }
    }


    public function import_bot_check()
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(257,$this->module_access)) exit();
        if(!$_POST) exit();

        $template_id=$this->input->post('template_id',true);
        $page_id=$this->input->post('import_id',true);
        $json_upload_input=$this->input->post('json_upload_input',true);

        if($template_id=="" && $json_upload_input=="")
        {
            echo json_encode(array('json_upload_input'=>$json_upload_input,'page_id'=>$page_id,'template_id'=>$template_id,'status'=>'0','message'=>$this->lang->line("No template found also no json found.")));
            exit();
        }

        if($template_id!="" && $json_upload_input!="")
        {
            echo json_encode(array('json_upload_input'=>$json_upload_input,'page_id'=>$page_id,'template_id'=>$template_id,'status'=>'0','message'=>$this->lang->line("You can not choose both template and upload file at the same time.")));
            exit();
        }

        if($json_upload_input!="")
        {
            $path=FCPATH.'upload/'.$json_upload_input;
            $array='';
            if(file_exists($path))
            {
                $json=file_get_contents($path);
                $array=json_decode($json,true);
            }
            if(!is_array($array))
            {
                 echo json_encode(array('json_upload_input'=>$json_upload_input,'page_id'=>$page_id,'template_id'=>$template_id,'status'=>'0','message'=>$this->lang->line("Uploaded json is not a valid template json.")));
                 exit();
            }
        }

        if($this->basic->is_exist("messenger_bot",array("page_id"=>$page_id)) || $this->basic->is_exist("messenger_bot_postback",array("page_id"=>$page_id)) || $this->basic->is_exist("messenger_bot_persistent_menu",array("page_id"=>$page_id)) )
        {
            echo json_encode(array('json_upload_input'=>$json_upload_input,'page_id'=>$page_id,'template_id'=>$template_id,'status'=>'1','message'=>$this->lang->line("Template has not been imported because there are existing bot settings or persistent menu settings found. Importing this template will delete all your previous bot settings, persistent menu settings as well as get started welcome screen message etc. Do you want to delete all your previous settings for this page and import this template?")));
            exit();
        }
        
        echo json_encode(array('json_upload_input'=>$json_upload_input,'page_id'=>$page_id,'template_id'=>$template_id,'status'=>'1','message'=>$this->lang->line("System has finished data checking and ready to import new template settings. Are you sure that you want to import this template?")));     
     }

    public function import_bot()
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(257,$this->module_access)) exit();
        if(!$_POST) exit();

        $template_id=$this->input->post('template_id',true);
        $page_id=$this->input->post('page_id',true);
        $json_upload_input=$this->input->post('json_upload_input',true);


        $pagedata=$this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_id,"user_id"=>$this->user_id)));       
        if(!isset($pagedata[0]))
        {
            echo json_encode(array('status'=>'0','message'=>$this->lang->line("Page not found")));
            exit();
        }

        $jsondata='';
        if($template_id!="")
        {
            $get_bot_settings=$this->basic->get_data("messenger_bot_saved_templates",array("where"=>array("id"=>$template_id)));        
            if(!isset($get_bot_settings[0]))
            {
                echo json_encode(array('status'=>'0','message'=>$this->lang->line("Template not found")));
                exit();
            }
            $jsondata=$get_bot_settings[0]['savedata'];
        }
        else
        {
            $path=FCPATH.'upload/'.$json_upload_input;
            if(file_exists($path))
            {
                $jsondata=file_get_contents($path);
                @unlink($path); 
            }          
        }

        $this->db->db_debug = FALSE; //disable debugging for queries
        $this->db->trans_start();

        // deleting current settings so that we can import new settings
        $this->basic->delete_data("messenger_bot",array("page_id"=>$page_id,"user_id"=>$this->user_id));
        $this->basic->delete_data("messenger_bot_postback",array("page_id"=>$page_id,"user_id"=>$this->user_id));
        $this->basic->delete_data("messenger_bot_persistent_menu",array("page_id"=>$page_id));
        // -------------------------------------------------------------

        $savedata=json_decode($jsondata,true);        
        $fb_page_id=isset($pagedata[0]['page_id'])?$pagedata[0]['page_id']:"";
        $page_access_token=isset($pagedata[0]['page_access_token'])?$pagedata[0]['page_access_token']:"";

        $bot_settings=isset($savedata['bot_settings'])?$savedata['bot_settings']:array();
        $empty_postback_settings=isset($savedata['empty_postback_settings'])?$savedata['empty_postback_settings']:array();
        $persistent_menu_settings=isset($savedata['persistent_menu_settings'])?$savedata['persistent_menu_settings']:array();
        $bot_general_info=isset($savedata['bot_general_info'])?$savedata['bot_general_info']:array();

        // inserting messenger_bot + messenger_bot_postback data        
        foreach ($bot_settings as $key => $value)
        {
            $bot_info=isset($value['message_bot'])?$value['message_bot']:array();

            $messenger_bot_row=array
            (
                "user_id"=>$this->user_id,
                "page_id"=>$page_id,
                "fb_page_id"=>$fb_page_id
            );
            foreach ($bot_info as $key2 => $value2) 
            {
              if($key2=="postback_template_info") continue;
              $messenger_bot_row[$key2]=$value2;
            }           

            $this->basic->insert_data("messenger_bot",$messenger_bot_row);
            $messenger_bot_insert_id=$this->db->insert_id();      

            $postback_template_info=isset($value['message_bot']['postback_template_info'])?$value['message_bot']['postback_template_info']:array(); // getting postback data
            foreach ($postback_template_info as $key2 => $value2) 
            {               
                $messenger_bot_postback_row=array
                (
                    "user_id"=>$this->user_id,
                    "page_id"=>$page_id
                );
                foreach ($value2 as $key3 => $value3)
                {
                   if($key3=="postback_child") continue;
                   $messenger_bot_postback_row[$key3]=$value3;
                }   
                $messenger_bot_postback_row['messenger_bot_table_id']=$messenger_bot_insert_id;
                $messenger_bot_postback_row['template_id']=0;

                $this->basic->insert_data("messenger_bot_postback",$messenger_bot_postback_row);
                $messenger_bot_postback_insert_id=$this->db->insert_id();  

                $postback_template_info2=isset($value2['postback_child'])?$value2['postback_child']:array(); // getting postback data level2

                
                foreach ($postback_template_info2 as $key3 => $value3) 
                {
                   $messenger_bot_postback_row2=array
                   (
                        "user_id"=>$this->user_id,
                        "page_id"=>$page_id
                   );
                   foreach ($value3 as $key4 => $value4) 
                   {
                     $messenger_bot_postback_row2[$key4]=$value4;
                   }
                   $messenger_bot_postback_row2['messenger_bot_table_id']=0;
                   $messenger_bot_postback_row2['template_id']=$messenger_bot_postback_insert_id;
                   $this->basic->insert_data("messenger_bot_postback",$messenger_bot_postback_row2);
                }
                
            }              
        }
        // ----------------------------------------------------------------


        // inserting empty postback
        foreach ($empty_postback_settings as $key => $value) 
        {           
            $messenger_bot_postback_empty_row=array
            (
                "user_id"=>$this->user_id,
                "page_id"=>$page_id
            );
            foreach ($value as $key2 => $value2)
            {
               $messenger_bot_postback_empty_row[$key2]=$value2;
            }   
            $messenger_bot_postback_empty_row['template_id']=0;
            $this->basic->insert_data("messenger_bot_postback",$messenger_bot_postback_empty_row);            
        }
        //-----------------------------------------------------------------



        // inserting persistent menu
        if($this->session->userdata('user_type') == 'Admin' || in_array(197,$this->module_access))
        {
            foreach ($persistent_menu_settings as $key => $value) 
            {
                $persistent_menu_row=array();
                foreach ($value as $key2 => $value2) 
                {
                   $persistent_menu_row[$key2]=$value2;
                }
                $persistent_menu_row['page_id']=$page_id;
                $persistent_menu_row['user_id']=$this->user_id;
                unset($persistent_menu_row['id']);
                $this->basic->insert_data("messenger_bot_persistent_menu",$persistent_menu_row); 
            }
        }
        //-----------------------------------------------------------------

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            echo "<div class='alert alert-danger text-center'><i class='fa fa-remove'></i> ".$this->lang->line("Import was unsuccessful. Database error occured during importing template.")."</div>";
            exit();
        }

        $welcome_message=isset($bot_general_info['welcome_message'])?$bot_general_info['welcome_message']:"";
        $started_button_enabled=isset($bot_general_info['started_button_enabled'])?$bot_general_info['started_button_enabled']:"0";
        $persistent_enabled=isset($bot_general_info['persistent_enabled'])?$bot_general_info['persistent_enabled']:"0";
        $enable_mark_seen=isset($bot_general_info['enable_mark_seen'])?$bot_general_info['enable_mark_seen']:"0";
        $enbale_type_on=isset($bot_general_info['enbale_type_on'])?$bot_general_info['enbale_type_on']:"0";
        $reply_delay_time=isset($bot_general_info['reply_delay_time'])?$bot_general_info['reply_delay_time']:"0";

        $this->load->library("fb_rx_login"); 

        //enabling get started
        $error_msg_array=array();
        $success_msg_array=array();
        if($started_button_enabled=='1')
        {
            $response=$this->fb_rx_login->add_get_started_button($page_access_token);
            if(!isset($response['error']))
            {
                $response2=$this->fb_rx_login->set_welcome_message($page_access_token,$welcome_message);
                if(!isset($response2['error']))
                {
                   $this->basic->update_data("facebook_rx_fb_page_info",array("id"=>$page_id,"user_id"=>$this->user_id),array("started_button_enabled"=>"1","welcome_message"=>$welcome_message));
                   $success_msg=$this->lang->line("Successful");
                   $success_msg=$this->lang->line("Enable Get Started")." : ".$success_msg;
                   array_push($success_msg_array, $success_msg);
                }
                else
                {
                    $error_msg=isset($response2['error']['message'])?$response2['error']['message']:$this->lang->line("something went wrong, please try again.");
                    $error_msg=$this->lang->line("Enable Get Started")." : ".$error_msg;
                    array_push($error_msg_array, $error_msg);
                }
            }
            else
            {
                $error_msg=isset($response['error']['message'])?$response['error']['message']:$this->lang->line("something went wrong, please try again.");
                $error_msg=$this->lang->line("Enable Get Started")." : ".$error_msg;
                array_push($error_msg_array, $error_msg);
            }
        }
        else
        {
            $response=$this->fb_rx_login->delete_get_started_button($page_access_token);
            if(!isset($response['error']))
            {
                $response2=$this->fb_rx_login->unset_welcome_message($page_access_token);
                if(!isset($response2['error']))
                {
                   $this->basic->update_data("facebook_rx_fb_page_info",array("id"=>$page_id,"user_id"=>$this->user_id),array("started_button_enabled"=>"0","welcome_message"=>""));
                   $success_msg=$this->lang->line("Successful");
                   $success_msg=$this->lang->line("Disable Get Started")." : ".$success_msg;
                   array_push($success_msg_array, $success_msg);
                }
                else
                {
                    $error_msg=isset($response2['error']['message'])?$response2['error']['message']:$this->lang->line("something went wrong, please try again.");
                    $error_msg=$this->lang->line("Disable Get Started")." : ".$error_msg;
                    array_push($error_msg_array, $error_msg);
                }
            }
            else
            {
                $error_msg=isset($response['error']['message'])?$response['error']['message']:$this->lang->line("something went wrong, please try again.");
                $error_msg=$this->lang->line("Disable Get Started")." : ".$error_msg;
                array_push($error_msg_array, $error_msg);
            }
        }
        //-----------------------------------------------------------------


        // Publishing persistent menu
        if($this->session->userdata('user_type') == 'Admin' || in_array(197,$this->module_access))
        {
            if($persistent_enabled=='1')
            {
                $json_array=array();
                $menu_data=$this->basic->get_data("messenger_bot_persistent_menu",array("where"=>array("page_id"=>$page_id,"user_id"=>$this->user_id)));
                foreach ($menu_data as $key => $value) 
                {
                    $temp=json_decode($value["item_json"],true);
                    $json_array["persistent_menu"][]=$temp;
                }            
                $json=json_encode($json_array);          
                $response=$this->fb_rx_login->add_persistent_menu($page_access_token,$json);            
                if(!isset($response['error']))
                {                
                    $this->basic->update_data('facebook_rx_fb_page_info',array("id"=>$page_id,'user_id'=>$this->user_id),array("persistent_enabled"=>'1'));
                    $success_msg=$this->lang->line("Successful");
                    $success_msg=$this->lang->line("Persistent Menu Publish")." : ".$success_msg;
                    array_push($success_msg_array, $success_msg);
                }
                else
                {
                    $error_msg=isset($response['error']['message'])?$response['error']['message']:$this->lang->line("something went wrong, please try again.");
                    $error_msg=$this->lang->line("Persistent Menu Publish")." : ".$error_msg;
                    array_push($error_msg_array, $error_msg);
                }
            }
            else
            {         
                $response=$this->fb_rx_login->delete_persistent_menu($page_access_token);            
                if(!isset($response['error']))
                {                
                    $this->basic->update_data('facebook_rx_fb_page_info',array("id"=>$page_id,'user_id'=>$this->user_id),array("persistent_enabled"=>'0'));
                    $success_msg=$this->lang->line("Successful");
                    $success_msg=$this->lang->line("Persistent Menu Remove")." : ".$success_msg;
                    array_push($success_msg_array, $success_msg);
                }
                else
                {
                    $error_msg=isset($response['error']['message'])?$response['error']['message']:$this->lang->line("something went wrong, please try again.");
                    $error_msg=$this->lang->line("Persistent Menu Remove")." : ".$error_msg;
                    array_push($error_msg_array, $error_msg);
                }
            }
        }
        //-----------------------------------------------------------------

        
        // enabling mark seen       
        if($this->basic->update_data('facebook_rx_fb_page_info',array('id'=>$page_id,"user_id"=>$this->user_id),array('enable_mark_seen'=>$enable_mark_seen)))
        {
            if($enable_mark_seen=='1')
            {
                $success_msg=$this->lang->line("Successful");
                $success_msg=$this->lang->line("enable mark seen")." : ".$success_msg;
                array_push($success_msg_array, $success_msg);
            }
            else
            {
                $success_msg=$this->lang->line("Successful");
                $success_msg=$this->lang->line("disable mark seen")." : ".$success_msg;
                array_push($success_msg_array, $success_msg);
            }
        }
        else
        {
            $error_msg=$this->lang->line("something went wrong, please try again.");
            if($enable_mark_seen=='1') $error_msg=$this->lang->line("enable mark seen")." : ".$error_msg;
            else $error_msg=$this->lang->line("disable mark seen")." : ".$error_msg;
            array_push($error_msg_array, $error_msg);
        }        
        //-----------------------------------------------------------------
        


        // typing on settings
        // if($this->basic->update_data('facebook_rx_fb_page_info',array('id'=>$page_id,"user_id"=>$this->user_id),array('enbale_type_on'=>$enbale_type_on,'reply_delay_time'=>$reply_delay_time)))
        // {
        //     $success_msg=$this->lang->line("Successful");
        //     $success_msg=$this->lang->line("Typing on Settings")." : ".$success_msg;
        //     array_push($success_msg_array, $success_msg);
        // }
        // else
        // {
        //     $error_msg=$this->lang->line("something went wrong, please try again.");
        //     $error_msg=$this->lang->line("Typing on Settings")." : ".$error_msg;
        //     array_push($error_msg_array, $error_msg);
        // }
        //-----------------------------------------------------------------
        

        echo "<div class='alert alert-info text-center'><i class='fa fa-check-circle'></i> ".$this->lang->line("Template settings has been imported to database successfully.")."</div>";

        if(!empty($success_msg_array))
        {
            echo "<br><br>";
            echo "<div class=''><i class='fas fa-list-ol'></i> ".$this->lang->line("Related successful operations")."<br>";
            $i=0;
                echo '<div style="margin-top:10px;padding-left:10px;">';
                    foreach ($success_msg_array as $key => $value) 
                    {
                        $i++;
                        echo "<i class='fa fa-check-circle'></i> ".$value.'<br>';
                    }
                echo '</div>';
            echo "</div>";
        }

        if(!empty($error_msg_array))
        {
            echo "<br><br>";
            echo "<div class='alert alert-warning'><i class='fa fa-info-circle'></i> ".$this->lang->line("Related unsuccessful operations").":<br>";
            $i=0;
                echo '<div style="margin-top:10px;padding-left:10px;">';
                    foreach ($error_msg_array as $key => $value) 
                    {
                        $i++;
                        echo "<i class='fa fa-remove'></i> ".$value.'<br>';
                    }
                echo '</div>';
            echo "</div>";
        }

        
    }


    private function get_bot_settings($page_table_id=0)
    {
        $where['where'] = array('page_id'=> $page_table_id,"user_id"=>$this->user_id);
        /**Get BOT settings information from messenger_bot table as base table. **/
        $messenger_bot_info = $this->basic->get_data("messenger_bot",$where);
        $bot_settings=array();
        $i=0;
        foreach ($messenger_bot_info as $bot_info) 
        {
            $message_bot_id= $bot_info['id'];
            foreach ($bot_info as $key => $value) 
            {
                if($key=='id' || $key=='user_id' || $key=='page_id' || $key=='fb_page_id' || $key=='last_replied_at' || $key=='broadcaster_labels') continue;
                $bot_settings[$i]['message_bot'][$key]=$value;
            }

            /*** Get postback information from messenger_bot_postback table, it's from postback manager  ****/
            $where['where'] = array('messenger_bot_table_id'=> $message_bot_id,"template_id"=>"0");
            $messenger_postback_info = $this->basic->get_data("messenger_bot_postback",$where);

            $j=0;
            foreach ($messenger_postback_info as $postback_info) 
            {
                $message_postback_id= $postback_info['id'];
                foreach ($postback_info as $key1 => $value1) 
                {
                    if($key1=="template_id" || $key1=='id' || $key1=='user_id' || $key1=='page_id' || $key1=='messenger_bot_table_id' || $key1=='last_replied_at' || $key1=='broadcaster_labels') continue;
                    $bot_settings[$i]['message_bot']['postback_template_info'][$j][$key1]=$value1;
                }
                /** Get Child Postback from Post back Manager  whose BOT is already set.**/
                $where['where'] = array('template_id'=> $message_postback_id,);
                $messenger_postback_child_info = $this->basic->get_data("messenger_bot_postback",$where);
                $m=0;
                foreach ($messenger_postback_child_info as $postback_child_info) 
                {
                    foreach ($postback_child_info as $key2 => $value2) 
                    {
                        if($key2=="template_id" || $key2=='id' || $key2=='user_id' || $key2=='page_id' || $key2=='messenger_bot_table_id' || $key2=='last_replied_at' || $key2=='broadcaster_labels') continue;

                        $bot_settings[$i]['message_bot']['postback_template_info'][$j]["postback_child"][$m][$key2]=$value2;
                    }
                    $m++;
                }
                $j++;
            }
            $i++;
        }
        /*** Get empty Postback from messenger_bot_postback table. The child postback for those bot isn't set yet . ***/
        $where['where'] = array('template_id'=> '0','messenger_bot_table_id'=>'0','is_template'=>'0','page_id'=>$page_table_id);
        $messenger_emptypostback_info = $this->basic->get_data("messenger_bot_postback",$where);
        $empty_postback_settings=array();
        $x=0;
        foreach ($messenger_emptypostback_info as $emptypostback_child_info) 
        {
            foreach ($emptypostback_child_info as $key4 => $value4) 
            {
                if($key4=='id' || $key4=='user_id' || $key4=='page_id' || $key4=='messenger_bot_table_id' || $key4=='last_replied_at' || $key4=='broadcaster_labels') continue;
                $empty_postback_settings[$x][$key4]=$value4;
            }
            $x++;
        }
        /****   Get Information of Persistent Menu ***/
        $persistent_menu_settings=array();
        $where['where'] = array('page_id'=>$page_table_id);
        $persistent_menu_info = $this->basic->get_data("messenger_bot_persistent_menu",$where);
        $y=0;
        foreach ($persistent_menu_info as $persistent_menu) 
        {
            foreach ($persistent_menu as $key5 => $value5) 
            {
                $persistent_menu_settings[$y][$key5] = $value5;
            }
            $y++;
        }

        /***Get general information from facebook_rx_fb_page_info table***/
        $bot_general_info=array();
        $where['where'] = array('id'=>$page_table_id);
        $bot_page_general_info = $this->basic->get_data("facebook_rx_fb_page_info",$where);
        foreach ($bot_page_general_info as $general_info) 
        {
            $bot_general_info['welcome_message']= isset($general_info['welcome_message']) ? $general_info['welcome_message']:"";
            $bot_general_info['started_button_enabled']= isset($general_info['started_button_enabled']) ? $general_info['started_button_enabled']:"";
            $bot_general_info['persistent_enabled']= isset($general_info['persistent_enabled']) ? $general_info['persistent_enabled']:"";
            $bot_general_info['enable_mark_seen']= isset($general_info['enable_mark_seen']) ? $general_info['enable_mark_seen']:"";
            $bot_general_info['enbale_type_on']= isset($general_info['enbale_type_on']) ? $general_info['enbale_type_on']:"";
            $bot_general_info['reply_delay_time']= isset($general_info['reply_delay_time']) ? $general_info['reply_delay_time']:"";
        }


        $full_bot_settings=array();
        $full_bot_settings['bot_settings']=$bot_settings;
        $full_bot_settings['empty_postback_settings']=$empty_postback_settings;     
        $full_bot_settings['persistent_menu_settings']=$persistent_menu_settings;       
        $full_bot_settings['bot_general_info']=$bot_general_info;   

        return $full_bot_settings;
    }

    

    public function tree_view($page_id=0)
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(257,$this->module_access)) exit();
        if($page_id==0) exit();
        $page_table_id=$page_id;


        $page_info = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_table_id,"user_id"=>$this->user_id)));
    

        /***    Get Started Information    ***/
        $where=array();
        $where['where'] = array('page_id'=> $page_table_id,'keyword_type'=>"get-started");
        $messenger_bot_info = $this->basic->get_data("messenger_bot",$where,$select='',$join='',$limit='1');
        $this->postback_info=array();
        $get_started_data=$this->get_child_info($messenger_bot_info,$page_table_id);
        $get_started_data_copy=$get_started_data;

        $get_started_tree = $this->make_tree($get_started_data_copy,1,$page_table_id);

         /***   No match tree    ***/
        $where=array();
        $where['where'] = array('page_id'=> $page_table_id,'keyword_type'=>"no match");
        $messenger_bot_info = $this->basic->get_data("messenger_bot",$where,$select='',$join='',$limit='1');
        $this->postback_info=array();
        $no_match_data=$this->get_child_info($messenger_bot_info,$page_table_id);
        $no_match_data_copy=$no_match_data;

        $no_match_tree = $this->make_tree($no_match_data_copy,2,$page_table_id);


        /**Get BOT settings information from messenger_bot table as base table. **/
        $where=array();
        $where['where'] = array('page_id'=> $page_table_id,'keywords !=' => "");
        $messenger_bot_info = $this->basic->get_data("messenger_bot",$where);
        $this->postback_info=array();
        $keyword_data=$this->get_child_info($messenger_bot_info,$page_table_id);
        $keyword_data_copy=$keyword_data;

        $keyword_bot_tree=array();

        foreach ($keyword_data_copy as $key => $value) 
        {
            $bot_tree_optimize_array=array($key=>$value);
            // echo "<pre>";print_r($bot_tree_optimize_array); 
            $keyword_bot_tree[] = $this->make_tree($bot_tree_optimize_array,0,$page_table_id);
        }


        $data['get_started_tree']=$get_started_tree;
        $data['keyword_bot_tree']=$keyword_bot_tree;
        $data['no_match_tree']=$no_match_tree;
        $data['body']='messenger_tools/tree_view';
        $data['page_info'] = isset($page_info[0])?$page_info[0]:array();
        $page_name = isset($page_info[0]['page_name']) ? $page_info[0]['page_name'] : "";
        $data['page_title']=$page_name.' - '.$this->lang->line("Tree View");
        $this->_viewcontroller($data);
    }

   

    private function make_tree($get_started_data_copy,$is_get_started=1,$page_table_id=0) // 0 = keyword, 1=get started, 2 = no match
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(257,$this->module_access)) return "";
        $get_started_level=0;
        $postback_array=array();
        $parent_key='';
        $linear_postback_array=array(); // holds associative array of postback and it's content
        foreach ($get_started_data_copy as $key => $value) 
        {
            $parent_key=$key;
            $postback_array=isset($value['postback_info'])?$value['postback_info']:array();
            $keywrods_list=isset($value['keywrods_list'])?$value['keywrods_list']:array();
            $postback_array_temp=$postback_array;
            foreach ($postback_array as $key2 => $value2) 
            {
                if(!isset($linear_postback_array[$key2]))
                $linear_postback_array[$key2]=$value2;
            }
            $last_postback_info=array_pop($postback_array_temp);
            $get_started_level=isset($last_postback_info['level'])?$last_postback_info['level']:0; // maximum postback nest level
            break;
        }

        $this->postback_array=$this->set_nest_easy($postback_array,$get_started_level);

        // putting nested postback to main data
        if(isset($get_started_data_copy[$parent_key]['postback_info']))$get_started_data_copy[$parent_key]['postback_info']=$this->postback_array;
        if($is_get_started!='0')// keyword list is always empty for get started and no match
        if(isset($get_started_data_copy[$parent_key]['keywrods_list']))unset($get_started_data_copy[$parent_key]['keywrods_list']);

        if($is_get_started=='1')
        {
            if($parent_key=="") $getstarted_start='<i class="fas fa-check-circle"></i> Get <br> Started'; 
            else $getstarted_start='<div class="getstartedcell"><a class="iframed" href="'.base_url('messenger_bot/edit_bot/'.$parent_key.'/1/getstart').'"><span data-toggle="tooltip" title="Click to edit settings"><i class="fas fa-check-circle"></i> Get <br> Started</span></a></div>';
        }
        else if($is_get_started=='2')
        {
            if($parent_key=="") $getstarted_start='<i class="fas fa-comment-slash""></i> No <br> Match'; 
            else $getstarted_start='<div class="getstartedcell"><a class="iframed" href="'.base_url('messenger_bot/edit_bot/'.$parent_key.'/1/nomatch').'"><span data-toggle="tooltip" title="Click to edit settings"><i class="fas fa-comment-slash"></i> No <br> Match</span></a></div>';
        }
        else
        {
            if($parent_key=="") $getstarted_start='<i class="fas fa-times"></i> No <br> Keyword'; // no get started found
            else $getstarted_start='<div class="keywordcell" title="'.$keywrods_list.'"><a class="iframed" href="'.base_url('messenger_bot/edit_bot/'.$parent_key.'/1').'"><span data-toggle="tooltip" title="Click to edit settings">'.$keywrods_list.'</span></a></div>';
        }


        $get_started_tree='
        <li>
            '.$getstarted_start.'
            <ul>';
                foreach ($get_started_data_copy as $key_temp => $value_temp) 
                {
                  foreach ($value_temp as $key_temp2 => $value_temp2) 
                  {
                    if($key_temp2=="keywrods_list") continue;
                    if($key_temp2!="postback_info")
                    {
                      $templabel=$this->formatlabel($this->tree_security($key_temp2));                      
                      if(is_array($value_temp2) && !empty($value_temp2))
                      {
                          if($key_temp2=="web_url") 
                          {
                            foreach($value_temp2 as $tempukey => $tempuval) 
                            {                                
                                $get_started_tree.= '
                                <li>
                                    <a data-toggle="tooltip" title="'.$this->tree_security($tempuval).'" href="'.$this->tree_security($tempuval).'" target="_blank"><i class="fas fa-external-link-alt"></i> '.$templabel.'</a>
                                </li>';
                            }
                          }
                          else if($key_temp2=="call_us") 
                          {
                            foreach($value_temp2 as $tempukey => $tempuval) 
                            {                                
                                $get_started_tree.= '
                                <li data-toggle="tooltip" title="'.$this->tree_security($tempuval).'"><i class="fas fa-headset"></i> '.$templabel.'</li>';
                            }
                          }
                          else if($key_temp2=="birthdate") 
                          {
                            foreach($value_temp2 as $tempukey => $tempuval) 
                            {                                
                                $get_started_tree.= '
                                <li data-toggle="tooltip" title="'.$this->tree_security($tempuval).'"><i class="fas fa-birthday-cake"></i> '.$templabel.'</li>';
                            }
                          }
                          else if($key_temp2=="webview") 
                          {
                            foreach($value_temp2 as $tempukey => $tempuval) 
                            {                                
                                $get_started_tree.= '
                                <li>
                                    <a data-toggle="tooltip" title="'.$this->tree_security($tempuval).'" href="'.$this->tree_security($tempuval).'" target="_blank"><i class="fab fa-wpforms"></i> '.$templabel.'</a>
                                </li>';
                            }
                          }
                          else 
                          {
                            foreach($value_temp2 as $tempukey => $tempuval) 
                            {                                
                                $get_started_tree.= '
                                <li><i class="far fa-circle"></i> '.$templabel.'</li>';
                            }
                          }
                      }
                    }
                    else //postback sub-tree
                    {
                      $postback_info=array_filter($value_temp2);

                      if(count($postback_info)>0)                        
                        foreach ($postback_info as $key0 => $value0)
                        {       
                            if(is_array($value0)) // if have new child that does not appear in parent tree
                            {
                                $tempid=isset($value0['id'])?$value0['id']:0;
                                $tempis_template=isset($value0['is_template'])?$value0['is_template']:'';
                                $tempostbackid=isset($value0['postback_id'])?$this->tree_security($value0['postback_id']):'';
                                $tempbotname=isset($value0['bot_name'])?$this->tree_security($value0['bot_name']):'';

                                if($tempis_template=='1') $tempurl=base_url('messenger_bot/edit_template/'.$tempid.'/1'); // it is template
                                else if($tempis_template=='0') $tempurl=base_url('messenger_bot/edit_bot/'.$tempid.'/1'); // it is bot
                                else $tempurl="";
                                
                                if($tempbotname!='') $display="<span class='text-info' data-toggle='tooltip' title='".$tempostbackid." : click to edit settings'><i class='far fa-hand-pointer'></i> ".$tempbotname.'</span>';
                                else $display="<span class='text-info'><i class='far fa-hand-pointer'></i> ".$tempostbackid.'</span>';

                                if($tempurl!="") $templabel='<a class="iframed" href="'.$tempurl.'">'.$display.'</a>';
                                else $templabel=$display;

                                $get_started_tree.= '
                                <li>'.$templabel;
                            }
                            else // child already appear in parent tree
                            {                                
                                if(isset($linear_postback_array[$value0]))
                                {
                                    $tempid=isset($linear_postback_array[$value0]['id'])?$linear_postback_array[$value0]['id']:0;
                                    $tempis_template=isset($linear_postback_array[$value0]['is_template'])?$linear_postback_array[$value0]['is_template']:'';
                                    $tempostbackid=isset($linear_postback_array[$value0]['postback_id'])?$this->tree_security($linear_postback_array[$value0]['postback_id']):'';
                                    $tempbotname=isset($linear_postback_array[$value0]['bot_name'])?$this->tree_security($linear_postback_array[$value0]['bot_name']):'';

                                    if($tempis_template=='1') $tempurl=base_url('messenger_bot/edit_template/'.$tempid.'/1'); // it is template
                                    else if($tempis_template=='0') $tempurl=base_url('messenger_bot/edit_bot/'.$tempid.'/1'); // it is bot
                                    else $tempurl="";

                                    if($tempbotname!='') $display="<span class='text-muted' data-toggle='tooltip' title='".$tempostbackid." is already exist in the tree><i class='far fa-hand-pointer'></i> ".$tempbotname.'</span>';
                                    else $display="<span class='text-muted' class='text-muted' data-toggle='tooltip' title='".$tempostbackid." is already exist in the tree'><i class='far fa-hand-pointer'></i> ".$tempostbackid.'</span>';

                                    if($tempurl!="") $templabel='<a class="iframed" href="'.$tempurl.'">'.$display.'</a>';
                                    else $templabel=$display;

                                    $get_started_tree.= '
                                    <li>'.$templabel;
                                }
                            }

                           $phpcomand_array=array();
                           $closing_bracket='';

                           for($i=1; $i<=$get_started_level;$i++) 
                            {    
                                $phpcomand_array[]=$this->get_nest($i,$page_table_id);
                                $closing_bracket.="}  \$get_started_tree.='</ul>';";                                
                            }
                            $phpcomand_str=implode(' ', $phpcomand_array);
                            $phpcomand_str.=$closing_bracket;
                            eval($phpcomand_str);
                            
                            $get_started_tree.= 
                            "</li>";
                        }


                    } // end if postbock          
                  } // end 2nd foreach
                } // end 1st foreach
            $get_started_tree.='
            </ul>
        </li>';

        return $get_started_tree;

    }



    private function formatlabel($raw="")
    {
        if($raw=="") return "";  
        $tempraw=str_replace('_', ' ', $raw);
        $tempraw=ucwords($tempraw);
        return $tempraw;
    }

    private function tree_security($input="")
    {
        $output=strip_tags($input);
        $output=str_replace(array('<?php','<?','<? php','?>','<?=','$','(',')','{','}','[',']',"'",'"',"\\"), "", $input);
        return $output;
    }



    public function typing_on_settings()
    {
        if(!$_POST) exit();

        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                echo "<div class='alert alert-danger text-center'><i class='fa fa-ban'></i> This function is disabled from admin account in this demo!!</div>";
                exit();
            }
        }

        $table_id=$this->input->post('table_id');
        $reply_delay_time=$this->input->post('reply_delay_time');
        $enbale_type_on=$this->input->post('enbale_type_on');
        if($enbale_type_on=="0") $reply_delay_time=0;
        $this->basic->update_data('facebook_rx_fb_page_info',array('id'=>$table_id,"user_id"=>$this->user_id),array('enbale_type_on'=>$enbale_type_on,'reply_delay_time'=>$reply_delay_time));
        $this->session->set_flashdata('bot_action',$this->lang->line("Settings has been saved successfully."));
    }

    

    public function mark_seen_chat_human_settings()
    {
        if(!$_POST) exit();

        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                echo "<div class='alert alert-danger text-center'><i class='fa fa-ban'></i> This function is disabled from admin account in this demo!!</div>";
                exit();
            }
        }

        $table_id=$this->input->post('table_id');
        $mark_seen_status=$this->input->post('mark_seen_status');
        $chat_human_email=$this->input->post('chat_human_email');
        $no_match_found_reply=$this->input->post('no_match_found_reply');

        $this->basic->update_data('facebook_rx_fb_page_info',array('id'=>$table_id),array('enable_mark_seen'=>$mark_seen_status,'chat_human_email'=>$chat_human_email,'no_match_found_reply'=>$no_match_found_reply));
        $response['status'] = '1';
        $response['message'] = $this->lang->line('General settings have been stored successfully.');
        echo json_encode($response);
    }

   
    public function check_page_response()
    {
        $response = array('has_pageresponse'=>'0');
        echo json_encode($response);
    }

    public function delete_full_bot()
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(200,$this->module_access))
        exit();
        if(!$_POST) exit();

        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                echo "<div class='alert alert-danger text-center'><i class='fa fa-ban'></i> This function is disabled from admin account in this demo!!</div>";
                exit();
            }
        }

        $user_id = $this->user_id;
        $page_id=$this->input->post('page_id');
        $already_disabled=$this->input->post('already_disabled');

        $this->load->library("fb_rx_login");         

        $page_data=$this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_id)));
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

        echo json_encode(array('success'=>'successfully deleted.'));

    }


    private function delete_bot_data($page_id,$fb_page_id)
    {
        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                echo "<div class='alert alert-danger text-center'><i class='fa fa-ban'></i> This function is disabled from admin account in this demo!!</div>";
                exit();
            }
        }

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

   //=============================DOMAIN WHITELIST================================
    public function domain_whitelist()
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(200,$this->module_access))
        redirect('home/login_page', 'location'); 
        $table = "facebook_rx_fb_page_info";     
        $where_simple['facebook_rx_fb_page_info.user_id'] = $this->user_id;
        $where_simple['facebook_rx_fb_page_info.bot_enabled'] = '1';
        $where  = array('where'=>$where_simple);
        $join = array('facebook_rx_fb_user_info'=>"facebook_rx_fb_user_info.id=facebook_rx_fb_page_info.facebook_rx_fb_user_info_id,left");   
        $page_info = $this->basic->get_data($table, $where, $select=array("facebook_rx_fb_page_info.*","facebook_rx_fb_user_info.name as account_name"),$join,'','','page_name asc');
        $pagelist=array();
        $i=0;
        foreach($page_info as $key => $value) 
        {
           $pagelist[$value["facebook_rx_fb_user_info_id"]]["account_name"]=$value['account_name'];
           $pagelist[$value["facebook_rx_fb_user_info_id"]]["page_data"][$i]["page_name"]=$value['page_name'];
           $pagelist[$value["facebook_rx_fb_user_info_id"]]["page_data"][$i]["page_id"]=$value['id'];
           $i++;
        }
        $data['page_title'] = $this->lang->line("Whitelisted Domains");
        $data['pagelist'] = $pagelist;
        $data['body'] = 'messenger_tools/domain_list';
        $this->_viewcontroller($data); 
    }

    public function domain_whitelist_data()
    {
        $this->ajax_check();
        $domain_page = $this->input->post('domain_page',true);
        $display_columns = array("#","CHECKBOX",'id', 'account_name', 'page_name', 'count', 'action');

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $sort_index = isset($_POST['order'][0]['column']) ? strval($_POST['order'][0]['column']) : 2;
        $sort = isset($display_columns[$sort_index]) ? $display_columns[$sort_index] : 'page_name';
        $order = isset($_POST['order'][0]['dir']) ? strval($_POST['order'][0]['dir']) : 'ASC';
        $order_by=$sort." ".$order;


        $where_simple=array();
        if($domain_page != '')
        {
            $sql = "domain like '%".$domain_page."%' OR page_name like '%".$domain_page."%'";
            $this->db->where($sql);
        }
        
        $where_simple['messenger_bot_domain_whitelist.user_id'] = $this->user_id;
        $where_simple['facebook_rx_fb_page_info.user_id'] = $this->user_id;
        $where_simple['facebook_rx_fb_page_info.deleted'] = '0';
        $where_simple['facebook_rx_fb_page_info.bot_enabled'] = '1';
        $where  = array('where'=>$where_simple);
        $result = array();       
        $table = "messenger_bot_domain_whitelist";     
        $join = array
        (
            'facebook_rx_fb_page_info'=>"facebook_rx_fb_page_info.id=messenger_bot_domain_whitelist.page_id,left",
            'facebook_rx_fb_user_info'=>"facebook_rx_fb_user_info.id=facebook_rx_fb_page_info.facebook_rx_fb_user_info_id,left"
        );   
        $group_by = "messenger_bot_domain_whitelist.page_id";
        $info = $this->basic->get_data($table, $where, $select=array("messenger_bot_domain_whitelist.*","facebook_rx_fb_page_info.page_name","facebook_rx_fb_page_info.id as page_table_id", "facebook_rx_fb_page_info.page_id as fb_page_id", "facebook_rx_fb_user_info.name as account_name","count(messenger_bot_domain_whitelist.id) as count"), $join, $limit, $start, $order_by,$group_by);
        
        $total_rows_array = $this->basic->count_row($table, $where, $count="messenger_bot_domain_whitelist.id",$join,$group_by);      
        $total_result = $total_rows_array[0]['total_rows'];



        $i=0;
        $base_url=base_url();
        foreach ($info as $key => $value) 
        {
            $info[$i]["action"] = "<a href='#' style='cursor:pointer' class='btn btn-circle btn-outline-info domain_list' title='".$this->lang->line("Domain List")."' data-account-name='".$value['account_name']."' data-page-name='".$value['page_name']."' data-page='".$value['page_table_id']."'><i class='fa fa-eye'></i></a>";
            $info[$i]["page_name"] = "<a target='_BLANK' href='https://facebook.com/".$value['fb_page_id']."'>".$value['page_name']."</i></a>";
            $i++;
        }

        $data['draw'] = (int)$_POST['draw'] + 1;
        $data['recordsTotal'] = $total_result;
        $data['recordsFiltered'] = $total_result;
        $data['data'] = convertDataTableResult($info, $display_columns ,$start,$primary_key="id");

        echo json_encode($data);

    }

    public function domain_details()
    {
        $this->ajax_check();
        $page_id = $this->input->post("page_id");
        $searching = $this->input->post('searching',true);
        $display_columns = array("#", 'domain', 'created_at', 'actions');

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $sort_index = isset($_POST['order'][0]['column']) ? strval($_POST['order'][0]['column']) : 2;
        $sort = isset($display_columns[$sort_index]) ? $display_columns[$sort_index] : 'messenger_bot_domain_whitelist.id';
        $order = isset($_POST['order'][0]['dir']) ? strval($_POST['order'][0]['dir']) : 'desc';
        $order_by=$sort." ".$order;


        $table_name = "messenger_bot_domain_whitelist";
        $where_simple['user_id'] = $this->user_id;
        $where_simple['page_id'] = $page_id;
        if($searching != '')
        $where_simple['domain like'] = '%'.$searching.'%';

        $where['where'] = $where_simple;
        $info = $this->basic->get_data($table_name,$where,'','',$limit,$start,$order_by);

        $total_rows_array=$this->basic->count_row('messenger_bot_domain_whitelist',$where,"messenger_bot_domain_whitelist.id");
        $total_result=$total_rows_array[0]['total_rows'];

        foreach ($info as $key => $one_user) 
        {
            $btn_id=$one_user['id'];
            $delete_btn= "<a href='#' class='btn btn-circle btn-outline-danger delete_domain'title='".$this->lang->line("delete")."' id='domain-".$btn_id."' data-id='".$btn_id."'><i class='fa fa-trash'></i></a>";       
            
            $info[$key]['actions'] = $delete_btn;
            $info[$key]['domain'] = "<a target='_BLANK' href='".$one_user['domain']."'>".$one_user['domain']."</a>";
            $info[$key]['created_at'] = date("jS M, y H:i:s",strtotime($one_user['created_at']));
        }

        $data['draw'] = (int)$_POST['draw'] + 1;
        $data['recordsTotal'] = $total_result;
        $data['recordsFiltered'] = $total_result;
        $data['data'] = convertDataTableResult($info, $display_columns ,$start,$primary_key="id");

        echo json_encode($data);
        
    }

    public function delete_domain()
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(200,$this->module_access))
        exit();

        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                echo "<div class='alert alert-danger text-center'><i class='fa fa-ban'></i> This function is disabled from admin account in this demo!!</div>";
                exit();
            }
        }
        
        $this->ajax_check();
        $domain_id=$this->input->post('domain_id');
        if($this->basic->delete_data('messenger_bot_domain_whitelist',array('id'=>$domain_id,'user_id'=>$this->user_id))) echo "1";
        else echo "0";
    }

    public function delete_bot()
    {
        if(!$_POST) exit();
        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                echo "<div class='alert alert-danger text-center'><i class='fa fa-ban'></i> This function is disabled from admin account in this demo!!</div>";
                exit();
            }
        }
        $id=$this->input->post("id");
        $bot_posback_ids = $this->basic->get_data('messenger_bot',array('where'=>array('id'=>$id)));
        $postback_id = array();
        if($bot_posback_ids[0]['keyword_type'] == 'post-back')
        {
            $postback_id = explode(',', $bot_posback_ids[0]['postback_id']);
        }

        $this->db->trans_start();
        $this->basic->delete_data("messenger_bot",array("id"=>$id,"user_id"=>$this->user_id));
        
        if(!empty($postback_id))
        {            
            $this->db->where_in("postback_id", $postback_id);
            $this->db->update('messenger_bot_postback', array('use_status' => '0'));
        }      
        $this->db->trans_complete();
        if($this->db->trans_status() === false)
            echo '0';
        else
            echo '1';
    }

    public function add_domain()
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(200,$this->module_access))
            exit();        
        $this->ajax_check();

        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                echo "<div class='alert alert-danger text-center'><i class='fa fa-ban'></i> This function is disabled from admin account in this demo!!</div>";
                exit();
            }
        }

        $page_id=$this->input->post('page_id');
        $domain_name=$this->input->post('domain_name');
        $userdata=$this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_id,"user_id"=>$this->user_id)));
        $facebook_rx_fb_user_info_id=isset($userdata[0]['facebook_rx_fb_user_info_id']) ? $userdata[0]['facebook_rx_fb_user_info_id'] : "";
        $page_access_token=isset($userdata[0]['page_access_token']) ? $userdata[0]['page_access_token'] : "";
        if(!$this->basic->is_exist('messenger_bot_domain_whitelist',array('page_id'=>$page_id,'domain'=>$domain_name,'user_id'=>$this->user_id)))
        {
            $this->basic->insert_data('messenger_bot_domain_whitelist',array('page_id'=>$page_id,'domain'=>$domain_name,"created_at"=>date("Y-m-d H:i:s"),"messenger_bot_user_info_id"=>$facebook_rx_fb_user_info_id,"user_id"=>$this->user_id));
            $this->load->library("fb_rx_login"); 
            $response=array();
            $response=$this->fb_rx_login->domain_whitelist($page_access_token,$domain_name);
        }
        else $response=array('status'=>'1','result'=>$this->lang->line("Successfully updated whitelisted domains"));
        echo json_encode($response);
       
    }
   //=============================DOMAIN WHITELIST================================

    
    public function delete_error_log($id=0)
    {  
        if($id == 0) exit();      
        $this->basic->delete_data("messenger_bot_reply_error_log",array("id"=>$id));
        redirect(base_url('messenger_bot/bot_list'),'location');
    }

    public function error_log_report()
    {
        $this->ajax_check();
        $user_id = $this->user_id;
        $page_table_id = $this->input->post('table_id',true);     
        $error_search = $this->input->post('error_search',true);    
        $display_columns = array("#", 'bot_name', 'error_message', 'error_time', 'actions'); 

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $sort_index = isset($_POST['order'][0]['column']) ? strval($_POST['order'][0]['column']) : 2;
        $sort = isset($display_columns[$sort_index]) ? $display_columns[$sort_index] : 'messenger_bot_reply_error_log.id';
        $order = isset($_POST['order'][0]['dir']) ? strval($_POST['order'][0]['dir']) : 'desc';
        $order_by=$sort." ".$order;


        $table_name = "messenger_bot_reply_error_log";
        $select = array("messenger_bot_reply_error_log.*","bot_name");
        $join = array('messenger_bot'=>"messenger_bot_reply_error_log.bot_settings_id=messenger_bot.id,left");  
        $where_simple['messenger_bot_reply_error_log.user_id'] = $user_id;
        $where_simple['messenger_bot_reply_error_log.page_id'] = $page_table_id;
        $where['where'] = $where_simple;

        if($error_search != '')
        {
            $sql = "(messenger_bot.bot_name like '%".$error_search."%' OR messenger_bot_reply_error_log.error_message like '%".$error_search."%')";
            $this->db->where($sql);
        }

        $info = $this->basic->get_data($table_name,$where,$select,$join,$limit,$start,$order_by);   

        $total_rows_array=$this->basic->count_row('messenger_bot_reply_error_log',$where,"messenger_bot_reply_error_log.id",$join);
        $total_result=$total_rows_array[0]['total_rows'];   

        foreach ($info as $key=>$error_info) 
        {
            $action_button = "<div style='min-width:90px'><a class='btn btn-circle btn-outline-warning' data-toggle='tooltip' title='".$this->lang->line("Edit Bot")."' href='".base_url('messenger_bot/edit_bot/').$error_info['bot_settings_id']."/0/errlog'> <i class='fa fa-edit'></i></a>&nbsp;
                              <a class='btn btn-circle btn-outline-danger' data-toggle='tooltip' title='".$this->lang->line("Delete Log")."' href=".base_url('messenger_bot/delete_error_log/').$error_info['id']."> <i class='fa fa-trash'></i></a></div>
                              <script>
                $('[data-toggle=\"tooltip\"]').tooltip();
              </script>";
            $info[$key]['actions'] = $action_button;
            $info[$key]['bot_name'] = $error_info['bot_name'];
            $info[$key]['error_message'] = $error_info['error_message'];
            $info[$key]['error_time'] = date("jS M, y H:i:s",strtotime($error_info['error_time']));
        }

        $data['draw'] = (int)$_POST['draw'] + 1;
        $data['recordsTotal'] = $total_result;
        $data['recordsFiltered'] = $total_result;
        $data['data'] = convertDataTableResult($info, $display_columns ,$start,$primary_key="id");

        echo json_encode($data);
        
    }


    public function error_log_report_X()
    {
        if(empty($_POST['table_id'])) {
            die();
        }
        $user_id = $this->user_id;
        $page_table_id = $this->input->post('table_id');        
        $table_name = "messenger_bot_reply_error_log";
        $select=array("messenger_bot_reply_error_log.*","bot_name");
        $join = array('messenger_bot'=>"messenger_bot_reply_error_log.bot_settings_id=messenger_bot.id,left");   
        $where['where'] = array('messenger_bot_reply_error_log.user_id' => $user_id, 'messenger_bot_reply_error_log.page_id' => $page_table_id);
        $error_log_report_info = $this->basic->get_data($table_name,$where,$select,$join);       
        $html = '<script>
                    $(document).ready(function() {
                        $("#error_log_datatable").DataTable();
                    }); 
                 </script>
                 <style>
                    .dataTables_filter
                     {
                        float : right;
                     }
                 </style>';
        $html .= "
            <table id='error_log_datatable' class='table table-striped table-bordered' cellspacing='0' width='100%''>
            <thead>
                <tr>
                    <th>".$this->lang->line("Bot Name")."</th>
                    <th>".$this->lang->line("Error Message")."</th>
                    <th class='text-center'>".$this->lang->line("Error Time")."</th>
                    <th class='text-center'>".$this->lang->line("Actions")."</th>
                </tr>
            </thead>
            <tbody>";
        foreach ($error_log_report_info as $error_info) 
        {
            $html .= "<tr>
                        <td>".$error_info['bot_name']."</td>
                        <td>".$error_info['error_message']."</td>
                        <td class='text-center'>".date("jS M, y H:i:s",strtotime($error_info['error_time']))."</td>
                        <td class='text-center'>
                              <a class='btn btn-outline-warning' href='".base_url('messenger_bot/edit_bot/').$error_info['bot_settings_id']."/0/errlog'> <i class='fa fa-edit'></i> ".$this->lang->line("Edit Bot")."</a> 
                              <a class='btn btn-outline-danger' href=".base_url('messenger_bot/delete_error_log/').$error_info['id']."> <i class='fa fa-trash'></i> ".$this->lang->line("Delete Log")."</a> 
                             
                        </td>";
            $html .= "</tr>";
        }
        $html .= "</tbody>
                </table>
                ";
        echo $html;
    }
    
    public function remove_persistent_menu_locale($auto_id=0,$page_auto_id=0,$iframe=0)
    {
        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                echo "<div class='alert alert-danger text-center'><i class='fa fa-ban'></i> This function is disabled from admin account in this demo!!</div>";
                exit();
            }
        }
        if($this->session->userdata('user_type') != 'Admin' && !in_array(197,$this->module_access))
        redirect('home/login_page', 'location'); 
        $this->basic->delete_data("messenger_bot_persistent_menu",array("id"=>$auto_id,"user_id"=>$this->user_id));
        $this->session->set_flashdata('remove_persistent_menu_locale',1);
        redirect(base_url('messenger_bot/persistent_menu_list/'.$page_auto_id.'/'.$iframe),'location');    
    } 

    public function remove_persistent_menu($page_auto_id=0,$iframe=0)
    {
        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                echo "<div class='alert alert-danger text-center'><i class='fa fa-ban'></i> This function is disabled from admin account in this demo!!</div>";
                exit();
            }
        }
        if($this->session->userdata('user_type') != 'Admin' && !in_array(197,$this->module_access))
        redirect('home/login_page', 'location'); 
        
        $this->load->library("fb_rx_login"); 
        $page_info=$this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_auto_id,'user_id'=>$this->user_id)));
        if(!isset($page_info[0])) exit();
        $page_access_token=$page_info[0]['page_access_token'];
        $response=$this->fb_rx_login->delete_persistent_menu($page_access_token);
        if(!isset($response['error']))
        {
            $this->basic->update_data('facebook_rx_fb_page_info',array("id"=>$page_auto_id,'user_id'=>$this->user_id),array("persistent_enabled"=>'0'));
            $this->basic->delete_data('messenger_bot_persistent_menu',array("page_id"=>$page_auto_id,'user_id'=>$this->user_id));
            $this->session->set_flashdata('perrem_success',1);
            $this->_delete_usage_log($module_id=197,$request=1);
        }
        else
        {
            $err_message=isset($response['error']['message'])?$response['error']['message']:$this->lang->line("something went wrong, please try again.");
            
            $this->session->set_flashdata('perrem_success',0);
            $this->session->set_flashdata('perrem_message',$err_message);
        }
        redirect(base_url("messenger_bot/persistent_menu_list/$page_auto_id/$iframe"),'location');
    } 

    public function publish_persistent_menu($page_auto_id=0,$iframe=0)
    {
        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                echo "<div class='alert alert-danger text-center'><i class='fa fa-ban'></i> This function is disabled from admin account in this demo!!</div>";
                exit();
            }
        }
        if($this->session->userdata('user_type') != 'Admin' && !in_array(197,$this->module_access))
        redirect('home/login_page', 'location'); 
        $page_info=$this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_auto_id,'user_id'=>$this->user_id)));
        if(!isset($page_info[0])) exit();
        $page_access_token=$page_info[0]['page_access_token'];
        $is_already_persistent_enabled=$page_info[0]['persistent_enabled'];
        if($is_already_persistent_enabled=='0') // no need to check if it was already published and user is just editing menu
        {
            $status=$this->_check_usage($module_id=197,$request=1);
            if($status=="3") 
            {
                $this->session->set_flashdata('per_success',0);
                $this->session->set_flashdata('per_message',$this->lang->line("You are not allowed to publish new persistent menu. Module limit has been exceeded.")); 
                $this->_insert_usage_log($module_id=197,$request=1);   
                redirect(base_url('messenger_bot/persistent_menu_list/'.$page_auto_id.'/'.$iframe),'location'); 
            }
        }
        $this->load->library("fb_rx_login"); 
        $json_array=array();
        $menu_data=$this->basic->get_data("messenger_bot_persistent_menu",array("where"=>array("page_id"=>$page_auto_id,"user_id"=>$this->user_id)));
        foreach ($menu_data as $key => $value) 
        {
            $temp=json_decode($value["item_json"],true);
            $temp2=isset($temp['call_to_actions']) ? $temp['call_to_actions'] : array();
          
            if($this->session->userdata('user_type') == 'Member' && in_array(198,$this->module_access) && count($temp2)<3)
            {
                end($temp2);        
                $key2 = key($temp2); 
                $key2++;
                $copyright_text=$this->config->item("persistent_menu_copyright_text");
                if($copyright_text=="") $copyright_text=$this->config->item("product_name");
                $copyright_url=$this->config->item("persistent_menu_copyright_url");
                if($copyright_url=="") $copyright_url=base_url();
                $temp["call_to_actions"][$key2]["title"]=$copyright_text;
                $temp["call_to_actions"][$key2]["type"]="web_url";
                $temp["call_to_actions"][$key2]["url"]=$copyright_url;
            }
            $json_array["persistent_menu"][]=$temp;
        }
        
        $json=json_encode($json_array);
      
        $response=$this->fb_rx_login->add_persistent_menu($page_access_token,$json);
        
        if(!isset($response['error']))
        {
            if(!empty($postback_insert_data))
            $this->db->insert_batch('messenger_bot_postback',$postback_insert_data);
            $this->basic->update_data('facebook_rx_fb_page_info',array("id"=>$page_auto_id,'user_id'=>$this->user_id),array("persistent_enabled"=>'1'));
            $this->session->set_flashdata('menu_success',1); 
            if($is_already_persistent_enabled=='0') // no need to check if it was already published and user is just editing menu
            $this->_insert_usage_log($module_id=197,$request=1);   
            redirect(base_url('messenger_bot/persistent_menu_list/'.$page_auto_id.'/'.$iframe),'location');        
        }
        else
        {
            $err_message=isset($response['error']['message'])?$response['error']['message']:$this->lang->line("something went wrong, please try again.");
            $this->session->set_flashdata('per_success',0);
            $this->session->set_flashdata('per_message',$err_message); 
            redirect(base_url('messenger_bot/persistent_menu_list/'.$page_auto_id.'/'.$iframe),'location');       
        }         
    }

    public function persistent_menu_list($page_auto_id=0,$iframe='0')
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(197,$this->module_access))
        redirect('home/login_page', 'location'); 
        
        $data['body'] = 'messenger_tools/persistent_menu_list';
        $data['page_title'] = $this->lang->line('Persistent Menu List');  
        $page_info=$this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_auto_id,'user_id'=>$this->user_id)));
        if(!isset($page_info[0])) exit();
        
        $data['page_info'] = isset($page_info[0]) ? $page_info[0] : array(); 
        $data["menu_info"]=$this->basic->get_data("messenger_bot_persistent_menu",array("where"=>array("page_id"=>$page_auto_id,"user_id"=>$this->user_id)));
        
        $data['iframe']=$iframe;
        $this->_viewcontroller($data); 
    }

    public function create_persistent_menu($page_auto_id=0,$iframe='0')
    {        
        if($this->session->userdata('user_type') != 'Admin' && !in_array(197,$this->module_access))
        redirect('home/login_page', 'location'); 
        
        $data['body'] = 'messenger_tools/persistent_menu';
        $data['page_title'] = $this->lang->line('Persistent Menu');  
        $page_info=$this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_auto_id,'user_id'=>$this->user_id)));
        if(!isset($page_info[0])) exit();
        
        $data['page_info'] = isset($page_info[0]) ? $page_info[0] : array(); 
        $started_button_enabled = isset($page_info[0]["started_button_enabled"])?$page_info[0]["started_button_enabled"]:"0";
        $postback_id_list = $this->basic->get_data('messenger_bot_postback',array('where'=>array('user_id'=>$this->user_id,'page_id'=>$page_auto_id)));
        $data['postback_ids'] = $postback_id_list;
        $data['page_auto_id'] = $page_auto_id;
        $data['started_button_enabled'] = $started_button_enabled;
        $data['locale']=$this->sdk_locale();
        
        $data['iframe']=$iframe;
        $this->_viewcontroller($data); 
    }

    public function create_persistent_menu_action()
    {
        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                echo "<div class='alert alert-danger text-center'><i class='fa fa-ban'></i> This function is disabled from admin account in this demo!!</div>";
                exit();
            }
        }

        if(!$_POST) exit();
        $post=$_POST;
        foreach ($post as $key => $value) 
        {
            $$key=$value;
        }
        if($this->basic->is_exist("messenger_bot_persistent_menu",array("page_id"=>$page_table_id,"locale"=>$locale)))
        {
            echo json_encode(array('status'=>'0','message'=>$this->lang->line("persistent menu is already exists for this locale.")));
            exit();
        }
        $menu=array();
        $postback_insert_data=array();
        $only_postback=array();
        for($i=1;$i<=$level1_limit;$i++)
        {
            $level_title_temp="text_with_buttons_text_".$i;
            $level_type_temp="text_with_button_type_".$i;
            if($$level_title_temp=="") continue; // form gets everything but we need only filled data
            if($$level_type_temp=="post_back") $$level_type_temp="postback";
            $menu[$i]=array
            (
                "title"=>$$level_title_temp,
                "type"=> $$level_type_temp
            );
            if($$level_type_temp=="postback")
            {
                $level_postback_temp="text_with_button_post_id_".$i;
                $level_postback_temp_data=isset($$level_postback_temp) ? $$level_postback_temp : '';
                // $$level_postback_temp=strtoupper($$level_postback_temp);
                $menu[$i]["payload"]=$level_postback_temp_data;
                $single_postback_insert_data = array();
                $single_postback_insert_data['user_id'] = $this->user_id;
                $single_postback_insert_data['postback_id'] = $level_postback_temp_data;
                $single_postback_insert_data['page_id'] = $page_table_id;
                $single_postback_insert_data['bot_name'] = '';
                $postback_insert_data[] = $single_postback_insert_data; 
                $only_postback[]=$level_postback_temp_data;
            }
            else if($$level_type_temp=="web_url")
            {
                $level_web_url_temp="text_with_button_web_url_".$i;
                $menu[$i]["url"]=$$level_web_url_temp;
            }
            else
            {
                for($j=1;$j<=$level2_limit;$j++)
                {
                    $level2_title_temp="text_with_buttons_text_".$i."_".$j;
                    $level2_type_temp="text_with_button_type_".$i."_".$j;
                    if($$level2_title_temp=="") continue; // form gets everything but we need only filled data
                    if($$level2_type_temp=="post_back") $$level2_type_temp="postback";
                    $menu[$i]["call_to_actions"][$j]["title"]=$$level2_title_temp;
                    $menu[$i]["call_to_actions"][$j]["type"]=$$level2_type_temp;
                    if($$level2_type_temp=="postback")
                    {
                        $level2_postback_temp="text_with_button_post_id_".$i."_".$j;
                        $level2_postback_temp_data=isset($$level2_postback_temp) ? $$level2_postback_temp : '';
                        // $$level2_postback_temp=strtoupper($$level2_postback_temp);
                        $menu[$i]["call_to_actions"][$j]["payload"]=$level2_postback_temp_data;
                        $single_postback_insert_data = array();
                        $single_postback_insert_data['user_id'] = $this->user_id;
                        $single_postback_insert_data['postback_id'] = $level2_postback_temp_data;
                        $single_postback_insert_data['page_id'] = $page_table_id;
                        $single_postback_insert_data['bot_name'] = '';
                        $postback_insert_data[] = $single_postback_insert_data; 
                        $only_postback[]=$level2_postback_temp_data;
                    }
                    else if($$level2_type_temp=="web_url")
                    {
                        $level2_web_url_temp="text_with_button_web_url_".$i."_".$j;
                        $menu[$i]["call_to_actions"][$j]["url"]=$$level2_web_url_temp;
                    }
                    else
                    {
                        for($k=1;$k<=$level3_limit;$k++)
                        {
                            $level3_title_temp="text_with_buttons_text_".$i."_".$j."_".$k;
                            $level3_type_temp="text_with_button_type_".$i."_".$j."_".$k;
                            if($$level3_title_temp=="") continue; // form gets everything but we need only filled data
                            if($$level3_type_temp=="post_back") $$level3_type_temp="postback";
                            $menu[$i]["call_to_actions"][$j]["call_to_actions"][$k]["title"]=$$level3_title_temp;
                            $menu[$i]["call_to_actions"][$j]["call_to_actions"][$k]["type"]=$$level3_type_temp;
                            if($$level3_type_temp=="postback")
                            {
                                $level3_postback_temp="text_with_button_post_id_".$i."_".$j."_".$k;
                                $level3_postback_temp_data=isset($$level3_postback_temp) ? $$level3_postback_temp : '';
                                // $$level3_postback_temp=strtoupper($$level3_postback_temp);
                                $menu[$i]["call_to_actions"][$j]["call_to_actions"][$k]["payload"]=$level3_postback_temp_data;
                                $single_postback_insert_data = array();
                                $single_postback_insert_data['user_id'] = $this->user_id;
                                $single_postback_insert_data['postback_id'] = $level3_postback_temp_data;
                                $single_postback_insert_data['page_id'] = $page_table_id;
                                $single_postback_insert_data['bot_name'] = '';
                                $postback_insert_data[] = $single_postback_insert_data; 
                                $only_postback[]=$level3_postback_temp_data;
                            }
                            else if($$level3_type_temp=="web_url")
                            {
                                $level3_web_url_temp="text_with_button_web_url_".$i."_".$j."_".$k;
                                $menu[$i]["call_to_actions"][$j]["call_to_actions"][$k]["url"]=$$level3_web_url_temp;
                            }
                        }
                    }
                }
            }
        }
        $menu_json_array=array();
        $menu_json_array["locale"]=$locale;
        $composer_input_disabled2='false';
        if($composer_input_disabled==='1') $composer_input_disabled2='true';
        $menu_json_array["composer_input_disabled"]=$composer_input_disabled2;
        $index=1;
        foreach ($menu as $key => $value) 
        {
           $menu_json_array["call_to_actions"][$index]=$value;
           $index++;
        }
        $menu_json=json_encode($menu_json_array); 
        $insert_data = array();       
        $insert_data['page_id'] = $page_table_id;
        $facebook_rx_fb_user_info_id = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_table_id)),array("facebook_rx_fb_user_info_id","page_access_token"));
        $page_access_token = $facebook_rx_fb_user_info_id[0]['page_access_token'];
        $facebook_rx_fb_user_info_id = $facebook_rx_fb_user_info_id[0]["facebook_rx_fb_user_info_id"];
        $this->db->trans_start();
        // if(!empty($postback_insert_data)) $this->db->insert_batch('messenger_bot_postback',$postback_insert_data);
        $this->basic->insert_data("messenger_bot_persistent_menu",array("user_id"=>$this->user_id,"page_id"=>$page_table_id,"locale"=>$locale,"item_json"=>$menu_json,"composer_input_disabled"=>$composer_input_disabled,'poskback_id_json'=>json_encode($only_postback)));
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE)
        echo json_encode(array('status'=>'0','message'=>$this->lang->line("something went wrong, please try again.")));
        else  
        {
            $this->session->set_flashdata('per_success',1);
            echo json_encode(array('status'=>'1','message'=>$this->lang->line("persistent menu has been created successfully.")));
        }      
    }

    public function edit_persistent_menu($id=0,$iframe=0)
    {        
        if($this->session->userdata('user_type') != 'Admin' && !in_array(197,$this->module_access))
        redirect('home/login_page', 'location'); 
        
        $data['body'] = 'messenger_tools/persistent_menu_edit';
        $data['page_title'] = $this->lang->line('Edit Persistent Menu');  
        $xdata=$this->basic->get_data("messenger_bot_persistent_menu",array("where"=>array("id"=>$id,"user_id"=>$this->user_id)));
        if(!isset($xdata[0])) exit();
        $data['xdata']=$xdata[0];
        $page_auto_id=$xdata[0]["page_id"];
        $page_info=$this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_auto_id,'user_id'=>$this->user_id)));
        if(!isset($page_info[0])) exit();

        $page_id=$page_auto_id;// database id      
        $postback_data=$this->basic->get_data("messenger_bot_postback",array("where"=>array("page_id"=>$page_id,'is_template'=>'1','template_for'=>'reply_message')),'','','',$start=NULL,$order_by='template_name ASC');        
        $poption=array();
        foreach ($postback_data as $key => $value) 
        {
            // if($value["template_for"]=="email-quick-reply" || $value["template_for"]=="phone-quick-reply" || $value["template_for"]=="location-quick-reply") continue;
            $poption[$value["postback_id"]]=$value['template_name'].' ['.$value['postback_id'].']';
        }
        $data['poption']=$poption;
        
        $data['page_info'] = isset($page_info[0]) ? $page_info[0] : array(); 
        $started_button_enabled = isset($page_info[0]["started_button_enabled"])?$page_info[0]["started_button_enabled"]:"0";
        $postback_id_list = $this->basic->get_data('messenger_bot_postback',array('where'=>array('user_id'=>$this->user_id,'page_id'=>$page_auto_id)));
        $data['postback_ids'] = $postback_id_list;
        $data['page_auto_id'] = $page_auto_id;
        $data['started_button_enabled'] = $started_button_enabled;
        $data['locale']=$this->sdk_locale();
        
        $data['iframe']=$iframe;
        $this->_viewcontroller($data); 
    }

    public function edit_persistent_menu_action()
    {
        if($this->is_demo == '1')
        {
            if($this->session->userdata('user_type') == "Admin")
            {
                echo "<div class='alert alert-danger text-center'><i class='fa fa-ban'></i> This function is disabled from admin account in this demo!!</div>";
                exit();
            }
        }
        if(!$_POST) exit();
        $post=$_POST;

        foreach ($post as $key => $value) 
        {
            $$key=$value;
        }
        if($this->basic->is_exist("messenger_bot_persistent_menu",array("page_id"=>$page_table_id,"locale"=>$locale,"id!="=>$auto_id)))
        {
            echo json_encode(array('status'=>'0','message'=>$this->lang->line("persistent menu is already exists for this locale.")));
            exit();
        }
        $menu=array();
        $postback_insert_data=array();
        $only_postback=array();
        $current_postbacks=json_decode($current_postbacks,true);
        $current_postbacks=array_map('strtoupper', $current_postbacks);
        for($i=1;$i<=$level1_limit;$i++)
        {
            $level_title_temp="text_with_buttons_text_".$i;
            $level_type_temp="text_with_button_type_".$i;
            if($$level_title_temp=="") continue; // form gets everything but we need only filled data
            if($$level_type_temp=="post_back") $$level_type_temp="postback";
            $menu[$i]=array
            (
                "title"=>$$level_title_temp,
                "type"=> $$level_type_temp
            );
            if($$level_type_temp=="postback")
            {
                $level_postback_temp="text_with_button_post_id_".$i;
                $level_postback_temp_data=isset($$level_postback_temp) ? $$level_postback_temp : '';
                // $$level_postback_temp=strtoupper($$level_postback_temp);
                $menu[$i]["payload"]=$level_postback_temp_data;
                $single_postback_insert_data = array();
                $single_postback_insert_data['user_id'] = $this->user_id;
                $single_postback_insert_data['postback_id'] = $level_postback_temp_data;
                $single_postback_insert_data['page_id'] = $page_table_id;
                $single_postback_insert_data['bot_name'] = '';
                if(!in_array(strtoupper($level_postback_temp_data), $current_postbacks))
                $postback_insert_data[] = $single_postback_insert_data; 
                $only_postback[]=$level_postback_temp_data;
            }
            else if($$level_type_temp=="web_url")
            {
                $level_web_url_temp="text_with_button_web_url_".$i;
                $menu[$i]["url"]=$$level_web_url_temp;
            }
            else
            {
                for($j=1;$j<=$level2_limit;$j++)
                {
                    $level2_title_temp="text_with_buttons_text_".$i."_".$j;
                    $level2_type_temp="text_with_button_type_".$i."_".$j;
                    if($$level2_title_temp=="") continue; // form gets everything but we need only filled data
                    if($$level2_type_temp=="post_back") $$level2_type_temp="postback";
                    $menu[$i]["call_to_actions"][$j]["title"]=$$level2_title_temp;
                    $menu[$i]["call_to_actions"][$j]["type"]=$$level2_type_temp;
                    if($$level2_type_temp=="postback")
                    {
                        $level2_postback_temp="text_with_button_post_id_".$i."_".$j;
                        $level2_postback_temp_data=isset($$level2_postback_temp) ? $$level2_postback_temp : '';
                        // $$level2_postback_temp=strtoupper($$level2_postback_temp);
                        $menu[$i]["call_to_actions"][$j]["payload"]=$level2_postback_temp_data;
                        $single_postback_insert_data = array();
                        $single_postback_insert_data['user_id'] = $this->user_id;
                        $single_postback_insert_data['postback_id'] = $level2_postback_temp_data;
                        $single_postback_insert_data['page_id'] = $page_table_id;
                        $single_postback_insert_data['bot_name'] = '';
                        if(!in_array(strtoupper($level2_postback_temp_data), $current_postbacks))
                        $postback_insert_data[] = $single_postback_insert_data; 
                        $only_postback[]=$level2_postback_temp_data;
                    }
                    else if($$level2_type_temp=="web_url")
                    {
                        $level2_web_url_temp="text_with_button_web_url_".$i."_".$j;
                        $menu[$i]["call_to_actions"][$j]["url"]=$$level2_web_url_temp;
                    }
                    else
                    {
                        for($k=1;$k<=$level3_limit;$k++)
                        {
                            $level3_title_temp="text_with_buttons_text_".$i."_".$j."_".$k;
                            $level3_type_temp="text_with_button_type_".$i."_".$j."_".$k;
                            if($$level3_title_temp=="") continue; // form gets everything but we need only filled data
                            if($$level3_type_temp=="post_back") $$level3_type_temp="postback";
                            $menu[$i]["call_to_actions"][$j]["call_to_actions"][$k]["title"]=$$level3_title_temp;
                            $menu[$i]["call_to_actions"][$j]["call_to_actions"][$k]["type"]=$$level3_type_temp;
                            if($$level3_type_temp=="postback")
                            {
                                $level3_postback_temp="text_with_button_post_id_".$i."_".$j."_".$k;
                                $level3_postback_temp_data=isset($$level3_postback_temp) ? $$level3_postback_temp : '';
                                // $$level3_postback_temp=strtoupper($$level3_postback_temp);
                                $menu[$i]["call_to_actions"][$j]["call_to_actions"][$k]["payload"]=$level3_postback_temp_data;
                                $single_postback_insert_data = array();
                                $single_postback_insert_data['user_id'] = $this->user_id;
                                $single_postback_insert_data['postback_id'] = $level3_postback_temp_data;
                                $single_postback_insert_data['page_id'] = $page_table_id;
                                $single_postback_insert_data['bot_name'] = '';
                                if(!in_array(strtoupper($level3_postback_temp_data), $current_postbacks))
                                $postback_insert_data[] = $single_postback_insert_data; 
                                $only_postback[]=$level3_postback_temp_data;
                            }
                            else if($$level3_type_temp=="web_url")
                            {
                                $level3_web_url_temp="text_with_button_web_url_".$i."_".$j."_".$k;
                                $menu[$i]["call_to_actions"][$j]["call_to_actions"][$k]["url"]=$$level3_web_url_temp;
                            }
                        }
                    }
                }
            }
        }


        $menu_json_array=array();
        $menu_json_array["locale"]=$locale;
        $composer_input_disabled2='false';
        if($composer_input_disabled==='1') $composer_input_disabled2='true';
        $menu_json_array["composer_input_disabled"]=$composer_input_disabled2;
        $index=1;
        foreach ($menu as $key => $value) 
        {
           $menu_json_array["call_to_actions"][$index]=$value;
           $index++;
        }
        $menu_json=json_encode($menu_json_array); 
        $insert_data = array();       
        $insert_data['page_id'] = $page_table_id;
        $facebook_rx_fb_user_info_id = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$page_table_id)),array("facebook_rx_fb_user_info_id","page_access_token"));
        $page_access_token = $facebook_rx_fb_user_info_id[0]['page_access_token'];
        $facebook_rx_fb_user_info_id = $facebook_rx_fb_user_info_id[0]["facebook_rx_fb_user_info_id"];
        
        $this->db->trans_start();
        // if(!empty($postback_insert_data)) $this->db->insert_batch('messenger_bot_postback',$postback_insert_data);
        $this->basic->update_data("messenger_bot_persistent_menu",array("id"=>$auto_id,"user_id"=>$this->user_id),array("locale"=>$locale,"item_json"=>$menu_json,"composer_input_disabled"=>$composer_input_disabled,'poskback_id_json'=>json_encode($only_postback)));
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE)
        echo json_encode(array('status'=>'0','message'=>$this->lang->line("something went wrong, please try again.")));
        else  
        {
            $this->session->set_flashdata('per_update_success',1);
            echo json_encode(array('status'=>'1','message'=>$this->lang->line("persistent menu has been updated successfully.")));
        }      
    }


    // -------------Tree view data functions-------------------------
    private function set_nest_easy($postback_array=array(),$get_started_level)
    {
        for ($loop_level=$get_started_level-1; $loop_level >=1 ; $loop_level--) 
        { 
            foreach ($postback_array as $key => $value) 
            {
                $level=$value['level'];
                if($level==$loop_level)
                {
                    if(isset($value['child_postback']) && is_array($value['child_postback']))
                    {
                        foreach ($value['child_postback'] as $key2 => $value2) 
                        {
                            $postback_array[$key]['child_postback'][$key2]=$postback_array[$value2];
                        }
                    }
                }

            }
        }

        foreach ($postback_array as $key => $value)
        {
            if($value['level']>1)
            unset($postback_array[$key]); // removing other  unnessary rows so that only nested postback stays 
        }

        return $postback_array;
    }

    private function get_nest($current_level=1,$page_table_id=0)
    {       
        if($this->session->userdata('user_type') != 'Admin' && !in_array(257, $this->module_access)) return ""; 
            $current_level_prev=$current_level-1;

            $condition="if(\$tempurl!='') \$templabel='<a class=\"iframed\" href=\"'.\$tempurl.'\">'.\$display.'</a>';
            else \$templabel='<span class=\"text-danger\">'.\$display.'</span>';";

            $output="";
            $output.=" 
            \$get_started_tree.='<ul>'; ";
            $output.="
            // nested post back may have weburl,phone or email child and they are single element without child                
            if(!empty(\$value".$current_level_prev."['web_url'])) // has a web url as child, 0 index consists url
            {              
              foreach(\$value".$current_level_prev."['web_url'] as \$tempukey => \$tempuval)
              {
                \$get_started_tree.= '
                <li>
                    <a href=\"'.\$this->tree_security(\$tempuval).'\" data-toggle=\"tooltip\" data-title=\"'.\$this->tree_security(\$tempuval).'\" target=\"_blank\"><i class=\"fas fa-external-link-alt\"></i> Web URL</a>
                </li>';
              }
            }
            if(!empty(\$value".$current_level_prev."['phone_number'])) // has a phone as child
            {
              foreach(\$value".$current_level_prev."['phone_number'] as \$tempukey => \$tempuval)
              {
                \$get_started_tree.= '
                <li><i class=\"fas fa-phone-square\"></i> Phone Number</li>';
              }
            }

            if(!empty(\$value".$current_level_prev."['email'])) // has a email as child
            {
              foreach(\$value".$current_level_prev."['email'] as \$tempukey => \$tempuval)
              {
                \$get_started_tree.= '
                <li> <i class=\"far fa-envelope-open\"></i> Email</li>';
              }
            }

            if(!empty(\$value".$current_level_prev."['location'])) // has a location as child
            {
              foreach(\$value".$current_level_prev."['location'] as \$tempukey => \$tempuval)
              {
                \$get_started_tree.= '
                <li><i class=\"fas fa-map-marker-alt\"></i> Location</li>';
              }
            }

            if(!empty(\$value".$current_level_prev."['call_us'])) // has a call_us as child
            {
              foreach(\$value".$current_level_prev."['call_us'] as \$tempukey => \$tempuval)
              {
                \$get_started_tree.= '
                <li><span data-toggle=\"tooltip\" title=\"'.\$this->tree_security(\$tempuval).'\"><i class=\"fas fa-headset\"></i> Call Us</span></li>';
              }
            }

            if(!empty(\$value".$current_level_prev."['birthdate'])) // has a birthdate webview as child
            {
              foreach(\$value".$current_level_prev."['birthdate'] as \$tempukey => \$tempuval)
              {
                \$get_started_tree.= '
                <li><i class=\"fas fa-birthday-cake\"></i> Birthdate</li>';
              }
            }

            if(!empty(\$value".$current_level_prev."['webview'])) // has a web view as child, 0 index consists url
            {              
              foreach(\$value".$current_level_prev."['webview'] as \$tempukey => \$tempuval)
              {
                \$get_started_tree.= '
                <li>
                    <a href=\"'.\$this->tree_security(\$tempuval).'\" data-toggle=\"tooltip\" data-title=\"'.\$this->tree_security(\$tempuval).'\" target=\"_blank\"><i class=\"fab fa-wpforms\"></i> Webview</a>
                </li>';
              }
            }

            if(isset(\$value".$current_level_prev."['child_postback']))
            foreach (\$value".$current_level_prev."['child_postback'] as \$key".$current_level." => \$value".$current_level.")
            {                                    
                if(is_array(\$value".$current_level.")) // if have new child that does not appear in parent tree
                {
                    \$tempid=isset(\$value".$current_level."['id'])?\$value".$current_level."['id']:0;
                    \$tempis_template=isset(\$value".$current_level."['is_template'])?\$value".$current_level."['is_template']:'';
                    \$tempostbackid=isset(\$value".$current_level."['postback_id'])?\$this->tree_security(\$value".$current_level."['postback_id']):'';
                    \$tempbotname=isset(\$value".$current_level."['bot_name'])?\$this->tree_security(\$value".$current_level."['bot_name']):'';
                    
                    if(\$tempis_template=='1') \$tempurl=base_url('messenger_bot/edit_template/'.\$tempid.'/1'); // it is template
                    else if(\$tempis_template=='0') \$tempurl=base_url('messenger_bot/edit_bot/'.\$tempid.'/1'); // it is bot
                    else \$tempurl='';  

                    if(\$tempurl!='')
                    {
                        if(\$tempbotname!='') \$display='<span class=\"text-info\" data-toggle=\"tooltip\" title=\"'.\$tempostbackid.' : click to edit settings\"><i class=\"far fa-hand-pointer\"></i> '.\$tempbotname.'</span>';
                        else \$display='<span class=\"text-info\"><i class=\"far fa-hand-pointer\"></i> '.\$tempostbackid.'</span>';
                    }
                    else  // orphan postback
                    {
                        \$create_child_postback_url = base_url('messenger_bot/create_new_template/1/'.\$page_table_id.'/'.urlencode(\$tempostbackid));
                        \$display='<a class=\"iframed text-danger\" href=\"'.\$create_child_postback_url.'\" data-toggle=\"tooltip\" title=\"'.\$tempostbackid.' is an empty child postback, click to set reply\"><i class=\"fas fa-exclamation-triangle\"></i> '.\$tempostbackid.'</a>';
                    }   
                    
                    ".$condition."

                    \$get_started_tree.= '
                    <li>'.\$templabel;
                } 
                else // child already appear in parent tree
                {                    
                    if(isset(\$linear_postback_array[\$value".$current_level."])) 
                    {
                        \$tempid=isset(\$linear_postback_array[\$value".$current_level."]['id'])?\$linear_postback_array[\$value".$current_level."]['id']:0;
                        \$tempis_template=isset(\$linear_postback_array[\$value".$current_level."]['is_template'])?\$linear_postback_array[\$value".$current_level."]['is_template']:'';
                        \$tempostbackid=isset(\$linear_postback_array[\$value".$current_level."]['postback_id'])?\$this->tree_security(\$linear_postback_array[\$value".$current_level."]['postback_id']):'';
                        \$tempbotname=isset(\$linear_postback_array[\$value".$current_level."]['bot_name'])?\$this->tree_security(\$linear_postback_array[\$value".$current_level."]['bot_name']):'';

                        if(\$tempis_template=='1') \$tempurl=base_url('messenger_bot/edit_template/'.\$tempid.'/1'); // it is template
                        else if(\$tempis_template=='0') \$tempurl=base_url('messenger_bot/edit_bot/'.\$tempid.'/1'); // it is bot
                        else \$tempurl='';

                        if(\$tempbotname!='') \$display='<span class=\"text-muted\" data-toggle=\"tooltip\" title=\"'.\$tempostbackid.' is already exist in the tree\"><i class=\"fas fa-redo\"></i> '.\$tempbotname.'</span>';
                        else \$display='<span class=\"text-muted\" data-toggle=\"tooltip\" title=\"'.\$tempostbackid.' is already exist in the tree\"><i class=\"fas fa-redo\"></i> '.\$tempostbackid.'</span>';
                        
                         ".$condition."

                        \$get_started_tree.= '
                        <li>'.\$templabel;
                    }
                
                }";

        return $output;
    }

    

    private function get_child_info($messenger_bot_info,$page_table_id)
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(257, $this->module_access)) return array(); 
        foreach ($messenger_bot_info as $info) 
        {

            $message= $info['message'];
            $keyword_bot_id= $info['id'];
            $keywrods_list= $info['keywords'];
            $template_type=$info['template_type'];
            $this->postback_info[$keyword_bot_id]['keywrods_list']=$keywrods_list;


            /** Get all postback button id from json message **/

            $button_information= $this->get_button_information_from_json($message,$template_type);
            $matches[1]=isset($button_information['postback']) ? $button_information['postback'] : array();
            
            $web_url=isset($button_information['web_url']) ? $button_information['web_url'] : array();
            $webview=isset($button_information['webview']) ? $button_information['webview'] : array();
            $phone_number=isset($button_information['phone_number']) ? $button_information['phone_number'] : array();
            $email=isset($button_information['email']) ? $button_information['email'] : array();
            $location=isset($button_information['location']) ? $button_information['location'] : array();
            $call_us=isset($button_information['call_us']) ? $button_information['call_us'] : array();
            $birthdate=isset($button_information['birthdate']) ? $button_information['birthdate'] : array();


            $k=0;
            $level=0;

            do
            {

                $level++;
                $this->get_postback_info($matches[1],$page_table_id,$keyword_bot_id,$level);

                $matches=array();

                if(!isset($this->postback_info[$keyword_bot_id]['postback_info'])) break;

                foreach ($this->postback_info[$keyword_bot_id]['postback_info'] as $p_info) {

                    $child=$p_info['child_postback'];

                    if(empty($child)) continue;

                    foreach ($child as $child_postback) {
                        if(!isset($this->postback_info[$keyword_bot_id]['postback_info'][$child_postback])) 
                            $matches[1][]=$child_postback;
                    }
                    
                }

                 $k++;

                if($k==100) break;


            }
            while(!empty($matches[1])); 

            $this->postback_info[$keyword_bot_id]['web_url']= $web_url;
            $this->postback_info[$keyword_bot_id]['webview']= $webview;
            $this->postback_info[$keyword_bot_id]['phone_number']= $phone_number;
            $this->postback_info[$keyword_bot_id]['email']= $email;
            $this->postback_info[$keyword_bot_id]['location']= $location;
            $this->postback_info[$keyword_bot_id]['call_us']= $call_us;

        }
    
        return $this->postback_info;

    }

    private function get_postback_info($matches,$page_table_id,$keyword_bot_id,$level)
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(257, $this->module_access)) return array();     
        foreach ($matches as $postback_match) 
        {

            $where['where'] = array('page_id'=> $page_table_id,'postback_id' =>$postback_match);
            /**Get BOT settings information from messenger_bot table as base table. **/
            $messenger_postback_info = $this->basic->get_data("messenger_bot",$where);

            $message= isset($messenger_postback_info[0]['message']) ? $messenger_postback_info[0]['message'] :"" ;

            $id= isset($messenger_postback_info[0]['id']) ? $messenger_postback_info[0]['id']:"";
            $is_template= isset($messenger_postback_info[0]['is_template']) ? $messenger_postback_info[0]['is_template']:"";
            $template_type= isset($messenger_postback_info[0]['template_type']) ? $messenger_postback_info[0]['template_type']:"";
            $bot_name= isset($messenger_postback_info[0]['bot_name']) ? $messenger_postback_info[0]['bot_name']:"";


            if($is_template=='1'){
                $postback_id_info=$this->basic->get_data('messenger_bot_postback',array('where'=>array('messenger_bot_table_id'=>$id,'is_template'=>'1')));
                $id= isset($postback_id_info[0]['id']) ? $postback_id_info[0]['id']:"";
            }          

            

            preg_match_all('#payload":"(.*?)"#si', $message, $matches);

            $button_information= $this->get_button_information_from_json($message,$template_type);
            $matches[1]=isset($button_information['postback']) ? $button_information['postback'] : array();

            $web_url= isset($button_information['web_url']) ? $button_information['web_url'] : array();
            $webview=isset($button_information['webview']) ? $button_information['webview'] : array();
            $phone_number=isset($button_information['phone_number']) ? $button_information['phone_number'] : array();
            $email=isset($button_information['email']) ? $button_information['email'] : array();
            $location=isset($button_information['location']) ? $button_information['location'] : array();
            $call_us=isset($button_information['call_us']) ? $button_information['call_us'] : array();
            $birthdate=isset($button_information['birthdate']) ? $button_information['birthdate'] : array();
        
            $this->postback_info[$keyword_bot_id]['postback_info'][$postback_match] = array("id"=>$id,"child_postback"=>$matches[1],'postback_id'=>$postback_match,"level"=>$level,'is_template'=>$is_template,"web_url"=>$web_url,"webview"=>$webview,
                "phone_number" =>$phone_number,
                "email"     =>$email,
                "location"  =>$location,
                'bot_name'  =>$bot_name,
                'call_us'   =>$call_us,
                'birthdate' =>$birthdate
                );
        }

        return $this->postback_info;
    }


    private function get_button_information_from_json($json_message,$template_type)
    {
        if($this->session->userdata('user_type') != 'Admin' && !in_array(257, $this->module_access)) return array();

        $full_message_array = json_decode($json_message,true);
        $result = array();

        if(!isset($full_message_array[1]))
        {
          $full_message_array[1] = $full_message_array;
          $full_message_array[1]['message']['template_type'] = $template_type;
        }


        for($k=1;$k<=3;$k++)
        { 

          $full_message[$k] = isset($full_message_array[$k]['message']) ? $full_message_array[$k]['message'] : array();

          if(isset($full_message[$k]["template_type"]))
            $full_message[$k]["template_type"] = str_replace('_', ' ', $full_message[$k]["template_type"]);  

          for ($i=1; $i <=11 ; $i++) 
          {

            if(isset($full_message[$k]['quick_replies'][$i-1]['payload']))
              $result['postback'][] = (isset($full_message[$k]['quick_replies'][$i-1]['payload'])) ? $full_message[$k]['quick_replies'][$i-1]['payload']:"";

            else if(isset($full_message[$k]['quick_replies'][$i-1]['content_type']) && $full_message[$k]['quick_replies'][$i-1]['content_type'] == 'user_phone_number')
              $result['phone_number'][] = "user_phone_number";

            else if(isset($full_message[$k]['quick_replies'][$i-1]['content_type']) && $full_message[$k]['quick_replies'][$i-1]['content_type'] == 'user_email')
              $result['email'][] = "user_email";

            else if(isset($full_message[$k]['quick_replies'][$i-1]['content_type']) && $full_message[$k]['quick_replies'][$i-1]['content_type'] == 'location')
              $result['location'][] = "location";


            else if(isset($full_message[$k]['attachment']['payload']['buttons'][$i-1]['payload']) && $full_message[$k]['attachment']['payload']['buttons'][$i-1]['type'] == 'postback')
              $result['postback'][] = (isset($full_message[$k]['attachment']['payload']['buttons'][$i-1]['payload']) && $full_message[$k]['attachment']['payload']['buttons'][$i-1]['type'] == 'postback') ? $full_message[$k]['attachment']['payload']['buttons'][$i-1]['payload']:"";


            else if(isset($full_message[$k]['attachment']['payload']['buttons'][$i-1]['webview_height_ratio']) && strpos($full_message[$k]['attachment']['payload']['buttons'][$i-1]['url'], 'webview_builder/get_birthdate') !==false)
              $result['birthdate'][] = "user_birthdate";


              else if(isset($full_message[$k]['attachment']['payload']['buttons'][$i-1]['webview_height_ratio']))
              $result['webview'][] = (isset($full_message[$k]['attachment']['payload']['buttons'][$i-1]['url'])) ? $full_message[$k]['attachment']['payload']['buttons'][$i-1]['url'] : "";

            
            else if(isset($full_message[$k]['attachment']['payload']['buttons'][$i-1]['url']) && !isset($full_message[$k]['attachment']['payload']['buttons'][$i-1]['webview_height_ratio']))
              $result['web_url'][] = (isset($full_message[$k]['attachment']['payload']['buttons'][$i-1]['url'])) ? $full_message[$k]['attachment']['payload']['buttons'][$i-1]['url'] : "";

           

            else if(isset($full_message[$k]['attachment']['payload']['buttons'][$i-1]['payload']) && $full_message[$k]['attachment']['payload']['buttons'][$i-1]['type'] == 'phone_number')
              $result['call_us'][] = (isset($full_message[$k]['attachment']['payload']['buttons'][$i-1]['payload']) && $full_message[$k]['attachment']['payload']['buttons'][$i-1]['type'] == 'phone_number') ? $full_message[$k]['attachment']['payload']['buttons'][$i-1]['payload'] : "";
          }


          for ($j=1; $j <=5 ; $j++)
          {
            for ($i=1; $i <=3 ; $i++)
            {
              if(isset($full_message[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['payload']) && $full_message[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['type'] == 'postback')
                $result['postback'][] = (isset($full_message[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['payload']) && $full_message[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['type'] == 'postback') ? $full_message[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['payload']:"";

              else if(isset($full_message[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['webview_height_ratio']) && strpos($full_message[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['url'], 'webview_builder/get_birthdate') !==false)
              $result['birthdate'][] = "user_birthdate";


             else if(isset($full_message[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['webview_height_ratio']))
                $result['webview'][] = (isset($full_message[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['url'])) ? $full_message[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['url'] : "";

              else if(isset($full_message[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['url']) && !isset($full_message[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['webview_height_ratio']))
                $result['web_url'][] = (isset($full_message[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['url'])) ? $full_message[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['url'] : "";

            


              else if(isset($full_message[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['payload']) && $full_message[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['type'] == 'phone_number')
                $result['call_us'][] = (isset($full_message[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['payload']) && $full_message[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['type'] == 'phone_number') ? $full_message[$k]['attachment']['payload']['elements'][$j-1]['buttons'][$i-1]['payload'] : "";
            }
          }

        }

        return $result;
    }
    // -------------Tree view data functions-------------------------



    public function bot_menu_section()
    {
        $data['body'] = 'messenger_tools/menu_block';
        $data['page_title'] = $this->lang->line('Messenger Bot');
        $this->_viewcontroller($data);
    }
    


    public function update_subscriber_last_interaction($subscriber_id='',$time=''){

        $unixtime=strtotime($time);
        date_default_timezone_set('UTC');
        $utc_time= date("Y-m-d H:i:s",$unixtime);
        //update Subscriber information
        $update_data=array("last_subscriber_interaction_time"=>$utc_time,"is_24h_1_sent"=>'0',"unavailable"=>"0","unavailable_conversation"=>"0");
        $this->basic->update_data('messenger_bot_subscriber',$where_array=array("subscribe_id"=>$subscriber_id),$update_data); 

    }

    public function get_json_code()
    {
        $this->ajax_check();
        $table_id = $this->input->post('table_id');
        $postback_info = $this->basic->get_data('messenger_bot_postback',array('where'=>array('id'=>$table_id,'user_id'=>$this->user_id)),array('template_jsoncode'));
        if(empty($postback_info))
        {
            $error_message = '
                        <div class="card" id="nodata">
                          <div class="card-body">
                            <div class="empty-state">
                              <img class="img-fluid" style="height: 200px" src="'.base_url('assets/img/drawkit/drawkit-nature-man-colour.svg').'" alt="image">
                              <h2 class="mt-0">'.$this->lang->line("We could not find any data.").'</h2>
                            </div>
                          </div>
                        </div>';
            echo $error_message;
        }
        else
        {     
            $json_info = json_decode($postback_info[0]['template_jsoncode'],true);

            $content='<div class="row">
                    <div class="col-12">';
            $i=1;
            foreach($json_info as $value)
            {
                $json_value = $value['message'];
                unset($json_value['typing_on_settings']);
                unset($json_value['delay_in_reply']);
                $content .= '
                            <div class="card">
                              <div class="card-header">
                                <h4>Reply '.$i.'</h4>
                              </div>
                              <div class="card-body">
                                <pre class="language-javascript">
                                    <code class="dlanguage-javascript copy_code">
'.json_encode($json_value).'
                                    </code>
                                </pre>
                              </div>
                            </div>';
                $i++;
            }

                        


            $content .='</div>
                </div>
                <script>
                    $(document).ready(function() {
                        Prism.highlightAll();
                        $(".toolbar-item").find("a").addClass("copy");

                        $(document).on("click", ".copy", function(event) {
                            event.preventDefault();

                            $(this).html("'.$this->lang->line('Copied!').'");
                            var that = $(this);
                            
                            var text = $(this).prev("code").text();
                            var temp = $("<input>");
                            $("body").append(temp);
                            temp.val(text).select();
                            document.execCommand("copy");
                            temp.remove();

                            setTimeout(function(){
                              $(that).html("'.$this->lang->line('Copy').'");
                            }, 2000); 

                        });
                    });
                </script>
                ';
            echo $content;
        }

    }

}   