<?php
require_once("../../interface/globals.php");
require_once("$srcdir/calendar.inc");
require_once("$srcdir/patient.inc");
require_once("$srcdir/appointments.inc.php");
require_once("$srcdir/formatting.inc.php");

$dateFormat = DateFormatRead();

if ($_SESSION['authorizeduser'] == 1) {
  $facilities = getFacilities();
} else {
  $facilities = getUserFacilities($_SESSION['authId']);
}

$providers = getProviderInfo();

// used to filter based on keywords in the form
function keywordFilter($arr) {
  if($_POST['keyword'] != "") {
    $keyword = $_POST['keyword'];
    $fmlName = $arr['fname'] . " " . $arr['mname'] . " " . $arr['lname'];
    $flName = $arr['fname'] . " " . $arr['lname'];
    return preg_match("/$keyword/i", $fmlName) || preg_match("/$keyword/i", $flName) || $keyword == $arr['pc_pid'];
  }
  else return true;
}
?>
<html>
<head>
  <link href="<?php echo $GLOBALS['standard_js_path'] ?>bootstrap-3-3-4/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="<?php echo $GLOBALS['standard_js_path'] ?>jquery-datetimepicker-2-5-4/build/jquery.datetimepicker.min.css" rel="stylesheet" />
  <link href="css/search.css" rel="stylesheet" />
  
  <script src='full_calendar/lib/jquery.min.js'></script>
  <script src='full_calendar/lib/moment.min.js'></script>
  <script src="<?php echo $GLOBALS['standard_js_path'] ?>jquery-datetimepicker-2-5-4/build/jquery.datetimepicker.full.min.js"></script>
  <script type="text/javascript" src="../../library/dialog.js"></script>
</head>
<body>
  
  <div class="container-fluid">
    <div class="row">
      <h2><button type="button" class="btn btn-default btn-sm" onclick="window.location.href='index.php'">Back to Calendar</button></h2>
      <h3>Search</h3>
    </div>
    
    <form class="form-horizontal"  action="" method="POST">
      <div class="form-group">
        <label class="control-label col-sm-2" for="keyword">Keyword</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" id="keyword" name="keyword" placeholder="Name or ID of patient (optional)" value="<?php echo $_POST['keyword'];?>">
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-sm-2" for="startDate">Start</label>
        <div class="col-sm-4">
          <input type="text" class="form-control" id="startDate" name="startDate" placeholder="Date" required value="<?php echo $_POST['startDate'];?>">
        </div>
        <label class="control-label col-sm-2" for="endDate">End</label>
        <div class="col-sm-4">
          <input type="text" class="form-control" id="endDate" name="endDate" placeholder="Date" required value="<?php echo $_POST['endDate'];?>">
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-sm-2" for="provider_id">Provider</label>
        <div class="col-sm-10">
          <select class="form-control" id="provider_id" name="provider_id">
            <option value="0">All Providers</option>
            <?php
            foreach($providers as $provider) {
              $id = $provider['id'];
              $selected = ($_POST['provider_id'] == $id) ? "selected" : "";
              echo "<option value='$id' $selected>" . htmlspecialchars($provider['lname'],ENT_QUOTES) . ", " . htmlspecialchars($provider['fname'],ENT_QUOTES) . "</option>\n";
            }
            ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-sm-2" for="facility_id">Facility</label>
        <div class="col-sm-10">
          <select class="form-control" id="facility_id" name="facility_id">
            <option value="0">All Facilities</option>
            <?php
            foreach($facilities as $facility) {
              $id = $facility['id'];
              $selected = ($_POST['facility_id'] == $id) ? "selected" : "";
              echo "<option value='$id' $selected>" . htmlspecialchars($facility['name'],ENT_QUOTES) . "</option>\n";
            }
            ?>
          </select>
        </div>
      </div>
      <div class="form-group"> 
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" class="btn btn-default" name="submit" value="1">Submit</button>
        </div>
      </div>
    </form>
    
    <!-- if search performed -->
    <?php if($_SERVER['REQUEST_METHOD'] == 'POST') { 
      $pid = ($_POST['provider_id'] == "0") ? null : $_POST['provider_id'];
      $fid = ($_POST['facility_id'] == "0") ? null : $_POST['facility_id'];
      $appointments = fetchAppointments(fixDate($_POST['startDate']), fixDate($_POST['endDate']), null, $pid, $fid);
      
      // filter the appointments using the keyword
      $filteredAppointments = array_filter($appointments, "keywordFilter");
    ?>
    <table class="table table-hover">
      <tr>
        <th>Date</th>
        <th>Time</th>
        <th>Provider</th>
        <th>Patient</th>
        <th>Category</th>
      </tr>
      <?php foreach($filteredAppointments as $appointment) {
        $eid = $appointment['pc_eid'];
        $pid = $appointment['pc_pid'];
        $date = $appointment['pc_eventDate'];
        echo "<tr class='resultRow' onclick='openEvent(\"$eid\", \"$date\")'>";
        echo "<td>" . oeFormatShortDate($appointment['pc_eventDate']) . "</td>";
        echo "<td>" . oeFormatTime($appointment['pc_startTime']) . " - " . oeFormatTime($appointment['pc_endTime']) . "</td>";
        echo "<td>" . $appointment['ulname'] . ", " . $appointment['ufname'] . "</td>";
        echo "<td onclick='goPid($pid)' title='Go to Patient'><a href='javascript:;'>" . $appointment['lname'] . ", " . $appointment['fname'] . "</a></td>";
        echo "<td>" . $appointment['pc_title'] . "</td>";
        echo "</tr>";
      }
      ?>
    </table>
    <!-- end if -->
    <?php } ?>
  </div>
  
  <script>
    $('#startDate').datetimepicker({ 
      timepicker: false,
      format: '<?php echo $dateFormat; ?>',
      formatDate: '<?php echo $dateFormat; ?>',
      onShow:function( ct ){
        this.setOptions({
          maxDate: $('#endDate').val() ? $('#endDate').val() : false
       })
      }
    });
    $('#endDate').datetimepicker({ 
      timepicker: false,
      format: '<?php echo $dateFormat; ?>',
      formatDate: '<?php echo $dateFormat; ?>',
      onShow:function( ct ){
        this.setOptions({
          minDate: $('#startDate').val() ? $('#startDate').val() : false
       })
      }
    });
    
    function openEvent(eid, date) {
      dlgopen('../../interface/main/calendar/add_edit_event.php?date='+ moment(date).format('YYYYMMDD') +'&eid=' + eid +'&prov=0', '_blank', 775, 375);
    }
    
    function goPid(pid) {
      top.RTop.location = '../../interface/patient_file/summary/demographics.php' + '?set_pid=' + pid;
      
      // cancel event bubble trying to open add_edit_event
      if (!e) var e = window.event;
      e.cancelBubble = true;
      if (e.stopPropagation) e.stopPropagation();
    }
  </script>
</body>
</html>
