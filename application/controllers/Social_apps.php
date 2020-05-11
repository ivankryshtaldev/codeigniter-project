<?php

require_once("Home.php"); // loading home controller

/**
* @category controller
* class Admin
*/

class Social_apps extends Home
{
    
    public function __construct()
    {
        parent::__construct();

        if ($this->session->userdata('logged_in')!= 1) {
            redirect('home/login', 'location');
        }

        if ($this->session->userdata('user_type')== "Member" && $this->config->item("backup_mode")==0) {
            redirect('home/login', 'location');
        }
        
        $this->load->helper('form');
        $this->load->library('upload');
        
        $this->upload_path = realpath(APPPATH . '../upload');
        set_time_limit(0);

        $this->important_feature();
        $this->periodic_check();

    }


    public function index()
    {
        $this->settings();
    }


    public function settings()
    {

        $data['page_title'] = $this->lang->line('Social Apps');

        $data['body'] = 'admin/social_apps/settings';
        $data['title'] = $this->lang->line('Social Apps');

        $this->_viewcontroller($data);
    }


    public function google_settings()
    {

        if ($this->session->userdata('user_type') != 'Admin')
        redirect('home/login_page', 'location');

        $google_settings = $this->basic->get_data('login_config');

        if (!isset($google_settings[0])) $google_settings = array();
        else $google_settings = $google_settings[0];

        if($this->is_demo == '1')
        {
            $google_settings['api_key'] = 'XXXXXXXXXXX';
            $google_settings['google_client_secret'] = 'XXXXXXXXXXX';
        }

        $data['google_settings'] = $google_settings;
        $data['page_title'] = $this->lang->line('Google App Settings');
        $data['title'] = $this->lang->line('Google App Settings');
        $data['body'] = 'admin/social_apps/google_settings';

        $this->_viewcontroller($data);
    }



    public function google_settings_action()
    {
        if($this->is_demo == '1')
        {
            echo "<h2 style='text-align:center;color:red;border:1px solid red; padding: 10px'>This feature is disabled in this demo.</h2>"; 
            exit();
        }

        if ($this->session->userdata('user_type') != 'Admin')
        redirect('home/login_page', 'location');

        if (!isset($_POST)) exit;

        $this->form_validation->set_rules('google_client_id', $this->lang->line("Client ID"), 'trim|required');
        $this->form_validation->set_rules('google_client_secret', $this->lang->line("Client Secret"), 'trim|required');

        if ($this->form_validation->run() == FALSE) 
            $this->google_settings();
        else {

            $insert_data['app_name'] = $this->input->post('app_name');
            $insert_data['api_key'] = $this->input->post('api_key');
            $insert_data['google_client_id'] = $this->input->post('google_client_id');
            $insert_data['google_client_secret'] = $this->input->post('google_client_secret');
            
            $status = $this->input->post('status');
            if($status=='') $status='0';
            $insert_data['status'] = $status;

            $google_settings = $this->basic->get_data('login_config');

            if (count($google_settings) > 0 ) {

                $id = $google_settings[0]['id'];
                $this->basic->update_data('login_config', array('id' => $id), $insert_data);
            }
            else 
                $this->basic->insert_data('login_config', $insert_data);

            $this->session->set_flashdata('success_message', '1');
            redirect(base_url('social_apps/google_settings'),'location');
        }

        


    }



    protected function facebookTokenValidityCheck($input_token)
    {

        if($input_token=="") 
        return "<span class='badge badge-status text-danger'><i class='fas fa-times-circle red'></i> ".$this->lang->line('Invalid')."</span>";
        $this->load->library("fb_rx_login"); 
        
        if($this->config->item('developer_access') == '1')
        {
            $valid_or_invalid = $this->fb_rx_login->access_token_validity_check_for_user($input_token);
            
            if($valid_or_invalid)
                return "<span class='badge badge-status text-success'><i class='fa fa-check-circle green'></i> ".$this->lang->line('Valid')."</span>";
            else
                return "<span class='badge badge-status text-danger'><i class='fa fa-clock-o red'></i> ".$this->lang->line('Expired')."</span>";
        }
        else
        {
            $url="https://graph.facebook.com/debug_token?input_token={$input_token}&access_token={$input_token}";
            $result= $this->fb_rx_login->run_curl_for_fb($url);
            $result = json_decode($result,true);
             
            if(isset($result["data"]["is_valid"]) && $result["data"]["is_valid"])
                return "<span class='badge badge-status text-success'><i class='fa fa-check-circle green'></i> ".$this->lang->line('Valid')."</span>";
            else
                return "<span class='badge badge-status text-danger'><i class='fa fa-clock-o red'></i> ".$this->lang->line('Expired')."</span>"; 
        }

    }



