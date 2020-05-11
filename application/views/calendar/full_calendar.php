<style type="text/css">
	
		.fc-button-group button:focus{
			outline: none;
			
		}

    .fc-icon-left-single-arrow:after {
        font-size: 32px !important; 
        top: -15% !important; 
    }

    .fc-icon-right-single-arrow:after {
        font-size: 32px !important; 
        top: -15% !important; 
    }

</style>


<!-- fullCalendar -->
<link rel="stylesheet" href="<?php echo base_url();?>/plugins/fullcalendar/fullcalendar.min.css">
<!-- full calender -->


<section class="section section_custom">
  <div class="section-header">
    <h1><i class='fa fa-calendar'></i> <?php echo $this->lang->line("activity calendar"); ?></h1>
  </div>


  <div class="section-body">
    <div class="card">
      <div class="card-body">
        <div id="su"></div>
      </div>
    </div>
  </div>
</section>


<script src="<?php echo base_url();?>plugins/fullcalendar/fullcalendar.min.js"></script>
 <script>
   
   $(function () {



     /* initialize the calendar
      -----------------------------------------------------------------*/
     //Date for the calendar events (dummy data)
     
     var events = <?php echo json_encode($data) ?>;

     var date = new Date()
     var d    = date.getDate(),
         m    = date.getMonth(),
         y    = date.getFullYear()


     $('#su').fullCalendar({
       header    : {
         left  : 'prev,next today',
         center: 'title',
         right : 'month,agendaWeek,agendaDay'
       },
       buttonText: {
         today: 'today',
         month: 'month',
         week : 'week',
         day  : 'day'
       },
       eventRender: function(eventObj, $el) {
         $el.popover({
           title: eventObj.title,
           content: eventObj.description,
           trigger: 'hover',
           placement: 'top',
           container: 'body',
           html:true
         });
       },
       //Random default events
       events    : events,
       editable  : true,
       droppable : true, // this allows things to be dropped onto the calendar !!!

     })


   })
 </script>