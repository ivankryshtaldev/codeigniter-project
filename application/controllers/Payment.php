<?php require_once("Home.php"); // including home controller

class Payment extends Home
{
    public function __construct()
    {
        parent::__construct();
        if ($this->session->userdata('logged_in') != 1) redirect('home/login_page', 'location');
        $this->load->library('paypal_class');
        $this->load->library('stripe_class');
        $this->important_feature();
        $this->periodic_check();
    }

 
    public function accounts()
    {     
        if($this->session->userdata('user_type') != 'Admin') redirect('home/login_page', 'location');
        if($this->session->userdata('license_type') != 'double') redirect('home/access_forbidden', 'location');
        $data['body'] = "admin/payment/accounts";
        $data['page_title'] = $this->lang->line('Payment Accounts');
        $get_data = $this->basic->get_data("payment_config");
        $data['xvalue'] = isset($get_data[0])?$get_data[0]:array();
        if($this->is_demo == '1')$data["xvalue"]["stripe_secret_key"]=$data["xvalue"]["stripe_publishable_key"]=$data["xvalue"]["paypal_email"]="XXXXXXXXXX";
        $data['currency_list'] = $this->basic->get_enum_values_assoc("payment_config","currency");
        $this->_viewcontroller($data);
    }

    public function accounts_action()
    {
        if($this->is_demo == '1')
        {
            echo "<h2 style='text-align:center;color:red;border:1px solid red; padding: 10px'>This feature is disabled in this demo.</h2>"; 
            exit();
        }
        if($this->session->userdata('user_type') != 'Admin') redirect('home/login_page', 'location');
        if($this->session->userdata('license_type') != 'double') redirect('home/access_forbidden', 'location');

        if ($_SERVER['REQUEST_METHOD'] === 'GET') redirect('home/access_forbidden', 'location');
        if ($_POST) 
        {
            // validation
            $this->form_validation->set_rules('paypal_email','<b>'.$this->lang->line("Paypal Email").'</b>','trim');
            $this->form_validation->set_rules('paypal_payment_type','<b>'.$this->lang->line("Paypal Recurring Payment").'</b>','trim');
            $this->form_validation->set_rules('paypal_mode','<b>'.$this->lang->line("Paypal Sandbox Mode").'</b>','trim');
            $this->form_validation->set_rules('stripe_secret_key','<b>'.$this->lang->line("Stripe Secret Key").'</b>','trim');
            $this->form_validation->set_rules('stripe_publishable_key','<b>'.$this->lang->line("Stripe Publishable Key").'</b>','trim');
            $this->form_validation->set_rules('currency','<b>'.$this->lang->line("Currency").'</b>',  'trim');
            

            // go to config form page if validation wrong
            if ($this->form_validation->run() == false) 
            {
                return $this->accounts();
            } 
            else 
            {
                // assign
                $paypal_email=$this->input->post('paypal_email');
                $paypal_payment_type=$this->input->post('paypal_payment_type');
                $paypal_mode=$this->input->post('paypal_mode');
                $stripe_secret_key=$this->input->post('stripe_secret_key');
                $stripe_publishable_key=$this->input->post('stripe_publishable_key');
                $currency=$this->input->post('currency');

                if($paypal_payment_type=="") $paypal_payment_type="manual";
                if($paypal_mode=="") $paypal_mode="live";

                $update_data = 
                array
                (
                    'paypal_email'=>$paypal_email,
                    'paypal_payment_type'=>$paypal_payment_type,
                    'paypal_mode'=>$paypal_mode,
                    'stripe_secret_key'=>$stripe_secret_key,
                    'stripe_publishable_key'=>$stripe_publishable_key,
                    'currency'=>$currency,
                    'deleted' => '0'
                );


                $get_data = $this->basic->get_data("payment_config");
                if(isset($get_data[0]))
                $this->basic->update_data("payment_config",array("id >"=>0),$update_data);
                else $this->basic->insert_data("payment_config",$update_data);      
                                         
                $this->session->set_flashdata('success_message', 1);
                redirect('payment/accounts', 'location');
            }
        }
    }

