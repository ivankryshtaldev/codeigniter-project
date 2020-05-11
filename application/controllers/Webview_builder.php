<?php
require_once("application/controllers/Home.php"); // loading home controller
class Webview_builder extends Home 
{
	public function __construct()
    {
        parent::__construct();              
    }

   
	public function get_birthdate()
	{
		header('X-Frame-Options: ALLOW-FROM https://www.messenger.com/');
		header('X-Frame-Options: ALLOW-FROM https://www.facebook.com/');

		$subscriber_id=$this->input->get('subscriber_id');
		$data['subscriber_id'] =$subscriber_id;
		// $data['form'] = $form[0] ?? ['form_title' => "Your Birthdate"];
		$data['form']['form_title'] = $this->lang->line("Your Birthdate");
        $data['body'] = 'webview_builder/birthday_view';
        $data['page_title'] = '';

        // Get Facebook app ID 
        $fb_app_id_info=$this->basic->get_data('facebook_rx_config',$where=array('where'=>array('status'=>'1')));
        $data['fb_app_id']=isset($fb_app_id_info[0]['api_id']) ? $fb_app_id_info[0]['api_id']: "";


        $this->load->view('webview_builder/bare-theme', $data);  
	}

    public function birthdate_submit(){

    	$form_data=$this->input->post();
    	$subscriber_id=$form_data['subscriber_id'];
    	$birthdate=$form_data['birthdate'];
  
    	if($subscriber_id==""){
    		$response['error']='1';
    		$response['error_message']=$this->lang->line("Subscriber information not found.");
       		echo json_encode($response);
    		exit; 
    	}

    	// Get subscriber information & page information 

       $where = array("where"=> array('messenger_bot_subscriber.subscribe_id'=>$subscriber_id));
       $select = array("facebook_rx_fb_page_info.facebook_rx_fb_user_info_id","facebook_rx_fb_page_info.page_id","page_access_token","first_name","last_name","messenger_bot_subscriber.status","facebook_rx_fb_page_info.id","messenger_bot_subscriber.user_id");
       $join  = array("facebook_rx_fb_page_info"=>"messenger_bot_subscriber.page_table_id=facebook_rx_fb_page_info.id,left");
       $table="messenger_bot_subscriber";
       $subscriber_info = $this->basic->get_data($table,$where,$select,$join);

       $page_access_token= isset($subscriber_info[0]['page_access_token']) ? $subscriber_info[0]['page_access_token'] : ""; 
       $page_id= isset($subscriber_info[0]['page_id']) ? $subscriber_info[0]['page_id'] : ""; 
       $page_table_id=isset($subscriber_info[0]['id']) ? $subscriber_info[0]['id'] : "";
       $user_id= isset($subscriber_info[0]['user_id']) ? $subscriber_info[0]['user_id'] : "";

       if(empty($subscriber_info)){
       		$response['error']='1';
    		$response['error_message']=$this->lang->line("Subscriber information not found.");
       		echo json_encode($response);
    		exit; 
       }

       //Update Subscribers BirthDate : 
       $update_data=array("birthdate"=>$birthdate,"birthdate_entry_time"=>date("Y-m-d H:i:s"));
       $update_where=array("subscribe_id"=>$subscriber_id,"page_table_id"=>$page_table_id);
       $this->basic->update_data("messenger_bot_subscriber",$update_where,$update_data);


	    // Send message to subscriber 

	  	$where=array('where'=>array('keyword_type'=>'birthday-quick-reply','page_id'=>$page_table_id));
		$reply_template_info=$this->basic->get_data('messenger_bot',$where);

		if(!empty($reply_template_info)) {

			$message_str = $reply_template_info[0]['message'];
		    $message_array = json_decode($message_str,true);

		    foreach($message_array as $msg)
		    {
		        $template_type_file_track=$msg['message']['template_type'];
		        unset($msg['message']['template_type']);

		        // typing on and typing on delay [alamin]
		        $enable_typing_on = isset($msg['message']['typing_on_settings']) ? $msg['message']['typing_on_settings'] : "";
		        $enable_typing_on = ($enable_typing_on=='on')  ? 1 : 0;
		        unset($msg['message']['typing_on_settings']);
		        $typing_on_delay_time = isset($msg['message']['delay_in_reply']) ? $msg['message']['delay_in_reply'] : "";
		        if($typing_on_delay_time=="") $typing_on_delay_time = 0;
		        unset($msg['message']['delay_in_reply']);
		        
		        /** Spintax **/
		        if(isset($msg['message']['text']))
		            $msg['message']['text']=spintax_process($msg['message']['text']);               
		        
		        $msg['messaging_type'] = "RESPONSE";
		        $reply = json_encode($msg);     

		        $replace_search=array('{"id":"replace_id"}','#SUBSCRIBER_ID_REPLACE#');
		        $replace_with=array('{"id":"'.$subscriber_id.'"}',$subscriber_id);
		        $reply=str_replace($replace_search, $replace_with, $reply);

		        if(isset($subscriber_info[0]['first_name']))
		            $reply=str_replace('#LEAD_USER_FIRST_NAME#', $subscriber_info[0]['first_name'], $reply);
		        if(isset($subscriber_info[0]['last_name']))
		            $reply=str_replace('#LEAD_USER_LAST_NAME#', $subscriber_info[0]['last_name'], $reply);
		        $access_token = $page_access_token;
		        if(isset($subscriber_info[0]['status']) && $subscriber_info[0]['status']=="1"){
		        
		            // typing on and typing on delay [alamin]
		            if($enable_typing_on) $this->sender_action($subscriber_id,"typing_on",$access_token);                                
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
		                    $bot_settings_id= $reply_template_info[0]['id'];
		                    $reply_error_message= $reply_response['error']['message'];
		                    $error_time= date("Y-m-d H:i:s");
		                    $error_insert_data=array("page_id"=>$page_table_id,"fb_page_id"=>$page_id,"user_id"=>$user_id,
		                                        "error_message"=>$reply_error_message,"bot_settings_id"=>$bot_settings_id,
		                                        "error_time"=>$error_time);
		                    $this->basic->insert_data('messenger_bot_reply_error_log',$error_insert_data);
		                    
		                }
		        }
		    }
		}

		if($this->basic->is_exist("add_ons",array("project_id"=>31)))
		$this->thirdparty_webhook_trigger($page_id,$subscriber_id,"trigger_birthdate");

		$response['error']='0';
		echo json_encode($response);


    }

}