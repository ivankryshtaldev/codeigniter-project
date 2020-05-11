<?php  
include("Facebook/autoload.php");

class Fb_rx_login
{				
	public $database_id=""; 
	public $app_id="";
	public $app_secret="";		
	public $user_access_token="";
	public $fb;


	function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->database();
		$this->CI->load->helper('my_helper');
		$this->CI->load->library('session');
		$this->CI->load->model('basic');
		$this->database_id=$this->CI->session->userdata("fb_rx_login_database_id");
		if($this->database_id=="" || $this->database_id==0) exit();

		$facebook_config=$this->CI->basic->get_data("facebook_rx_config",array("where"=>array("id"=>$this->database_id)));
		if(isset($facebook_config[0]))
		{			
			$this->app_id=$facebook_config[0]["api_id"];
			$this->app_secret=$facebook_config[0]["api_secret"];
			$this->user_access_token=$facebook_config[0]["user_access_token"];
			if (session_status() == PHP_SESSION_NONE) {
			    session_start();
			}
			$this->fb = new Facebook\Facebook([
				'app_id' => $this->app_id, 
				'app_secret' => $this->app_secret,
				'default_graph_version' => 'v2.8',
				'fileUpload'	=>TRUE
				]);
		}				
	}


	function login_for_user_access_token($redirect_url="")
	{	
		$helper = $this->fb->getRedirectLoginHelper();
		$permissions = ['email','manage_pages','read_insights','publish_actions','publish_pages','pages_show_list','business_management','user_managed_groups','read_page_mailboxes','public_profile'];
		$loginUrl = $helper->getLoginUrl($redirect_url, $permissions);	
		return '<a href="' . htmlspecialchars($loginUrl) . '">Login with Facebook!</a>';
	}


	public function login_callback()
	{
		$helper = $this->fb->getRedirectLoginHelper();
		try {
			$accessToken = $helper->getAccessToken();
			$response = $this->fb->get('/me?fields=id,name,email', $accessToken);

			$user = $response->getGraphUser()->asArray();
		} catch(Facebook\Exceptions\FacebookResponseException $e) {

			$user['status']="0";
			$user['message']= $e->getMessage();
			return $user;

		} catch(Facebook\Exceptions\FacebookSDKException $e) {
			$user['status']="0";
			$user['message']= $e->getMessage();
			return $user;
		}

		$access_token	= (string) $accessToken;
		$access_token = $this->create_long_lived_access_token($access_token);

		$user["access_token_set"]=$access_token;

		return $user;
	}



	public function app_id_secret_check()
	{
		if($this->app_id == '' || $this->app_secret == '') return 'not_configured';
	}

	function access_token_validity_check(){

		$access_token=$this->user_access_token;
		$client_id=$this->app_id;
		$result=array();
		$url="https://graph.facebook.com/v2.8/oauth/access_token_info?client_id={$client_id}&access_token={$access_token}";

		$headers = array("Content-type: application/json");

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
		curl_setopt($ch, CURLOPT_COOKIEJAR,'cookie.txt');  
		curl_setopt($ch, CURLOPT_COOKIEFILE,'cookie.txt');  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3"); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

		$st=curl_exec($ch); 

		$result=json_decode($st,TRUE);

		if(!isset($result["error"])) return 1;
		else return 0;

	}


	public function create_long_lived_access_token($short_lived_user_token){

		$app_id=$this->app_id;
		$app_secret=$this->app_secret;
		$short_token=$short_lived_user_token;

		$url="https://graph.facebook.com/v2.6/oauth/access_token?grant_type=fb_exchange_token&client_id={$app_id}&client_secret={$app_secret}&fb_exchange_token={$short_token}";

		$headers = array("Content-type: application/json");

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
		curl_setopt($ch, CURLOPT_COOKIEJAR,'cookie.txt');  
		curl_setopt($ch, CURLOPT_COOKIEFILE,'cookie.txt');  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3"); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

		$st=curl_exec($ch); 
		$result=json_decode($st,TRUE);

		$access_token=isset($result["access_token"]) ? $result["access_token"] : "";

		return $access_token;

	}



	public function facebook_api_call($url){

		$headers = array("Content-type: application/json");

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
		curl_setopt($ch, CURLOPT_COOKIEJAR,'cookie.txt');  
		curl_setopt($ch, CURLOPT_COOKIEFILE,'cookie.txt');  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3"); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

		$st=curl_exec($ch); 

		return  $results=json_decode($st,TRUE);	 
	}

	public function get_page_list($access_token="")
	{
		$request = $this->fb->get('/me/accounts?fields=cover,emails,picture,id,name,url,username,access_token', $access_token);	
		$response = $request->getGraphList()->asArray();
		return $response;
	}


	public function get_page_insight_info($access_token,$metrics,$page_id){
		
		$from = date('Y-m-d', strtotime(date('Y-m-d').' -28 day'));
        $to   = date('Y-m-d', strtotime(date("Y-m-d").'-1 day'));
		$request = $this->fb->get("/{$page_id}/{$metrics}?&since=".$from."&until=".$to,$access_token);
		$response = $request->getGraphList()->asArray();
		return $response;
		 
	}


	public function get_group_list($access_token="")
	{
		$request = $this->fb->get('/me/groups?fields=cover,emails,picture,id,name,url,username,access_token,accounts,perms,category', $access_token);	
		$response_group = $request->getGraphList()->asArray();		
		return $response_group;
	}


	public function send_user_roll_access($app_id,$user_id, $user_access_token)
	{
		$url="https://graph.facebook.com/{$app_id}/roles?user={$user_id}&role=testers&access_token={$user_access_token}&method=post";
		$resuls = $this->run_curl_for_fb($url);
		return json_decode($resuls,TRUE);
	}


	public function run_curl_for_fb($url)
	{
		$headers = array("Content-type: application/json"); 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
		curl_setopt($ch, CURLOPT_COOKIEJAR,'cookie.txt');  
		curl_setopt($ch, CURLOPT_COOKIEFILE,'cookie.txt');  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3"); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
		$results=curl_exec($ch); 	   
		return  $results;   
	}
	

	function get_meta_tag_fb($url)
	{  
		$html=$this->run_curl_for_fb($url);	  
		$doc = new DOMDocument();
		@$doc->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">'.$html);
		$nodes = $doc->getElementsByTagName('title');	  
		if(isset($nodes->item(0)->nodeValue))
			$title = $nodes->item(0)->nodeValue;
		else  $title="";

		$response=array('title'=>'','image'=>'','description'=>'','author'=>'');


		$response['title']=$title;
		$org_desciption="";

		$metas = $doc->getElementsByTagName('meta');

		for ($i = 0; $i < $metas->length; $i++)
		{
			$meta = $metas->item($i);	   
			if($meta->getAttribute('property')=='og:title')
				$response['title'] = $meta->getAttribute('content');		    
			if($meta->getAttribute('property')=='og:image')
				$response['image'] = $meta->getAttribute('content');		    
			if($meta->getAttribute('property')=='og:description')
				$response['description'] = $meta->getAttribute('content');		   
			if($meta->getAttribute('name')=='author')
				$response['author'] = $meta->getAttribute('content');		    
			if($meta->getAttribute('name')=='description')
				$org_desciption =  $meta->getAttribute('content');   
		}

		if(!isset($response['description']))
			$org_desciption =  $org_desciption;

		return $response;   

	}




	/*	$page_id = page id / profile id / Group id 
	$scheduled_publish_time = TimeStamp Format using strtotime() function and set the date_default_timezone_set(),
	$post_access_token = user access token for profile and group/ page access token for page post. 
	$image_link can't be use without $link	
	*/

	function feed_post($message="",$link="",$image_link="",$scheduled_publish_time="",$link_overwrite_title="",$link_overwrite_description="",$post_access_token="",$page_id="")
	{

		if($message!="")
			$params['message'] = $message;


		if($link!=""){

			$params['link'] = $link;

			if($image_link!="")
				$params['thumbnail'] = $this->fb->fileToUpload($image_link);

			if($link_overwrite_description!="")
				$params['description']= $link_overwrite_description;

			if($link_overwrite_title!="")
				$params['name']= $link_overwrite_title;
		}
		if($scheduled_publish_time!=""){
			$params['scheduled_publish_time'] = $scheduled_publish_time;
			$params['published'] = false;
		}

		$response = $this->fb->post("{$page_id}/feed",$params,$post_access_token);

		return $response->getGraphObject()->asArray();					
	}





	public function cta_post($message="", $link="",$description="",$name="",$cta_type="",$cta_value="",$thumbnail="",$scheduled_publish_time="",$post_access_token,$page_id)
	{

		if($message!="")
			$params['message'] = $message;

		if($link!="")
			$params['link'] = $link;

		if($description!="")
			$params['description'] = $description;

		if($thumbnail!="")
			$params['thumbnail'] =$this->fb->fileToUpload($thumbnail) ;

		if($name!="")
			$params['name']= $name;

		$call_to_action_array=array(
			"type"=>$cta_type,
			"value"=>$cta_value
			);

		$params['call_to_action'] = $call_to_action_array;

		if($scheduled_publish_time!=""){
			$params['scheduled_publish_time'] = $scheduled_publish_time;
			$params['published'] = false;
		}

		$response = $this->fb->post("{$page_id}/feed",$params,$post_access_token);	

		return $response->getGraphObject()->asArray();

	}


	public function photo_post($message="",$image='',$scheduled_publish_time="",$post_access_token,$page_id)
	{
		if($message!="")
			$params['message'] = $message;

		if($image!="")
			$params['source']= $this->fb->fileToUpload($image);


		if($scheduled_publish_time!=""){
			$params['scheduled_publish_time'] = $scheduled_publish_time;
			$params['published'] = false;
		}

		$response = $this->fb->post("{$page_id}/photos",$params,$post_access_token);

		return $response->getGraphObject()->asArray();
	}


	/***** pass one between $file_url or $file_source. 
	$file_url	 =if online video like youtube  
	$file_source = Local video 

	call_to_action[type] must be one of the following values: SHOP_NOW, BOOK_TRAVEL, LEARN_MORE, SIGN_UP, DOWNLOAD, WATCH_MORE, NO_BUTTON, GET_MOBILE_APP, INSTALL_MOBILE_APP, USE_MOBILE_APP, INSTALL_APP, USE_APP, PLAY_GAME, WATCH_VIDEO, OPEN_LINK, LISTEN_MUSIC, MOBILE_DOWNLOAD, GET_OFFER, GET_OFFER_VIEW, BUY_NOW, BUY_TICKETS, UPDATE_APP, BET_NOW, GET_DIRECTIONS, ADD_TO_CART, ORDER_NOW, SELL_NOW' 

	Limitations

	If you upload a video with a multi-part HTTP request or by providing a URL to a video, the video cannot exceed 1GB in size and 20 minutes in duration. (When using file_url The video should be downloaded within 5 minutes).

	https://developers.facebook.com/docs/graph-api/reference/v2.3/page/videos


	***/

	public function post_video($description="",$title="",$file_url="", $file_source="",$thumbnail="",$scheduled_publish_time="",$post_access_token,$page_id )
	{
		if($description!="")
			$params['description']=$description;

		if($description!="")
			$params['title']=$title;

		if($file_url!="")
			$params['file_url']=$file_url;

		if($file_source!="")
			$params['source']=$this->fb->fileToUpload($file_source);

		if($thumbnail!="")
			$params['thumb']=$this->fb->fileToUpload($thumbnail);

		if($scheduled_publish_time!=""){
			$params['scheduled_publish_time'] = $scheduled_publish_time;
			$params['published'] = false;
		}
		
		$params['is_crossposting_eligible']=1;

		$response = $this->fb->post("{$page_id}/videos",$params,$post_access_token);
		return $response->getGraphObject()->asArray();	
	}

	public function allow_business_crossposting_permission($post_video_id='',$post_access_token='')
	{
		$params['allow_bm_crossposting']=1;
		$response = $fb->post($post_video_id, $params, $post_access_token);

		return $response->getGraphObject()->asArray();
	}

	public function cross_post_video($crossposted_video_id='', $page_id='', $post_access_token='')
	{

		$params = array("crossposted_video_id" => $crossposted_video_id,"is_crossposting_eligible" => TRUE,"allow_bm_crossposting" => TRUE);
		
		$response = $fb->post("{$page_id}/videos",$params,$post_access_token);
		
		return $response->getGraphObject()->asArray();
	}


	public function get_youtube_video_url($youtube_video_id)
	{
		$vformat = "video/mp4"; 
		parse_str(file_get_contents("http://youtube.com/get_video_info?video_id={$youtube_video_id}"),$info);
		$streams = $info['url_encoded_fmt_stream_map']; 
		$streams = explode(',',$streams);

		foreach($streams as $stream){
			parse_str($stream,$data); 
			if(stripos($data['type'],$vformat) !== false){ //We've found the right stream with the correct format
			$video_file_url = $data['url'];
			}
		}
	return $video_file_url;				
	}



	public function get_post_permalink($post_id,$post_access_token)
	{
		$params['fields']="permalink_url";
		$response = $this->fb->get("{$post_id}?fields=permalink_url",$post_access_token);
		return $response->getGraphObject()->asArray();

	}

	/*********  

	Auto like $object_id is the post's id, Only for live video id is not worked, we need to get permalink and get the id from it and pass it. 

	**********/

	public function auto_like($object_id,$post_access_token)
	{
		$response = $this->fb->post("{$object_id}/likes",array(),$post_access_token);
		return $response->getGraphObject()->asArray();	
	}


	public function auto_comment($message,$object_id,$post_access_token)
	{
		$params['message']=$message;
		$response = $this->fb->post("{$object_id}/comments",$params,$post_access_token);
		return $response->getGraphObject()->asArray();	
	}




	public function live_video_schedule($description="",$planned_time,$image="",$post_access_token,$page_id)
	{

		if($description!="")
			$params['description'] = $description;

		if($planned_time!="")
			$params['planned_start_time'] = $planned_time;

		if($image!="")
			$params['schedule_custom_profile_image'] = $this->fb->fileToUpload($image);

		$params['status'] = "SCHEDULED_UNPUBLISHED";

		$response = $this->fb->post("{$page_id}/live_videos",$params,$post_access_token);
		$response= $response->getGraphObject()->asArray();

		return $response;
	}


	/***Param status must be one of {UNPUBLISHED, LIVE_NOW, SCHEDULED_UNPUBLISHED, SCHEDULED_LIVE, SCHEDULED_CANCELED}***/

	public function update_live_video_schedule($live_video_id,$description="",$planned_time="",$image="",$is_live=0,$post_access_token,$page_id)
	{

		if($description!="")
			$params['description'] = $description;

		if($planned_time!="")
			$params['planned_start_time'] = $planned_time;

		if($image!="")
			$params['schedule_custom_profile_image'] = $this->fb->fileToUpload($image);

		if($is_live==1)
			$params['status'] = "LIVE_NOW";

		$response = $this->fb->post("{$live_video_id}",$params,$post_access_token);

		$response= $response->getGraphObject()->asArray();

		return $response;
	}



	public function post_image_video($description="",$image_urls=array(),$duration,$transition_time,$scheduled_publish_time="",$post_access_token,$page_id)
	{

		$slideshow_spec_array=array(
		"images_urls"=>$image_urls,
		"duration_ms"  => $duration,
		"transition_ms"  => $transition_time
		);

		if($description!="")
			$params['description'] = $description;


		if($scheduled_publish_time!=""){
			$params['scheduled_publish_time'] = $scheduled_publish_time;
			$params['published'] = false;
		}

		$params['slideshow_spec'] = $slideshow_spec_array;
		$response = $this->fb->post("{$page_id}/videos",$params,$post_access_token);
		return $response->getGraphObject()->asArray();
	}



	public function get_all_conversation_page($post_access_token,$page_id)
	{

		$message_info=array();
		$i=0;

		//	$url = "https://graph.facebook.com/{$page_id}/conversations?access_token={$post_access_token}&limit=200&fields=participants,message_count,unread_count,senders,is_subscribed,snippet,id";	

		$url = "https://graph.facebook.com/{$page_id}/conversations?access_token={$post_access_token}&limit=200&fields=participants,message_count,unread_count,is_subscribed&limit=200";	

		do
		{
			$results = $this->run_curl_for_fb($url);
			$results=json_decode($results,true);

			foreach($results['data'] as $thread_info){
				foreach($thread_info['participants']['data'] as $participant_info){
					$user_id= $participant_info['id'];
					if($user_id!=$page_id){
						$message_info[$i]['name']=$participant_info['name'];
						$message_info[$i]['id']=$participant_info['id'];
					}
				}
				$message_info[$i]['is_subscribed'] = $thread_info['is_subscribed'];
				$message_info[$i]['thead_id'] = $thread_info['id'];
				$message_info[$i]['message_count'] = isset($thread_info['message_count']) ? $thread_info['message_count']:0;
				$message_info[$i]['unread_count'] = isset($thread_info['unread_count']) ? $thread_info['unread_count']:0;

				$i++;
			}

			$url= isset($results['paging']['next']) ? $results['paging']['next']: "" ;

		}
		while($url!='');
		return $message_info;
	}


	public function send_message_to_thread($thread_id,$message,$post_access_token)
	{

		$message=urlencode($message);
		$url= "https://graph.facebook.com/{$thread_id}/messages?access_token={$post_access_token}&message={$message}&method=post";
		$results= $this->run_curl_for_fb($url);
		return json_decode($results,TRUE);

	}



	function carousel_post($message="",$link="",$child_attachments="",$scheduled_publish_time="",$post_access_token="",$page_id="")
	{

		if($message!="")
			$params['message'] = $message;

		if($link!=""){
			$params['link'] = $link;
		}

		$params['child_attachments'] = $child_attachments;

		if($scheduled_publish_time!=""){
			$params['scheduled_publish_time'] = $scheduled_publish_time;
			$params['published'] = false;
		}

		$response = $this->fb->post("{$page_id}/feed",$params,$post_access_token);

		return $response->getGraphObject()->asArray();
	}


	 public function get_all_comment_of_post($post_ids,$post_access_token)
	 {
 
	   $url="https://graph.facebook.com/?ids={$post_ids}&fields=comments&access_token={$post_access_token}&limit=200";
	   $results= $this->run_curl_for_fb($url);
	   return json_decode($results,TRUE);	  
	 }
	 
	 public function send_private_reply($message,$comment_id,$post_access_token)
	 {	  
	   $message= urlencode($message);
	   $url="https://graph.facebook.com/v2.6/{$comment_id}/private_replies?access_token={$post_access_token}&method=post&message={$message}"; 
	   $results= $this->run_curl_for_fb($url);
	   return json_decode($results,TRUE);	  
	 }


	public function video_insight($video_id,$post_access_token){
		$request = $this->fb->get("/{$video_id}/video_insights",$post_access_token);
		$response = $request->getGraphList()->asArray();
		return $response;	 
	}
	
	
	


}