    public function earning_summary()
    {
        if($this->session->userdata('user_type') != 'Admin') redirect('home/login_page', 'location');
        if($this->session->userdata('license_type') != 'double') redirect('home/access_forbidden', 'location');

        $user_data = $this->basic->get_data('users',$where='',$select=array('count(id) as total_user'));

        $year = date("Y");
        $lastyear = $year-1;
        $month = date("m");
        $date = date("Y-m-d");

        $payment_result = $this->db->query("SELECT * FROM transaction_history WHERE  DATE_FORMAT(payment_date,'%Y')='{$year}' OR DATE_FORMAT(payment_date,'%Y')='{$lastyear}' ORDER BY payment_date DESC");
        $payment_data = $payment_result->result_array();

        $payment_today=$payment_month=$payment_year=$payment_life=0;
        $array_month = array();
        $array_year = array();
        $this_year_earning=array();
        $last_year_earning=array();
        $this_year_top= array();
        $last_year_top= array();

        $month_names = array();
        for($m=1; $m<=$month; ++$m)
        {
            $name=date('M', mktime(0, 0, 0, $m, 1));
            $month_names[]=$this->lang->line($name);
            $this_year_earning[]=0;
            $last_year_earning[]=0;
        }

        foreach ($payment_data as $key => $value) 
        {
           $mon = date("F",strtotime($value['payment_date']));
           $mon2 = date("m",strtotime($value['payment_date']));

           if(strtotime($value['payment_date']) == strtotime($date)) $payment_today += $value["paid_amount"];

           if(date("m",strtotime($value['payment_date'])) == $month && date("Y",strtotime($value['payment_date'])) == $year) 
           {
                $payment_month += $value["paid_amount"];
                $payment_date = date("jS M y",strtotime($value['payment_date']));

                if(!isset($array_month[$payment_date])) $array_month[$payment_date] = 0;
                $array_month[$payment_date] += $value["paid_amount"];
           }

           if(date("Y",strtotime($value['payment_date'])) == $year) 
           {
                $payment_year += $value["paid_amount"];
                $payment_life += $value["paid_amount"];
                if(!isset($array_year[$mon])) $array_year[$mon] = 0;
                $array_year[$mon] += $value["paid_amount"];

                if(isset($this_year_earning[$mon2-1])) $this_year_earning[$mon2-1] += $value["paid_amount"];

                if(!isset($this_year_top[$value['country']])) $this_year_top[$value['country']] = 0;
                $this_year_top[$value['country']] += $value["paid_amount"];
           }

           if(date("Y",strtotime($value['payment_date'])) == $lastyear) 
           {
                 if(isset($last_year_earning[$mon2-1])) $last_year_earning[$mon2-1] += $value["paid_amount"];

                if(!isset($last_year_top[$value['country']])) $last_year_top[$value['country']] = 0;
                $last_year_top[$value['country']] += $value["paid_amount"];
           }
        }
        arsort($this_year_top);
        arsort($last_year_top);

        $data['payment_today'] = $payment_today;
        $data['payment_month'] = $payment_month;
        $data['payment_year'] = $payment_year;
        $data['payment_life'] = $payment_life;
        $data['array_month'] = $array_month;
        $data['array_year'] = $array_year;
        $data['month_names'] = $month_names;
        $data['this_year_earning'] = $this_year_earning;
        $data['last_year_earning'] = $last_year_earning;
        $data['year'] = $year;
        $data['lastyear'] = $lastyear;
        $data['this_year_top'] = $this_year_top;
        $data['last_year_top'] = $last_year_top;
        $data['country_names'] = $this->get_country_names();

        $data['user_data'] = $user_data[0]['total_user'];

        $data['body'] = 'admin/payment/earning_summary';
        $data['page_title'] = $this->lang->line("Earning Summary");

        $config_data=$this->basic->get_data("payment_config");
        $currency=isset($config_data[0]["currency"])?$config_data[0]["currency"]:"USD";
        $currency_icons = $this->currency_icon();
        $data["curency_icon"]= isset($currency_icons[$currency])?$currency_icons[$currency]:"$";
        $data["currency"]= $currency;
        $this->_viewcontroller($data);
    }