    public function facebook_settings()
    {
        $data['page_title'] = $this->lang->line('Facebook App Settings');
        $data['title'] = $this->lang->line('Facebook App Settings');
        $data['body'] = 'admin/social_apps/facebook_app_settings';

        $this->_viewcontroller($data);
    }


    public function facebook_settings_data()
    {
        $this->ajax_check();
        $search_value = $_POST['search']['value'];
        $display_columns = array("#","CHECKBOX",'id', 'app_name', 'api_id', 'api_secret', 'status', 'token_validity', 'action');
        $search_columns = array('app_name', 'api_id');

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $sort_index = isset($_POST['order'][0]['column']) ? strval($_POST['order'][0]['column']) : 2;
        $sort = isset($display_columns[$sort_index]) ? $display_columns[$sort_index] : 'id';
        $order = isset($_POST['order'][0]['dir']) ? strval($_POST['order'][0]['dir']) : 'desc';
        $order_by=$sort." ".$order;

        $where_custom = '';
        $where_custom="user_id = ".$this->user_id;

        if ($search_value != '') 
        {
            foreach ($search_columns as $key => $value) 
            $temp[] = $value." LIKE "."'%$search_value%'";
            $imp = implode(" OR ", $temp);
            $where_custom .=" AND (".$imp.") ";
        }


        $table="facebook_rx_config";
        $this->db->where($where_custom);
        $info=$this->basic->get_data($table,$where='',$select='',$join='',$limit,$start,$order_by,$group_by='');
        $this->db->where($where_custom);
        $total_rows_array=$this->basic->count_row($table,$where='',$count=$table.".id",$join='',$group_by='');
        $total_result=$total_rows_array[0]['total_rows'];

        $i=0;
        $base_url=base_url();
        foreach ($info as $key => $value) 
        {
            if($this->is_demo == '1')
            $info[$i]['api_secret'] = "XXXXXXXXXXX";

            $token_validity = $this->facebookTokenValidityCheck($value['user_access_token']);
            if($value['status'] == 1)
                $info[$i]['status'] = "<span class='badge badge-status text-success'><i class='fa fa-check-circle green'></i> ".$this->lang->line('Active')."</span>";
            else
                $info[$i]['status'] = "<span class='badge badge-status text-danger'><i class='fa fa-check-circle red'></i> ".$this->lang->line('Inactive')."</span>";
            $info[$i]['token_validity'] = $token_validity;

            $info[$i]['action'] = "";
            
            if($this->is_demo != '1')
            $info[$i]['action'] .= "<div style='min-width:130px'><a href='".base_url('social_apps/edit_facebook_settings/').$value['id']."' class='btn btn-outline-warning btn-circle' data-toggle='tooltip' data-placement='top' title='".$this->lang->line('Edit APP Settings')."'><i class='fas fa-edit'></i></a> ";
            
            if($this->is_demo != '1')
            $info[$i]['action'] .= "<a href='".base_url('social_apps/login_button/').$value['id']."' class='btn btn-outline-primary btn-circle' data-toggle='tooltip' data-placement='top' title='".$this->lang->line('Login to validate your accesstoken.')."'><i class='fab fa-facebook-square'></i></a> <a href='#' class='btn btn-outline-danger btn-circle delete_app' table_id='".$value['id']."' data-toggle='tooltip' data-placement='top' title='".$this->lang->line('Delete this APP')."'><i class='fas fa-trash-alt'></i></a></div>";

            $info[$i]["action"] .="<script>$('[data-toggle=\"tooltip\"]').tooltip();</script>";
            $i++;
        }

        $data['draw'] = (int)$_POST['draw'] + 1;
        $data['recordsTotal'] = $total_result;
        $data['recordsFiltered'] = $total_result;
        $data['data'] = convertDataTableResult($info, $display_columns ,$start,$primary_key="id");

        echo json_encode($data);
    }


