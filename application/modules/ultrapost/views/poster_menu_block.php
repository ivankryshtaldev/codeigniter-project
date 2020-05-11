 <section class="section">
  <div class="section-header">
    <h1><i class="fas fa-share-square"></i> <?php echo $page_title; ?></h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item"><?php echo $page_title; ?></div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">

      <?php if($this->session->userdata('user_type') == 'Admin' || in_array(223,$this->module_access)) : ?>
      <div class="col-lg-6">
        <div class="card card-large-icons">
          <div class="card-icon text-primary">
            <i class="fas fa-list"></i>
          </div>
          <div class="card-body">
            <h4><?php echo $this->lang->line("Text/Link/Image/Video Post"); ?></h4>
            <p><?php echo $this->lang->line("Text, Link, Image, Video Poster..."); ?></p>
            <a href="<?php echo base_url("ultrapost/text_image_link_video"); ?>" class="card-cta"><?php echo $this->lang->line("Campaign List"); ?> <i class="fas fa-chevron-right"></i></a>
          </div>
        </div>
      </div>
      <?php endif; ?>


      <?php if($this->session->userdata('user_type') == 'Admin' || in_array(220,$this->module_access)) : ?>
      <div class="col-lg-6">
        <div class="card card-large-icons">
          <div class="card-icon text-primary">
            <i class="fas fa-hand-point-up"></i>
          </div>
          <div class="card-body">
            <h4><?php echo $this->lang->line("CTA Post"); ?></h4>
            <p><?php echo $this->lang->line("Call to Action Poster"); ?></p>
            <a href="<?php echo base_url("ultrapost/cta_post"); ?>" class="card-cta"><?php echo $this->lang->line("Campaign List"); ?> <i class="fas fa-chevron-right"></i></a>
          </div>
        </div>
      </div>
      <?php endif; ?>

      
      <?php if($this->session->userdata('user_type') == 'Admin' || in_array(222,$this->module_access)) : ?>
      <div class="col-lg-6">
        <div class="card card-large-icons">
          <div class="card-icon text-primary">
            <i class="fas fa-video"></i>
          </div>
          <div class="card-body">
            <h4><?php echo $this->lang->line("Carousel/Video Post"); ?></h4>
            <p><?php echo $this->lang->line("Carousel, Video Poster..."); ?></p>
            <a href="<?php echo base_url("ultrapost/carousel_slider_post"); ?>" class="card-cta"><?php echo $this->lang->line("Campaign List"); ?> <i class="fas fa-chevron-right"></i></a>
          </div>
        </div>
      </div>
      <?php endif; ?>


      <?php if($this->session->userdata('user_type') == 'Admin' || in_array(256,$this->module_access)) : ?>
      <div class="col-lg-6">
        <div class="card card-large-icons">
          <div class="card-icon text-primary">
            <i class="fas fa-rss"></i>
          </div>
          <div class="card-body">
            <h4><?php echo $this->lang->line("RSS Auto Post"); ?></h4>
            <p><?php echo $this->lang->line("RSS Auto Poster"); ?></p>
            <a href="<?php echo base_url("autoposting/settings"); ?>" class="card-cta"><?php echo $this->lang->line("Campaign List"); ?> <i class="fas fa-chevron-right"></i></a>
          </div>
        </div>
      </div>
    <?php endif; ?>

    </div>
  </div>
</section>