    public function transaction_log() // works for both admin and member
    {

        if($this->session->userdata('license_type') != 'double') redirect('home/access_forbidden', 'location');

        $action = isset($_GET['action']) ? $_GET['action'] : ""; // if redirect after purchase
        if($action!="")
        {
            if($action=="cancel") $this->session->set_userdata('payment_cancel',1);
            else if($action=="success") $this->session->set_userdata('payment_success',1);
            redirect('payment/transaction_log','refresh');
        }

        $data['body']='admin/payment/transaction_log';
        $data['page_title']=$this->lang->line("Transaction Log");
        
        $config_data=$this->basic->get_data("payment_config");
        $currency=isset($config_data[0]["currency"])?$config_data[0]["currency"]:"USD";
        $currency_icons = $this->currency_icon();
        $data["curency_icon"]= isset($currency_icons[$currency])?$currency_icons[$currency]:"$";
        $this->_viewcontroller($data);  
    }

    public function transaction_log_data()
    { 
        $this->ajax_check();
        if($this->session->userdata('license_type') != 'double') redirect('home/access_forbidden', 'location');

        $payment_date_range = $this->input->post("payment_date_range");
        $search_value = $_POST['search']['value'];
        $display_columns = array("#","CHECKBOX",'id','receiver_email','first_name', 'last_name', 'payment_type', 'cycle_start_date','cycle_expired_date', 'payment_date','paid_amount');
        $search_columns = array('receiver_email','first_name', 'last_name','paid_amount', 'payment_type','transaction_id');

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $sort_index = isset($_POST['order'][0]['column']) ? strval($_POST['order'][0]['column']) : 2;
        $sort = isset($display_columns[$sort_index]) ? $display_columns[$sort_index] : 'id';
        $order = isset($_POST['order'][0]['dir']) ? strval($_POST['order'][0]['dir']) : 'desc';
        $order_by=$sort." ".$order;

        $where_custom = '';

        if($this->session->userdata('user_type')=='Admin')
        $where_custom="user_id > 0 ";
        else $where_custom="user_id = ".$this->user_id;

        if ($search_value != '') 
        {
            foreach ($search_columns as $key => $value) 
            $temp[] = $value." LIKE "."'%$search_value%'";
            $imp = implode(" OR ", $temp);
            $where_custom .=" AND (".$imp.") ";
        }
        if($payment_date_range!="")
        {
            $exp = explode('|', $payment_date_range);
            $from_date = isset($exp[0])?$exp[0]:"";
            $to_date = isset($exp[1])?$exp[1]:"";
            if($from_date!="Invalid date" && $to_date!="Invalid date")
            $where_custom .= " AND payment_date >= '{$from_date}' AND payment_date <='{$to_date}'";
        }
          
        $table="transaction_history";
        $this->db->where($where_custom);
        $info=$this->basic->get_data($table,$where='',$select='',$join='',$limit,$start,$order_by,$group_by='');
        $this->db->where($where_custom);
        $total_rows_array=$this->basic->count_row($table,$where='',$count=$table.".id",$join='',$group_by='');
        $total_result=$total_rows_array[0]['total_rows'];

        $i=0;
        $base_url=base_url();
        foreach ($info as $key => $value) 
        {
            $info[$i]["cycle_start_date"] = date("jS M y",strtotime($info[$i]["cycle_start_date"]));
            $info[$i]["cycle_expired_date"] = date("jS M y",strtotime($info[$i]["cycle_expired_date"]));
            $info[$i]["payment_date"] = date("jS M y H:i:s",strtotime($info[$i]["payment_date"]));

            if($this->session->userdata('user_type')=="Admin") 
            $info[$i]["receiver_email"] = "<a href='".base_url("admin/edit_user/".$info[$i]["user_id"])."'>".$info[$i]["receiver_email"]."</a>";

            $i++;
        }

        $data['draw'] = (int)$_POST['draw'] + 1;
        $data['recordsTotal'] = $total_result;
        $data['recordsFiltered'] = $total_result;
        $data['data'] = convertDataTableResult($info, $display_columns ,$start,$primary_key="id");

        echo json_encode($data);
    }   

    public function member_payment_history() // kept because not sure if it is called from somewhere
    {
        if ($this->session->userdata('license_type') == 'double' && $this->session->userdata('user_type') == 'Member') 
        redirect('payment/transaction_log', 'location');
        else redirect('home/access_forbidden', 'location');
    }

