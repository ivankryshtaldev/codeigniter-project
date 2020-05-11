<?php

require_once("Home.php"); // including home controller

/**
* class config
* @category controller
*/
class My_files extends Home
{
    public $user_id;
    public $user_email;
    public $signuplist_db;

    /**
    * load constructor method
    * @access public
    * @return void
    */
    public function __construct()
    {
        parent::__construct();
        if ($this->session->userdata('logged_in') != 1){
            redirect('home/login_page', 'location');
        }
        $this->user_id=$this->session->userdata('user_id');
        $this->user_email=$this->session->userdata('user_login_email');
        $this->signuplist_db = $this->load->database('signuplist', TRUE);
    
        set_time_limit(0);
        $this->important_feature();
        $this->member_validity();     
    }

    public function index(){
        // If file upload form submitted
        if($this->input->post('fileSubmit') && !empty($_FILES['files']['name'])){
            $filesCount = count($_FILES['files']['name']);
            for($i = 0; $i < $filesCount; $i++){
                $_FILES['file']['name']     = $_FILES['files']['name'][$i];
                $_FILES['file']['type']     = $_FILES['files']['type'][$i];
                $_FILES['file']['tmp_name'] = $_FILES['files']['tmp_name'][$i];
                $_FILES['file']['error']     = $_FILES['files']['error'][$i];
                $_FILES['file']['size']     = $_FILES['files']['size'][$i];
                
                // File upload configuration
                $browser_path = $this->config->item("file_browser_path");
                $url = $this->input->post('fileupload');
                // $url = join(" ",$url);
                $dir = $url;
                // $dir = $browser_path . $browser_root;

                $config['upload_path'] = $dir;
                $config['allowed_types'] = 'jpg|jpeg|png|gif|pdf|xlsx|xls';
                
                // Load and initialize upload library
                $this->load->library('upload', $config);
                $this->upload->initialize($config);

                // Upload file to server
                if($this->upload->do_upload('file')){
                    // Uploaded file data
                    $fileData = $this->upload->data();
                    $uploadData[$i]['file_name'] = $fileData['file_name'];
                    $uploadData[$i]['uploaded_on'] = date("Y-m-d H:i:s");
                }
            }
            redirect('my_files');
        }

        $data['body'] = 'my_files/file_browser';
        $data['title'] = 'My Files';
        $data['page_title'] = 'My Files';
        $this->_viewcontroller($data);
    }

    public function file_list(){
        $result = $this->signuplist_db->query("select * from list1 where email = '". $this->user_email ."'");
        $email_id = 0;
        if(count($result->result())){
            $email_id = $result->result()[0]->id;
        } else {
            $this->signuplist_db->query("insert into list1 (email)  values ('". $this->user_email ."')");
            $email_id = $this->signuplist_db->insert_id();
        }

        $browser_path = $this->config->item("file_browser_path");
        $browser_root = $email_id;
        $dir   = $browser_path . $browser_root;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $items = $this->scan($dir);

        header("Content-Type: application/json");
        echo json_encode([
            "name"  => $browser_root,
            "type"  => 'folder',
            "path"  => $dir,
            "items" => $items            
        ]);
        die;
    }

    private function scan($dir){
        $files = [];

        if (file_exists($dir)) {

            foreach (scandir($dir) as $f) {
                if (!$f || $f[0] == '.') {
                    continue; // ignore hidden files
                }

                if (is_dir($dir . '/' . $f)) {
                    // folder
                    $files[] = [
                       'name'  => $f,
                       'type'  => 'folder',
                       'path'  => $dir . '/' . $f,
                       'items' => $this->scan($dir . '/' . $f) // recursively get the contents of the folder
                    ];
                } else {
                    // file
                    $files[] = [
                        'name' => $f,
                        'type' => "file",
                        'path' => $dir . '/' . $f,
                        'size' => filesize($dir . '/' . $f)
                    ];
                }
            }

        }

        return $files;        
    }

    public function view($my_file){
        $result = $this->signuplist_db->query("select * from list1 where email = '". $this->user_email ."'");
        $email_id = 0;
        if(count($result->result())){
            $email_id = $result->result()[0]->id;
        }

        $browser_path = $this->config->item("file_browser_path");
        $browser_root = $email_id;
        $filename   = $browser_path . $browser_root . "/$my_file";
        if (!file_exists($filename)) {
            echo "<h1 style='margin: 50px 0; text-align: center;'>There is no file.</h1>";
            die;
        }

        $file_extension = strtolower(substr(strrchr($filename,"."),1));
        switch( $file_extension ) {
            case "gif": $ctype="image/gif"; break;
            case "png": $ctype="image/png"; break;
            case "jpeg":
            case "jpg": $ctype="image/jpeg"; break;
            case "pdf": $ctype="application/pdf"; break;
            default:
        }

        header('Content-type: ' . $ctype);
        $image = file_get_contents($filename);
        echo $image;
    }
}