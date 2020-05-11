<section class="section">
  <div class="section-header">
    <h1><i class="fas fa-tasks"></i> <?php echo $page_title; ?></h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item"><?php echo $this->lang->line("System"); ?></div>      
      <div class="breadcrumb-item"><?php echo $page_title; ?></div>
    </div>
  </div>

  <?php $this->load->view('admin/theme/message'); ?>

  <div class="section-body">
  	<div class="row">
      <div class="col-12">
      	<div class="card">
	                  
		  	<?php
			$text= $this->lang->line("Generate API Key");
			$get_key_text=$this->lang->line("Get Your API Key");
			if(isset($api_key) && $api_key!="")
			{
				$text=$this->lang->line("Re-generate API Key");
				$get_key_text=$this->lang->line("Your API Key");
			}
			if($this->is_demo=='1') $api_key='xxxxxxxxxxxxxxxxxxxxxxxxxx';
			?>

			<form class="form-horizontal" enctype="multipart/form-data" action="<?php echo site_url().'cron_job/get_api_action';?>" method="GET">
				<div class="card-header">
		            <h4><i class="fas fa-key"></i> <?php echo $get_key_text; ?></h4>
		          </div>
		          <div class="card-body">
		            <h4><?php echo $api_key; ?></h4>
		            <?php if($api_key=="") echo $this->lang->line("Every cron url must contain the API key for authentication purpose. Generate your API key to see the cron job list."); ?>
		          </div>
		          <div class="card-footer bg-whitesmoke">
		          	<button type="submit" name="button" class="btn btn-primary btn-lg btn <?php if($this->is_demo=='1') echo 'disabled';?>"><i class="fas fa-redo"></i> <?php echo $text; ?></button>
		          </div>
		        </div>
		    </form>


			<?php
			if($api_key!="") 
			{ ?>
				<div class="card">
                  <div class="card-header">
                    <h4><i class="fas fa-circle"></i> <?php echo $this->lang->line("Membership Expiration Alert & Delete Junk Data");?> <code><?php echo $this->lang->line("Once/Day"); ?></code></h4>
                  </div>
                  <div class="card-body">
                    <pre class="language-javascript"><code class="dlanguage-javascript"><span class="token keyword"><?php echo "curl ".site_url("cron_job/membership_alert_delete_junk_data")."/".$api_key." >/dev/null 2>&1"; ?></span></code></pre>
                  </div>
                </div>
          

                <div class="card">
                  <div class="card-header">
                    <h4><i class="fas fa-circle"></i> 
                      <?php echo $this->lang->line("Subscriber Background Scan & Migrated Bot Subscriber Profile Info Update");?>
                        <?php if($this->basic->is_exist("add_ons",array("project_id"=>30))) echo " & ".$this->lang->line("Sequence Message Broadcast (Daily)"); ?> 
                      <code><?php echo $this->lang->line("Once/5 Minutes"); ?></code></h4>
                  </div>
                  <div class="card-body">
                    <pre class="language-javascript"><code class="dlanguage-javascript"><span class="token keyword"><?php echo "curl ".site_url("cron_job/background_scanning_update_subscriber_info")."/".$api_key." >/dev/null 2>&1"; ?></span></code></pre>
                  </div>
                </div>

                <div class="card">
                  <div class="card-header">
                    <h4><i class="fas fa-circle"></i> 
                      <?php echo $this->lang->line("Messenger Broadcasting");?>
                      <?php if($this->basic->is_exist("add_ons",array("project_id"=>30))) echo " & ".$this->lang->line("Sequence Message Broadcast (Hourly)"); ?> 
                      <code><?php echo $this->lang->line("Once/Minute"); ?></code></h4>
                  </div>
                  <div class="card-body">
                    <pre class="language-javascript"><code class="dlanguage-javascript"><span class="token keyword"><?php echo "curl ".site_url("cron_job/braodcast_message")."/".$api_key." >/dev/null 2>&1"; ?></span></code></pre>
                  </div>
                </div>

                <div class="card">
                  <div class="card-header">
                    <h4>
                      <i class="fas fa-circle"></i> 
                      <?php echo $this->lang->line("Auto Comment on Post");?>
                        <?php if($this->basic->is_exist("add_ons",array("project_id"=>29))) : ?>
                        <?php echo ', '.$this->lang->line("Comment Bulk Tag");?>,
                        <?php echo $this->lang->line("Bulk Comment Reply");?>,
                        <?php echo $this->lang->line("Auto Share on Post");?> &
                        <?php echo $this->lang->line("Auto Like on Post");?>
                      <?php endif; ?>
                      <code><?php echo $this->lang->line("Once/2 Minutes"); ?></code></h4>
                  </div>
                  <div class="card-body">
                    <pre class="language-javascript"><code class="dlanguage-javascript"><span class="token keyword"><?php echo "curl ".site_url("cron_job/auto_comment_on_post")."/".$api_key." >/dev/null 2>&1"; ?></span></code></pre>
                  </div>
                </div>

                <div class="card">
                  <div class="card-header">
                    <h4><i class="fas fa-circle"></i> <?php echo $this->lang->line("Facebook Posting");?> <code><?php echo $this->lang->line("Once/5 Minutes"); ?></code></h4>
                  </div>
                  <div class="card-body">
                    <pre class="language-javascript"><code class="dlanguage-javascript"><span class="token keyword"><?php echo "curl ".site_url("cron_job/publish_post")."/".$api_key." >/dev/null 2>&1"; ?></span></code></pre>
                  </div>
                </div>
               

                <div class="card">
                  <div class="card-header">
                    <h4><i class="fas fa-circle"></i> <?php echo $this->lang->line("Download Subscriber Avatar (optional)");?> <i class="fas fa-info-circle pointer text-warning" title="<?php echo $this->lang->line('This will download subscriber profile picture in your server which may take a lot of space. Do not set this cron job if your server space is not large enough.'); ?>" data-toggle="tooltip"></i> <code><?php echo $this->lang->line("Once/2 Hours"); ?></code></h4>
                  </div>
                  <div class="card-body">
                    <pre class="language-javascript"><code class="dlanguage-javascript"><span class="token keyword"><?php echo "curl ".site_url("cron_job/download_subscriber_avatar")."/".$api_key." >/dev/null 2>&1"; ?></span></code></pre>
                  </div>
                </div>


			<?php }?>
	  </div>
	</div>
  </div>
</section>