    public function buy_package()
    {
       if($this->session->userdata('license_type') == 'double' && $this->session->userdata('user_type') == 'Member')
       {
           $data['body'] = "member/buy_package";
           $data['page_title'] = $this->lang->line('Payment');

           $config_data=$this->basic->get_data("payment_config");
           $currency=isset($config_data[0]["currency"])?$config_data[0]["currency"]:"USD";
           $currency_icons = $this->currency_icon();
           $data["currency"]=$currency;
           $data["curency_icon"]= isset($currency_icons[$currency])?$currency_icons[$currency]:"$";

           $data['payment_type'] = isset($config_data[0]['paypal_payment_type'])?$config_data[0]['paypal_payment_type']:"manual";
           $payment_method = $this->basic->get_data('transaction_history', array('where' => array('user_id' => $this->user_id), array('payment_type'), '', '', '', 'payment_date,dsc'));
           $data['payment_method'] = isset($payment_method[0]['payment_type']) ? $payment_method[0]['payment_type'] : 'Paypal';
           $data["payment_package"]=$this->basic->get_data("package",$where=array("where"=>array("is_default"=>"0","price > "=>0,"validity >"=>0,"visible"=>"1")),$select='',$join='',$limit='',$start=NULL,$order_by='CAST(`price` AS SIGNED)');

           $user_info = $this->basic->get_data('users', array('where' => array('id' => $this->user_id)), array('paypal_subscription_enabled', 'last_payment_method'));
           if(!isset($user_info[0])) exit();
           if($user_info[0]['paypal_subscription_enabled'] == '1' ) $data['has_reccuring'] = 'true';
           else $data['has_reccuring'] = 'false';
           $data['last_payment_method'] = $user_info[0]['last_payment_method'];
           $this->_viewcontroller($data);
        }
        else redirect('home/access_forbidden', 'location');

       
    }


    public function payment_button()
    {        
        $this->ajax_check();
        if ($this->session->userdata('license_type') == 'double' && $this->session->userdata('user_type') == 'Member') 
        {
            $config_data=$this->basic->get_data("payment_config");
            if(!isset($config_data[0])) 
            {
                echo '
                <div class="alert alert-warning alert-has-icon">
                  <div class="alert-icon"><i class="far fa-credit-card"></i></div>
                  <div class="alert-body">
                    <div class="alert-title">'.$this->lang->line("Warning").'</div>
                    '.$this->lang->line("No payment method found").'
                  </div>
                </div>';
                exit();
            }
            $config_data = $config_data[0];
            
            if($_POST)
            {
                $cancel_url=base_url()."payment/transaction_log?action=cancel";
                $success_url=base_url()."payment/transaction_log?action=success";
                

                $payment_amount=0;
                $package_name="";
                $package_validity="";
                $package_id=$this->input->post("package");
                $package_data=$this->basic->get_data("package",$where=array("where"=>array("package.id"=>$package_id)));
                if(is_array($package_data) && array_key_exists(0, $package_data))
                {
                    $payment_amount=$package_data[0]["price"];
                    $package_name=$package_data[0]["package_name"];
                    $package_validity=$package_data[0]["validity"];
                    $validity_extra_info=$package_data[0]["validity_extra_info"];
                    $validity_extra_info = explode(',', $validity_extra_info);
                }
                else 
                {
                    // echo $this->lang->line("something went wrong, please try again.");
                    exit();
                }

                $where['where'] = array('deleted'=>'0');
                $payment_config = $this->basic->get_data('payment_config',$where,$select='');
                if(!empty($payment_config)) 
                {
                    $paypal_email = $payment_config[0]['paypal_email'];
                    $currency=$payment_config[0]["currency"];
                    $stripe_secret= $payment_config[0]["stripe_secret_key"];
                    
                } 
                else 
                {
                    $paypal_email = "";
                    $currency="USD";
                }

                
                // $this->paypal_class->mode="live";
                $this->paypal_class->cancel_url=$cancel_url;
                $this->paypal_class->success_url=$success_url;
                $this->paypal_class->notify_url=site_url()."paypal_ipn/ipn_notify";

                // echo $this->session->userdata('license_type');exit;
                // $this->session->set_userdata('license_type', 'double');

                if ($this->session->userdata('license_type') == 'double' && $config_data['paypal_payment_type'] == 'recurring') {

                    $this->paypal_class->a3=$payment_amount;
                    $this->paypal_class->p3=$validity_extra_info[0];
                    $this->paypal_class->t3=$validity_extra_info[1];
                    $this->paypal_class->src='1';
                    $this->paypal_class->sra='1';
                    $this->paypal_class->is_recurring=true;
                }
                else
                    $this->paypal_class->amount=$payment_amount;

                $this->paypal_class->user_id=$this->user_id;
                $this->paypal_class->business_email=$paypal_email;
                $this->paypal_class->currency=$currency;
                $this->paypal_class->package_id=$package_id;
                $this->paypal_class->product_name=$this->config->item("product_name")." : ".$package_name." (".$package_validity." days)";
                
                $pp_button = $st_button = "";
                
                $this->session->set_userdata('stripe_payment_package_id',$package_id);
                $this->session->set_userdata('stripe_payment_amount',$payment_amount);
                
                if($paypal_email!="")
                $pp_button = $this->paypal_class->set_button(); 

                /*****  Stripe Button ******/
                if($stripe_secret!=""){
                $this->stripe_class->description=$this->config->item("product_name")." : ".$package_name." (".$package_validity." days)";
                $this->stripe_class->amount=$payment_amount;
                $this->stripe_class->action_url=site_url()."stripe_action";
                $st_button = $this->stripe_class->set_button();
                }              

                echo 
                '<br>
                <div class="row">
                    <div class="col-6">'.$pp_button.'</div>
                    <div class="col-6 text-right">'.$st_button.'</div>
                </div>';
            }
        }

    }