    public function add_facebook_settings()
    {
        $data['table_id'] = 0;
        $data['facebook_settings'] = array();
        $data['page_title'] = $this->lang->line('Facebook App Settings');
        $data['title'] = $this->lang->line('Facebook App Settings');
        $data['body'] = 'admin/social_apps/facebook_settings';

        $this->_viewcontroller($data);
    }


    public function edit_facebook_settings($table_id=0)
    {
        
        if($this->is_demo == '1')
        {
            echo "<h2 style='text-align:center;color:red;border:1px solid red; padding: 10px'>This feature is disabled in this demo.</h2>"; 
            exit();
        }
        if($table_id==0) exit;
        $facebook_settings = $this->basic->get_data('facebook_rx_config',array("where"=>array("id"=>$table_id)));
        if (!isset($facebook_settings[0])) $facebook_settings = array();
        else $facebook_settings = $facebook_settings[0];
        $data['table_id'] = $table_id;
        $data['facebook_settings'] = $facebook_settings;
        $data['page_title'] = $this->lang->line('Facebook App Settings');
        $data['title'] = $this->lang->line('Facebook App Settings');
        $data['body'] = 'admin/social_apps/facebook_settings';

        $this->_viewcontroller($data);
    }




    public function facebook_settings_update_action()
    {
        if($this->is_demo == '1')
        {
            echo "<h2 style='text-align:center;color:red;border:1px solid red; padding: 10px'>This feature is disabled in this demo.</h2>"; 
            exit();
        }

        if (!isset($_POST)) exit;



        $this->form_validation->set_rules('api_id', $this->lang->line("App ID"), 'trim|required');
        $this->form_validation->set_rules('api_secret', $this->lang->line("App Secret"), 'trim|required');
        $table_id = $this->input->post('table_id',true);

        if ($this->form_validation->run() == FALSE) 
        {
            if($table_id == 0) $this->add_facebook_settings();
            else $this->edit_facebook_settings($table_id);
        }
        else {

            $insert_data['app_name'] = $this->input->post('app_name');
            $insert_data['api_id'] = $this->input->post('api_id');
            $insert_data['api_secret'] = $this->input->post('api_secret');
            $insert_data['user_id'] = $this->user_id;

            if($this->session->userdata('user_type') == 'Admin')
                $insert_data['use_by'] = 'everyone';
            else
                $insert_data['use_by'] = 'only_me';
            
            $status = $this->input->post('status');
            if($status=='') $status='0';
            $insert_data['status'] = $status;

            $facebook_settings = $this->basic->get_data('facebook_rx_config');

            if ($table_id != 0) {
                $this->basic->update_data('facebook_rx_config', array('id' => $table_id,"user_id"=>$this->user_id), $insert_data);
            }
            else 
                $this->basic->insert_data('facebook_rx_config', $insert_data);

            $this->session->set_flashdata('success_message', '1');
            redirect(base_url('social_apps/facebook_settings'),'location');
            
        }

        


    }


