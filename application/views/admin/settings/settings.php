 <section class="section">
  <div class="section-header">
    <h1><i class="fas fa-cogs"></i> <?php echo $page_title; ?></h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item"><?php echo $this->lang->line("System"); ?></div>
      <div class="breadcrumb-item"><?php echo $page_title; ?></div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">
      <div class="col-lg-6">
        <div class="card card-large-icons">
          <div class="card-icon text-primary">
            <i class="fas fa-toolbox"></i>
          </div>
          <div class="card-body">
            <h4><?php echo $this->lang->line("General"); ?></h4>
            <p><?php echo $this->lang->line("brand, logo, language, phpmail, https, upload..."); ?></p>
            <a href="<?php echo base_url("admin/general_settings"); ?>" class="card-cta"><?php echo $this->lang->line("Change Setting"); ?> <i class="fas fa-chevron-right"></i></a>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card card-large-icons">
          <div class="card-icon text-primary">
            <i class="fas fa-store"></i>
          </div>
          <div class="card-body">
            <h4><?php echo $this->lang->line("Front-end"); ?></h4>
            <p><?php echo $this->lang->line("Hide, theme, social, review, video..."); ?></p>
            <a href="<?php echo base_url("admin/frontend_settings"); ?>" class="card-cta"><?php echo $this->lang->line("Change Setting"); ?> <i class="fas fa-chevron-right"></i></a>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card card-large-icons">
          <div class="card-icon text-primary">
            <i class="fas fa-envelope"></i>
          </div>
          <div class="card-body">
            <h4><?php echo $this->lang->line("SMTP Settings"); ?></h4>
            <p><?php echo $this->lang->line("SMTP email settings"); ?></p>
            <a href="<?php echo base_url("admin/smtp_settings"); ?>" class="card-cta"><?php echo $this->lang->line("Change Setting"); ?> <i class="fas fa-chevron-right"></i></a>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card card-large-icons">
          <div class="card-icon text-primary">
            <i class="fas fa-id-card"></i>
          </div>
          <div class="card-body">
            <h4><?php echo $this->lang->line("Email Template"); ?></h4>
            <p><?php echo $this->lang->line("Signup, change password, expiry, payment..."); ?></p>
            <a href="<?php echo base_url("admin/email_template_settings"); ?>" class="card-cta"><?php echo $this->lang->line("Change Setting"); ?> <i class="fas fa-chevron-right"></i></a>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card card-large-icons">
          <div class="card-icon text-primary">
           <i class="fas fa-chart-pie"></i>
          </div>
          <div class="card-body">
            <h4><?php echo $this->lang->line("Analytics"); ?></h4>
            <p><?php echo $this->lang->line("Gogole analytics, Facebook pixel code..."); ?></p>
            <a href="<?php echo base_url("admin/analytics_settings"); ?>" class="card-cta"><?php echo $this->lang->line("Change Setting"); ?> <i class="fas fa-chevron-right"></i></a>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card card-large-icons">
          <div class="card-icon text-primary">
           <i class="fab fa-adversal"></i>
          </div>
          <div class="card-body">
            <h4><?php echo $this->lang->line("Advertisement"); ?></h4>
            <p><?php echo $this->lang->line("Banner, potrait, landscape image ads..."); ?></p>
            <a href="<?php echo base_url("admin/advertisement_settings"); ?>" class="card-cta"><?php echo $this->lang->line("Change Setting"); ?> <i class="fas fa-chevron-right"></i></a>
          </div>
        </div>
      </div>

    

    </div>
  </div>
</section>