    public function package_manager()
    {
        if($this->session->userdata('logged_in') == 1 && $this->session->userdata('user_type') != 'Admin') 
        redirect('home/login_page', 'location');
        
        $data['body']='admin/payment/package_list';
        $data['page_title']=$this->lang->line("Package Manager");
        $data['payment_config']=$this->basic->get_data('payment_config');
        $this->_viewcontroller($data);  
    }

    public function package_manager_data()
    { 
        $this->ajax_check();
        if($this->session->userdata('logged_in') == 1 && $this->session->userdata('user_type') != 'Admin') exit();

        $search_value = $_POST['search']['value'];
        $display_columns = array("#",'id', 'package_name','price','validity','is_default');
        $search_columns = array( 'package_name','price','validity');

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $sort_index = isset($_POST['order'][0]['column']) ? strval($_POST['order'][0]['column']) : 1;
        $sort = isset($display_columns[$sort_index]) ? $display_columns[$sort_index] : 'id';
        $order = isset($_POST['order'][0]['dir']) ? strval($_POST['order'][0]['dir']) : 'desc';
        $order_by=$sort." ".$order;

        $where = array();
        if ($search_value != '') 
        {
            $or_where = array();
            foreach ($search_columns as $key => $value) 
            $or_where[$value.' LIKE '] = "%$search_value%";
            $where = array('or_where' => $or_where);
        }
            
        $table="package";
        $info=$this->basic->get_data($table,$where,$select='',$join='',$limit,$start,$order_by,$group_by='');
        $total_rows_array=$this->basic->count_row($table,$where,$count=$table.".id",$join='',$group_by='');
        $total_result=$total_rows_array[0]['total_rows'];

        $data['draw'] = (int)$_POST['draw'] + 1;
        $data['recordsTotal'] = $total_result;
        $data['recordsFiltered'] = $total_result;
        $data['data'] = convertDataTableResult($info, $display_columns ,$start);

        echo json_encode($data);
    }


    public function add_package()
    {       
        $data['body']='admin/payment/add_package';     
        $data['page_title']=$this->lang->line('Add Package');     
        $data['modules']=$this->basic->get_data('modules',$where='',$select='',$join='',$limit='',$start='',$order_by='module_name asc',$group_by='',$num_rows=0);
        $data['payment_config']=$this->basic->get_data('payment_config');
        $data['validity_type'] = array('D' => $this->lang->line('Day'), 'W' => $this->lang->line('Week'), 'M' => $this->lang->line('Month'), 'Y' => $this->lang->line('Year'));
        $this->_viewcontroller($data);
    }