    public function login_button($id)
    {     
        $fb_config_info = $this->basic->get_data('facebook_rx_config',array('where'=>array('id'=>$id)));
        if(isset($fb_config_info[0]['developer_access']) && $fb_config_info[0]['developer_access'] == '1' && $this->session->userdata('user_type')=="Admin")
        {
            $url = "https://ac.getapptoken.com/home/get_secret_code_info";
            $config_id = $fb_config_info[0]['secret_code'];

            $json="secret_code={$config_id}";
     
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$json);
            curl_setopt($ch,CURLOPT_POST,1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
            curl_setopt($ch, CURLOPT_COOKIEJAR,'cookie.txt');  
            curl_setopt($ch, CURLOPT_COOKIEFILE,'cookie.txt');  
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
            $st=curl_exec($ch);  
            $result=json_decode($st,TRUE);


            if(isset($result['error']))
            {
                $this->session->set_userdata('secret_code_error','Invalid secret code!');
                redirect('facebook_rx_config/index','location');                
                exit();
            }


            // collect data from our server and then insert it into faceboo_rx_config_table and then call library for user and page info
            $config_data = array(
                'api_id' => $result['api_id'],
                'api_secret' => $result['api_secret'],
                'facebook_id' => $result['fb_id'],
                'user_access_token' => $result['access_token']
            );
            $this->basic->update_data("facebook_rx_config",array('id'=>$id),$config_data);

            $data = array(
                'user_id' => $this->user_id,
                'facebook_rx_config_id' => $id,
                'access_token' => $result['access_token'],
                'name' => isset($result['name']) ? $result['name'] : "",
                'email' => isset($result['email']) ? $result['email'] : "",
                'fb_id' => $result['fb_id'],
                'add_date' => date('Y-m-d')
                );

            $where=array();
            $where['where'] = array('user_id'=>$this->user_id,'fb_id'=>$result['fb_id']);
            $exist_or_not = $this->basic->get_data('facebook_rx_fb_user_info',$where);

            if(empty($exist_or_not))
            {
                $this->basic->insert_data('facebook_rx_fb_user_info',$data);
                $facebook_table_id = $this->db->insert_id();
            }
            else
            {
                $facebook_table_id = $exist_or_not[0]['id'];
                $where = array('user_id'=>$this->user_id,'fb_id'=>$result['fb_id']);
                $this->basic->update_data('facebook_rx_fb_user_info',$where,$data);
            }

            $this->session->set_userdata("facebook_rx_fb_user_info",$facebook_table_id);


            $this->session->set_userdata("fb_rx_login_database_id",$id);
            $this->fb_rx_login->app_initialize($id);
            $page_list = $this->fb_rx_login->get_page_list($result['access_token']);            
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
                    $page_username = '';
                    if(isset($page['username'])) $page_username = $page['username'];
                    $page_access_token = '';
                    if(isset($page['access_token'])) $page_access_token = $page['access_token'];
                    $page_email = '';
                    if(isset($page['emails'][0])) $page_email = $page['emails'][0];

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
                        'add_date' => date('Y-m-d')
                        );

                    $where=array();
                    $where['where'] = array('facebook_rx_fb_user_info_id'=>$facebook_table_id,'page_id'=>$page['id']);
                    $exist_or_not = $this->basic->get_data('facebook_rx_fb_page_info',$where);

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

            $group_list = $this->fb_rx_login->get_group_list($result['access_token']);

            if(!empty($group_list))
            {
                foreach($group_list as $group)
                {
                    $user_id = $this->user_id;
                    $group_access_token = $result['access_token']; // group uses user access token
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
                        'add_date' => date('Y-m-d')
                        );

                    $where=array();
                    $where['where'] = array('facebook_rx_fb_user_info_id'=>$facebook_table_id,'group_id'=>$group['id']);
                    $exist_or_not = $this->basic->get_data('facebook_rx_fb_group_info',$where);

                    if(empty($exist_or_not))
                    {
                        $this->basic->insert_data('facebook_rx_fb_group_info',$data);
                    }
                    else
                    {
                        $where = array('facebook_rx_fb_user_info_id'=>$facebook_table_id,'group_id'=>$page['id']);
                        $this->basic->update_data('facebook_rx_fb_group_info',$where,$data);
                    }
                }
            }
            $this->session->set_userdata('success_message', 'success');
            redirect('facebook_rx_account_import/index','location');
        }
        else
        {
            $this->session->set_userdata("fb_rx_login_database_id",$id);
            $this->load->library('fb_rx_login');
            $redirect_url = base_url()."home/redirect_rx_link";        
            $data['fb_login_button'] = $this->fb_rx_login->login_for_user_access_token($redirect_url);  

            $data['body'] = 'facebook_rx/admin_login';
            $data['page_title'] =  $this->lang->line("Admin login");
            $data['expired_or_not'] = $this->fb_rx_login->access_token_validity_check();
            $this->_viewcontroller($data);
        }
    }



}

