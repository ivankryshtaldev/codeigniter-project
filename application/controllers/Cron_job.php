<?php
require_once("Home.php");

class Cron_job extends Home
{
    public function __construct()
    {
        parent::__construct();
        $this->upload_path = realpath( APPPATH . '../upload');        
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

    protected function get_fb_rx_config($fb_user_id=0)
    {
        if($fb_user_id==0) return 0;

        $getdata= $this->basic->get_data("facebook_rx_fb_user_info",array("where"=>array("id"=>$fb_user_id)),array("facebook_rx_config_id"));
        $return_val = isset($getdata[0]["facebook_rx_config_id"]) ? $getdata[0]["facebook_rx_config_id"] : 0;

        return $return_val; 
       
    }


    public function index()
    {
       $this->get_api();
    }

    public function _api_key_generator()
    {
        if ($this->session->userdata('logged_in') != 1)
        redirect('home/login_page', 'location');

        if($this->session->userdata('user_type') != 'Admin')
        redirect('home/login_page', 'location');

        $this->member_validity();
        $val=$this->session->userdata("user_id")."-".substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') , 0 , 7 ).time()
        .substr(str_shuffle('abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ23456789') , 0 , 7 );
        return $val;
    }

    public function get_api()
    {
        if ($this->session->userdata('logged_in') != 1)
        redirect('home/login_page', 'location');

        if($this->session->userdata('user_type') != 'Admin')
        redirect('home/login_page', 'location');

        $this->member_validity();

        $data['body'] = "admin/cron_job/command";
        $data['page_title'] = $this->lang->line("Cron Job");
        $api_data=$this->basic->get_data("native_api",array("where"=>array("user_id"=>$this->session->userdata("user_id"))));
        $data["api_key"]="";
        if(count($api_data)>0) $data["api_key"]=$api_data[0]["api_key"];
        $this->_viewcontroller($data);
    }

    public function get_api_action()
    { 
        if($this->is_demo == '1')
        {
            echo "<h2 style='text-align:center;color:red;border:1px solid red; padding: 10px'>This feature is disabled in this demo.</h2>"; 
            exit();
        }
        
        if ($this->session->userdata('logged_in') != 1)
        redirect('home/login', 'location');

        if($this->session->userdata('user_type') != 'Admin')
        redirect('home/login_page', 'location');

        $api_key=$this->_api_key_generator(); 
        if($this->basic->is_exist("native_api",array("api_key"=>$api_key)))
        $this->get_api_action();

        $user_id=$this->session->userdata("user_id");        
        if($this->basic->is_exist("native_api",array("user_id"=>$user_id)))
        $this->basic->update_data("native_api",array("user_id"=>$user_id),array("api_key"=>$api_key));
        else $this->basic->insert_data("native_api",array("api_key"=>$api_key,"user_id"=>$user_id));
            
        redirect('cron_job/get_api', 'location');
    }



    public function api_key_check($api_key="")
    {
        $user_id="";
        if($api_key!="")
        {
            $explde_api_key=explode('-',$api_key);
            $user_id="";
            if(array_key_exists(0, $explde_api_key))
            $user_id=$explde_api_key[0];
        }

        if($api_key=="")
        {        
            echo "API Key is required.";    
            exit();
        }

        if(!$this->basic->is_exist("native_api",array("api_key"=>$api_key,"user_id"=>$user_id)))
        {
           echo "API Key does not match with any user.";
           exit();
        }

        if(!$this->basic->is_exist("users",array("id"=>$user_id,"status"=>"1","deleted"=>"0","user_type"=>"Admin")))
        {
            echo "API Key does not match with any authentic user.";
            exit();
        }              
       

    }      


    // =====================SUBSCRIBER & AUTO REPLY/COMMENT===================
    public function background_scanning($api_key="") //background_scanning_update_subscriber_info
    {
    
        $this->api_key_check($api_key);
        $this->load->library('Fb_rx_login');

        $auto_sync_data = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("auto_sync_lead"=>"1")),$select='',$join='',$limit=1,$start=NULL,$order_by='last_lead_sync ASC'); // will work on only one row