    public function add_package_action() 
    {
        if($this->is_demo == '1')
        {
            echo "<h2 style='text-align:center;color:red;border:1px solid red; padding: 10px'>This feature is disabled in this demo.</h2>"; 
            exit();
        }

        if($this->session->userdata('logged_in') == 1 && $this->session->userdata('user_type') != 'Admin') 
        redirect('home/login_page', 'location');

        if($_SERVER['REQUEST_METHOD'] === 'GET') 
        redirect('home/access_forbidden','location');

        if($_POST)
        {
            $this->form_validation->set_rules('name', '<b>'.$this->lang->line("Package Name").'</b>', 'trim|required');   
            $this->form_validation->set_rules('price', '<b>'.$this->lang->line("Price").'</b>', 'trim|required');
            $this->form_validation->set_rules('validity_amount', '<b>'.$this->lang->line("Validity").'</b>', 'trim|required|integer');   
            $this->form_validation->set_rules('visible', '<b>'.$this->lang->line("Available to Purchase").'</b>', 'trim');
            $this->form_validation->set_rules('highlight', '<b>'.$this->lang->line("Highlighted Package").'</b>', 'trim');
            $this->form_validation->set_rules('modules[]','<b>'.$this->lang->line("Modules").'</b>','trim|required');       
                
            if ($this->form_validation->run() == FALSE)
            {
                $this->add_package(); 
            }
            else
            {
                $validity_type_arr['D'] = 1;
                $validity_type_arr['W'] = 7;
                $validity_type_arr['M'] = 30;
                $validity_type_arr['Y'] = 365;

                $package_name=$this->input->post('name');
                $price=$this->input->post('price');
                $visible=$this->input->post('visible');
                $highlight=$this->input->post('highlight');

                if($visible=='') $visible='0';
                if($highlight=='') $highlight='0';

                $validity_amount=$this->input->post('validity_amount');
                $validity_type=$this->input->post('validity_type');
                $validity = $validity_amount * $validity_type_arr[$validity_type];
                $validity_extra_info = implode(',', array($validity_amount, $validity_type));
                
                $modules=array();
                if(count($this->input->post('modules'))>0)  
                {
                   $modules=$this->input->post('modules');                            
                }

                $bulk_limit=array();
                $monthly_limit=array();

                foreach ($modules as $value) 
                {
                    $monthly_field="monthly_".$value;
                   
                    $val=$this->input->post($monthly_field);
                    if($val=="") $val=0;
                    $monthly_limit[$value]=$val;
               

                    $bulk_field="bulk_".$value;
                    
                    $val=$this->input->post($bulk_field);
                    if($val=="") $val=0;
                    $bulk_limit[$value]=$val;                    
                }



                $modules_str=implode(',',$modules);                        
                               
                $data=array
                (
                    'package_name'=>$package_name,
                    'price'=>$price,
                    'validity'=>$validity,
                    'visible'=>$visible,
                    'highlight'=>$highlight,
                    'validity_extra_info'=>$validity_extra_info,
                    'module_ids'=>$modules_str,
                    'monthly_limit'=>json_encode($monthly_limit),
                    'bulk_limit'=>json_encode($bulk_limit)
                );
                
                if($this->basic->insert_data('package',$data))                                      
                $this->session->set_flashdata('success_message',1);   
                else    
                $this->session->set_flashdata('error_message',1);     
                
                redirect('payment/package_manager','location');                 
                
            }
        }   
    }


    public function details_package($id=0)
    {        
        if($this->session->userdata('logged_in') == 1 && $this->session->userdata('user_type') != 'Admin') 
        redirect('home/login_page', 'location');

        if($id==0)
        redirect('home/access_forbidden','location');

        $data['body']='admin/payment/details_package';        
        $data['page_title']=$this->lang->line("Package Details");        
        $data['modules']=$this->basic->get_data('modules',$where='',$select='',$join='',$limit='',$start='',$order_by='module_name asc',$group_by='',$num_rows=0);
        $data['value']=$this->basic->get_data('package',$where=array("where"=>array("id"=>$id)));
        $data['payment_config']=$this->basic->get_data('payment_config');
        $data['validity_type'] = array('D' => $this->lang->line('Days'), 'W' => $this->lang->line('Weeks'), 'M' => $this->lang->line('Months'), 'Y' => $this->lang->line('Years'));

        $validity_days = $data['value'][0]['validity'];

        if ($validity_days % 365 == 0) {

            $data['validity_type_info'] = 'Y';
            $data['validity_amount'] = $validity_days / 365;
        }
        else if ($validity_days % 30 == 0) {

            $data['validity_type_info'] = 'M';
            $data['validity_amount'] = $validity_days / 30;
        }
        else if ($validity_days % 7 == 0) {

            $data['validity_type_info'] = 'W';
            $data['validity_amount'] = $validity_days / 7;
        }
        else {

            $data['validity_type_info'] = 'D';
            $data['validity_amount'] = $validity_days;
        }

        $this->_viewcontroller($data);  
    }


