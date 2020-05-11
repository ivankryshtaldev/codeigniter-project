<!DOCTYPE html>
<html>
	<head>
		<?php 
		include(FCPATH.'application/views/include/css_include_back.php'); 
		include(FCPATH.'application/views/include/js_include_back.php'); 
		?>		
		<script type="text/javascript">   
		  // $(window).load(function() {
		  // $(".preloading_body").fadeOut("slow");
		  // });

		  window.addEventListener('load', function () {
		$(".preloading_body").fadeOut("slow");
		}, false);
		</script>

		<style type="text/css">
		body
		{
		    overflow:hidden !important;
		    height:100% !important;
		    background: #fff !important;
		}

		.preloading_body i{font-size:40px;display: table-cell; vertical-align: middle;padding:30px 0;}
		
		.preloading_body {
			height: 100%;width:100%;display: table;
		}

		/* loader */
		</style>
	</head>
	<body>
		<div class="text-center preloading_body">
		  <i class="fas fa-spinner fa-spin blue text-center"></i>
		</div>
		<div style="background: #fff !important;"> 
			<?php $this->load->view($body); ?>
		</div>
	</body>
</html>