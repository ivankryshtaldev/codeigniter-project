<?php

require_once("Home.php"); // including home controller

/**
* class config
* @category controller
*/
class Dashboard extends Home
{
    public $user_id;
    /**
    * load constructor method
    * @access public
    * @return void
    */
    public function __construct()
    {
        parent::__construct();
        if ($this->session->userdata('logged_in') != 1)
        redirect('home/login_page', 'location');
        $this->user_id=$this->session->userdata('user_id');
    
        set_time_limit(0);
        $this->important_feature();
        $this->member_validity();     
    }

    /**
    * load index method. redirect to config
    * @access public
    * @return void
    */
    
    public function index($default_value='0')
    {
        if($this->session->userdata('user_type') != 'Admin') $default_value='0';
        if($default_value == '0')
        {
            $user_id=$this->user_id;
            $data['other_dashboard'] = '0';
        }
        else
        {
            $user_id = $default_value;
            if($default_value == 'system')
                $data['system_dashboard'] = 'yes';
            else
            {
                $user_info = $this->basic->get_data('users',array('where'=>array('id'=>$user_id)));
                $data['user_name'] = isset($user_info[0]['name']) ? $user_info[0]['name'] : '';
                $data['user_email'] = isset($user_info[0]['email']) ? $user_info[0]['email'] : '';
                $data['system_dashboard'] = 'no';
            }

            $data['other_dashboard'] = '1';
        }

        if($this->is_demo === '1' && $data['other_dashboard'] === '1' && isset($data['system_dashboard']) && $data['system_dashboard'] === 'no')
        {
        	echo "<h2 style='text-align:center;color:red;border:1px solid red; padding: 10px'>This feature is disabled in this demo.</h2>"; 
            exit();
        }


        $current_year = date("Y");
        $lastyear = $current_year-1;
        $current_month = date("Y-m");
        $current_date = date("Y-m-d");
        $data['month_number'] = date('m');

        // first item section
        $total_subscribers = 0;
        $where = array(
            'where' => array(
                // 'user_id' => $user_id,
                'date_format(subscribed_at,"%Y-%m")' => $current_month,
                'permission' => '1'
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $select = array('count(id) as subscribers');
        $subscriber_info = $this->basic->get_data('messenger_bot_subscriber',$where,$select);
        $subscribed = isset($subscriber_info[0]['subscribers']) ? $subscriber_info[0]['subscribers'] : 0;
        $data['subscribed'] = $subscribed;
        $where = array(
            'where' => array(
                // 'user_id' => $user_id,
                'date_format(unsubscribed_at,"%Y-%m")' => $current_month,
                'permission' => '0'
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $select = array('count(id) as unsubscribers');
        $unsubscriber_info = $this->basic->get_data('messenger_bot_subscriber',$where,$select);
        $unsubscribed = isset($unsubscriber_info[0]['unsubscribers']) ? $unsubscriber_info[0]['unsubscribers'] : 0;
        $data['unsubscribed'] = $unsubscribed;
        $total_subscribers = $subscribed + $unsubscribed;
        $data['total_subscribers'] = $total_subscribers;

        $where = array(
            'where' => array(
                // 'user_id' => $user_id,
                'date_format(completed_at,"%Y-%m")' => $current_month
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $select = array('sum(successfully_sent) as total_message_sent');
        $conversation_message_sent_info = $this->basic->get_data("facebook_ex_conversation_campaign",$where,$select);
        $total_conversion_message_sent = isset($conversation_message_sent_info[0]['total_message_sent']) ? $conversation_message_sent_info[0]['total_message_sent'] : 0;

        $where = array(
            'where' => array(
                // 'user_id' => $user_id,
                'date_format(completed_at,"%Y-%m")' => $current_month
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        if($this->is_broadcaster_exist)
        {
            $select = array('sum(successfully_sent) as total_message_sent');
            $broadcast_message_sent_info = $this->basic->get_data("messenger_bot_broadcast_serial",$where,$select);
        }
        $total_broadcast_message_sent = isset($broadcast_message_sent_info[0]['total_message_sent']) ? $broadcast_message_sent_info[0]['total_message_sent'] : 0;
        $data['total_message_sent'] = $total_conversion_message_sent + $total_broadcast_message_sent;

        // end of first item section
        

        // second item section [last 7 days subscribers]
        $last_seven_day = date("Y-m-d", strtotime("$current_date - 7 days"));
        $where = array(
            'where' => array(
                // 'user_id' => $user_id,
                'date_format(subscribed_at,"%Y-%m-%d") >=' => $last_seven_day
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $select = array('count(id) as subscribers','date_format(subscribed_at,"%Y-%m-%d") as subscribed_at');
        $seven_days_subscriber_info = $this->basic->get_data('messenger_bot_subscriber',$where,$select,'','','','date_format(subscribed_at,"%Y-%m-%d") asc','date_format(subscribed_at,"%Y-%m-%d")');
        $seven_days_subscriber_chart_label = array();
        $seven_days_subscriber_chart_data = array();
        $seven_days_subscriber_gain = 0;
        if(!empty($seven_days_subscriber_info))
        {
            foreach($seven_days_subscriber_info as $value)
            {
                array_push($seven_days_subscriber_chart_label, date("jS M y",strtotime($value['subscribed_at'])));
                array_push($seven_days_subscriber_chart_data, $value['subscribers']);
                $seven_days_subscriber_gain = $seven_days_subscriber_gain + $value['subscribers'];
            }
        }
        $data['seven_days_subscriber_chart_label'] = $seven_days_subscriber_chart_label;
        $data['seven_days_subscriber_chart_data'] = $seven_days_subscriber_chart_data;
        $data['seven_days_subscriber_gain'] = $seven_days_subscriber_gain;
        // end of second item section
        
        // third item section [24 hour interaction]
        $current_time = date("Y-m-d H:i:s");
        $yesterday=date("Y-m-d H:i:s",strtotime($current_time." -1 day"));        
        $where = array(
            'where' => array(
                // 'user_id' => $user_id,
                'last_subscriber_interaction_time >=' => $yesterday
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $select = array('count(id) as subscribers','date_format(last_subscriber_interaction_time,"%Y-%m-%d %H:%i") as subscribed_at');
        $hourly_subscriber_info = $this->basic->get_data('messenger_bot_subscriber',$where,$select,'','','','date_format(last_subscriber_interaction_time,"%Y-%m-%d %H") asc','date_format(last_subscriber_interaction_time,"%Y-%m-%d %H")');
        $hourly_subscriber_chart_label = array();
        $hourly_subscriber_chart_data = array();
        $hourly_subscriber_gain = 0;
        if(!empty($hourly_subscriber_info))
        {
            foreach($hourly_subscriber_info as $value)
            {
                array_push($hourly_subscriber_chart_label, date("h A",strtotime($value['subscribed_at'])));
                array_push($hourly_subscriber_chart_data, $value['subscribers']);
                $hourly_subscriber_gain = $hourly_subscriber_gain + $value['subscribers'];
            }
        }
        $data['hourly_subscriber_chart_label'] = $hourly_subscriber_chart_label;
        $data['hourly_subscriber_chart_data'] = $hourly_subscriber_chart_data;
        $data['hourly_subscriber_gain'] = $hourly_subscriber_gain;
        // end of third item section
        
        // forth item section [male vs female subscriber]
        $male_list = array();
        $female_list = array();
        $male_female_date_list = array();
        $past_thirty_day=date("Y-m-d",strtotime($current_date." -30 days"));
        $where = array(
            'where' => array(
                // 'user_id' => $user_id,
                'date_format(subscribed_at,"%Y-%m-%d") >=' => $past_thirty_day,
                'is_bot_subscriber' => '1'
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $select = array('count(id) as subscribers','gender','date_format(subscribed_at,"%Y-%m-%d") as subscribed_at');
        $subscriber_info = $this->basic->get_data('messenger_bot_subscriber',$where,$select,'','','','date_format(subscribed_at,"%Y-%m-%d") asc','date_format(subscribed_at,"%Y-%m-%d"),gender');
        foreach($subscriber_info as $value)
        {
            if($value['gender'] == 'male')
                $male_list[$value['subscribed_at']] = $value['subscribers'];
            else
                $female_list[$value['subscribed_at']] = $value['subscribers'];

            if(!isset($male_list[$value['subscribed_at']])) $male_list[$value['subscribed_at']] = 0;
            if(!isset($female_list[$value['subscribed_at']])) $female_list[$value['subscribed_at']] = 0;

            $formated_date = date("jS M",strtotime($value['subscribed_at']));
            $male_female_date_list[$value['subscribed_at']] = $formated_date;
        }
        
        $largest_values = array();
        $max_value = 1;
        if(!empty($male_list)) array_push($largest_values, max($male_list));
        if(!empty($female_list)) array_push($largest_values, max($female_list));
        if(!empty($largest_values)) $max_value = max($largest_values);
        if($max_value > 10) $data['step_size'] = floor($max_value/10);
        else $data['step_size'] = 1;

        $data['male_subscribers'] = $male_list;
        $data['female_subscribers'] = $female_list;
        $data['male_female_date_list'] = $male_female_date_list;
        // end of forth item section [male vs female subscriber]

        // fifth item section [email,phone,birthdate,locale gain]    
        
        // email section     
        $where = array(
            'where' => array(
                // 'user_id' => $user_id,
                'date_format(entry_time,"%Y-%m")' => $current_month,
                'permission' => '1',
                'email !=' => ''
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $select = array('count(id) as subscribers','gender');
        $subscriber_info = $this->basic->get_data('messenger_bot_subscriber',$where,$select,'','','','','gender');
        $combined_info['email']['male'] = 0;
        $combined_info['email']['female'] = 0;
        foreach($subscriber_info as $value)
        {
            if($value['gender'] == 'male')
                $combined_info['email']['male'] = $value['subscribers'];
            else
                $combined_info['email']['female'] = $value['subscribers'];
        }
        $combined_info['email']['total_email_gain'] = $combined_info['email']['male']+$combined_info['email']['female'];
        $percentage_info = $this->get_percentage($combined_info['email']['male'],$combined_info['email']['female']);
        $combined_info['email']['male_percentage'] = $percentage_info[0];
        $combined_info['email']['female_percentage'] = $percentage_info[1];
        // end of email section
        
        // phone section
        $where = array(
            'where' => array(
                // 'user_id' => $user_id,
                'date_format(phone_number_entry_time,"%Y-%m")' => $current_month,
                'permission' => '1',
                'phone_number !=' => ''
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $select = array('count(id) as subscribers','gender');
        $subscriber_info = $this->basic->get_data('messenger_bot_subscriber',$where,$select,'','','','','gender');
        $combined_info['phone']['male'] = 0;
        $combined_info['phone']['female'] = 0;
        foreach($subscriber_info as $value)
        {
            if($value['gender'] == 'male')
                $combined_info['phone']['male'] = $value['subscribers'];
            else
                $combined_info['phone']['female'] = $value['subscribers'];
        }
        $combined_info['phone']['total_phone_gain'] = $combined_info['phone']['male']+$combined_info['phone']['female'];
        $percentage_info = $this->get_percentage($combined_info['phone']['male'],$combined_info['phone']['female']);
        $combined_info['phone']['male_percentage'] = $percentage_info[0];
        $combined_info['phone']['female_percentage'] = $percentage_info[1];
        // end of phone section
        

        // birthdate section
        $where = array(
            'where' => array(
                // 'user_id' => $user_id,
                'date_format(birthdate_entry_time,"%Y-%m")' => $current_month,
                'permission' => '1'
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $select = array('count(id) as subscribers','gender');
        $subscriber_info = $this->basic->get_data('messenger_bot_subscriber',$where,$select,'','','','','gender');
        $combined_info['birthdate']['male'] = 0;
        $combined_info['birthdate']['female'] = 0;
        foreach($subscriber_info as $value)
        {
            if($value['gender'] == 'male')
                $combined_info['birthdate']['male'] = $value['subscribers'];
            else
                $combined_info['birthdate']['female'] = $value['subscribers'];
        }
        $combined_info['birthdate']['total_birthdate_gain'] = $combined_info['birthdate']['male']+$combined_info['birthdate']['female'];
        $percentage_info = $this->get_percentage($combined_info['birthdate']['male'],$combined_info['birthdate']['female']);
        $combined_info['birthdate']['male_percentage'] = $percentage_info[0];
        $combined_info['birthdate']['female_percentage'] = $percentage_info[1];
        // end of birthdate section

        
        $data['combined_info'] = $combined_info;
        // end of fifth item section [email,phone,birthdate,locale gain]
        
        // sixth item section [latest subscribers]
        $page_list = array();
        $latest_subscriber_list = array();
        $where = array(
            'where' => array(
                // 'user_id' => $user_id,
                'permission' => '1',
                'is_bot_subscriber' => '1'
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $latest_subscriber_info = $this->basic->get_data('messenger_bot_subscriber',$where,'','',6,'','subscribed_at desc');

        $where = array(
            'where'=>array()
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $page_info = $this->basic->get_data('facebook_rx_fb_page_info',$where,array('id','page_name','page_id'));
        foreach($page_info as $value)
        {
            $page_list[$value['id']]['page_name'] = $value['page_name'];
            $page_list[$value['id']]['page_id'] = $value['page_id'];
        }
        $i=0;
        foreach($latest_subscriber_info as $value)
        {
            $latest_subscriber_list[$i]['first_name'] = $value['first_name'];
            $latest_subscriber_list[$i]['last_name'] = $value['last_name'];
            $latest_subscriber_list[$i]['full_name'] = $value['full_name'];
            if($value['link'] == '')
                $latest_subscriber_list[$i]['link'] = 'disabled';
            else
                $latest_subscriber_list[$i]['link'] = $value['link'];

            $latest_subscriber_list[$i]['subscribed_at'] = date_time_calculator($value['subscribed_at'],true);
            $latest_subscriber_list[$i]['subscribe_id'] = $value['subscribe_id'];
            $latest_subscriber_list[$i]['page_name'] = $page_list[$value['page_table_id']]['page_name'];
            $latest_subscriber_list[$i]['page_id'] = $page_list[$value['page_table_id']]['page_id'];

            $profile_pic = ($value['profile_pic']!="") ? $value["profile_pic"] :  base_url('assets/img/avatar/avatar-1.png');
            $latest_subscriber_list[$i]['image_path']=($value["image_path"]!="") ? base_url($value["image_path"]) : $profile_pic;

            $i++;
        }
        $data['latest_subscriber_list'] = $latest_subscriber_list;
        // end sixth item section [latest subscribers]
        
        

        // item section [latest 24h subscribers]
        $current_time = date("Y-m-d H:i:s");
        $yesterday=date("Y-m-d H:i:s",strtotime($current_time." -1 day"));        
        $where = array(
            'where' => array(
                // 'user_id' => $user_id,
                'last_subscriber_interaction_time >=' => $yesterday
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $latest_24hsubscriber_info = $this->basic->get_data('messenger_bot_subscriber',$where,'','',6,'','last_subscriber_interaction_time desc');
        
        $i=0;
        $latest_24hsubscriber_list = array();
        foreach($latest_24hsubscriber_info as $value)
        {
            $latest_24hsubscriber_list[$i]['first_name'] = $value['first_name'];
            $latest_24hsubscriber_list[$i]['last_name'] = $value['last_name'];
            $latest_24hsubscriber_list[$i]['full_name'] = $value['full_name'];
            $latest_24hsubscriber_list[$i]['link'] = $value['link'];
            $latest_24hsubscriber_list[$i]['last_subscriber_interaction_time'] = date_time_calculator($value['last_subscriber_interaction_time'],true);
            $latest_24hsubscriber_list[$i]['subscribe_id'] = $value['subscribe_id'];
            $latest_24hsubscriber_list[$i]['page_name'] = $page_list[$value['page_table_id']]['page_name'];
            $latest_24hsubscriber_list[$i]['page_id'] = $page_list[$value['page_table_id']]['page_id'];

            $profile_pic = ($value['profile_pic']!="") ? $value["profile_pic"] :  base_url('assets/img/avatar/avatar-1.png');
            $latest_24hsubscriber_list[$i]['image_path']=($value["image_path"]!="") ? base_url($value["image_path"]) : $profile_pic;

            $i++;
        }
        $data['latest_24hsubscriber_list'] = $latest_24hsubscriber_list;
        // end item section [latest 24h subscribers]

        // seventh item section [top sources of subscribers]
        $refferer_source_info = array();
        $refferer_source_info['checkbox_plugin']['title'] = $this->lang->line("Checkbox Plugin");
        $refferer_source_info['customer_chat_plugin']['title'] = $this->lang->line("Customer Chat Plugin");
        $refferer_source_info['sent_to_messenger']['title'] = $this->lang->line("Sent to Messenger Plugin");
        $refferer_source_info['me_link']['title'] = $this->lang->line("m.me Link");
        $refferer_source_info['direct']['title'] = $this->lang->line("Direct From Facebook");
        $refferer_source_info['direct']['subscribers'] = 0;
        $refferer_source_info['comment_private_reply']['title'] = $this->lang->line("Comment Private Reply");
        $where = array(
            'where' => array(
                // 'user_id' => $user_id,
                // 'date_format(subscribed_at,"%Y-%m")' => $current_month,
                'permission' => '1'
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $select = array('count(id) as subscribers','refferer_source');
        $subscriber_refferer_info = $this->basic->get_data('messenger_bot_subscriber',$where,$select,'','','','','refferer_source');

        foreach($subscriber_refferer_info as $value)
        {
            if($value['refferer_source'] == 'checkbox_plugin')
                $refferer_source_info['checkbox_plugin']['subscribers'] = $value['subscribers'];
            else if ($value['refferer_source'] == 'CUSTOMER_CHAT_PLUGIN')
                $refferer_source_info['customer_chat_plugin']['subscribers'] = $value['subscribers'];
            else if ($value['refferer_source'] == 'SEND-TO-MESSENGER-PLUGIN')
                $refferer_source_info['sent_to_messenger']['subscribers'] = $value['subscribers'];
            else if ($value['refferer_source'] == 'SHORTLINK')
                $refferer_source_info['me_link']['subscribers'] = $value['subscribers'];
            else if ($value['refferer_source'] == 'FB PAGE' || $value['refferer_source'] == '')
                $refferer_source_info['direct']['subscribers'] += $value['subscribers'];
            else if ($value['refferer_source'] == 'COMMENT PRIVATE REPLY')
                $refferer_source_info['comment_private_reply']['subscribers'] = $value['subscribers'];
        }
        $data['refferer_source_info'] = $refferer_source_info;
        // end of seventh item section [top sources of subscribers]
        
        
        // last auto reply report section
        $where = array(
            'where'=>array()
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $last_auto_reply_post_info = $this->basic->get_data('facebook_ex_autoreply_report',$where,$select='',$join='',$limit='6',$start=NULL,'reply_time DESC');        

        $data['my_last_auto_reply_data'] = $last_auto_reply_post_info;
        // end of last auto reply report section
        

        // upcoming facebook poster campaign section
        $where = array(
            'where'=>array(
                'posting_status'=>'0'
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $scheduled_auto_post_campaign = $this->basic->get_data('facebook_rx_auto_post',$where,$select='',$join='',$limit=5,$start=NULL,'schedule_time ASC');
        $where = array(
            'where'=>array(
                'posting_status'=>'0'
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $scheduled_cta_post_campaign = $this->basic->get_data('facebook_rx_cta_post',$where,$select='',$join='',$limit=5,$start=NULL,'schedule_time ASC');
        $where = array(
            'where'=>array(
                'posting_status'=>'0'
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $scheduled_carousel_slider_campaign = $this->basic->get_data('facebook_rx_slider_post',$where,$select='',$join='',$limit=5,$start=NULL,'schedule_time ASC');

        $upcoming_post_campaign_array = array();
        
        foreach($scheduled_auto_post_campaign as $value)
            $upcoming_post_campaign_array[] = $value;
        foreach($scheduled_cta_post_campaign as $value)
            $upcoming_post_campaign_array[] = $value;
        foreach($scheduled_carousel_slider_campaign as $value)
            $upcoming_post_campaign_array[] = $value;

        usort($upcoming_post_campaign_array, function($a, $b) {
            if ($a['schedule_time'] == $b['schedule_time'])
            return 0;
            else if ($a['schedule_time'] > $b['schedule_time'])
            return 1;
            else
            return -1;
        });
        $data['upcoming_post_campaign_array'] = $upcoming_post_campaign_array;
        // end of upcoming facebook poster campaign section

        // recently completed facebook poster campaign
        $where = array(
            'where'=>array(
                'posting_status'=>'2'
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $all_time_auto_post = $this->basic->get_data('facebook_rx_auto_post',$where,$select='',$join='',$limit=5,$start=NULL,'last_updated_at DESC');
        $where = array(
            'where'=>array(
                'posting_status'=>'2'
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $all_time_cta_post = $this->basic->get_data('facebook_rx_cta_post',$where,$select='',$join='',$limit=5,$start=NULL,'last_updated_at DESC');
        $where = array(
            'where'=>array(
                'posting_status'=>'2'
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $all_time_slider_post = $this->basic->get_data('facebook_rx_slider_post',$where,$select='',$join='',$limit=5,$start=NULL,'last_updated_at DESC');

        $recently_completed_post_array = array();
        foreach($all_time_auto_post as $value)
            $recently_completed_post_array[] = $value;
        foreach($all_time_cta_post as $value)
            $recently_completed_post_array[] = $value;
        foreach($all_time_slider_post as $value)
            $recently_completed_post_array[] = $value;
        usort($recently_completed_post_array, function($a, $b) {
            if ($a['last_updated_at'] == $b['last_updated_at'])
            return 0;
            else if ($a['last_updated_at'] < $b['last_updated_at'])
            return 1;
            else
            return -1;
        });
        $data['recently_completed_post_array'] = $recently_completed_post_array;
        // end of recently completed facebook poster campaign

        $where = array(
            'where'=>array(
                'posting_status'=>'2'
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $recently_message_sent_completed_campaing_info = $this->basic->get_data('facebook_ex_conversation_campaign',$where,$select='',$join='',$limit='5',$start=NULL,'added_at DESC');
        
        $where = array(
            'where'=>array(
                'posting_status'=>'0'
            )
        );
        if($default_value != 'system') $where['where']['user_id'] = $user_id;
        $upcoming_message_sent_campaign_info = $this->basic->get_data('facebook_ex_conversation_campaign',$where,$select='',$join='',$limit='5',$start=NULL,'added_at DESC');

        $data['recently_message_sent_completed_campaing_info'] = $recently_message_sent_completed_campaing_info;
        $data['upcoming_message_sent_campaign_info'] = $upcoming_message_sent_campaign_info;

        $data['body'] = 'dashboard/dashboard';
        $data['page_title'] = $this->lang->line('Dashboard');
        $this->_viewcontroller($data);
    }

    public function get_first_div_content()
    {
        $this->ajax_check();
        $month_no = $this->input->post('month_no',true);
        if($month_no == 'year')
            $search_year = date("Y");
        else
            $search_month = date("Y-{$month_no}");

        // first item section
        $total_subscribers = 0;
        $where_simple = array();
        $where_simple['user_id'] = $this->user_id;
        $where_simple['permission'] = '1';
        if($month_no == 'year')
            $where_simple['date_format(subscribed_at,"%Y")'] = $search_year;
        else
            $where_simple['date_format(subscribed_at,"%Y-%m")'] = $search_month;

        $where = array(
            'where' => $where_simple
        );
        $select = array('count(id) as subscribers');
        $subscriber_info = $this->basic->get_data('messenger_bot_subscriber',$where,$select);
        $subscribed = isset($subscriber_info[0]['subscribers']) ? $subscriber_info[0]['subscribers'] : 0;
        $data['subscribed'] = custom_number_format($subscribed);
        
        $where_simple = array();
        $where_simple['user_id'] = $this->user_id;
        $where_simple['permission'] = '0';
        if($month_no == 'year')
            $where_simple['date_format(unsubscribed_at,"%Y")'] = $search_year;
        else
            $where_simple['date_format(unsubscribed_at,"%Y-%m")'] = $search_month;
        $where = array(
            'where' => $where_simple
        );
        $select = array('count(id) as unsubscribers');
        $unsubscriber_info = $this->basic->get_data('messenger_bot_subscriber',$where,$select);
        $unsubscribed = isset($unsubscriber_info[0]['unsubscribers']) ? $unsubscriber_info[0]['unsubscribers'] : 0;
        $data['unsubscribed'] = custom_number_format($unsubscribed);
        $total_subscribers = $subscribed + $unsubscribed;
        $data['total_subscribers'] = custom_number_format($total_subscribers);

        $where_simple = array();
        $where_simple['user_id'] = $this->user_id;
        if($month_no == 'year')
            $where_simple['date_format(completed_at,"%Y")'] = $search_year;
        else
            $where_simple['date_format(completed_at,"%Y-%m")'] = $search_month;
        $where = array(
            'where' => $where_simple
        );
        $select = array('sum(successfully_sent) as total_message_sent');
        $conversation_message_sent_info = $this->basic->get_data("facebook_ex_conversation_campaign",$where,$select);
        $total_conversion_message_sent = isset($conversation_message_sent_info[0]['total_message_sent']) ? $conversation_message_sent_info[0]['total_message_sent'] : 0;

        $where_simple = array();
        $where_simple['user_id'] = $this->user_id;
        if($month_no == 'year')
            $where_simple['date_format(completed_at,"%Y")'] = $search_year;
        else
            $where_simple['date_format(completed_at,"%Y-%m")'] = $search_month;
        $where = array(
            'where' => $where_simple
        );
        $select = array('sum(successfully_sent) as total_message_sent');
        if($this->is_broadcaster_exist)
        {
            $broadcast_message_sent_info = $this->basic->get_data("messenger_bot_broadcast_serial",$where,$select);
        }
        $total_broadcast_message_sent = isset($broadcast_message_sent_info[0]['total_message_sent']) ? $broadcast_message_sent_info[0]['total_message_sent'] : 0;
        $total_message_sent = $total_conversion_message_sent + $total_broadcast_message_sent;
        $data['total_message_sent'] = custom_number_format($total_message_sent);
        // end of first item section
        echo json_encode($data,true);
    }

    public function get_subscriber_data_div()
    {
        $this->ajax_check();
        $period = $this->input->post('period',true);
        $today = date("Y-m-d");
        $last_seven_day = date("Y-m-d", strtotime("$today - 7 days"));
        $this_month = date("Y-m");
        $this_year = date("Y");

        // fifth item section [email,phone,birthdate,locale gain]    
        
        // email section     
        $where_simple = array();
        $where_simple['user_id'] = $this->user_id;

        if($period == 'today')
            $where_simple['date_format(entry_time,"%Y-%m-%d")'] = $today;
        else if($period == 'week')
            $where_simple['date_format(entry_time,"%Y-%m-%d") >='] = $last_seven_day;
        else if($period == 'month')
            $where_simple['date_format(entry_time,"%Y-%m")'] = $this_month;
        else if($period == 'year')
            $where_simple['date_format(entry_time,"%Y")'] = $this_year;

        $where_simple['permission'] = '1';
        $where_simple['email !='] = '';
        $where = array(
            'where' => $where_simple
        );
        $select = array('count(id) as subscribers','gender');
        $subscriber_info = $this->basic->get_data('messenger_bot_subscriber',$where,$select,'','','','','gender');
        $combined_info['email']['male'] = 0;
        $combined_info['email']['female'] = 0;
        foreach($subscriber_info as $value)
        {
            if($value['gender'] == 'male')
                $combined_info['email']['male'] = number_format($value['subscribers']);
            else
                $combined_info['email']['female'] = number_format($value['subscribers']);
        }
        $combined_info['email']['total_email_gain'] = number_format($combined_info['email']['male']+$combined_info['email']['female']);
        $percentage_info = $this->get_percentage($combined_info['email']['male'],$combined_info['email']['female']);
        $combined_info['email']['male_percentage'] = $percentage_info[0].'%';
        $combined_info['email']['female_percentage'] = $percentage_info[1].'%';
        // end of email section
        
        // phone section
        $where_simple = array();
        $where_simple['user_id'] = $this->user_id;

        if($period == 'today')
            $where_simple['date_format(phone_number_entry_time,"%Y-%m-%d")'] = $today;
        else if($period == 'week')
            $where_simple['date_format(phone_number_entry_time,"%Y-%m-%d") >='] = $last_seven_day;
        else if($period == 'month')
            $where_simple['date_format(phone_number_entry_time,"%Y-%m")'] = $this_month;
        else if($period == 'year')
            $where_simple['date_format(phone_number_entry_time,"%Y")'] = $this_year;

        $where_simple['permission'] = '1';
        $where_simple['phone_number !='] = '';
        $where = array(
            'where' => $where_simple
        );
        $select = array('count(id) as subscribers','gender');
        $subscriber_info = $this->basic->get_data('messenger_bot_subscriber',$where,$select,'','','','','gender');
        $combined_info['phone']['male'] = 0;
        $combined_info['phone']['female'] = 0;
        foreach($subscriber_info as $value)
        {
            if($value['gender'] == 'male')
                $combined_info['phone']['male'] = number_format($value['subscribers']);
            else
                $combined_info['phone']['female'] = number_format($value['subscribers']);
        }
        $combined_info['phone']['total_phone_gain'] = number_format($combined_info['phone']['male']+$combined_info['phone']['female']);
        $percentage_info = $this->get_percentage($combined_info['phone']['male'],$combined_info['phone']['female']);
        $combined_info['phone']['male_percentage'] = $percentage_info[0].'%';
        $combined_info['phone']['female_percentage'] = $percentage_info[1].'%';
        // end of phone section
        

        // birthdate section
        $where_simple = array();
        $where_simple['user_id'] = $this->user_id;
        if($period == 'today')
            $where_simple['date_format(birthdate_entry_time,"%Y-%m-%d")'] = $today;
        else if($period == 'week')
            $where_simple['date_format(birthdate_entry_time,"%Y-%m-%d") >='] = $last_seven_day;
        else if($period == 'month')
            $where_simple['date_format(birthdate_entry_time,"%Y-%m")'] = $this_month;
        else if($period == 'year')
            $where_simple['date_format(birthdate_entry_time,"%Y")'] = $this_year;
        $where_simple['permission'] = '1';
        $where = array(
            'where' => $where_simple
        );
        $select = array('count(id) as subscribers','gender');
        $subscriber_info = $this->basic->get_data('messenger_bot_subscriber',$where,$select,'','','','','gender');
        $combined_info['birthdate']['male'] = 0;
        $combined_info['birthdate']['female'] = 0;
        foreach($subscriber_info as $value)
        {
            if($value['gender'] == 'male')
                $combined_info['birthdate']['male'] = number_format($value['subscribers']);
            else
                $combined_info['birthdate']['female'] = number_format($value['subscribers']);
        }
        $combined_info['birthdate']['total_birthdate_gain'] = number_format($combined_info['birthdate']['male']+$combined_info['birthdate']['female']);
        $percentage_info = $this->get_percentage($combined_info['birthdate']['male'],$combined_info['birthdate']['female']);
        $combined_info['birthdate']['male_percentage'] = $percentage_info[0].'%';
        $combined_info['birthdate']['female_percentage'] = $percentage_info[1].'%';
        // end of birthdate section

        // end of fifth item section [email,phone,birthdate,locale gain]

        echo json_encode($combined_info,true);
    }

    public function get_male_female_div_content()
    {
        $this->ajax_check();
        $current_date = date("Y-m-d");
        // forth item section [male vs female subscriber]
        $male_list = array();
        $female_list = array();
        $male_female_date_list = array();
        $past_thirty_day=date("Y-m-d",strtotime($current_date." -3 days"));
        $where = array(
            'where' => array(
                'user_id' => $this->user_id,
                'date_format(subscribed_at,"%Y-%m-%d") >=' => $past_thirty_day
            )
        );
        $select = array('count(id) as subscribers','gender','date_format(subscribed_at,"%Y-%m-%d") as subscribed_at');
        $subscriber_info = $this->basic->get_data('messenger_bot_subscriber',$where,$select,'','','','date_format(subscribed_at,"%Y-%m-%d") asc','date_format(subscribed_at,"%Y-%m-%d"),gender');
        foreach($subscriber_info as $value)
        {
            if($value['gender'] == 'male')
                $male_list[$value['subscribed_at']] = $value['subscribers'];
            else
                $female_list[$value['subscribed_at']] = $value['subscribers'];

            if(!isset($male_list[$value['subscribed_at']])) $male_list[$value['subscribed_at']] = 0;
            if(!isset($female_list[$value['subscribed_at']])) $female_list[$value['subscribed_at']] = 0;

            $formated_date = date("jS M",strtotime($value['subscribed_at']));
            $male_female_date_list[$value['subscribed_at']] = $formated_date;
        }
        $data['male_subscribers'] = $male_list;
        $data['female_subscribers'] = $female_list;
        $data['male_female_date_list'] = $male_female_date_list;

        $str = "
        <script>
            var male_vs_female_chart = document.getElementById('male_vs_female_chart').getContext('2d');
            var myChart = new Chart(male_vs_female_chart, {
              type: 'line',
              data: {
                labels: ".json_encode(array_values($male_female_date_list)).",
                datasets: [{
                  label: '".$this->lang->line('Female')."',
                  data: ".json_encode(array_values($female_list)).",
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
                  label: '".$this->lang->line('Male')."',
                  data: ".json_encode(array_values($male_list)).",
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
                      drawBorder: false,
                      color: '#f2f2f2',
                    },
                    ticks: {
                      beginAtZero: true,
                      display: false
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
        </script>";
        // end of forth item section [male vs female subscriber]
        echo $str;
    }

    public function get_percentage($first_number, $second_number) {
        if($first_number == 0 && $second_number == 0)
            return [(float) 0, (float) 0];

        $total = (int) $first_number + (int) $second_number;
        
        $first_percent = ($first_number / $total) * 100;
        $second_percent = ($second_number / $total) * 100;
        
        return [(float) $first_percent, (float) $second_percent];
    }


 
}