    public function edit_package($id=0)
    {       
        if($this->session->userdata('logged_in') == 1 && $this->session->userdata('user_type') != 'Admin') 
        redirect('home/login_page', 'location');

        if($id==0) 
        redirect('home/access_forbidden','location');

        $data['body']='admin/payment/edit_package';     
        $data['page_title']=$this->lang->line('Edit Package');     
        $data['modules']=$this->basic->get_data('modules',$where='',$select='',$join='',$limit='',$start='',$order_by='module_name asc',$group_by='',$num_rows=0);
        $data['value']=$this->basic->get_data('package',$where=array("where"=>array("id"=>$id)));
        $data['payment_config']=$this->basic->get_data('payment_config');
        $data['validity_type'] = array('D' => $this->lang->line('Days'), 'W' => $this->lang->line('Weeks'), 'M' => $this->lang->line('Months'), 'Y' => $this->lang->line('Years'));

        $validity_days = $data['value'][0]['validity'];

        if ($validity_days % 365 == 0) {

            $data['validity_type_info'] = 'Y';
            $data['validity_amount'] = $validity_days / 365;
        }
        else if ($validity_days % 30 == 0) {

            $data['validity_type_info'] = 'M';
            $data['validity_amount'] = $validity_days / 30;
        }
        else if ($validity_days % 7 == 0) {

            $data['validity_type_info'] = 'W';
            $data['validity_amount'] = $validity_days / 7;
        }
        else {

            $data['validity_type_info'] = 'D';
            $data['validity_amount'] = $validity_days;
        }

        $this->_viewcontroller($data);
    }


    public function edit_package_action() 
    {
        if($this->is_demo == '1')
        {
            echo "<h2 style='text-align:center;color:red;border:1px solid red; padding: 10px'>This feature is disabled in this demo.</h2>"; 
            exit();
        }

        if($this->session->userdata('logged_in') == 1 && $this->session->userdata('user_type') != 'Admin') 
        redirect('home/login_page', 'location');

        if($_SERVER['REQUEST_METHOD'] === 'GET') 
        redirect('home/access_forbidden','location');

        if($_POST)
        {
            $validity_type_arr['D'] = 1;
            $validity_type_arr['W'] = 7;
            $validity_type_arr['M'] = 30;
            $validity_type_arr['Y'] = 365;

            $id=$this->input->post("id");
            $this->form_validation->set_rules('name', '<b>'.$this->lang->line("Package Name").'</b>', 'trim|required');
            $this->form_validation->set_rules('visible', '<b>'.$this->lang->line("Available to Purchase").'</b>', 'trim');
            $this->form_validation->set_rules('highlight', '<b>'.$this->lang->line("Highlighted Package").'</b>', 'trim');
            $this->form_validation->set_rules('modules[]','<b>'.$this->lang->line("modules").'</b>','trim');   
            $this->form_validation->set_rules('price', '<b>'.$this->lang->line("price").'</b>', 'trim|required');    
            
            if(($this->input->post("is_default")=="1" && $this->input->post("price")=="Trial") || $this->input->post("is_default")=="0")  
            $this->form_validation->set_rules('validity_amount', '<b>'.$this->lang->line("Validity").'</b>', 'trim|required|integer');   
            
            if ($this->form_validation->run() == FALSE)
            {
                $this->edit_package($id); 
            }
            else
            {
                $package_name=$this->input->post('name');
                $price=$this->input->post('price');
                $visible=$this->input->post('visible');
                $highlight=$this->input->post('highlight');

                if($visible=='') $visible='0';
                if($highlight=='') $highlight='0';

                // $validity=$this->input->post('validity');
                $validity_amount=$this->input->post('validity_amount');
                $validity_type=$this->input->post('validity_type');
                $validity = $validity_amount * $validity_type_arr[$validity_type];
                $validity_extra_info = implode(',', array($validity_amount, $validity_type));
                
                $modules=array();
                if(count($this->input->post('modules'))>0)  
                {
                   $modules=$this->input->post('modules');                            
                }

                $bulk_limit=array();
                $monthly_limit=array();

                foreach ($modules as $value) 
                {
                    $monthly_field="monthly_".$value;
                   
                    $val=$this->input->post($monthly_field);
                    if($val=="") $val=0;
                    $monthly_limit[$value]=$val;
               

                    $bulk_field="bulk_".$value;
                    
                    $val=$this->input->post($bulk_field);
                    if($val=="") $val=0;
                    $bulk_limit[$value]=$val;                    
                }


                $modules_str=implode(',',$modules);                        
                               
                if($this->input->post("is_default")=="1" && $this->input->post("price")=="0") 
                $validity="0"; 
                $data=array
                (
                    'package_name'=>$package_name,
                    'validity'=>$validity,
                    'visible'=>$visible,
                    'highlight'=>$highlight,
                    'validity_extra_info'=>$validity_extra_info,
                    'module_ids'=>$modules_str,
                    'price'=>$price,
                    'monthly_limit'=>json_encode($monthly_limit),
                    'bulk_limit'=>json_encode($bulk_limit)
                );
                
                if($this->basic->update_data('package',array("id"=>$id),$data))                                      
                $this->session->set_flashdata('success_message',1);   
                else    
                $this->session->set_flashdata('error_message',1);   


                // print_r($data); exit();
                
                redirect('payment/package_manager','location');                 
                
            }
        }   
    }