        foreach ($auto_sync_data as $key2 => $value2) 
        {
            $this->basic->update_data("facebook_rx_fb_page_info",array("id"=>$value2['id']),array("auto_sync_lead"=>"2")); // making it processing

            $facebook_rx_fb_page_info_id = $value2['id'];
            $get_concersation_info = $this->fb_rx_login->get_all_conversation_page_cron($value2['page_access_token'],$value2['page_id'],$scan_limit=1000,$value2["next_scan_url"]); // will get 1000 lead per cron call

            $success = 0;
            $total=0;

            $facebook_rx_fb_user_info_id = $value2['facebook_rx_fb_user_info_id'];                
            $db_page_id =  $value2['page_id'];
            $db_user_id =  $value2['user_id'];

            foreach($get_concersation_info['message_info'] as &$item) 
            {                
                $db_client_id  =  $item['id'];
                $db_client_thread_id  =   $item['thead_id'];

                $insert_name=0;
                if($item['name'] != 'Facebook User') $insert_name=1;
                $link = $item['link'];

                $db_client_name  =  $this->db->escape($item['name']);
                $db_permission  =  '1';
                $subscribed_at=date("Y-m-d H:i:s");

                if($insert_name)
                {                
                    // $this->basic->execute_complex_query("INSERT IGNORE INTO messenger_bot_subscriber(page_table_id,page_id,user_id,client_thread_id,subscribe_id,full_name,permission,subscribed_at,link,is_imported,is_updated_name,is_bot_subscriber) VALUES('$facebook_rx_fb_page_info_id','$db_page_id',$db_user_id,'$db_client_thread_id','$db_client_id',$db_client_name,'$db_permission','$subscribed_at','$link','1','0','0');");

                    $sql="INSERT INTO messenger_bot_subscriber(page_table_id,page_id,user_id,client_thread_id,subscribe_id,full_name,permission,subscribed_at,link,is_imported,is_updated_name,is_bot_subscriber) VALUES('$facebook_rx_fb_page_info_id','$db_page_id',$db_user_id,'$db_client_thread_id','$db_client_id',$db_client_name,'$db_permission','$subscribed_at','$link','1','1','0')
                      ON DUPLICATE KEY UPDATE client_thread_id =  '$db_client_thread_id',link='$link',full_name=$db_client_name";

                    $this->basic->execute_complex_query($sql);

                    if($this->db->affected_rows() != 0) $success++ ;
                    $total++;
                }
            }

            $next_scan_url=$get_concersation_info["next_scan_url"];
            if($next_scan_url=="") $current_state="3";
            else $current_state="1";

            $sql = "SELECT count(id) as permission_count FROM `messenger_bot_subscriber` WHERE page_table_id='$facebook_rx_fb_page_info_id' AND permission='1' AND user_id=".$db_user_id;
            $count_data = $this->db->query($sql)->row_array();

            $sql2 = "SELECT count(id) as permission_count FROM `messenger_bot_subscriber` WHERE page_table_id='$facebook_rx_fb_page_info_id' AND permission='0' AND user_id=".$db_user_id;
            $count_data2 = $this->db->query($sql2)->row_array();

           // how many are subscribed and how many are unsubscribed
            $subscribed = isset($count_data["permission_count"]) ? $count_data["permission_count"] : 0;
            $unsubscribed = isset($count_data2["permission_count"]) ? $count_data2["permission_count"] : 0;
            $current_lead_count=$subscribed+$unsubscribed;

            $this->basic->update_data("facebook_rx_fb_page_info",array("id"=>$facebook_rx_fb_page_info_id,"facebook_rx_fb_user_info_id"=>$facebook_rx_fb_user_info_id),array("current_subscribed_lead_count"=>$subscribed,"current_unsubscribed_lead_count"=>$unsubscribed,"last_lead_sync"=>date("Y-m-d H:i:s"),"current_lead_count"=>$current_lead_count,"auto_sync_lead"=>$current_state,"next_scan_url"=>$next_scan_url));
            // echo $this->db->last_query();
        
        } 
    }
    
    public function auto_comment_on_post_orginal($api_key = '')
    {

        //api key need to be checked
        $this->api_key_check($api_key);

        //load library for commenting
        $this->load->library('fb_rx_login');

        //fetch data from database
        if($this->is_demo == '1')
        $where['where'] = array('auto_comment_reply_info.auto_private_reply_status' => '0', 'auto_comment_reply_info.user_id !='=>1);
        else
        $where['where'] = array('auto_comment_reply_info.auto_private_reply_status' => '0');

        $join = array('auto_comment_reply_tb'=>"auto_comment_reply_info.auto_comment_template_id=auto_comment_reply_tb.id,left");
        $select = array('auto_comment_reply_info.*','auto_comment_reply_tb.auto_reply_comment_text');
        $limit = 10;
        $order_by = 'auto_comment_reply_info.last_updated_at asc';
        $auto_comment_reply_info = $this->basic->get_data('auto_comment_reply_info', $where, $select, $join, $limit, "", $order_by);

        if(count($auto_comment_reply_info) == 0) 
            return; 

        //update campaign status and create page access token's array
        $page_info_table_list = array();
        $campaign_post_id_info = array();
        $campaign_post_info = array();
        foreach ($auto_comment_reply_info as $single_comment_reply_info) {
            
            $this->basic->update_data('auto_comment_reply_info', array("id" => $single_comment_reply_info['id']), array("auto_private_reply_status" => '1'));

            array_push($page_info_table_list, $single_comment_reply_info['page_info_table_id']);
            $campaign_post_id_info[$single_comment_reply_info['id']] = $single_comment_reply_info['page_info_table_id'];
        }
        
        $page_info_table_list = array_unique($page_info_table_list);


        //page's info array
        $where = array("where_in" => array("facebook_rx_fb_page_info.id" => $page_info_table_list) );
        $join = array('facebook_rx_fb_user_info'=>"facebook_rx_fb_page_info.facebook_rx_fb_user_info_id=facebook_rx_fb_user_info.id,left");
        $select = array("facebook_rx_fb_page_info.*", "facebook_rx_fb_user_info.facebook_rx_config_id");
        $page_info_list = $this->basic->get_data('facebook_rx_fb_page_info',$where, $select, $join);


        //associate page info and other info with campaign id
        foreach ($campaign_post_id_info as $key_id => $page_info_id) {
            
            foreach ($page_info_list as $single_page_info) {
                
                if($page_info_id == $single_page_info['id']){

                    $campaign_post_info[$key_id]['facebook_rx_fb_user_info_id'] = $single_page_info['facebook_rx_fb_user_info_id'];
                    $campaign_post_info[$key_id]['page_access_token'] = $single_page_info['page_access_token'];
                    $campaign_post_info[$key_id]['facebook_rx_config_id'] = $single_page_info['facebook_rx_config_id'];

                }
            }
    
        }

        foreach ($auto_comment_reply_info as $single_comment_reply_info) {

            //check if template exists
            if($single_comment_reply_info['auto_reply_comment_text'] == ""){

                $this->basic->update_data("auto_comment_reply_info",array("id"=>$single_comment_reply_info['id']),array("auto_private_reply_status"=>"2", "error_message" => "Template is missing."));
                continue;
            }
            
            $time_zone = $single_comment_reply_info['time_zone'];
            if($time_zone != '')
              date_default_timezone_set($time_zone);

            $current_time = date("Y-m-d H:i:s");
            $current_value = strtotime($current_time);

            //check comment schedule type
            $comment_schedule_type = $single_comment_reply_info['schedule_type'];

            if($comment_schedule_type == "onetime"){

                //check time
                $schedule_time = $single_comment_reply_info['schedule_time'];
                $compare_value = strtotime($schedule_time);
                if($current_value >= $compare_value){

                    //post comment
                    $this->fb_rx_login->app_initialize($campaign_post_info[$single_comment_reply_info['id']]['facebook_rx_config_id']);

                    $temp_message = $single_comment_reply_info['auto_reply_comment_text'];
                    $temp_message = json_decode($temp_message,true);
                    $message = $temp_message[0];
                    $post_id = $single_comment_reply_info['post_id'];
                    $access_token = $campaign_post_info[$single_comment_reply_info['id']]['page_access_token'];

                    try 
                    {

                      $response=$this->fb_rx_login->auto_comment($message,$post_id,$access_token);
                      $commentid=isset($response['id'])?$response['id']:"";  

                      $id = $commentid;
                      $comment_text = $message;
                      $comment_time = $current_time;
                      $schedule_type = $comment_schedule_type;
                      $reply_status = "success";

                      $report_data = array();
                      $report_data['id'] = $id;
                      $report_data['comment_text'] = $comment_text;
                      $report_data['comment_time'] = $comment_time;
                      $report_data['schedule_type'] = $schedule_type;
                      $report_data['reply_status'] = $reply_status;

                      $auto_reply_done_info = array();
                      if($single_comment_reply_info['auto_reply_done_info'] != "")
                        $auto_reply_done_info = json_decode($single_comment_reply_info['auto_reply_done_info'],true);
                      array_push($auto_reply_done_info, $report_data);

                      $report = json_encode($auto_reply_done_info);

       
                      $this->basic->update_data("auto_comment_reply_info",array("id"=>$single_comment_reply_info['id']),array("auto_private_reply_status"=>"2","last_reply_time"=>$current_time,"last_updated_at"=>$current_time, "auto_reply_done_info" => $report, "auto_comment_count" => 1));
                    } 
                    catch (Exception $e) 
                    {
                      $error_msg = $e->getMessage();



                      $id = "";
                      $comment_text = $message;
                      $comment_time = $current_time;
                      $schedule_type = $comment_schedule_type;
                      $reply_status = "failed (".$error_msg.")";

                      $report_data = array();
                      $report_data['id'] = $id;
                      $report_data['comment_text'] = $comment_text;
                      $report_data['comment_time'] = $comment_time;
                      $report_data['schedule_type'] = $schedule_type;
                      $report_data['reply_status'] = $reply_status;

                      $auto_reply_done_info = array();
                      if($single_comment_reply_info['auto_reply_done_info'] != "")
                        $auto_reply_done_info = json_decode($single_comment_reply_info['auto_reply_done_info'],true);
                      array_push($auto_reply_done_info, $report_data);

                      $report = json_encode($auto_reply_done_info);


                      $this->basic->update_data("auto_comment_reply_info",array("id"=>$single_comment_reply_info['id']),array("auto_private_reply_status"=>"2","last_reply_time"=>$current_time,"last_updated_at"=>$current_time,"error_message"=>$error_msg, "auto_reply_done_info" => $report));
                    }
                    
                }
                else{

                    //update status
                    $this->basic->update_data("auto_comment_reply_info",array("id"=>$single_comment_reply_info['id']),array("auto_private_reply_status"=>"0"));
                }
            }
            else if($comment_schedule_type == "periodic"){

                //check time
                $campaign_start_time = $single_comment_reply_info['campaign_start_time'];
                $campaign_end_time = $single_comment_reply_info['campaign_end_time'];

                $compare_start = strtotime($campaign_start_time);
                $compare_end = strtotime($campaign_end_time);


                if($current_value >= $compare_start && $current_value <= $compare_end){

                    $comment_start_time = $single_comment_reply_info['comment_start_time'];
                    $comment_end_time = $single_comment_reply_info['comment_end_time'];

                    $comment_start = strtotime($comment_start_time);
                    $comment_end = strtotime($comment_end_time);

                    $current_date_time = date("H:i:s");
                    $current_date_time_value = strtotime($current_date_time);

                    if($current_date_time_value >= $comment_start && $current_date_time_value <= $comment_end){

                        //check time again
                        $periodic_time = $single_comment_reply_info['periodic_time'];

                        $last_reply_time = $single_comment_reply_info['last_reply_time'];
                        $last_reply_time_value = strtotime($last_reply_time);

                        $temp = ($last_reply_time_value + ($periodic_time * 60) );
                        
                        if($last_reply_time_value == "" || ($temp <= $current_value) ){

                            //post comment
                            $this->fb_rx_login->app_initialize($campaign_post_info[$single_comment_reply_info['id']]['facebook_rx_config_id']);

                            $auto_comment_type = $single_comment_reply_info['auto_comment_type'];
                            $temp_message = $single_comment_reply_info['auto_reply_comment_text'];
                            $temp_message = json_decode($temp_message,true);

                            if($auto_comment_type == "random"){
                                $rand_index = rand(0,(count($temp_message)-1));
                                $message = $temp_message[$rand_index];
                            }
                            else{

                                $periodic_serial_reply_count = $single_comment_reply_info['periodic_serial_reply_count'];
                                if($periodic_serial_reply_count >= count($temp_message))
                                    $periodic_serial_reply_count = 0;

                                $message = $temp_message[$periodic_serial_reply_count];
                                $periodic_serial_reply_count++;
                                
                            }
                            $post_id = $single_comment_reply_info['post_id'];
                            $access_token = $campaign_post_info[$single_comment_reply_info['id']]['page_access_token'];

                            try 
                            {

                              $response=$this->fb_rx_login->auto_comment($message,$post_id,$access_token);
                              $commentid=isset($response['id'])?$response['id']:"";        

                              $auto_comment_count = $single_comment_reply_info['auto_comment_count']; 
                              $auto_comment_count++;

                              $id = $commentid;
                              $comment_text = $message;
                              $comment_time = $current_time;
                              $schedule_type = $comment_schedule_type;
                              $reply_status = "success";

                              $report_data = array();
                              $report_data['id'] = $id;
                              $report_data['comment_text'] = $comment_text;
                              $report_data['comment_time'] = $comment_time;
                              $report_data['schedule_type'] = $schedule_type;
                              $report_data['reply_status'] = $reply_status;

                              $auto_reply_done_info = array();
                              if($single_comment_reply_info['auto_reply_done_info'] != "")
                                $auto_reply_done_info = json_decode($single_comment_reply_info['auto_reply_done_info'],true);
                              array_push($auto_reply_done_info, $report_data);

                              $report = json_encode($auto_reply_done_info);

                              $this->basic->update_data("auto_comment_reply_info",array("id"=>$single_comment_reply_info['id']),array("auto_private_reply_status"=>"0","last_reply_time"=>$current_time,"last_updated_at"=>$current_time, "auto_comment_count" => $auto_comment_count, "auto_reply_done_info" => $report));

                              //update comment count if necessary
                              if($auto_comment_type == "serially"){

                                $periodic_serial_reply_count = $single_comment_reply_info['periodic_serial_reply_count'];
                                if($periodic_serial_reply_count >= count($temp_message))
                                    $periodic_serial_reply_count = 0;

                                $periodic_serial_reply_count++;

                                $this->basic->update_data("auto_comment_reply_info",array("id"=>$single_comment_reply_info['id']),array("periodic_serial_reply_count"=>$periodic_serial_reply_count));
                              }
                            } 
                            catch (Exception $e) 
                            {
                              $error_msg = $e->getMessage();


                              $id = "";
                              $comment_text = $message;
                              $comment_time = $current_time;
                              $schedule_type = $comment_schedule_type;
                              $reply_status = "failed (".$error_msg.")";

                              $report_data = array();
                              $report_data['id'] = $id;
                              $report_data['comment_text'] = $comment_text;
                              $report_data['comment_time'] = $comment_time;
                              $report_data['schedule_type'] = $schedule_type;
                              $report_data['reply_status'] = $reply_status;

                              $auto_reply_done_info = array();
                              if($single_comment_reply_info['auto_reply_done_info'] != "")
                                $auto_reply_done_info = json_decode($single_comment_reply_info['auto_reply_done_info'],true);
                              array_push($auto_reply_done_info, $report_data);

                              $report = json_encode($auto_reply_done_info);


                              $this->basic->update_data("auto_comment_reply_info",array("id"=>$single_comment_reply_info['id']),array("auto_private_reply_status"=>"0","last_reply_time"=>$current_time,"last_updated_at"=>$current_time,"error_message"=>$error_msg, "auto_reply_done_info" => $report));
                            }
                            //update campaign status
                        }
                        else{

                            //update campaign status
                            $this->basic->update_data("auto_comment_reply_info",array("id"=>$single_comment_reply_info['id']),array("auto_private_reply_status"=>"0"));
                        }
                    }
                    else{

                        //update campaign status
                        $this->basic->update_data("auto_comment_reply_info",array("id"=>$single_comment_reply_info['id']),array("auto_private_reply_status"=>"0"));
                    }
                }
                else if($current_value > $compare_end){
                    
                    //update campaign status
                    $this->basic->update_data("auto_comment_reply_info",array("id"=>$single_comment_reply_info['id']),array("auto_private_reply_status"=>"2"));
                }
            }
            
        }
    }
    // =====================SUBSCRIBER & AUTO REPLY/COMMENT===================
    
  
 

    // ===========FACEBOOK POSTER RELATED FUNCTIONS=============
    public function text_image_link_video_post($api_key="") //publish_post
    {
        $this->api_key_check($api_key);
        // $this->load->library("fb_rx_login");


        if($this->is_demo == '1')
        $where['where']=array("posting_status"=>"0", "facebook_rx_auto_post.user_id !="=>1);
        else
        $where['where']=array("posting_status"=>"0");
        /***   Taking fist 200 post for auto post ***/
        $post_info= $this->basic->get_data("facebook_rx_auto_post",$where,$select='',$join='',$limit=25, $start=0, $order_by='schedule_time ASC');


        $database = array();

        $campaign_id_array=array();

        // $count_post = 0;

        foreach($post_info as $info)
        {
            $time_zone= $info['time_zone'];
            $schedule_time= $info['schedule_time'];

            if($time_zone) date_default_timezone_set($time_zone);
            $now_time = date("Y-m-d H:i:s");

            if(strtotime($now_time) < strtotime($schedule_time)) continue;

            $campaign_id_array[] = $info['id'];
        }

        if(empty($campaign_id_array)) exit();

        $this->db->where_in("id",$campaign_id_array);
        $this->db->update("facebook_rx_auto_post",array("posting_status"=>"1"));

        $config_id_database = array();
        foreach($post_info as $info)
        {
            $campaign_id= $info['id'];

            if(!in_array($campaign_id, $campaign_id_array)) continue;

            $post_type= $info['post_type'];
            $page_group_user_id= $info["page_group_user_id"];
            $page_or_group_or_user= $info["page_or_group_or_user"];
            $user_id= $info['user_id'];
            $message =$info['message'];
            $link =$info['link'];
            $link_preview_image =$info['link_preview_image'];
            $link_caption =$info['link_caption'];
            $link_description =$info['link_description'];
            $image_url =$info['image_url'];
            $video_title =$info['video_title'];
            $video_url =$info['video_url'];
            $video_thumb_url =$info['video_thumb_url'];
            $link =$info['link'];

            $time_zone= $info['time_zone'];
            $schedule_time= $info['schedule_time'];

            // setting fb confid id for library call
            $fb_rx_fb_user_info_id= $info['facebook_rx_fb_user_info_id'];
            if(!isset($config_id_database[$fb_rx_fb_user_info_id]))
            {
                $config_id_database[$fb_rx_fb_user_info_id] = $this->get_fb_rx_config($fb_rx_fb_user_info_id);
            }
            $this->session->set_userdata("fb_rx_login_database_id", $config_id_database[$fb_rx_fb_user_info_id]);
            $this->load->library("fb_rx_login");
            // setting fb confid id for library call


            if($page_or_group_or_user=="page")
            {
                $table_name = "facebook_rx_fb_page_info";
                $fb_id_field =  "page_id";
                $access_token_field =  "page_access_token";
            }
            else if($page_or_group_or_user=="user")
            {
                $table_name = "facebook_rx_fb_user_info";
                $fb_id_field =  "fb_id";
                $access_token_field =  "access_token";
            }
            else
            {
                $table_name = "facebook_rx_fb_group_info";
                $fb_id_field =  "group_id";
                $access_token_field =  "group_access_token";

            }

            if(!isset($database[$page_or_group_or_user][$page_group_user_id])) // if not exists in database
            {
                $access_data = $this->basic->get_data($table_name,array("where"=>array("id"=>$page_group_user_id)));

                $use_access_token = isset($access_data["0"][$access_token_field]) ? $access_data["0"][$access_token_field] : "";
                $use_fb_id = isset($access_data["0"][$fb_id_field]) ? $access_data["0"][$fb_id_field] : "";

                //inserting new data in database
                $database[$page_or_group_or_user][$page_group_user_id] = array("use_access_token"=>$use_access_token,"use_fb_id"=>$use_fb_id);
            }

            $use_access_token = isset($database[$page_or_group_or_user][$page_group_user_id]["use_access_token"]) ? $database[$page_or_group_or_user][$page_group_user_id]["use_access_token"] : "";
            $use_fb_id = isset($database[$page_or_group_or_user][$page_group_user_id]["use_fb_id"]) ? $database[$page_or_group_or_user][$page_group_user_id]["use_fb_id"] : "";

            $response =array();
            $error_msg ="";
            if($post_type=="text_submit")
            {
                try
                {
                    $response = $this->fb_rx_login->feed_post($message,"","","","","",$use_access_token,$use_fb_id);
                }
                catch(Exception $e)
                {
                    $error_msg = $e->getMessage();
                }
            }

            else if($post_type=="link_submit")
            {
                try
                {
                    $response = $this->fb_rx_login->feed_post($message,$link,"","","","",$use_access_token,$use_fb_id);
                }
                catch(Exception $e)
                {
                    $error_msg = $e->getMessage();
                }
            }

            else if($post_type=="image_submit")
            {
                $image_list = explode(',', $image_url);
                if(count($image_list) == 1)
                {                    
                    try
                    {
                        $response = $this->fb_rx_login->photo_post($message,$image_list[0],"",$use_access_token,$use_fb_id);
                    }
                    catch(Exception $e)
                    {
                        $error_msg = $e->getMessage();
                    }
                }
                else
                {
                    $multi_image_post_response_array = array();
                    $attach_media_array = array();
                    foreach ($image_list as $key => $value) {
                        try
                        {
                            $response = $this->fb_rx_login->photo_post_for_multipost($message,$value,"",$use_access_token,$use_fb_id);
                            $attach_media_array['media_fbid'] = $response['id'];
                            $multi_image_post_response_array[] = $attach_media_array;
                        }
                        catch(Exception $e)
                        {
                            $error_msg = $e->getMessage();
                        }
                    }


                    try
                    {
                        $response = $this->fb_rx_login->multi_photo_post($message,$multi_image_post_response_array,"",$use_access_token,$use_fb_id);
                    }
                    catch(Exception $e)
                    {
                        $error_msg = $e->getMessage();
                    }
                }
            }

            else
            {
                try
                {
                    $response = $this->fb_rx_login->post_video($message,$video_title,$video_url,"",$video_thumb_url,"",$use_access_token,$use_fb_id);
                }
                catch(Exception $e)
                {
                    $error_msg = $e->getMessage();
                }
            }

            if($post_type=="image_submit")
            {
                if(count($image_list) > 1)
                $object_id=isset($response["id"]) ? $response["id"] : "";
                else
                $object_id=isset($response["post_id"]) ? $response["post_id"] : "";
                
            }
            else $object_id=isset($response["id"]) ? $response["id"] : "";

            $temp_data=array();
            try
            {
                $temp_data=$this->fb_rx_login->get_post_permalink($object_id,$use_access_token);
            }
            catch(Exception $e)
            {
                $error_msg1 = $e->getMessage();
            }

            $post_url= isset($temp_data["permalink_url"]) ? $temp_data["permalink_url"] : "";

            $update_data = array("posting_status"=>'2',"full_complete"=>'1',"post_id"=>$object_id,"post_url"=>$post_url,"error_mesage"=>$error_msg,"last_updated_at"=>date("Y-m-d H:i:s"));

            $this->basic->update_data("facebook_rx_auto_post",array("id"=>$campaign_id),$update_data);



            if($info['ultrapost_auto_reply_table_id'] != 0)
            {

                //************************************************//
                $status=$this->_check_usage($module_id=204,$request=1);
                if($status!="2" && $status!="3") 
                {

                    $auto_reply_table_info = $this->basic->get_data('ultrapost_auto_reply',['where'=>['id' => $info['ultrapost_auto_reply_table_id'] ]]);

                    $facebook_page_info = $this->basic->get_data('facebook_rx_fb_page_info',['where' => ['id' => $info['page_group_user_id']]]);

                    $auto_reply_table_data = [];

                    foreach ($auto_reply_table_info as $single_auto_reply_table_info) {

                        foreach ($single_auto_reply_table_info as $auto_key => $auto_value) {
                            
                            if($auto_key == 'id')
                                continue;

                            if($auto_key == 'ultrapost_campaign_name')
                                $auto_reply_table_data['auto_reply_campaign_name'] = $auto_value;
                            else
                                $auto_reply_table_data[$auto_key] = $auto_value;
                        }
                    }



                    $auto_reply_table_data['facebook_rx_fb_user_info_id'] = $fb_rx_fb_user_info_id;
                    $auto_reply_table_data['page_info_table_id'] = $facebook_page_info[0]['id'];
                    $auto_reply_table_data['page_name'] = $facebook_page_info[0]['page_name'];

                    if($post_type=="video_submit")
                        $auto_reply_table_data['post_id'] = $facebook_page_info[0]['page_id'].'_'.$object_id;
                    else
                        $auto_reply_table_data['post_id'] = $object_id;

                    $auto_reply_table_data['post_created_at'] = date("Y-m-d h:i:s");
                    $auto_reply_table_data['post_description'] = $message;
                    $auto_reply_table_data['auto_private_reply_status'] = '0';

                    $auto_reply_table_data['auto_private_reply_count'] = 0;
                    $auto_reply_table_data['last_updated_at'] = date("Y-m-d h:i:s");
                    $auto_reply_table_data['last_reply_time'] = '';
                    $auto_reply_table_data['error_message'] = '';
                    $auto_reply_table_data['hidden_comment_count'] = 0;
                    $auto_reply_table_data['deleted_comment_count'] = 0;
                    $auto_reply_table_data['auto_comment_reply_count'] = 0;

                    $this->basic->insert_data('facebook_ex_autoreply', $auto_reply_table_data);

                 
                    $this->_insert_usage_log($module_id=204,$request=1);                        
                 }
                //************************************************//
            }


            sleep(rand ( 1 , 6 ));

            // echo "$count_post<br>";
        }
    }
    public function cta_post($api_key="") //publish_post
    {
        $this->api_key_check($api_key);

        // $this->load->library('Fb_rx_login');
        if($this->is_demo == '1')
        $where['where']=array("posting_status"=>"0","facebook_rx_cta_post.user_id !="=>1);
        else
        $where['where']=array("posting_status"=>"0");

        $select="schedule_time,time_zone,cta_value,facebook_rx_cta_post.id as column_id,page_id,page_group_user_id,page_access_token,cta_type,message,facebook_rx_cta_post.ultrapost_auto_reply_table_id,link,link_preview_image,link_description,link_caption,facebook_rx_cta_post.facebook_rx_fb_user_info_id";
        $join=array('facebook_rx_fb_page_info'=>"facebook_rx_fb_page_info.id=facebook_rx_cta_post.page_group_user_id,left");

        /***    Taking fist 200 post for auto reply ***/
        $post_info= $this->basic->get_data("facebook_rx_cta_post",$where,$select,$join,$limit=30, $start=0,$order_by='schedule_time ASC');

        $campaign_id_array=array();

        foreach($post_info as $info)
        {
            $time_zone= $info['time_zone'];
            $schedule_time= $info['schedule_time'];

            if($time_zone) date_default_timezone_set($time_zone);
            $now_time = date("Y-m-d H:i:s");

            if(strtotime($now_time) < strtotime($schedule_time)) continue;

            $campaign_id_array[] = $info['column_id'];
        }

        if(empty($campaign_id_array)) exit();
        $this->db->where_in("id",$campaign_id_array);
        $this->db->update("facebook_rx_cta_post",array("posting_status"=>"1"));

        $config_id_database = array();
        foreach($post_info as $info)
        {

            $page_id =   $info['page_id'];
            $page_access_token = $info['page_access_token'];
            $post_column_id= $info['column_id'];

            if(!in_array($post_column_id, $campaign_id_array)) continue;

            $cta_type = $info["cta_type"];
            $cta_value = $info["cta_value"];
            $message = $info["message"];
            $link = $info["link"];
            $link_preview_image = $info["link_preview_image"];
            $link_caption = $info["link_caption"];
            $link_description = $info["link_description"];

            $time_zone= $info['time_zone'];
            $schedule_time= $info['schedule_time'];

            // setting fb confid id for library call
            $fb_rx_fb_user_info_id= $info['facebook_rx_fb_user_info_id'];
            if(!isset($config_id_database[$fb_rx_fb_user_info_id]))
            {
                $config_id_database[$fb_rx_fb_user_info_id] = $this->get_fb_rx_config($fb_rx_fb_user_info_id);
            }
            $this->session->set_userdata("fb_rx_login_database_id", $config_id_database[$fb_rx_fb_user_info_id]);
            $this->load->library("fb_rx_login");
            // setting fb confid id for library call

            $response =array();
            $error_msg ="";


            try
            {
                $response = $this->fb_rx_login->cta_post($message, $link,"","",$cta_type,$cta_value,"","",$page_access_token,$page_id);
            }
            catch(Exception $e)
            {
              $error_msg = $e->getMessage();
            }

            $object_id=isset($response["id"]) ? $response["id"] : "";

            $temp_data=array();
            try
            {
                $temp_data=$this->fb_rx_login->get_post_permalink($object_id,$page_access_token);
            }
            catch(Exception $e)
            {
                $error_msg1 = $e->getMessage();
            }

            $post_url= isset($temp_data["permalink_url"]) ? $temp_data["permalink_url"] : "";

            $update_data = array("posting_status"=>'2',"full_complete"=>'1',"post_id"=>$object_id,"post_url"=>$post_url,"error_mesage"=>$error_msg,"last_updated_at"=>date("Y-m-d H:i:s"));

            $this->basic->update_data("facebook_rx_cta_post",array("id"=>$post_column_id),$update_data);


            if($post_info[0]['ultrapost_auto_reply_table_id'] != 0)
            {

                //************************************************//
                $status=$this->_check_usage($module_id=204,$request=1);
                if($status!="2" && $status!="3") 
                {

                    $auto_reply_table_info = $this->basic->get_data('ultrapost_auto_reply',['where'=>['id' => $post_info[0]['ultrapost_auto_reply_table_id'] ]]);

                    $facebook_page_info = $this->basic->get_data('facebook_rx_fb_page_info',['where' => ['id' => $info['page_group_user_id']]]);

                    $auto_reply_table_data = [];

                    foreach ($auto_reply_table_info as $single_auto_reply_table_info) {

                        foreach ($single_auto_reply_table_info as $auto_key => $auto_value) {
                            
                            if($auto_key == 'id')
                                continue;

                            if($auto_key == 'ultrapost_campaign_name')
                                $auto_reply_table_data['auto_reply_campaign_name'] = $auto_value;
                            else
                                $auto_reply_table_data[$auto_key] = $auto_value;
                        }
                    }

                   

                    $auto_reply_table_data['facebook_rx_fb_user_info_id'] = $fb_rx_fb_user_info_id;
                    $auto_reply_table_data['page_info_table_id'] = $facebook_page_info[0]['id'];
                    $auto_reply_table_data['page_name'] = $facebook_page_info[0]['page_name'];
                    $auto_reply_table_data['post_id'] = $object_id;
                    $auto_reply_table_data['post_created_at'] = date("Y-m-d h:i:s");
                    $auto_reply_table_data['post_description'] = $message;
                    $auto_reply_table_data['auto_private_reply_status'] = '0';

                    $auto_reply_table_data['auto_private_reply_count'] = 0;
                    $auto_reply_table_data['last_updated_at'] = date("Y-m-d h:i:s");
                    $auto_reply_table_data['last_reply_time'] = '';
                    $auto_reply_table_data['error_message'] = '';
                    $auto_reply_table_data['hidden_comment_count'] = 0;
                    $auto_reply_table_data['deleted_comment_count'] = 0;
                    $auto_reply_table_data['auto_comment_reply_count'] = 0;

                    $this->basic->insert_data('facebook_ex_autoreply', $auto_reply_table_data);

                 
                     $this->_insert_usage_log($module_id=204,$request=1);                        
                 }
                //************************************************//
            }

            sleep(rand ( 1 , 6 ));


        }

    }
    public function carousel_slider_post($api_key="") //publish_post
    {
        $this->api_key_check($api_key);
        //$this->load->library("fb_rx_login");
        if($this->is_demo == '1')
        $where['where']=array("posting_status"=>"0","facebook_rx_slider_post.user_id !="=>1);
        else
        $where['where']=array("posting_status"=>"0");
        /***   Taking fist 200 post for auto post ***/
        $post_info= $this->basic->get_data("facebook_rx_slider_post",$where,$select='',$join='',$limit=20, $start=0, $order_by='schedule_time ASC');


        $database = array();

        $campaign_id_array = array();

        foreach($post_info as $info)
        {
            $time_zone = $info['time_zone'];
            $schedule_time = $info['schedule_time']; 

            if($time_zone) date_default_timezone_set($time_zone);            
            $now_time = date("Y-m-d H:i:s");
            
            if(strtotime($now_time) < strtotime($schedule_time)) continue; 

            $campaign_id_array[] = $info['id'];       
        }

        if(empty($campaign_id_array)) exit();

        $this->db->where_in("id",$campaign_id_array);
        $this->db->update("facebook_rx_slider_post",array("posting_status"=>"1"));
       
        $config_id_database = array();
        $this->load->library("fb_rx_login");
        foreach($post_info as $info)
        {    
            $campaign_id= $info['id'];

            if(!in_array($campaign_id, $campaign_id_array)) continue;

            $post_type= $info['post_type'];
            $page_group_user_id= $info["page_group_user_id"];
            $page_or_group_or_user= $info["page_or_group_or_user"];
            $user_id= $info['user_id'];            
            $message =$info['message'];

            $carousel_content=json_decode($info["carousel_content"],true);
            $carousel_link=$info["carousel_link"];
            $slider_images=json_decode($info["slider_images"],true);
            $slider_image_duration=$info["slider_image_duration"];
            $slider_transition_duration=$info["slider_transition_duration"];
           
            $time_zone= $info['time_zone'];
            $schedule_time= $info['schedule_time'];

            // setting fb confid id for library call
            $fb_rx_fb_user_info_id= $info['facebook_rx_fb_user_info_id'];
            if(!isset($config_id_database[$fb_rx_fb_user_info_id]))
            {
                $config_id_database[$fb_rx_fb_user_info_id] = $this->get_fb_rx_config($fb_rx_fb_user_info_id);
            }
            //$this->session->set_userdata("fb_rx_login_database_id", $config_id_database[$fb_rx_fb_user_info_id]);
            $this->fb_rx_login->app_initialize($config_id_database[$fb_rx_fb_user_info_id]);
            // setting fb confid id for library call  
            
            if($page_or_group_or_user=="page")
            {
                $table_name = "facebook_rx_fb_page_info";
                $fb_id_field =  "page_id";
                $access_token_field =  "page_access_token";  
            }
            else if($page_or_group_or_user=="user")
            {
                $table_name = "facebook_rx_fb_user_info";
                $fb_id_field =  "fb_id";
                $access_token_field =  "access_token";               
            }
            else
            {
                $table_name = "facebook_rx_fb_group_info`";
                $fb_id_field =  "group_id";
                $access_token_field =  "group_access_token";

            }

            if(!isset($database[$page_or_group_or_user][$page_group_user_id])) // if not exists in database
            {
                $access_data = $this->basic->get_data($table_name,array("where"=>array("id"=>$page_group_user_id)));
                          
                $use_access_token = isset($access_data["0"][$access_token_field]) ? $access_data["0"][$access_token_field] : "";
                $use_fb_id = isset($access_data["0"][$fb_id_field]) ? $access_data["0"][$fb_id_field] : "";
                
                //inserting new data in database
                $database[$page_or_group_or_user][$page_group_user_id] = array("use_access_token"=>$use_access_token,"use_fb_id"=>$use_fb_id);
            }

            $use_access_token = isset($database[$page_or_group_or_user][$page_group_user_id]["use_access_token"]) ? $database[$page_or_group_or_user][$page_group_user_id]["use_access_token"] : "";
            $use_fb_id = isset($database[$page_or_group_or_user][$page_group_user_id]["use_fb_id"]) ? $database[$page_or_group_or_user][$page_group_user_id]["use_fb_id"] : "";

            $response =array();
            $error_msg ="";
            if($post_type == 'carousel_post') //carousel post
            {
                try
                {
                    $response = $this->fb_rx_login->carousel_post($message,$carousel_link,$carousel_content,"",$use_access_token,$use_fb_id);                    
                }
                catch(Exception $e) 
                {
                    $error_msg = $e->getMessage();
                }
            }
            else // slider post
            {
                try
                {
                    $response = $this->fb_rx_login->post_image_video($message,$slider_images,$slider_image_duration,$slider_transition_duration,"",$use_access_token,$use_fb_id);
                }
                catch(Exception $e) 
                {
                  $error_msg = $e->getMessage();
                }
            } 

            $object_id=isset($response["id"]) ? $response["id"] : "";
            
            $temp_data=array();
            try
            {
                $temp_data=$this->fb_rx_login->get_post_permalink($object_id,$use_access_token);
            }
            catch(Exception $e) 
            {
                $error_msg1 = $e->getMessage();
            }
            
            $post_url= isset($temp_data["permalink_url"]) ? $temp_data["permalink_url"] : "";               

            $update_data = array("posting_status"=>'2',"full_complete"=>'1',"post_id"=>$object_id,"post_url"=>$post_url,"error_mesage"=>$error_msg,"last_updated_at"=>date("Y-m-d H:i:s"));

            $this->basic->update_data("facebook_rx_slider_post",array("id"=>$campaign_id),$update_data);



            if($info['ultrapost_auto_reply_table_id'] != 0)
            {

                //************************************************//
                $status=$this->_check_usage($module_id=204,$request=1);
                if($status!="2" && $status!="3") 
                {

                    $auto_reply_table_info = $this->basic->get_data('ultrapost_auto_reply',['where'=>['id' => $info['ultrapost_auto_reply_table_id'] ]]);

                    $facebook_page_info = $this->basic->get_data('facebook_rx_fb_page_info',['where' => ['id' => $info['page_group_user_id']]]);

                    $auto_reply_table_data = [];

                    foreach ($auto_reply_table_info as $single_auto_reply_table_info) {

                        foreach ($single_auto_reply_table_info as $auto_key => $auto_value) {
                            
                            if($auto_key == 'id')
                                continue;

                            if($auto_key == 'ultrapost_campaign_name')
                                $auto_reply_table_data['auto_reply_campaign_name'] = $auto_value;
                            else
                                $auto_reply_table_data[$auto_key] = $auto_value;
                        }
                    }



                    $auto_reply_table_data['facebook_rx_fb_user_info_id'] = $fb_rx_fb_user_info_id;
                    $auto_reply_table_data['page_info_table_id'] = $facebook_page_info[0]['id'];
                    $auto_reply_table_data['page_name'] = $facebook_page_info[0]['page_name'];

                    if($post_type=="slider_post")
                        $auto_reply_table_data['post_id'] = $facebook_page_info[0]['page_id'].'_'.$object_id;
                    else
                        $auto_reply_table_data['post_id'] = $object_id;

                    $auto_reply_table_data['post_created_at'] = date("Y-m-d h:i:s");
                    $auto_reply_table_data['post_description'] = $message;
                    $auto_reply_table_data['auto_private_reply_status'] = '0';

                    $auto_reply_table_data['auto_private_reply_count'] = 0;
                    $auto_reply_table_data['last_updated_at'] = date("Y-m-d h:i:s");
                    $auto_reply_table_data['last_reply_time'] = '';
                    $auto_reply_table_data['error_message'] = '';
                    $auto_reply_table_data['hidden_comment_count'] = 0;
                    $auto_reply_table_data['deleted_comment_count'] = 0;
                    $auto_reply_table_data['auto_comment_reply_count'] = 0;

                    $this->basic->insert_data('facebook_ex_autoreply', $auto_reply_table_data);

               
                     $this->_insert_usage_log($module_id=204,$request=1);                        
                 }
                //************************************************//
            }

            sleep(rand ( 1 , 6 ));


        }
     
    }
    public function rss_auto_post($api_key="") //publish_post
    {
        $this->api_key_check($api_key);
        $this->load->library("rss_feed");

        $cron_limit=5;

        $feed_data=$this->basic->get_data("autoposting",array("where"=>array("cron_status"=>"0","status"=>"1")),'','',$cron_limit,NULL,'last_updated_at ASC');

        $all_feed_id=array();
        foreach ($feed_data as $key => $value) 
        {   
            $user_id=isset($value['user_id'])?$value['user_id']:'';
            if(!$this->basic->is_exist("users",array("id"=>$user_id,"status"=>"1"))) // cancelinng inactive users feeds so that they does not start again
            {
                $this->basic->update_data("autoposting",array("id"=>$value['id']),array("status"=>"2","last_updated_at"=>date("Y-m-d H:i:s")));
                continue;
            }
            $all_feed_id[]=$value['id'];
        }
        if(empty($all_feed_id)) exit(); // stop, no data found

        $this->db->where_in("id",$all_feed_id);
        $this->db->update("autoposting",array("cron_status"=>"1","last_updated_at"=>date("Y-m-d H:i:s")));

        $datetime=date("Y-m-d H:i:s");
        
        foreach ($feed_data as $key => $value) 
        {            
            $user_id=isset($value['user_id'])?$value['user_id']:'';
            if(!$this->basic->is_exist("users",array("id"=>$user_id,"status"=>"1"))) continue; // skipping inactive users feeds
       
            $feed_name=isset($value['feed_name'])?$value['feed_name']:'';
            $last_pub_date=isset($value['last_pub_date'])?$value['last_pub_date']:'';
            $error_log=isset($value['error_message'])?json_decode($value['error_message'],true):array();

            $posting_start_time=isset($value['posting_start_time'])?$value['posting_start_time']:"00:00";
            $posting_end_time=isset($value['posting_end_time'])?$value['posting_end_time']:"23:59";
            $posting_timezone=isset($value['posting_timezone'])?$value['posting_timezone']:"";
            if($posting_timezone=="") $posting_timezone=$this->config->item("time_zone");

            $broadcast_start_time=isset($value['broadcast_start_time'])?$value['broadcast_start_time']:"00:00";
            $broadcast_end_time=isset($value['broadcast_end_time'])?$value['broadcast_end_time']:"23:59";
            $broadcast_timezone=isset($value['broadcast_timezone'])?$value['broadcast_timezone']:"";
            if($broadcast_timezone=="") $broadcast_timezone=$this->config->item("time_zone");
            $broadcast_notification_type=isset($value['broadcast_notification_type'])?$value['broadcast_notification_type']:"REGULAR";
            $broadcast_display_unsubscribe=isset($value['broadcast_display_unsubscribe'])?$value['broadcast_display_unsubscribe']:"0";

            $feed_url=isset($value['feed_url'])?$value['feed_url']:'';
            $feed=$this->rss_feed->getFeed($feed_url);

            if(!isset($feed['success']) || $feed['success']!='1') // stop if get error while getting feed
            {
                $error_message=isset($feed['error_message'])?$feed['error_message']:$this->lang->line("something went wrong while fetching feed data.");
                $error_message.=" [RSS]";
                $error_row=array("time"=>$datetime,"message"=>$error_message);
                array_push($error_log, $error_row);
                $this->basic->update_data("autoposting",array("id"=>$value['id']),array("cron_status"=>"0","last_updated_at"=>$datetime,"error_message"=>json_encode($error_log)));
                continue;
            }           

            // $new_last_pub_title=isset($feed['element_list'][0]['title'])?$feed['element_list'][0]['title']:"";
            // $new_last_pub_url=isset($feed['element_list'][0]['link'])?$feed['element_list'][0]['link']:"";
            // $new_last_pub_date=$last_pub_date;
            date_default_timezone_set('Europe/Dublin'); // operating in GMT
            // $new_last_pub_date=isset($feed['element_list'][0]['pubDate'])?$feed['element_list'][0]['pubDate']:"";
            // if($new_last_pub_date!="") $new_last_pub_date=date("Y-m-d H:i:s",strtotime($new_last_pub_date));

            $element_list=isset($feed['element_list'])?$feed['element_list']:array();
            $element_list=array_reverse($element_list);

            $valid_feed=0;
            $new_last_pub_title="";
            $new_last_pub_url="";
            $new_last_pub_date="";

            foreach($element_list as $key2 => $value2) // how many latest feed there will be
            {
                $pub_date=isset($value2['pubDate'])?$value2['pubDate']:"";
                $pub_date=date("Y-m-d H:i:s",strtotime($pub_date));
                if(strtotime($pub_date)>strtotime($last_pub_date)) $valid_feed++;

                if($value2['pubDate']!="")
                if($new_last_pub_date=="" || (strtotime($value2['pubDate'])>strtotime($new_last_pub_date)))
                {
                    $new_last_pub_date=isset($value2['pubDate'])?$value2['pubDate']:"";
                    $new_last_pub_date=date("Y-m-d H:i:s",strtotime($new_last_pub_date));
                    $new_last_pub_title=isset($value2['title'])?$value2['title']:"";
                    $new_last_pub_url=isset($value2['link'])?$value2['link']:"";
                }
            }

            if($valid_feed==0) // stop cron if no latest feed found
            {
                $time_zone = $this->config->item('time_zone');
                if($time_zone== '') $time_zone="Europe/Dublin";
                date_default_timezone_set($time_zone);
                $this->db->where_in("id",$all_feed_id);
                $this->db->update("autoposting",array("cron_status"=>"0","last_updated_at"=>date("Y-m-d H:i:s")));
                continue;
            }

            // posting time calculation
            date_default_timezone_set($posting_timezone);
            $current_datetime=date("Y-m-d H:i:s");
            $current_date=date("Y-m-d");
            $current_time=date("H:i");

            $temp0 = (float) str_replace(':','.',$current_time);
            $temp1 = (float) str_replace(':','.',$posting_start_time);
            $temp2 = (float) str_replace(':','.',$posting_end_time);
            $temp_difference = $temp2-$temp1;
            $temp_hour_min=ceil($temp_difference)*60;
            $temp_min=$temp_difference-ceil($temp_difference);
            $temp_min=number_format((float)$temp_min, 2, '.', '');
            $available_min=$temp_hour_min+$temp_min;
            $gap_minute=round($available_min/$valid_feed); // say we have 120 min time span and have 10 valid feed, then campaigns will be scheduled every 12 minutes

            $post_schedule_time="";

            if($temp0>=$temp1 && $temp0<=$temp2) // matches time slot
            {
                $post_schedule_time = strtotime($current_datetime.' + 2 minute');
                $post_schedule_time = date('Y-m-d H:i:s', $post_schedule_time);
            }
            else
            {
                $make_date=$current_date." ".$posting_start_time.":00";
                if(strtotime($make_date)<strtotime($current_datetime)) // if start time is less than current time then we will schedule it next day
                {
                    $post_schedule_time = strtotime($make_date.' + 1 day');
                    $post_schedule_time = date('Y-m-d H:i:s', $post_schedule_time);
                }
                else $post_schedule_time=$make_date;
            }            
            $post_gap_minute=0;
            // posting time calculation


            // broadcast time calculation
            date_default_timezone_set($broadcast_timezone);
            $broadcast_current_datetime=date("Y-m-d H:i:s");
            $broadcast_current_date=date("Y-m-d");
            $broadcast_current_time=date("H:i");

            $broadcast_temp0 = (float) str_replace(':','.',$broadcast_current_time);
            $broadcast_temp1 = (float) str_replace(':','.',$broadcast_start_time);
            $broadcast_temp2 = (float) str_replace(':','.',$broadcast_end_time);
            $broadcast_temp_difference = $broadcast_temp2-$broadcast_temp1;
            $broadcast_temp_hour_min=ceil($broadcast_temp_difference)*60;
            $broadcast_temp_min=$broadcast_temp_difference-ceil($broadcast_temp_difference);
            $broadcast_temp_min=number_format((float)$broadcast_temp_min, 2, '.', '');
            $broadcast_available_min=$broadcast_temp_hour_min+$broadcast_temp_min;
            $gap_minute2=round($broadcast_available_min/$valid_feed); // say we have 120 min time span and have 10 valid feed, then campaigns will be scheduled every 12 minutes

            $broadcast_schedule_time="";

            if($broadcast_temp0>=$broadcast_temp1 && $broadcast_temp0<=$broadcast_temp2) // matches time slot
            {
                $broadcast_schedule_time = strtotime($broadcast_current_datetime.' + 2 minute');
                $broadcast_schedule_time = date('Y-m-d H:i:s', $broadcast_schedule_time);
            }
            else
            {
                $broadcast_make_date=$broadcast_current_date." ".$broadcast_start_time.":00";
                if(strtotime($broadcast_make_date)<strtotime($broadcast_current_datetime)) // if start time is less than current time then we will schedule it next day
                {
                    $broadcast_schedule_time = strtotime($broadcast_make_date.' + 1 day');
                    $broadcast_schedule_time = date('Y-m-d H:i:s', $broadcast_schedule_time);
                }
                else $broadcast_schedule_time=$broadcast_make_date;
            }            
            $broadcast_gap_minute=0;
            // broadcast time calculation


            foreach($element_list as $key2 => $value2) 
            {
                date_default_timezone_set('Europe/Dublin'); // operating in GMT
                $pub_date=isset($value2['pubDate'])?$value2['pubDate']:"";
                $pub_date=date("Y-m-d H:i:s",strtotime($pub_date));                

                if(strtotime($pub_date)>strtotime($last_pub_date)) // only work with recent feed
                {                    
                    if($valid_feed>3) 
                    {
                        $post_gap_minute+=$gap_minute; 
                        $broadcast_gap_minute+=$gap_minute2;
                    }
                    else
                    {
                        $post_gap_minute+=15; 
                        $broadcast_gap_minute+=15;
                    }

                    $post_feed_url=isset($value2['link'])?$value2['link']:"";             

                    // processing facebook post
                    $page_ids = isset($value['page_ids'])?explode(',', $value['page_ids']):array();
                    $facebook_rx_fb_user_info_ids = isset($value['facebook_rx_fb_user_info_ids'])?json_decode($value['facebook_rx_fb_user_info_ids'],true):array();
                    $page_names = isset($value['page_names'])?json_decode($value['page_names'],true):array();
                    $request_count=count(array_filter($page_ids));
                    if($request_count>0)
                    {
                        $status=$this->_check_usage($module_id=223,$request_count,$user_id);
                        if($status=="3")
                        {
                            $this->basic->update_data("autoposting",array("id"=>$value['id']),array("error_message"=>$error_message));  

                            $error_message = $this->lang->line("Your monthly limit for Facebook posting module has been exceeded.");  
                            $error_message.=" [Facebook Posting]";
                            $error_row=array("time"=>$datetime,"message"=>$error_message);
                            array_push($error_log, $error_row);
                            $this->basic->update_data("autoposting",array("id"=>$value['id']),array("last_updated_at"=>$datetime,"error_message"=>json_encode($error_log)));               
                        }
                        else
                        {                            
                            foreach($page_ids as $key3 => $value3) 
                            {                               
                               $facebook_rx_fb_user_info_id=isset($facebook_rx_fb_user_info_ids[$value3])?$facebook_rx_fb_user_info_ids[$value3]:0;
                               $page_or_group_or_user_name=isset($page_names[$value3])?$page_names[$value3]:"";

                               $post_schedule_time_gapped=$post_schedule_time;
                               if($valid_feed<=3) // if there is a small amount of feeds then we will try to post in first hour
                               {
                                   $post_schedule_time_gapped = strtotime($post_schedule_time.' + '.$post_gap_minute.' minute');
                                   $post_schedule_time_gapped = date('Y-m-d H:i:s', $post_schedule_time_gapped);
                               }
                               else // if there is a large amount of feeds then we will try to span the feed post process to cover whole timeslot
                               {
                                   if($post_gap_minute>0)
                                   {
                                       $post_schedule_time_gapped = strtotime($post_schedule_time.' + '.$post_gap_minute.' minute');
                                       $post_schedule_time_gapped = date('Y-m-d H:i:s', $post_schedule_time_gapped);
                                   }
                                }
                               
                               $create_campaign_data=array
                               (
                                  "user_id"=>$user_id,
                                  "facebook_rx_fb_user_info_id"=>$facebook_rx_fb_user_info_id,
                                  "post_type"=>"link_submit",
                                  "campaign_name"=>$feed_name." [RSS Autopost]",
                                  "page_group_user_id"=>$value3,
                                  "page_or_group_or_user"=>"page",
                                  "page_or_group_or_user_name"=>$page_or_group_or_user_name,
                                  "link"=>$post_feed_url,
                                  "posting_status"=>"0",
                                  "last_updated_at"=>$datetime,
                                  "schedule_time"=>$post_schedule_time_gapped,
                                  "time_zone"=>$posting_timezone,
                                  "is_autopost"=>"1"
                               );                             

                               $this->basic->insert_data("facebook_rx_auto_post",$create_campaign_data);
                               $this->_insert_usage_log($module_id=223,$request=1,$user_id);
                            }                            
                            
                        }
                    }
                    // processing facebook post


                    //processing broadcasting
                    $get_meta_tag_fb=$this->rss_feed->get_meta_tag_fb($post_feed_url);
                    $feed_url_title=isset($get_meta_tag_fb['title'])?$get_meta_tag_fb['title']:"";
                    $feed_url_image=isset($get_meta_tag_fb['image'])?$get_meta_tag_fb['image']:"";
                    $feed_url_des=isset($get_meta_tag_fb['description'])?$get_meta_tag_fb['description']:"";

                    if(strlen($feed_url_des)>80)
                    $feed_url_subtitle=substr($feed_url_des,0,77)."...";
                    else $feed_url_subtitle=$feed_url_des;

                    if(strlen($feed_url_title)>80)
                    $feed_url_title=substr($feed_url_title,0,77)."...";

                    $broadcast_schedule_time_gapped=$broadcast_schedule_time;
                    if($valid_feed<=3) // if there is a small amount of feeds then we will try to post in first hour
                    {
                       $broadcast_schedule_time_gapped = strtotime($broadcast_schedule_time.' + '.$broadcast_gap_minute.' minute');
                       $broadcast_schedule_time_gapped = date('Y-m-d H:i:s', $broadcast_schedule_time_gapped);
                    }
                    else // if there is a large amount of feeds then we will try to span the feed post process to cover whole timeslot
                    {
                       if($broadcast_gap_minute>0)
                       {
                           $broadcast_schedule_time_gapped = strtotime($broadcast_schedule_time.' + '.$broadcast_gap_minute.' minute');
                           $broadcast_schedule_time_gapped = date('Y-m-d H:i:s', $broadcast_schedule_time_gapped);
                       }
                    }


                    if($value["page_id"]!="")
                    {
                        $post_data=array
                        (                        
                            "campaign_name" => $feed_name." [RSS Autopost]",
                            "fb_page_id" => $value["fb_page_id"],
                            "page" => $value["page_id"],
                            "notification_type" => $broadcast_notification_type,
                            "display_unsubscribe" => $broadcast_display_unsubscribe,
                            "label_ids" => $value['label_ids'],
                            "excluded_label_ids" => $value['excluded_label_ids'],
                            "template_type_1" => "generic template",    
                            "generic_template_image_1" => $feed_url_image,
                            "generic_template_image_destination_link_1" => $post_feed_url,
                            "generic_template_title_1" => $feed_url_title,
                            "generic_template_subtitle_1" => $feed_url_subtitle,
                            "generic_template_button_text_1_1" => "Unsubscribe",
                            "generic_template_button_type_1_1" => "post_back",                        
                            "generic_template_button_post_id_1_1" => "UNSUBSCRIBE_QUICK_BOXER",                        
                            "schedule_type" => "later",
                            "time_zone" => $broadcast_timezone,
                            "schedule_time" => $broadcast_schedule_time_gapped,
                            "user_id" => $user_id
                        );

                        // curl api to create auto post quick bulk broadcast campaign
                        $url=base_url("messenger_bot_enhancers/rss_autoposting_quick_broadcast_cron_call/".$api_key);                       
                        $post_data = json_encode($post_data);
                        $ch = curl_init($url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); 
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                        $st=curl_exec($ch);  
                        $result=json_decode($st,TRUE);
                        curl_close($ch);

                        if(isset($result['status']) && $result['status']=='0') // checking and logging if any error found
                        { 
                            $error_message=isset($result['message'])?$result['message']:$this->lang->line("Something went wrong while creating broadcast campaign.");
                            $error_message.=" [Broadcast]";
                            $error_row=array("time"=>$datetime,"message"=>$error_message);
                            array_push($error_log, $error_row);
                            $this->basic->update_data("autoposting",array("id"=>$value['id']),array("last_updated_at"=>$datetime,"error_message"=>json_encode($error_log)));                        
                        }

                    }
                    //processing broadcasting
                }
                
            } 
            $this->basic->update_data("autoposting",array("id"=>$value['id']),array("last_pub_date"=>$new_last_pub_date,"last_pub_title"=>$new_last_pub_title,"last_pub_url"=>$new_last_pub_url));
            
        } 

        $time_zone = $this->config->item('time_zone');
        if($time_zone== '') $time_zone="Europe/Dublin";
        date_default_timezone_set($time_zone);
        $this->db->where_in("id",$all_feed_id);
        $this->db->update("autoposting",array("cron_status"=>"0","last_updated_at"=>date("Y-m-d H:i:s")));
    }
    // ===========FACEBOOK POSTER RELATED FUNCTIONS=============



    // ===========MESSENGER BOT RELATED FUNCTIONS=============
    function download_subscriber_avatar($api_key="")
    {   
       $this->api_key_check($api_key);
       $limit=$this->config->item("messengerbot_subscriber_avatar_download_limit_per_cron_job");
       if($limit=="") $limit=25;
       $subscriber_info = $this->basic->get_data('messenger_bot_subscriber',array('where'=>array('is_image_download'=>'0', 'is_updated_name' => '1','is_bot_subscriber'=>'1')),$select='',$join='',$limit);
        
        foreach($subscriber_info as $info){
        
            $profile_pic_url=$info['profile_pic'];
            $subscribe_id=$info['subscribe_id'];
            $subscribe_auto_id=$info['id'];


            $upload_path="upload/subscriber_pic"; 
            
            if(!file_exists($upload_path))
                mkdir($upload_path,0755);
                
            $user_pic_name=$upload_path."/".$subscribe_id.".png";
        
        
            $content= @file_get_contents($profile_pic_url);
            
            if($content===FALSE){
                
                $this->basic->update_data("messenger_bot_subscriber",array("id"=>$subscribe_auto_id),array("is_image_download"=>"1"));
                
            }
            else{
                file_put_contents($user_pic_name,$content);
                $this->basic->update_data("messenger_bot_subscriber",array("id"=>$subscribe_auto_id),array("is_image_download"=>"1","image_path"=>$user_pic_name));
            }
        }        
    
    }
    public function update_subscriber_profile_info($api_key="") //background_scanning_update_subscriber_info
    {
        
        $this->api_key_check($api_key);
        $limit=$this->config->item("messengerbot_subscriber_profile_update_limit_per_cron_job");
        if($limit=="") $limit=100;
        $subscriber_info = $this->basic->get_data('messenger_bot_subscriber',array('where'=>array('is_updated_name'=>'0','is_bot_subscriber'=>'0')),$select='',$join='',$limit, '', 'last_name_update_time asc');
         
         foreach($subscriber_info as $info){
         
             $subscribe_id=$info['subscribe_id'];
             $subscribe_auto_id=$info['id'];
             $page_id = $info['page_id'];
             $page_table_id = $info['page_table_id'];

             $facebook_rx_fb_page_info = $this->basic->get_data('facebook_rx_fb_page_info', array('where' => array('id' => $page_table_id)));
             $facebook_rx_fb_page_info = $facebook_rx_fb_page_info[0];
             $access_token = $facebook_rx_fb_page_info['page_access_token'];

             $user_info = $this->subscriber_info($access_token, $subscribe_id); // home controller

             if (!isset($user_info['error'])) {

                 $first_name = isset($user_info['first_name']) ? $user_info['first_name'] : "";
                 $last_name = isset($user_info['last_name']) ? $user_info['last_name'] : "";
                 $profile_pic = isset($user_info['profile_pic']) ? $user_info['profile_pic'] : "";
                 $gender = isset($user_info['gender']) ? $user_info['gender'] : "";
                 $locale = isset($user_info['locale']) ? $user_info['locale'] : "";
                 $timezone = isset($user_info['timezone']) ? $user_info['timezone'] : "";
                 $full_name = isset($user_info['name']) ? $user_info['name'] : "";


                 if ($first_name != "") {

                     $data = array(
                         'first_name' => $first_name,
                         'last_name' => $last_name,
                         'profile_pic' => $profile_pic,
                         'is_updated_name' => '1',
                         'is_bot_subscriber' => '1',
                         'gender'=>$gender,
                         'locale'=>$locale,
                         'timezone'=>$timezone,
                         'last_name_update_time' => date('Y-m-d H:i:s')
                     );
                     if($full_name!="") $data["full_name"] = $full_name;

                     $this->basic->update_data('messenger_bot_subscriber', array('id' => $subscribe_auto_id), $data);
                 }
                 else 
                 {
                    $data = array('is_updated_name' => '1','is_bot_subscriber' => '0','last_name_update_time' => date('Y-m-d H:i:s')
                     );
                     $this->basic->update_data('messenger_bot_subscriber', array('id' => $subscribe_auto_id), $data);
                 }

                
             }
             else 
             {
                $data = array('is_updated_name' => '1','is_bot_subscriber' => '0','last_name_update_time' => date('Y-m-d H:i:s')
                 );
                 $this->basic->update_data('messenger_bot_subscriber', array('id' => $subscribe_auto_id), $data);
             }
             
         }
    }
    // ===========MESSENGER BOT RELATED FUNCTIONS=============




    // ===========MESSENGER BROADCASTER RELATED FUNCTIONS=============
    public function conversation_broadcast($api_key="") // braodcast_message
    {
        $this->api_key_check($api_key);
        $number_of_message_to_be_sent_in_try=$this->config->item("number_of_message_to_be_sent_in_try");
        if($number_of_message_to_be_sent_in_try=="") $number_of_message_to_be_sent_in_try=10; // default 10
        else if($number_of_message_to_be_sent_in_try==0) $number_of_message_to_be_sent_in_try=""; // 0 means unlimited
        $update_report_after_time=$this->config->item("update_report_after_time"); 
        if($update_report_after_time=="" || $update_report_after_time==0) $update_report_after_time=5;
        $number_of_campaign_to_be_processed = 1; // max number of campaign that can be processed by this cron job
        // $number_of_message_tob_be_sent = 50000;  // max number of message that can be sent in an hour

        $where['or_where'] = array('posting_status'=>"0","is_try_again"=>"1");

        /****** Get all campaign from database where status=0 means pending ******/
        $join = array('users'=>'facebook_ex_conversation_campaign.user_id=users.id,left');
        $campaign_info= $this->basic->get_data("facebook_ex_conversation_campaign",$where,$select=array("facebook_ex_conversation_campaign.*","users.deleted as user_deleted","users.status as user_status"),$join,$limit=50, $start=0, $order_by='schedule_time ASC');  

        $access_token_database_database = array(); //  [campaign_id][page_auto_id] =>access token
        $facebook_rx_fb_user_info_id_database = array(); // campaign_id => facebook_rx_fb_user_info_id
        $facebook_rx_config_id_database = array(); // facebook_rx_fb_user_info_id => facebook_rx_config_id
        $campaign_id_array=array();  // all selected campaign id array
        $campaign_info_fildered = array(); // valid for process, campign info array
        $page_ids_names = array(); // page_auto id => page name
        $fb_page_ids = array(); // facebook page id list

        // echo $this->db->last_query(); exit();
        $valid_campaign_count = 1;
        foreach($campaign_info as $info)
        {
            if($info['user_deleted'] == '1' || $info['user_status']=="0")
            {
                $this->db->where("id",$info['id']);
                $this->db->update("facebook_ex_conversation_campaign",array("posting_status"=>"1","is_try_again"=>"0"));
                continue;
            } 
            if($this->is_demo == '1' && $info['user_id'] == 1)
            {
                $this->db->where("id",$info['id']);
                $this->db->update("facebook_ex_conversation_campaign",array("posting_status"=>"1","is_try_again"=>"0"));
                continue;
            } 

            $campaign_id= $info['id'];
            $time_zone= $info['time_zone'];
            $schedule_time= $info['schedule_time']; 
            $total_thread = $info["total_thread"];
            $page_id = $info["page_id"]; // auto id
            $user_id = $info["user_id"];
            
            // $count_total_thread = $count_total_thread + $total_thread;            

            if($time_zone) date_default_timezone_set($time_zone);            
            $now_time = date("Y-m-d H:i:s");

            if((strtotime($now_time) < strtotime($schedule_time)) && $time_zone!="") continue; 
            if($valid_campaign_count > $number_of_campaign_to_be_processed) break; 

            // get access token and fb user id
            $token_info =  $this->basic->get_data('facebook_rx_fb_page_info',array("where"=>array('id'=>$page_id,'user_id'=>$user_id)),array("page_access_token","facebook_rx_fb_user_info_id","id","page_name","page_id"));
            foreach ($token_info as $key => $value) 
            {
                $access_token_database_database[$campaign_id][$value["id"]] = $value['page_access_token'];
                $facebook_rx_fb_user_info_id = $value["facebook_rx_fb_user_info_id"];
                $facebook_rx_fb_user_info_id_database[$campaign_id] = $facebook_rx_fb_user_info_id;
                $page_ids_names[$value["id"]] = $value["page_name"];
                $fb_page_ids[$value['id']] = $value['page_id'];
            }
           
            // valid campaign info and campig ids
            $campaign_info_fildered[] = $info;
            $campaign_id_array[] = $info['id']; 
            $valid_campaign_count++;      
        }


        if(count($campaign_id_array)==0) exit();        

        $this->db->where_in("id",$campaign_id_array);
        $this->db->update("facebook_ex_conversation_campaign",array("posting_status"=>"1","is_try_again"=>"0"));

        // get config id
        $getdata= $this->basic->get_data("facebook_rx_fb_user_info",array("where_in"=>array("id"=>$facebook_rx_fb_user_info_id_database)),array("id","facebook_rx_config_id"));
        foreach ($getdata as $key => $value) 
        {
            $facebook_rx_config_id_database[$value["id"]] = $value["facebook_rx_config_id"];
        } 


        $this->load->library("fb_rx_login");
        
        foreach($campaign_info_fildered as $info)
        {
            $campaign_id= $info['id'];            
            $campaign_message= $info['campaign_message'];  
            $user_id = $info["user_id"]; 
            $delay_time = $info["delay_time"];
            $catch_error_count=$info["last_try_error_count"];
            $successfully_sent=$info["successfully_sent"];

            $fb_rx_fb_user_info_id = $facebook_rx_fb_user_info_id_database[$campaign_id]; // find gb user id for this campaign
            // $this->session->set_userdata("fb_rx_login_database_id", $facebook_rx_config_id_database[$fb_rx_fb_user_info_id]);    // find fb config id for this fb user info and set to session to call library
            $this->fb_rx_login->app_initialize($facebook_rx_config_id_database[$fb_rx_fb_user_info_id]);

            $report = json_decode($info["report"],true); // get json lead list from database and decode it
            $i=0;
            $send_report = $report;
            $is_spam_caught_send = "0"; // is facebook marked this message as spam?
        
            $campaign_lead=$this->basic->get_data("facebook_ex_conversation_campaign_send",array("where"=>array("campaign_id"=>$campaign_id,"processed"=>"0")),'','',$number_of_message_to_be_sent_in_try);

            foreach($campaign_lead as $key => $value) 
            {
                if($catch_error_count>10)  // if 10 catch block error then stop sending, mark as complete
                {
                    $send_report_json= json_encode($send_report);
                    $this->basic->update_data("facebook_ex_conversation_campaign",array("id"=>$campaign_id),array("report"=>$send_report_json,"posting_status"=>'4','successfully_sent'=>$successfully_sent,'completed_at'=>date("Y-m-d H:i:s"),"is_spam_caught"=>$is_spam_caught_send,"error_message"=>$error_msg,"is_try_again"=>"0","last_try_error_count"=>$catch_error_count));
                    break;
                }

                $page_id_send  = $value["page_id"];
                $send_table_id = $value['id'];
                $client_thread_id_send = $value['client_thread_id'];
                $client_id_send = $value['client_id'];
                $client_username_send = $value['client_username'];
                $lead_inbox_link = $value['link'];

                if($value['first_name']=="")
                {
                    $client_username_send_array = explode(' ', $client_username_send);
                    $client_last_name = array_pop($client_username_send_array);
                    $client_first_name = implode(' ', $client_username_send_array);
                }
                else
                {
                    $client_first_name = $value['first_name'];
                    $client_last_name = $value['last_name'];                
                }
                
                $error_msg="";
                $message_error_code = "";
                $message_sent_id = "";

                if(!isset($access_token_database_database[$campaign_id][$page_id_send])) continue;
                $page_access_token_send = $access_token_database_database[$campaign_id][$page_id_send]; // get access toke from our access token database

                //  generating message
                $campaign_message_send = $campaign_message;
                $campaign_message_send = str_replace('#LEAD_USER_FIRST_NAME#',$client_first_name,$campaign_message_send);
                $campaign_message_send = str_replace('#LEAD_USER_LAST_NAME#',$client_last_name,$campaign_message_send);             

                try
                {
                    $campaign_message_send = spintax_process($campaign_message_send);
                    $response = $this->fb_rx_login->send_message_to_thread($client_thread_id_send,$campaign_message_send,$page_access_token_send);
                    if(isset($response['id']))
                    {
                        $message_sent_id = $response['id']; 
                        $successfully_sent++; 
                    }
                    else 
                    {
                        if(isset($response["error"]["message"])) $message_sent_id = $response["error"]["message"];  
                        if(isset($response["error"]["code"])) $message_error_code = $response["error"]["code"]; 

                        if($message_error_code=="368") // if facebook marked message as spam 
                        {
                            $error_msg=$message_sent_id;
                            // $is_spam_caught_send = "1";
                            $catch_error_count++;

                            $this->basic->update_data("messenger_bot_subscriber",array("id"=>$value["lead_id"]),array("unavailable_conversation"=>"1","last_error_message_conversation"=>$error_msg));
                        }

                        else if($message_error_code=="230") //user blocked page
                        {
                            $error_msg=$message_sent_id;
                            $this->basic->update_data("messenger_bot_subscriber",array("id"=>$value["lead_id"]),array("unavailable_conversation"=>"1","last_error_message_conversation"=>$error_msg));
                        }
                        else
                        {
                            $error_msg = $message_sent_id;
                            $catch_error_count++;
                        }
                    } 

                    if($delay_time==0)
                    sleep(rand(3,12));
                    else sleep($delay_time);                  
                    
                }

                catch(Exception $e) 
                {
                  $error_msg = $e->getMessage();
                  // $catch_error_count++;
                }

                // generating new report with send message info
                $now_sent_time=date("Y-m-d H:i:s");
                $send_report[$page_id_send][$client_thread_id_send] = array
                ( 
                    "client_username"=>$client_username_send,
                    "client_id"=>$client_id_send,
                    "message_sent_id"=> $message_sent_id,
                    "sent_time"=> $now_sent_time,
                    "page_name" => $page_ids_names[$page_id_send],
                    "lead_id" => $value["lead_id"],
                    "page_id" => $fb_page_ids[$page_id_send],
                    "link" => $lead_inbox_link,
                    "first_name" => $client_first_name,
                    "last_name" => $client_last_name
                );

                $i++;  
                // after 10 send update report in database
                if($i%$update_report_after_time==0)
                {
                    $send_report_json= json_encode($send_report);
                    $this->basic->update_data("facebook_ex_conversation_campaign",array("id"=>$campaign_id),array("report"=>$send_report_json,'successfully_sent'=>$successfully_sent,"error_message"=>$error_msg,"last_try_error_count"=>$catch_error_count));
                }                

                // updating a lead, marked as processed
                $this->basic->update_data("facebook_ex_conversation_campaign_send",array("id"=>$send_table_id),array('processed'=>'1',"sent_time"=>$now_sent_time,"message_sent_id"=>$message_sent_id,"page_name"=>$page_ids_names[$page_id_send]));
            
            } 

            // one campaign completed, now update database finally
            $send_report_json= json_encode($send_report);
            // if((count($campaign_lead)<$number_of_message_to_be_sent_in_try) || $number_of_message_to_be_sent_in_try=="" || $catch_error_count>10 || $message_error_code=="368")
            if((count($campaign_lead)<$number_of_message_to_be_sent_in_try) || $number_of_message_to_be_sent_in_try=="" || $catch_error_count>10)
            {
            	$new_posting_status = ($catch_error_count>10) ? '4' : '2';
                $complete_update=array("report"=>$send_report_json,"posting_status"=>$new_posting_status,'successfully_sent'=>$successfully_sent,'completed_at'=>date("Y-m-d H:i:s"),"is_spam_caught"=>$is_spam_caught_send,"is_try_again"=>"0","last_try_error_count"=>$catch_error_count);
                if(isset($error_msg))
                $complete_update["error_message"]=$error_msg;
                $this->basic->update_data("facebook_ex_conversation_campaign",array("id"=>$campaign_id),$complete_update);
            }
            else // suppose update_report_after_time=20 but there are 19 message to sent, need to update report in that case
            {
                $this->basic->update_data("facebook_ex_conversation_campaign",array("id"=>$campaign_id),array("report"=>$send_report_json,'successfully_sent'=>$successfully_sent,"is_try_again"=>"1"));
            }
        }          
    
    }
    public function subscriber_broadcaster($api_key="") // braodcast_message
    {
        $this->api_key_check($api_key);
        $broadcaster_number_of_message_to_be_sent_in_try=$this->config->item("broadcaster_number_of_message_to_be_sent_in_try");
        if($broadcaster_number_of_message_to_be_sent_in_try==0) $broadcaster_number_of_message_to_be_sent_in_try="";
        $broadcaster_update_report_after_time=$this->config->item("broadcaster_update_report_after_time"); 
        if($broadcaster_update_report_after_time=="" || $broadcaster_update_report_after_time==0) $broadcaster_update_report_after_time=10;
        $number_of_campaign_to_be_processed = 1; // max number of campaign that can be processed by this cron job
        // $number_of_message_tob_be_sent = 50000;  // max number of message that can be sent in an hour

        /****** Get all campaign from database where status=0 means pending ******/
        $where['or_where'] = array('posting_status'=>"0","is_try_again"=>"1");
        $join = array('users'=>'messenger_bot_broadcast_serial.user_id=users.id,left');
        $campaign_info= $this->basic->get_data("messenger_bot_broadcast_serial",$where,$select=array("messenger_bot_broadcast_serial.*","users.deleted as user_deleted","users.status as user_status","users.user_type as user_type"),$join,$limit=50, $start=0, $order_by='schedule_time ASC');  

        $page_ids_names=array();
        $access_token_database_database=array();
        $facebook_rx_fb_user_info_id_database=array();
        $campaign_id_array=array();  // all selected campaign id array
        $campaign_info_fildered = array(); // valid for process, campign info array

        $valid_campaign_count = 1;
        foreach($campaign_info as $info)
        {
            if($this->is_demo=='1' && $info['user_type']=="Admin")
            {
                $this->db->where("id",$info['id']);
                $this->db->update("messenger_bot_broadcast_serial",array("posting_status"=>"1","is_try_again"=>"0"));
                continue;
            }
            if($info['user_deleted'] == '1' || $info['user_status']=="0")
            {
                $this->db->where("id",$info['id']);
                $this->db->update("messenger_bot_broadcast_serial",array("posting_status"=>"1","is_try_again"=>"0"));
                continue;
            }

            $campaign_id= $info['id'];
            $time_zone= $info['timezone'];
            $schedule_time= $info['schedule_time']; 
            $total_thread = $info["total_thread"];
            $page_id =$info["page_id"]; // auto ids
            $fb_page_id =$info["fb_page_id"]; 
            $user_id = $info["user_id"];                  

            if($time_zone) date_default_timezone_set($time_zone);            
            $now_time = date("Y-m-d H:i:s");

            if((strtotime($now_time) < strtotime($schedule_time)) && $time_zone!="") continue; 
            if($valid_campaign_count > $number_of_campaign_to_be_processed) break; 

            // get access token and fb user id
            $token_info =  $this->basic->get_data('facebook_rx_fb_page_info',array("where"=>array('id'=>$page_id,'user_id'=>$user_id)));
            foreach ($token_info as $key => $value) 
            {
                $access_token_database_database[$campaign_id][$value["id"]] = $value['page_access_token'];
                $facebook_rx_fb_user_info_id = $value["facebook_rx_fb_user_info_id"];
                $facebook_rx_fb_user_info_id_database[$campaign_id] = $facebook_rx_fb_user_info_id;
                $page_ids_names[$value["id"]] = $value["page_name"];
            }

            // valid campaign info and campig ids
            $campaign_info_fildered[] = $info;
            $campaign_id_array[] = $info['id']; 
            $valid_campaign_count++;      
        }

        if(count($campaign_id_array)==0) exit();        

        $this->db->where_in("id",$campaign_id_array);
        $this->db->update("messenger_bot_broadcast_serial",array("posting_status"=>"1","is_try_again"=>"0"));

        // get config id
        $getdata= $this->basic->get_data("facebook_rx_fb_user_info",array("where_in"=>array("id"=>$facebook_rx_fb_user_info_id_database)),array("id","facebook_rx_config_id"));
        foreach ($getdata as $key => $value) 
        {
            $facebook_rx_config_id_database[$value["id"]] = $value["facebook_rx_config_id"];
        } 

        $this->load->library("fb_rx_login"); 

        // send message
        foreach($campaign_info_fildered as $info)
        {
            $campaign_id= $info['id'];            
            $user_id = $info["user_id"];           
            $catch_error_count=$info["last_try_error_count"];
            $successfully_sent=$info["successfully_sent"];
            $successfully_delivered=$info["successfully_delivered"];
 
            $fb_rx_fb_user_info_id = $facebook_rx_fb_user_info_id_database[$campaign_id]; // find gb user id for this campaign
            $this->fb_rx_login->app_initialize($facebook_rx_config_id_database[$fb_rx_fb_user_info_id]);

            $report = json_decode($info["report"],true); // get json lead list from database and decode it
            $i=0;
            $send_report = $report;
        
            $campaign_lead=$this->basic->get_data("messenger_bot_broadcast_serial_send",array("where"=>array("campaign_id"=>$campaign_id,"processed"=>"0")),'','',$broadcaster_number_of_message_to_be_sent_in_try);

            // echo "<pre>".$this->db->last_query(); 
            // print_r($campaign_lead); 

            foreach($campaign_lead as $key => $value) 
            {
                if($catch_error_count>30)  // if 30 catch block error then stop sending, mark as complete
                {
                    $send_report_json= json_encode($send_report);
                    $this->basic->update_data("messenger_bot_broadcast_serial",array("id"=>$campaign_id),array("report"=>$send_report_json,"posting_status"=>'4','successfully_sent'=>$successfully_sent,'completed_at'=>date("Y-m-d H:i:s"),"error_message"=>$error_msg,"is_try_again"=>"0","last_try_error_count"=>$catch_error_count));
                    break;
                }
                $campaign_message_send=$info["message"];
                $page_id_send  = $value["page_id"];
                $send_table_id = $value['id'];
                $subscribe_id = $value['subscribe_id'];
                $subscribeauto_id = $value['subscriber_auto_id'];
                $client_first_name = $value['subscriber_name'];
                $client_last_name = $value['subscriber_lastname'];
                
                $error_msg="";
                $message_error_code = "";
                $message_sent_id = "";

                if(!isset($access_token_database_database[$campaign_id][$page_id_send])) continue;
                $page_access_token_send = $access_token_database_database[$campaign_id][$page_id_send]; // get access toke from our access token database

                //  generating message
                $campaign_message_send = str_replace('{{first_name}}',$client_first_name,$campaign_message_send);
                $campaign_message_send = str_replace('{{last_name}}',$client_last_name,$campaign_message_send);
                $campaign_message_send = str_replace('PUT_SUBSCRIBER_ID',$subscribe_id,$campaign_message_send);

                // print_r($campaign_message_send); continue;

                $message_sent_id="";
                $now_sent_time=date("Y-m-d H:i:s");  
                $deliveryTime=''; 
                $isDelivered='0';  
                $successfully_sent++;    
                try
                {
                    // $campaign_message_send = spintax_process($campaign_message_send);
                    $response = $this->fb_rx_login->send_non_promotional_message_subscription($campaign_message_send,$page_access_token_send);

                    if(isset($response['message_id']))
                    {
                        $message_sent_id = $response['message_id']; 
                        $successfully_delivered++;
                        $deliveryTime=date("Y-m-d H:i:s");
                        $isDelivered="1";
                    }
                    else 
                    {
                        if(isset($response["error"]["message"])) $message_sent_id = $response["error"]["message"];  
                        if(isset($response["error"]["code"])) $message_error_code = $response["error"]["code"];

                        if($message_error_code=="551") // unvalilable user
                        {
                            $this->basic->update_data("messenger_bot_subscriber",array("id"=>$subscribeauto_id),array("unavailable"=>"1","last_error_message"=>$message_sent_id));
                        }
                        else
                        {
                            $error_msg = $message_sent_id;
                            $catch_error_count++;
                        }                    
                    }              
                    
                }

                catch(Exception $e) 
                {
                  $error_msg = $e->getMessage();
                  $catch_error_count++;
                }

                // generating new report with send message info
                
                $send_report[$subscribe_id] = array
                (
                    "subscribe_id"=>$subscribe_id,
                    "subscriber_auto_id"=>$subscribeauto_id,
                    "subscriber_name"=>$value["subscriber_name"],
                    "subscriber_lastname"=>$value["subscriber_lastname"],
                    "sent"=>"1",
                    "sent_time"=>$now_sent_time,
                    "delivered"=>$isDelivered,
                    "delivery_time"=>$deliveryTime,
                    "opened"=>"0",
                    "open_time"=>"",
                    "clicked"=>"0",
                    "click_time"=>"",
                    "click_ref"=>"",
                    "message_sent_id"=>$message_sent_id
                );

                $i++;  
                // after 10 send update report in database
                if($i%$broadcaster_update_report_after_time==0)
                {
                    $send_report_json= json_encode($send_report);
                    $this->basic->update_data("messenger_bot_broadcast_serial",array("id"=>$campaign_id),array("report"=>$send_report_json,'successfully_sent'=>$successfully_sent,"successfully_delivered"=>$successfully_delivered,"error_message"=>$error_msg,"last_try_error_count"=>$catch_error_count));
                }
       
                // updating a lead, marked as processed
                $this->basic->update_data("messenger_bot_broadcast_serial_send",array("id"=>$send_table_id),array('processed'=>'1',"sent_time"=>$now_sent_time,"delivered"=>$isDelivered,"delivery_time"=>$deliveryTime,"message_sent_id"=>$message_sent_id));
            
            } 
        

            // one campaign completed, now update database finally
            $send_report_json= json_encode($send_report);
            if((count($campaign_lead)<$broadcaster_number_of_message_to_be_sent_in_try) || $broadcaster_number_of_message_to_be_sent_in_try=="" || $catch_error_count>30)
            { 
                $new_posting_status = ($catch_error_count>30) ? '4' : '2';
                $complete_update=array("report"=>$send_report_json,"posting_status"=>$new_posting_status,'successfully_sent'=>$successfully_sent,"successfully_delivered"=>$successfully_delivered,'completed_at'=>date("Y-m-d H:i:s"),"is_try_again"=>"0","last_try_error_count"=>$catch_error_count);
                if(isset($error_msg))
                $complete_update["error_message"]=$error_msg;
                $this->basic->update_data("messenger_bot_broadcast_serial",array("id"=>$campaign_id),$complete_update);
            }
            else // suppose broadcaster_update_report_after_time=20 but there are 19 message to sent, need to update report in that case
            {
                $this->basic->update_data("messenger_bot_broadcast_serial",array("id"=>$campaign_id),array("report"=>$send_report_json,'successfully_sent'=>$successfully_sent,"successfully_delivered"=>$successfully_delivered,"is_try_again"=>"1"));
            }
        }          
   
    }
    // ===========MESSENGER BROADCASTER RELATED FUNCTIONS=============




    // ===========MESSENGER DRIP RELATED FUNCTIONS=============
    public function sequence_message_broadcast_hourly($api_key="")
    { 
        $this->api_key_check($api_key);
        $number_of_row=100; // number of subscriber on cron will process

        // reseting to system timezone
        $time_zone = $this->config->item('time_zone');
        if($time_zone== '') $time_zone="Europe/Dublin";        
        date_default_timezone_set($time_zone);
        
        $page_database=array(); // associated page auto id and page id

        // getting eligible subscriber data
        $subscriber_where=
        array
        (
            "where"=>array
            (
                "messenger_bot_drip_campaign_id !="=>"0",
                "messenger_bot_drip_initial_date !="=>"0000-00-00 00:00:00",
                "messenger_bot_drip_is_toatally_complete_hourly"=>"0",
                "messenger_bot_drip_processing_status_hourly"=>"0"
            ),
        );
        $join = array('messenger_bot_subscriber'=>"messenger_bot_subscriber.subscribe_id=messenger_bot_drip_campaign_assign.subscribe_id,right",
                      'messenger_bot_drip_campaign'=>"messenger_bot_drip_campaign.id=messenger_bot_drip_campaign_assign.messenger_bot_drip_campaign_id,right",
                      'facebook_rx_fb_page_info'=>'facebook_rx_fb_page_info.id=messenger_bot_drip_campaign_assign.page_table_id,right'
                    );

        $subscriber_data=$this->basic->get_data("messenger_bot_drip_campaign_assign",$subscriber_where,'messenger_bot_drip_campaign_assign.*,messenger_bot_subscriber.permission,messenger_bot_subscriber.page_id,messenger_bot_subscriber.first_name,messenger_bot_subscriber.last_name,
            messenger_bot_drip_campaign.message_content_hourly,facebook_rx_fb_page_info.page_access_token',$join,$number_of_row,NULL,'last_processing_started_at_hourly ASC');

        if(empty($subscriber_data)) exit();
       

        $this->load->library("fb_rx_login"); 

        // marking subscribers this cron is operating as processing (comment this query while test)
       $this->db->where($subscriber_where["where"]);
        $this->db->update("messenger_bot_drip_campaign_assign", array('messenger_bot_drip_processing_status_hourly' => "1","last_processing_started_at_hourly"=>date("Y-m-d H:i:s")));


        foreach ($subscriber_data as $key => $value) 
        {            
            $user_id=$value["user_id"];
            $subscribe_auto_id=$value["id"];
            $subscribe_id=$value["subscribe_id"];
            $first_name=$value["first_name"];
            $last_name=$value["last_name"];
            $messenger_bot_drip_campaign_id=$value["messenger_bot_drip_campaign_id"];
            $messenger_bot_drip_initial_date=$value["messenger_bot_drip_initial_date"];
            $messenger_bot_drip_last_completed_day=$value["messenger_bot_drip_last_completed_hour"];
            $page_table_id=$value["page_table_id"];


            $message_content=json_decode($value["message_content_hourly"],true);

            // if there is no hourly sequence not sent for this drip campaign, then update the subscribers hourly campaign as completed. 
            if(empty($message_content)){
                 $this->basic->update_data("messenger_bot_drip_campaign_assign",array("subscribe_id"=>$subscribe_id,"messenger_bot_drip_campaign_id"=>$messenger_bot_drip_campaign_id),array("messenger_bot_drip_is_toatally_complete_hourly"=>"1"));
                 continue;
            }

            $message_days=array_keys($message_content); // th days campaign will send message
            $max_send_day=max($message_days); // maximum campaign day, will decide campaign totally complete or not
           

            foreach ($message_days as $key2 => $value2) 
            {
               if($value2>$messenger_bot_drip_last_completed_day) // getting the next day to start sending message
               {
                
                  $today=date("Y-m-d H:i:s");
                 
                  $sending_day=$value2; // currently processing this drip time
                 // $adding_days=$sending_day-$messenger_bot_drip_last_completed_day; 
                  $sending_date=date('Y-m-d H:i:s', strtotime($messenger_bot_drip_initial_date. ' + '.$sending_day.' minutes'));
                  $is_totally_complete='0';
                  if($max_send_day==$sending_day) $is_totally_complete='1';

                  // calculate after 24 hours date. If for any reason it exceeds 24 hours then complete the campaign.
                   $after_24_date=date('Y-m-d H:i:s', strtotime($messenger_bot_drip_initial_date. '+ 24 hours'));

                  if(strtotime($today)>strtotime($after_24_date)) // if somehow some subscriber was failed to sent message and it will never be comeplete so we are canceling it
                  {
                    $this->basic->update_data("messenger_bot_drip_campaign_assign",array("subscribe_id"=>$subscribe_id,"messenger_bot_drip_campaign_id"=>$messenger_bot_drip_campaign_id),array("messenger_bot_drip_is_toatally_complete_hourly"=>"1"));
                  }


                  if(strtotime($today)>=strtotime($sending_date) && strtotime($today)<strtotime($after_24_date) ) // deciding if we have to send message or not now
                  {
                    //getting message template
                    $sent_response=array();
                    $sending_template_id=$message_content[$sending_day];
                    $template_data=$this->basic->get_data("messenger_bot_postback",array("where"=>array("id"=>$sending_template_id)));
                    if(!isset($template_data[0])) 
                    {
                          $sent_response[] = "Message template not found.";
                        // break;
                    }

                    //making message to be sent
                    $temp=isset($template_data[0]['template_jsoncode'])?$template_data[0]['template_jsoncode']:"";  

                    $message_array=($temp!="") ? json_decode($temp,true) : array();  

                    $p=0;
                    $curdate=date("Y-m-d H:i:s"); 
                    foreach($message_array as $msg)
                    {
                        $p++;
                        $template_type_file_track=$msg['message']['template_type'];
                        unset($msg['message']['template_type']);

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
                        // $msg["tag"]="NON_PROMOTIONAL_SUBSCRIPTION";


                        $campaign_message_send=json_encode($msg); 
                        $campaign_message_send = str_replace('#LEAD_USER_FIRST_NAME#',$first_name,$campaign_message_send);
                        $campaign_message_send = str_replace('#LEAD_USER_LAST_NAME#',$last_name,$campaign_message_send);

                        $replace_search=array('{"id":"replace_id"}','#SUBSCRIBER_ID_REPLACE#');
                        $replace_with=array('{"id":"'.$subscribe_id.'"}',$subscribe_id);
                        $campaign_message_send=str_replace($replace_search, $replace_with, $campaign_message_send);
                                              
                        $error_count=0;
                        try
                        {
                            $page_access_token_send=isset($value['page_access_token'])?$value['page_access_token']:"";
                            $response = $this->fb_rx_login->send_non_promotional_message_subscription($campaign_message_send,$page_access_token_send);
                        
                            if(isset($response['message_id']))
                            {
                                $sent_response[] = $response['message_id']; 
                            }
                            else 
                            {
                                if(isset($response["error"]["message"])) $sent_response[] = $response["error"]["message"];  
                                // if(isset($response["error"]["code"])) $message_error_code = $response["error"]["code"]; 
                                $error_count++;                 
                            }              
                            
                        }
                        catch(Exception $e) 
                        {
                          $sent_response[] = $e->getMessage();
                          $error_count++;
                        }
                    }  
                   
                    $insert_data=array
                    (
                        "messenger_bot_drip_campaign_id"=>$messenger_bot_drip_campaign_id,
                        "messenger_bot_subscriber_id"=>$subscribe_auto_id,
                        "page_id"=>$page_table_id,
                        "user_id"=>$user_id,
                        "subscribe_id"=>$subscribe_id,
                        "first_name"=>$first_name,
                        "last_name"=>$last_name,
                        "last_completed_hour"=>$sending_day,
                        "is_sent"=>'1',
                        "sent_at"=>$curdate,
                        "last_updated_at"=>$curdate,
                        "sent_response"=>json_encode($sent_response)
                    );

                    $total_count=count($message_array);
                    if(isset($error_count) && $error_count!=$total_count) // do not need to update delivery status if error
                    {
                        $curdate2=date("Y-m-d H:i:s");
                        $success_count=$total_count-$error_count;
                        $del_response=$success_count. "/". $total_count." ".$this->lang->line("success");
                        $insert_data["is_delivered"]='1';
                        $insert_data["delivered_at"]=$curdate2;
                        $insert_data["last_updated_at"]=$curdate2;
                        $insert_data["delivered_response"]=$del_response;
                    }
                    $this->basic->insert_data("messenger_bot_drip_report",$insert_data); // inserting send report
 
                    $sub_update=array
                    (
                        "messenger_bot_drip_last_completed_hour"=>$sending_day,
                        "messenger_bot_drip_is_toatally_complete_hourly"=>$is_totally_complete,
                        "messenger_bot_drip_last_sent_at"=>$curdate,
                        "messenger_bot_drip_processing_status_hourly"=>"0",
                    );
                    // comment this query while test
                    $this->basic->update_data("messenger_bot_drip_campaign_assign",array("subscribe_id"=>$subscribe_id,"messenger_bot_drip_campaign_id"=>$messenger_bot_drip_campaign_id),$sub_update);// updating subscriber so that it will process next drip day again
                  }
                  break;
               }
            }             
        }

        // marking subscribers this cron is operating as ok to process by another cron later  (comment this query while test)
        if(isset($subscriber_where['where']['messenger_bot_drip_processing_status_hourly'])) unset($subscriber_where['where']['messenger_bot_drip_processing_status_hourly']);
        $this->db->where($subscriber_where["where"]);
        $this->db->update("messenger_bot_drip_campaign_assign", array('messenger_bot_drip_processing_status_hourly' => "0"));
        $this->db->update("messenger_bot_drip_campaign", array('last_sent_at' => date("Y-m-d H:i:s")));
    }
    public function sequence_message_broadcast_daily($api_key="")
    { 
        $this->api_key_check($api_key);
        $number_of_row=50; // number of subscriber on cron will process

        $fb_page_ids=array(); // all facebook pages to be operated [auto id actiually]

        $get_all_campaign=$this->basic->get_data("messenger_bot_drip_campaign");
        $time_match_campaign_ids=array(); // holds ids of campaigns which time interval matches current time
        $campaign_data_formatted=array();

        foreach ($get_all_campaign as $key => $value) 
        {
            $cam_timezone=$value['timezone'];
            if($cam_timezone)  date_default_timezone_set($cam_timezone);

            $cam_between_start=$value['between_start'];
            $cam_between_end=$value['between_end'];

            $current_time=date("H:i");

            $temp0 = (float) str_replace(':','.',$current_time);
            $temp1 = (float) str_replace(':','.',$cam_between_start);
            $temp2 = (float) str_replace(':','.',$cam_between_end);      

            if($temp0>=$temp1 && $temp0<=$temp2) // matches time slot
            {
                $time_match_campaign_ids[]=$value['id'];
                $campaign_data_formatted[$value['id']]=$value;
                $fb_page_ids[]=$value['page_id'];
            }
        }
        $fb_page_ids=array_unique($fb_page_ids);

        if(empty($time_match_campaign_ids)) exit(); // no campaign matches current time zone, go home :p

        // reseting to system timezone
        $time_zone = $this->config->item('time_zone');
        if($time_zone== '') $time_zone="Europe/Dublin";        
        date_default_timezone_set($time_zone);
        
        $page_database=array(); // associated page auto id and page id

        // getting eligible subscriber data
        $subscriber_where=
        array
        (
            "where"=>array
            (
                "messenger_bot_drip_campaign_id !="=>"0",
                "messenger_bot_drip_initial_date !="=>"0000-00-00 00:00:00",
                "messenger_bot_drip_is_toatally_complete"=>"0",
                "messenger_bot_drip_processing_status"=>"0"
            ),
            "where_in"=>array("messenger_bot_drip_campaign_id"=>$time_match_campaign_ids)
        );
        $join = array('messenger_bot_subscriber'=>"messenger_bot_subscriber.subscribe_id=messenger_bot_drip_campaign_assign.subscribe_id,left");
        $subscriber_data=$this->basic->get_data("messenger_bot_drip_campaign_assign",$subscriber_where,'messenger_bot_drip_campaign_assign.*,permission,page_id,first_name,last_name',$join,$number_of_row,NULL,'last_processing_started_at ASC');
        if(empty($subscriber_data)) exit();
       

        $this->load->library("fb_rx_login"); 

        // marking subscribers this cron is operating as processing (comment this query while test)
        $this->db->where($subscriber_where["where"]);
        $this->db->where_in($subscriber_where["where_in"]);
        $this->db->update("messenger_bot_drip_campaign_assign", array('messenger_bot_drip_processing_status' => "1","last_processing_started_at"=>date("Y-m-d H:i:s")));

        // getting page access token
        $page_data=$this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("bot_enabled"=>"1"),"where_in"=>array('id'=>$fb_page_ids)));
        foreach ($page_data as $key => $value) 
        {
            $page_database[$value['page_id']]=array("page_id"=>$value['id'],"fb_page_id"=>$value['page_id'],"page_access_token"=>$value['page_access_token']);
        }
           
        foreach ($subscriber_data as $key => $value) 
        {            
            $user_id=$value["user_id"];
            $subscribe_auto_id=$value["id"];
            $subscribe_id=$value["subscribe_id"];
            $first_name=$value["first_name"];
            $last_name=$value["last_name"];
            $messenger_bot_drip_campaign_id=$value["messenger_bot_drip_campaign_id"];
            $messenger_bot_drip_initial_date=$value["messenger_bot_drip_initial_date"];
            $messenger_bot_drip_last_completed_day=$value["messenger_bot_drip_last_completed_day"];
            
            if(!isset($campaign_data_formatted[$messenger_bot_drip_campaign_id])) 
            {
                echo "Drip campaign ID : ".$messenger_bot_drip_campaign_id." not found <br>";
                continue;
            }
           
            $message_content=json_decode($campaign_data_formatted[$messenger_bot_drip_campaign_id]["message_content"],true);

             // if there is no Daily sequence not sent for this drip campaign, then update the subscribers Daily campaign as completed. 
            if(empty($message_content)){
                 $this->basic->update_data("messenger_bot_drip_campaign_assign",array("subscribe_id"=>$subscribe_id,"messenger_bot_drip_campaign_id"=>$messenger_bot_drip_campaign_id),array("messenger_bot_drip_is_toatally_complete"=>"1"));
                 continue;
            }



            $message_days=array_keys($message_content); // th days campaign will send message
            $max_send_day=max($message_days); // maximum campaign day, will decide campaign totally complete or not

            foreach ($message_days as $key2 => $value2) 
            {
               if($value2>$messenger_bot_drip_last_completed_day) // getting the next day to start sending message
               {
                  $today=date("Y-m-d");
                  // $today="2018-08-12";
                  $sending_day=$value2; // currently processing this drip day
                  //$adding_days=$sending_day-$messenger_bot_drip_last_completed_day; 
                  $sending_date=date('Y-m-d', strtotime($messenger_bot_drip_initial_date. ' + '.$sending_day.' days'));
                  $is_totally_complete='0';
                  if($max_send_day==$sending_day) $is_totally_complete='1';

                  if(strtotime($today)>strtotime($sending_date)) // if somehow some subscriber was failed to sent message and it will never be comeplete so we are canceling it
                  {
                    $this->basic->update_data("messenger_bot_drip_campaign_assign",array("subscribe_id"=>$subscribe_id,"messenger_bot_drip_campaign_id"=>$messenger_bot_drip_campaign_id),array("messenger_bot_drip_is_toatally_complete"=>"1"));
                  }

                  if(strtotime($today)==strtotime($sending_date)) // deciding if we have to send message or not today
                  {
                    //getting message template
                    $sent_response=array();
                    $sending_template_id=$message_content[$sending_day];
                    $template_data=$this->basic->get_data("messenger_bot_postback",array("where"=>array("id"=>$sending_template_id)));
                    if(!isset($template_data[0])) 
                    {
                          $sent_response[] = "Message template not found.";
                        // break;
                    }

                    //making message to be sent
                    $temp=isset($template_data[0]['template_jsoncode'])?$template_data[0]['template_jsoncode']:"";  

                    $message_array=($temp!="") ? json_decode($temp,true) : array();  

                    $p=0;
                    $curdate=date("Y-m-d H:i:s"); 
                    foreach($message_array as $msg)
                    {
                        $p++;
                        $template_type_file_track=$msg['message']['template_type'];
                        unset($msg['message']['template_type']);

                        $enable_typing_on = $msg['message']['typing_on_settings'];
                        $enable_typing_on = ($enable_typing_on=='on')  ? 1 : 0;
                        unset($msg['message']['typing_on_settings']);
                        $typing_on_delay_time = $msg['message']['delay_in_reply'];
                        if($typing_on_delay_time=="") $typing_on_delay_time = 0;
                        unset($msg['message']['delay_in_reply']);

                        /** Spintax **/
                        if(isset($msg['message']['text']))
                            $msg['message']['text']=spintax_process($msg['message']['text']);

                        $msg['messaging_type'] = "MESSAGE_TAG";
                        $msg["tag"]=$campaign_data_formatted[$messenger_bot_drip_campaign_id]["message_tag"];
                        


                        $campaign_message_send=json_encode($msg); 
                        $campaign_message_send = str_replace('#LEAD_USER_FIRST_NAME#',$first_name,$campaign_message_send);
                        $campaign_message_send = str_replace('#LEAD_USER_LAST_NAME#',$last_name,$campaign_message_send);

                        $replace_search=array('{"id":"replace_id"}','#SUBSCRIBER_ID_REPLACE#');
                        $replace_with=array('{"id":"'.$subscribe_id.'"}',$subscribe_id);
                        $campaign_message_send=str_replace($replace_search, $replace_with, $campaign_message_send);
                                              
                        $error_count=0;
                        try
                        {
                            $page_access_token_send=isset($page_database[$value['page_id']]['page_access_token'])?$page_database[$value['page_id']]['page_access_token']:"";
                            $response = $this->fb_rx_login->send_non_promotional_message_subscription($campaign_message_send,$page_access_token_send);
                        
                            if(isset($response['message_id']))
                            {
                                $sent_response[] = $response['message_id']; 
                            }
                            else 
                            {
                                if(isset($response["error"]["message"])) $sent_response[] = $response["error"]["message"];  
                                // if(isset($response["error"]["code"])) $message_error_code = $response["error"]["code"]; 
                                $error_count++;                 
                            }              
                            
                        }
                        catch(Exception $e) 
                        {
                          $sent_response[] = $e->getMessage();
                          $error_count++;
                        }
                    }  
                   
                    $insert_data=array
                    (
                        "messenger_bot_drip_campaign_id"=>$messenger_bot_drip_campaign_id,
                        "messenger_bot_subscriber_id"=>$subscribe_auto_id,
                        "page_id"=>$page_database[$value['page_id']]['page_id'],
                        "user_id"=>$user_id,
                        "subscribe_id"=>$subscribe_id,
                        "first_name"=>$first_name,
                        "last_name"=>$last_name,
                        "last_completed_day"=>$sending_day,
                        "is_sent"=>'1',
                        "sent_at"=>$curdate,
                        "last_updated_at"=>$curdate,
                        "sent_response"=>json_encode($sent_response)
                    );

                    $total_count=count($message_array);
                    if(isset($error_count) && $error_count!=$total_count) // do not need to update delivery status if error
                    {
                        $curdate2=date("Y-m-d H:i:s");
                        $success_count=$total_count-$error_count;
                        $del_response=$success_count. "/". $total_count." ".$this->lang->line("success");
                        $insert_data["is_delivered"]='1';
                        $insert_data["delivered_at"]=$curdate2;
                        $insert_data["last_updated_at"]=$curdate2;
                        $insert_data["delivered_response"]=$del_response;
                    }
                    $this->basic->insert_data("messenger_bot_drip_report",$insert_data); // inserting send report
 
                    $sub_update=array
                    (
                        "messenger_bot_drip_last_completed_day"=>$sending_day,
                        "messenger_bot_drip_is_toatally_complete"=>$is_totally_complete,
                        "messenger_bot_drip_last_sent_at"=>$curdate,
                        // "messenger_bot_drip_initial_date"=>$curdate,
                        "messenger_bot_drip_processing_status"=>"0",
                    );
                    // comment this query while test
                    $this->basic->update_data("messenger_bot_drip_campaign_assign",array("subscribe_id"=>$subscribe_id,"messenger_bot_drip_campaign_id"=>$messenger_bot_drip_campaign_id),$sub_update);// updating subscriber so that it will process next drip day again
                  }
                  break;
               }
            }             
        }

        // marking subscribers this cron is operating as ok to process by another cron later  (comment this query while test)
        if(isset($subscriber_where['where']['messenger_bot_drip_processing_status'])) unset($subscriber_where['where']['messenger_bot_drip_processing_status']);
        $this->db->where($subscriber_where["where"]);
        $this->db->where_in($subscriber_where["where_in"]);
        $this->db->update("messenger_bot_drip_campaign_assign", array('messenger_bot_drip_processing_status' => "0"));

        //updaing date in messenger_bot_drip_campaign table
        if(count($time_match_campaign_ids)>0)
        {
            $this->db->where_in('id', $time_match_campaign_ids);
            $this->db->update("messenger_bot_drip_campaign", array('last_sent_at' => date("Y-m-d H:i:s")));
        }
    }
    // ===========MESSENGER DRIP RELATED FUNCTIONS=============





    // =====================OTHER FUNCTIONS===================
    public function membership_alert($api_key="") //membership_alert_delete_junk_data
    {
        $this->api_key_check($api_key);    

        $current_date = date("Y-m-d");
        $tenth_day_before_expire = date("Y-m-d", strtotime("$current_date + 10 days"));
        $one_day_before_expire = date("Y-m-d", strtotime("$current_date + 1 days"));
        $one_day_after_expire = date("Y-m-d", strtotime("$current_date - 1 days"));

        // echo $tenth_day_before_expire."<br/>".$one_day_before_expire."<br/>".$one_day_after_expire;

        //send notification to members before 10 days of expire date
        $where = array();
        $where['where'] = array(
            'user_type !=' => 'Admin',
            'expired_date' => $tenth_day_before_expire
            );
        $info = array();
        $value = array();
        $info = $this->basic->get_data('users',$where,$select='');
        $from = $this->config->item('institute_email');
        $mask = $this->config->item('product_name');

        // getting email template info
        $email_template_info = $this->basic->get_data("email_template_management",array('where'=>array('template_type'=>'membership_expiration_10_days_before')),array('subject','message'));

        if(isset($email_template_info[0]) && $email_template_info[0]['subject'] !='' && $email_template_info[0]['message'] !='') {

            $subject = $email_template_info[0]['subject'];
            foreach ($info as $value) 
            {
                if(!$this->api_member_validity($value['id'])) continue;
                $url = base_url();

                $message = str_replace(array('#USERNAME#','#APP_URL#','#APP_NAME#'),array($value['name'],$url,$mask),$email_template_info[0]['message']);

                $to = $value['email'];
                $this->_mail_sender($from, $to, $subject, $message, $mask, $html=1);
            }


        } else {

            $subject = "Payment Notification";
            foreach ($info as $value) 
            {
                if(!$this->api_member_validity($value['id'])) continue;
                $message = "Dear {$value['name']},<br/> your account will expire after 10 days, Please pay your fees.<br/><br/>Thank you,<br/><a href='".base_url()."'>{$mask}</a> team";
                $to = $value['email'];
                $this->_mail_sender($from, $to, $subject, $message, $mask, $html=1);
            }

        }

        //send notificatio to members before 1 day of expire date
        $where = array();
        $where['where'] = array(
            'user_type !=' => 'Admin',
            'expired_date' => $one_day_before_expire
            );
        $info = array();
        $value = array();
        $info = $this->basic->get_data('users',$where,$select='');
        $from = $this->config->item('institute_email');
        $mask = $this->config->item('product_name');

        // getting email template info
        $email_template_info_01 = $this->basic->get_data("email_template_management",array('where'=>array('template_type'=>'membership_expiration_1_day_before')),array('subject','message'));

        if(isset($email_template_info_01[0]) && $email_template_info_01[0]['subject'] != '' && $email_template_info_01[0]['message'] != '') {

            $subject = $email_template_info_01[0]['subject'];
            foreach ($info as $value) {
                if(!$this->api_member_validity($value['id'])) continue;
                $url = base_url();
                $message = str_replace(array('#USERNAME#','#APP_URL#','#APP_NAME#'),array($value['name'],$url,$mask),$email_template_info_01[0]['message']);

                $to = $value['email'];
                $this->_mail_sender($from, $to, $subject, $message, $mask, $html=1);
            }

        }
        else {

            $subject = "Payment Notification";
            foreach ($info as $value) {
                if(!$this->api_member_validity($value['id'])) continue;
                $message = "Dear {$value['name']},<br/> your account will expire tomorrow, Please pay your fees.<br/><br/>Thank you,<br/><a href='".base_url()."'>{$mask}</a> team";
                $to = $value['email'];
                $this->_mail_sender($from, $to, $subject, $message, $mask, $html=1);
            }

        }
        

        //send notificatio to members after 1 day of expire date
        $where = array();
        $where['where'] = array(
            'user_type !=' => 'Admin',
            'expired_date' => $one_day_after_expire
            );
        $info = array();
        $value = array();
        $info = $this->basic->get_data('users',$where,$select='');
        $from = $this->config->item('institute_email');
        $mask = $this->config->item('product_name');

        $email_template_info_02 = $this->basic->get_data("email_template_management",array('where'=>array('template_type'=>'membership_expiration_1_day_after')),array('subject','message'));

        if(isset($email_template_info_02[0]) && $email_template_info_02[0]['subject'] != '' && $email_template_info_02[0]['message'] != '') {

            $subject = $email_template_info_02[0]['subject'];

            foreach ($info as $value) {
                if(!$this->api_member_validity($value['id'])) continue;
                $url = base_url();
                $message = str_replace(array('#USERNAME#','#APP_URL#','#APP_NAME#'),array($value['name'],$url,$mask),$email_template_info_02[0]['message']);
                $to = $value['email'];
                $this->_mail_sender($from, $to, $subject, $message, $mask, $html=1);
            }

        } else {

            $subject = "Payment Notification";
            foreach ($info as $value) {
                if(!$this->api_member_validity($value['id'])) continue;
                $message = "Dear {$value['name']},<br/> your account has been expired, Please pay your fees for continuity.<br/><br/>Thank you,<br/><a href='".base_url()."'>{$mask}</a> team";
                $to = $value['email'];
                $this->_mail_sender($from, $to, $subject, $message, $mask, $html=1);
            }
        }        

    }
    public function delete_junk_data($api_key="") //membership_alert_delete_junk_data
    {
        $this->api_key_check($api_key);
        $this->basic->delete_data("facebook_ex_conversation_campaign_send",array("processed"=>"1"));

        /**Clean Messenger Broadcaster Subscriber Sending Table**/        
       if($this->db->table_exists('messenger_bot_broadcast_serial_send')){
            $this->basic->delete_data("messenger_bot_broadcast_serial_send",array("processed"=>"1"));
       }
       /****Clean Cache Directory , keep all files of last 24 hours******/
       $all_cache_file=$this->delete_cache('application/cache');
       $all_cache_file=$this->delete_cache('upload/qrc');

       //Delete error log file in root
       @unlink("error_log");

    }
    protected function delete_cache($myDir) //delete_junk_data
    {

        $cur_time=date('Y-m-d H:i:s');
        $yesterday=date("Y-m-d H:i:s",strtotime($cur_time." -1 day"));
        $yesterday=strtotime($yesterday);


        $dirTree = array();
        $di = new RecursiveDirectoryIterator($myDir,RecursiveDirectoryIterator::SKIP_DOTS);
        
        foreach (new RecursiveIteratorIterator($di) as $filename) {
        
        $dir = str_replace($myDir, '', dirname($filename));
        //$dir = str_replace('/', '>', substr($dir,1));
        
        $org_dir=str_replace("\\", "/", $dir);
        
        
        if($org_dir)
        $file_path = $org_dir. "/". basename($filename);
        else
        $file_path = basename($filename);

        $path_explode = explode(".",$file_path);
        $extension= array_pop($path_explode);

        if($file_path!='.htaccess' && $file_path!='index.html'){

             $full_file_path=$myDir."/".$file_path;

             $file_creation_time=filemtime($full_file_path);
             $file_creation_time=date('Y-m-d H:i:s',$file_creation_time); //convert unix time to system time zone 
             $file_creation_time=strtotime($file_creation_time);


             if($file_creation_time<$yesterday){
                $dirTree[] = trim($file_path,"/");
                unlink($full_file_path);

             }
                
        }

        
        }
        
        return $dirTree;
            
    }
    // =====================OTHER FUNCTIONS===================



    //Manin cron job with multiple child cron job

    // 1  min
    public function braodcast_message($api_key='')
    {
    	$link=base_url().'cron_job/conversation_broadcast/'.$api_key;
    	$this->call_curl_internal_cronjob($link);

    	if($this->basic->is_exist("add_ons",array("project_id"=>30)))
    	{
    		$link=base_url().'cron_job/sequence_message_broadcast_hourly/'.$api_key;
    		$this->call_curl_internal_cronjob($link);

    		$link=base_url().'cron_job/subscriber_broadcaster/'.$api_key;
    		$this->call_curl_internal_cronjob($link);
    	}
    	
    }


    public function auto_comment_on_post($api_key='')
    {
        
        $link=base_url().'cron_job/auto_comment_on_post_orginal/'.$api_key;
        $this->call_curl_internal_cronjob($link);

        if($this->basic->is_exist("add_ons",array("project_id"=>29))){
            
            $link=base_url().'cron_job/comment_bulk_tag/'.$api_key;
            $this->call_curl_internal_cronjob($link);

            $link=base_url().'cron_job/bulk_comment_reply/'.$api_key;
            $this->call_curl_internal_cronjob($link);

            $link=base_url().'cron_job/auto_share_on_post/'.$api_key;
            $this->call_curl_internal_cronjob($link);

            $link=base_url().'cron_job/auto_like_on_post/'.$api_key;
            $this->call_curl_internal_cronjob($link);

        }


    }


    // 15 min
    public function background_scanning_update_subscriber_info($api_key="")
    {
    	$link=base_url().'cron_job/background_scanning/'.$api_key;
    	$this->call_curl_internal_cronjob($link);

    	$link=base_url().'cron_job/update_subscriber_profile_info/'.$api_key;
    	$this->call_curl_internal_cronjob($link);

    	if($this->basic->is_exist("add_ons",array("project_id"=>30)))
    	{
			$link=base_url().'cron_job/sequence_message_broadcast_daily/'.$api_key;
			$this->call_curl_internal_cronjob($link);
    	}
    	

    }

    //1 day
    public function membership_alert_delete_junk_data($api_key="")
    {

    	$link=base_url().'cron_job/membership_alert/'.$api_key;
    	$this->call_curl_internal_cronjob($link);

    	$link=base_url().'cron_job/delete_junk_data/'.$api_key;
    	$this->call_curl_internal_cronjob($link);


    }

    // 5 min
    public function publish_post($api_key="")
    {
    	$link=base_url().'cron_job/text_image_link_video_post/'.$api_key;
    	$this->call_curl_internal_cronjob($link);

    	$link=base_url().'cron_job/cta_post/'.$api_key;
    	$this->call_curl_internal_cronjob($link);

    	$link=base_url().'cron_job/carousel_slider_post/'.$api_key;
    	$this->call_curl_internal_cronjob($link);

    	$link=base_url().'cron_job/rss_auto_post/'.$api_key;
    	$this->call_curl_internal_cronjob($link);
    }



    protected function call_curl_internal_cronjob($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
        echo $reply_response=curl_exec($ch); 
    }


    // comment reply enhancers add-on cron job



    
    public function comment_bulk_tag($api_key="")
    {
        $this->api_key_check($api_key);

        $number_of_campaign_to_be_processed = 1; // max number of campaign that can be processed by this cron job

        /****** Get all campaign from database where status=0 means pending ******/
        $where['where'] = array('posting_status'=>"0");
        $join = array('users'=>'tag_machine_bulk_tag.user_id=users.id,left');
        $campaign_info= $this->basic->get_data("tag_machine_bulk_tag",$where,$select=array("tag_machine_bulk_tag.*","users.deleted as user_deleted"),$join,$limit=50, $start=0, $order_by='schedule_time ASC');  
        
        $access_token_database_database = array(); //  [campaign_id][page_auto_id] =>access token
        $facebook_rx_fb_user_info_id_database = array(); // campaign_id => facebook_rx_fb_user_info_id
        $facebook_rx_config_id_database = array(); // facebook_rx_fb_user_info_id => facebook_rx_config_id
        $campaign_id_array=array();  // all selected campaign id array
        $campaign_info_fildered = array(); // valid for process, campign info array

        $valid_campaign_count = 1;
        foreach($campaign_info as $info)
        {
             if($info['user_deleted'] == '1' || $info['user_deleted']=="") continue;

            $campaign_id = $info['id'];
            $time_zone = $info['time_zone'];
            $schedule_time = $info['schedule_time'];     
            $page_id = $info["page_info_table_id"]; 

            if($time_zone) date_default_timezone_set($time_zone);            
            $now_time = date("Y-m-d H:i:s");

            if((strtotime($now_time) < strtotime($schedule_time)) && $time_zone!="") continue; 
            if($valid_campaign_count > $number_of_campaign_to_be_processed) break; 

            $token_info =  $this->basic->get_data('facebook_rx_fb_page_info',array("where"=>array('id'=>$page_id)),array("page_access_token","facebook_rx_fb_user_info_id","id","page_name"));
            foreach ($token_info as $key => $value) 
            {
                $access_token_database_database[$campaign_id][$value["id"]] = $value['page_access_token'];
                $facebook_rx_fb_user_info_id = $value["facebook_rx_fb_user_info_id"];
                $facebook_rx_fb_user_info_id_database[$campaign_id] = $facebook_rx_fb_user_info_id;
            }           
           
            // valid campaign info and campig ids
            $campaign_info_fildered[] = $info;
            $campaign_id_array[] = $info['id']; 
            $valid_campaign_count++;   
        }

        if(count($campaign_id_array)==0) exit();        

        $this->db->where_in("id",$campaign_id_array);
        $this->db->update("tag_machine_bulk_tag",array("posting_status"=>"1"));

        // get config id
        $getdata= $this->basic->get_data("facebook_rx_fb_user_info",array("where_in"=>array("id"=>$facebook_rx_fb_user_info_id_database)),array("id","facebook_rx_config_id"));
        foreach ($getdata as $key => $value) 
        {
            $facebook_rx_config_id_database[$value["id"]] = $value["facebook_rx_config_id"];
        } 

        $this->load->library("fb_rx_login");

        foreach($campaign_info_fildered as $info)
        {
            $campaign_id = $info['id'];                   
            $post_id = $info['post_id'];                   
            $page_id = $info['page_info_table_id']; 
            $post_access_token = isset($access_token_database_database[$campaign_id][$page_id]) ? $access_token_database_database[$campaign_id][$page_id] : "";   
            $uploaded_image_video = $info["uploaded_image_video"]; 
     
            $now_time = date("Y-m-d H:i:s");

            if(!isset($post_access_token) || $post_access_token=="") 
            {
              $this->basic->update_data("tag_machine_bulk_tag",array("id"=>$campaign_id),array("posting_status"=>"2","error_message"=>"Access token not found.","last_updated_at"=>$now_time));
              continue;
            }

            $image=$video=$gif="";
            if($uploaded_image_video!="")
            {
              $ext_exp=explode('.', $uploaded_image_video);
              $ext=array_pop($ext_exp);
              $video_array=array("flv","mp4","wmv");
              if(in_array($ext,$video_array)) 
              $video=FCPATH.'upload/comment_reply_enhancers/'.$uploaded_image_video;  
              else if($ext=='gif') $gif=base_url("upload/comment_reply_enhancers/".$uploaded_image_video);
              else $image=base_url("upload/comment_reply_enhancers/".$uploaded_image_video); 
            }    

            $fb_rx_fb_user_info_id = isset($facebook_rx_fb_user_info_id_database[$campaign_id])?$facebook_rx_fb_user_info_id_database[$campaign_id]:""; // find gb user id for this campaign
            
            if(!isset($fb_rx_fb_user_info_id) || $facebook_rx_fb_user_info_id_database=="") 
            {
              $this->basic->update_data("tag_machine_bulk_tag",array("id"=>$campaign_id),array("posting_status"=>"2","error_message"=>"Facebook accouny not found.","last_updated_at"=>$now_time));
              continue;
            }
            
            $this->fb_rx_login->app_initialize($facebook_rx_config_id_database[$fb_rx_fb_user_info_id]);

            $tag_database=json_decode($info["tag_database"],true);
            $tags="";
            foreach ($tag_database as $key => $value) 
            {
               $tags.="@[".$key."], ";
            }
            $tags=trim($tags);
            $tags=trim($tags,',');
            $tag_content=$info["tag_content"]."

            ".$tags;

            try 
            {
              $response=$this->fb_rx_login->auto_comment($tag_content,$post_id,$post_access_token,$image,$video,$gif);
              $commentid=isset($response['id'])?$response['id']:"";         
              $this->basic->update_data("tag_machine_bulk_tag",array("id"=>$campaign_id),array("posting_status"=>"2","tag_response"=>$commentid,"last_updated_at"=>$now_time));
            } 
            catch (Exception $e) 
            {
              $error_msg = $e->getMessage();
              $this->basic->update_data("tag_machine_bulk_tag",array("id"=>$campaign_id),array("posting_status"=>"2","error_message"=>$error_msg,"last_updated_at"=>$now_time));
            }

            
        }          
    
    }
    

    public function bulk_comment_reply($api_key="")
    {
        $this->api_key_check($api_key);

        $number_of_message_to_be_sent_in_try=$this->config->item("number_of_message_to_be_sent_in_try"); 
        if($number_of_message_to_be_sent_in_try==0) $number_of_message_to_be_sent_in_try="";
        $update_report_after_time=$this->config->item("update_report_after_time"); 
        if($update_report_after_time=="" || $update_report_after_time==0) $update_report_after_time=10;
        $number_of_campaign_to_be_processed = 1; // max number of campaign that can be processed by this cron job

        /****** Get all campaign from database where status=0 means pending ******/
        $where['or_where'] = array('posting_status'=>"0","is_try_again"=>"1");
        $join = array('users'=>'tag_machine_bulk_reply.user_id=users.id,left');
        $campaign_info= $this->basic->get_data("tag_machine_bulk_reply",$where,$select=array("tag_machine_bulk_reply.*","users.deleted as user_deleted"),$join,$limit=50, $start=0, $order_by='schedule_time ASC');  

        $access_token_database_database = array(); //  [campaign_id][page_auto_id] =>access token
        $facebook_rx_fb_user_info_id_database = array(); // campaign_id => facebook_rx_fb_user_info_id
        $facebook_rx_config_id_database = array(); // facebook_rx_fb_user_info_id => facebook_rx_config_id
        $campaign_id_array=array();  // all selected campaign id array
        $campaign_info_fildered = array(); // valid for process, campign info array

        $valid_campaign_count = 1;
        foreach($campaign_info as $info)
        {
            if($info['user_deleted'] == '1' || $info['user_deleted']=="") continue;

            $campaign_id= $info['id'];
            $time_zone= $info['time_zone'];
            $schedule_time= $info['schedule_time'];    
            $page_info_table_id = $info["page_info_table_id"]; 

            if($time_zone) date_default_timezone_set($time_zone);            
            $now_time = date("Y-m-d H:i:s");

            if((strtotime($now_time) < strtotime($schedule_time)) && $time_zone!="") continue; 
            if($valid_campaign_count > $number_of_campaign_to_be_processed) break; 

            $token_info =  $this->basic->get_data('facebook_rx_fb_page_info',array("where"=>array('id'=>$page_info_table_id)),array("page_access_token","facebook_rx_fb_user_info_id","id","page_name"));
            foreach ($token_info as $key => $value) 
            {
                $access_token_database_database[$campaign_id][$value["id"]] = $value['page_access_token'];
                $facebook_rx_fb_user_info_id = $value["facebook_rx_fb_user_info_id"];
                $facebook_rx_fb_user_info_id_database[$campaign_id] = $facebook_rx_fb_user_info_id;
            }  
            // valid campaign info and campig ids
            $campaign_info_fildered[] = $info;
            $campaign_id_array[] = $info['id']; 
            $valid_campaign_count++;      
        }


        if(count($campaign_id_array)==0) exit();        

        $this->db->where_in("id",$campaign_id_array);
        $this->db->update("tag_machine_bulk_reply",array("posting_status"=>"1","is_try_again"=>"0"));

        // get config id
        $getdata= $this->basic->get_data("facebook_rx_fb_user_info",array("where_in"=>array("id"=>$facebook_rx_fb_user_info_id_database)),array("id","facebook_rx_config_id"));
        foreach ($getdata as $key => $value) 
        {
            $facebook_rx_config_id_database[$value["id"]] = $value["facebook_rx_config_id"];
        } 


        $this->load->library("fb_rx_login");
        foreach($campaign_info_fildered as $info)
        {
            $campaign_id= $info['id']; 
            $user_id = $info["user_id"]; 
            $delay_time = $info["delay_time"];
            $catch_error_count=$info["last_try_error_count"];
            $successfully_sent=$info["successfully_sent"];

            $reply_content = $info["reply_content"];                
            $post_id = $info['post_id'];                   
            $page_id = $info['page_info_table_id']; 

            $post_access_token = isset($access_token_database_database[$campaign_id][$page_id]) ? $access_token_database_database[$campaign_id][$page_id] : "";
            if(!isset($post_access_token) || $post_access_token=="") 
            {
              $this->basic->update_data("tag_machine_bulk_reply",array("id"=>$campaign_id),array("posting_status"=>"2","is_try_again"=>"0","error_message"=>"Access token not found.","last_updated_at"=>date("Y-m-d H:i:s")));
              continue;
            }

            $uploaded_image_video = $info["uploaded_image_video"]; 
            $image=$video=$gif="";
            if($uploaded_image_video!="")
            {
              $ext_exp=explode('.', $uploaded_image_video);
              $ext=array_pop($ext_exp);
              $video_array=array("flv","mp4","wmv");
              if(in_array($ext,$video_array)) 
              $video=FCPATH.'upload/comment_reply_enhancers/'.$uploaded_image_video;  
              else if($ext=='gif') $gif=base_url("upload/comment_reply_enhancers/".$uploaded_image_video);
              else $image=base_url("upload/comment_reply_enhancers/".$uploaded_image_video); 
            }   

            $fb_rx_fb_user_info_id = $facebook_rx_fb_user_info_id_database[$campaign_id]; // find gb user id for this campaign
            $this->fb_rx_login->app_initialize($facebook_rx_config_id_database[$fb_rx_fb_user_info_id]);

            $report = json_decode($info["report"],true); // get json lead list from database and decode it
            $i=0;
            $send_report = $report;
        
            $campaign_lead=$this->basic->get_data("tag_machine_bulk_reply_send",array("where"=>array("campaign_id"=>$campaign_id,"processed"=>"0")),'','',$number_of_message_to_be_sent_in_try);
            foreach($campaign_lead as $key => $value) 
            {             
                $send_table_id = $value['id'];
                $comment_id = $value['comment_id'];
                $commenter_fb_id = $value['commenter_fb_id'];
                $commenter_name = $value['commenter_name'];
                $comment_time = $value['comment_time'];
                $commenter_name_array = explode(' ', $commenter_name);
                $commenter_last_name = array_pop($commenter_name_array);
                $commenter_first_name = implode(' ', $commenter_name_array);
                $commenter_tag_name = "@[".$commenter_fb_id."]";
                $error_msg="";
                $reply_id = "";

                //  generating message
                $reply_content_send = $reply_content;
                $reply_content_send = str_replace('#LEAD_USER_FIRST_NAME#',$commenter_first_name,$reply_content_send);
                $reply_content_send = str_replace('#LEAD_USER_LAST_NAME#',$commenter_last_name,$reply_content_send);
                $reply_content_send = str_replace('#TAG_USER#',$commenter_tag_name,$reply_content_send);
                $reply_content_send = spintax_process($reply_content_send);
                               
                try
                {
                    $response = $this->fb_rx_login->auto_comment($reply_content_send,$comment_id,$post_access_token,$image,$video,$gif);
                    if(isset($response['id']))
                    {
                       $reply_id = $response['id']; 
                       $successfully_sent++; 
                    }
                    else 
                    {
                       $catch_error_count++;
                    } 
                    if($delay_time==0)
                    sleep(rand(2,10));
                    else sleep($delay_time); 
                }

                catch(Exception $e) 
                {
                  $error_msg = $e->getMessage();
                  $catch_error_count++;
                }

                // generating new report
                $now_sent_time=date("Y-m-d H:i:s");
                $reply_status="";
                if($reply_id!="") $reply_status=$reply_id;
                else $reply_id=$error_msg;

                $send_report[$comment_id] = array
                ( 
                    "commenter_name"=>$commenter_name,
                    "commenter_fb_id"=>$commenter_fb_id,
                    "comment_id"=> $comment_id,
                    "comment_time"=> $comment_time,
                    "status" => $reply_status,
                    "replied_at" => $now_sent_time
                );

                $i++;  
                // after 10 send update report in database
                if($i%$update_report_after_time==0)
                {
                    $send_report_json= json_encode($send_report);
                    $this->basic->update_data("tag_machine_bulk_reply",array("id"=>$campaign_id),array("report"=>$send_report_json,'successfully_sent'=>$successfully_sent,"error_message"=>$error_msg,"last_try_error_count"=>$catch_error_count,"last_updated_at"=>$now_sent_time));
                }

                // updating a lead, marked as processed
                $this->basic->update_data("tag_machine_bulk_reply_send",array("id"=>$send_table_id),array('processed'=>'1',"sent_time"=>$now_sent_time,"response"=>$reply_status));
            
            } 

            // one campaign completed, now update database finally
            $send_report_json= json_encode($send_report);
            if((count($campaign_lead)<$number_of_message_to_be_sent_in_try) || $number_of_message_to_be_sent_in_try=="")
            {
                $complete_update=array("report"=>$send_report_json,"posting_status"=>'2','successfully_sent'=>$successfully_sent,'last_updated_at'=>date("Y-m-d H:i:s"),"is_try_again"=>"0","last_try_error_count"=>$catch_error_count);
                if(isset($error_msg))
                $complete_update["error_message"]=$error_msg;
                $this->basic->update_data("tag_machine_bulk_reply",array("id"=>$campaign_id),$complete_update);
            }
            else // suppose update_report_after_time=20 but there are 19 message to sent, need to update report in that case
            {
                $this->basic->update_data("tag_machine_bulk_reply",array("id"=>$campaign_id),array("report"=>$send_report_json,'successfully_sent'=>$successfully_sent,"is_try_again"=>"1",'last_updated_at'=>date("Y-m-d H:i:s")));
            }
        }          
    
    }

    public function auto_like_on_post($api_key="")
    {
        $this->api_key_check($api_key);

        $auto_like_per_cron_job=$this->config->item("page_response_auto_like_per_cron_job");
        if($auto_like_per_cron_job=="") $auto_like_per_cron_job=10;

        $update_auto_like_report_every=$this->config->item("page_update_auto_like_report_every");
        if($update_auto_like_report_every=="") $update_auto_like_report_every=5;

        $where['where']=array("auto_like_post"=>"1"); //0 = no, 1 = yes, 2 = processing, 3 = completed
        $str =  "like_done < like_count";
        $this->db->where($str);

        $post_info= $this->basic->get_data("page_response_auto_like_share_report",$where,$select="",$join='',$limit='1', $start='', $order_by='id ASC');

        if(!empty($post_info))
            $this->basic->update_data('page_response_auto_like_share_report',array('id'=>$post_info[0]['id']),array('auto_like_post'=>'2'));

        $config_id_database = array();
        foreach($post_info as $info)
        {
            $user_id = $info['user_id'];
            $post_id = $info['post_id'];
            $post_column_id= $info['id'];
            $like_done= $info['like_done'];
            $post_user_fb_id= $info['page_response_user_info_id'];
            $like_report=json_decode($info["auto_like_report"],true);

            // setting fb confid id for library call
            $fb_rx_fb_user_info_id= $info['page_response_user_info_id'];
            if(!isset($config_id_database[$fb_rx_fb_user_info_id]))
            {
                $config_id_database[$fb_rx_fb_user_info_id] = $this->get_fb_rx_config($fb_rx_fb_user_info_id);
            }
            $this->session->set_userdata("fb_rx_login_database_id", $config_id_database[$fb_rx_fb_user_info_id]);
            $this->load->library("fb_rx_login");
            // setting fb confid id for library call

            /***   Get all pages of the user for publishing like by them ***/

            if(!isset($pages_access_token_user_wise[$post_user_fb_id])){

                $where['where']= array("user_id"=>$user_id);
                $select="id,page_id,page_access_token,page_name";
                $page_info= $this->basic->get_data("facebook_rx_fb_page_info",$where,$select);

                $i=0;
                foreach($page_info as $p){

                    $pages_access_token_user_wise[$p['id']]['page_id'] = $p['page_id'];
                    $pages_access_token_user_wise[$p['id']]['page_access_token'] = $p['page_access_token'];
                    $pages_access_token_user_wise[$p['id']]['page_name'] = $p['page_name'];
                    $i++;
                }
            }

            $i=$like_done;
            $get_auto_like_rows=$this->basic->get_data("page_response_auto_like_report",array("where"=>array("page_response_auto_like_share_report_id"=>$post_column_id,'status'=>'0')),"","",$auto_like_per_cron_job);

            foreach($get_auto_like_rows as $like_page)
            {
                $access_token = isset($pages_access_token_user_wise[$like_page['auto_like_page_table_id']]['page_access_token']) ? $pages_access_token_user_wise[$like_page['auto_like_page_table_id']]['page_access_token'] : 0;
                /**Like on the post***/
                try
                {
                    $like_response  =  $this->fb_rx_login->auto_like($post_id,$access_token);
                    $like_report[$i]['page_name']=$pages_access_token_user_wise[$like_page['auto_like_page_table_id']]['page_name'];
                    $like_report[$i]['page_id']=$pages_access_token_user_wise[$like_page['auto_like_page_table_id']]['page_id'];
                    $like_report[$i]['status']="Success";
                }

                catch(Exception $e) 
                {
                    $like_report[$i]['page_name']=$pages_access_token_user_wise[$like_page['auto_like_page_table_id']]['page_name'];
                    $like_report[$i]['page_id']=$pages_access_token_user_wise[$like_page['auto_like_page_table_id']]['page_id'];
                    $like_report[$i]['status']= $e->getMessage();                    
                }
                $i++;
                $like_done++;
                $this->basic->delete_data("page_response_auto_like_report",array("id"=>$like_page['id']));
                sleep(rand(1,5));

                 /****       Update databse that auto like is done        *****/ 
                if($i%$update_auto_like_report_every==0 || $like_done>=$info["like_count"])
                {
                    $like_report_json= json_encode($like_report);

                    if($like_done>=$info["like_count"])
                    $updateData=array("auto_like_post"=>"3",'auto_like_report'=>$like_report_json,"like_done"=>$like_done,"like_last_tried"=>date("Y-m-d H:i:s"));
                    else
                    $updateData=array("auto_like_post"=>"2",'auto_like_report'=>$like_report_json,"like_done"=>$like_done,"like_last_tried"=>date("Y-m-d H:i:s"));

                    $this->basic->update_data("page_response_auto_like_share_report",array("id"=>$post_column_id),$updateData);
                }
            }


        }

    }

    public function auto_share_on_post($api_key="")
    {
        $this->api_key_check($api_key);

        $auto_like_per_cron_job=$this->config->item("page_response_auto_like_per_cron_job");
        if($auto_like_per_cron_job=="") $auto_like_per_cron_job=10;

        $update_auto_share_report_every=$this->config->item("page_update_auto_share_report_every");
        if($update_auto_share_report_every=="") $update_auto_share_report_every=5;

        $where['where']=array("auto_share_post"=>"1"); //0 = no, 1 = yes, 2 = processing, 3 = completed
        $str =  "share_done < share_count";
        $this->db->where($str);

        $post_info= $this->basic->get_data("page_response_auto_like_share_report",$where,$select="",$join='',$limit='1', $start='', $order_by='id ASC');

        if(!empty($post_info))
            $this->basic->update_data('page_response_auto_like_share_report',array('id'=>$post_info[0]['id']),array('auto_share_post'=>'2'));

        $config_id_database = array();
        foreach($post_info as $info)
        {
            $user_id = $info['user_id'];
            $post_id = $info['post_id'];
            $post_column_id= $info['id'];
            $share_done= $info['share_done'];
            $post_user_fb_id= $info['page_response_user_info_id'];
            $main_page_info_table_id= $info['page_info_table_id'];
            $share_report=json_decode($info["auto_share_report"],true);

            // setting fb confid id for library call
            $fb_rx_fb_user_info_id= $info['page_response_user_info_id'];
            if(!isset($config_id_database[$fb_rx_fb_user_info_id]))
            {
                $config_id_database[$fb_rx_fb_user_info_id] = $this->get_fb_rx_config($fb_rx_fb_user_info_id);
            }
            $this->session->set_userdata("fb_rx_login_database_id", $config_id_database[$fb_rx_fb_user_info_id]);
            $this->load->library("fb_rx_login");
            // setting fb confid id for library call

            /***   Get all pages of the user for publishing like by them ***/

            if(!isset($pages_access_token_user_wise[$post_user_fb_id])){

                $where['where']= array("user_id"=>$user_id);
                $select="id,page_id,page_access_token,page_name";
                $page_info= $this->basic->get_data("facebook_rx_fb_page_info",$where,$select);

                $i=0;
                foreach($page_info as $p){

                    $pages_access_token_user_wise[$p['id']]['page_id'] = $p['page_id'];
                    $pages_access_token_user_wise[$p['id']]['page_access_token'] = $p['page_access_token'];
                    $pages_access_token_user_wise[$p['id']]['page_name'] = $p['page_name'];
                    $i++;
                }
            }

            $i=$share_done;
            $get_auto_like_rows=$this->basic->get_data("page_response_auto_share_report",array("where"=>array("page_response_auto_like_share_report_id"=>$post_column_id,'status'=>'0')),"","",$auto_like_per_cron_job);

            $get_accesstoken=$this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$main_page_info_table_id)));
            $main_access_token=isset($get_accesstoken[0]["page_access_token"]) ? $get_accesstoken[0]["page_access_token"] : "";
            $post_url = array();
            $post_url['permalink_url'] = '';
            if($main_access_token != "") $post_url= $this->fb_rx_login->get_post_permalink($post_id,$main_access_token);


            foreach($get_auto_like_rows as $share_page)
            {
                $access_token = isset($pages_access_token_user_wise[$share_page['auto_share_page_table_id']]['page_access_token']) ? $pages_access_token_user_wise[$share_page['auto_share_page_table_id']]['page_access_token'] : 0;
                $sharing_page_id = isset($pages_access_token_user_wise[$share_page['auto_share_page_table_id']]['page_id']) ? $pages_access_token_user_wise[$share_page['auto_share_page_table_id']]['page_id'] : 0;


                try
                {
                    $share_response= $this->fb_rx_login->feed_post($message="",$post_url['permalink_url'],"","","","",$access_token,$sharing_page_id); 

                    $share_report[$i]['page_name']=$pages_access_token_user_wise[$share_page['auto_share_page_table_id']]['page_name'];
                    $share_report[$i]['page_id']=$pages_access_token_user_wise[$share_page['auto_share_page_table_id']]['page_id'];
                    $share_report[$i]['status']="Success";
                }

                catch(Exception $e) 
                {
                    $share_report[$i]['page_name']=$pages_access_token_user_wise[$share_page['auto_share_page_table_id']]['page_name'];
                    $share_report[$i]['page_id']=$pages_access_token_user_wise[$share_page['auto_share_page_table_id']]['page_id'];
                    $share_report[$i]['status']= $e->getMessage();                    
                }
                $i++;
                $share_done++;
                 $this->basic->delete_data("page_response_auto_share_report",array("id"=>$share_page['id']));

                $delay = $info['delay_time'];
                if($delay == 0)
                    sleep(rand(1,10));
                else
                    sleep($delay);

                 /****       Update databse that auto like is done        *****/ 
                if($i%$update_auto_share_report_every==0 || $share_done>=$info["share_count"])
                {
                    $share_report_json= json_encode($share_report);

                    if($share_done>=$info["share_count"])
                    $updateData=array("auto_share_post"=>"3",'auto_share_report'=>$share_report_json,"share_done"=>$share_done,"share_last_tried"=>date("Y-m-d H:i:s"));
                    else
                    $updateData=array("auto_share_post"=>"2",'auto_share_report'=>$share_report_json,"share_done"=>$share_done,"share_last_tried"=>date("Y-m-d H:i:s"));

                    $this->basic->update_data("page_response_auto_like_share_report",array("id"=>$post_column_id),$updateData);
                }
            }


        }

    }
    
}