    public function delete_package($id=0)
    {
        $this->ajax_check();
        if($this->is_demo == '1')
        {
            echo json_encode(array("status"=>"0","message"=>"This feature is disabled in this demo.")); 
            exit();
        }        
        if($this->session->userdata('logged_in') == 1 && $this->session->userdata('user_type') != 'Admin') exit();
        if($id==0) exit();

        if($this->basic->update_data('package',array("id"=>$id),array("deleted"=>"1")))                                      
        echo json_encode(array("status"=>"1","message"=>$this->lang->line("Package has been deleted successfully"))); 
        else echo json_encode(array("status"=>"0","message"=>$this->lang->line("Something went wrong, please try again")));
    } 


    public function usage_history()
    {        
        if($this->session->userdata('user_type') != 'Member') 
        redirect('home/login_page', 'location');

        $current_month = date("n");
        $current_year = date("Y");

        $info = $this->basic->get_data($table="modules", $where="", $select = "usage_log.*,modules.module_name,modules.id as module_id,limit_enabled,extra_text,bulk_limit_enabled",$join=array('usage_log'=>"usage_log.module_id=modules.id AND user_id =".$this->session->userdata("user_id")." AND usage_month=".$current_month." AND usage_year=".$current_year.",left"),$limit='',$start=NULL,$order_by='module_name asc');  

        $package_info=$this->session->userdata("package_info");

        // module count of not monthly
        $this->db->select('sum(usage_count) as usage_count,module_id');
        $this->db->where('user_id', $this->user_id);
        $this->db->group_by('module_id');
        $not_monthy_info = $this->db->get('usage_log')->result_array();
        $not_monthy_module_info=array(); 
        foreach ($not_monthy_info as $key => $value) 
        {
            $not_monthy_module_info[$value['module_id']]=$value['usage_count'];
        }
        $data['not_monthy_module_info']=$not_monthy_module_info;

        $monthly_limit='';

        if(isset($package_info["monthly_limit"]))  $monthly_limit=$package_info["monthly_limit"];
        $bulk_limit=array();
        if(isset($package_info["bulk_limit"]))  $bulk_limit=$package_info["bulk_limit"];
        $package_name="No Package";
        if(isset($package_info["package_name"]))  $package_name=$package_info["package_name"];
        $validity="0";
        if(isset($package_info["validity"]))  $validity=$package_info["validity"];
        $price="0";
        if(isset($package_info["price"]))  $price=$package_info["price"];

        $data['info']=$info;
        $data['monthly_limit']=json_decode($monthly_limit,true);
        $data['bulk_limit']=json_decode($bulk_limit,true);
        $data['package_name']=$package_name;
        $data['validity']=$validity;
        $data['price']=$price;

        $config_data=$this->basic->get_data("payment_config");
        $currency=isset($config_data[0]["currency"])?$config_data[0]["currency"]:"USD";
        $currency_icons = $this->currency_icon();
        $data["currency"]=$currency;
        $data["curency_icon"]= isset($currency_icons[$currency])?$currency_icons[$currency]:"$";
        
        $data['body'] = 'member/usage_log';
        $data['page_title'] = $this->lang->line("Usage Log");

        $this->_viewcontroller($data);
    }

   

   
    
}