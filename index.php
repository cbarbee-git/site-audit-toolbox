<?php
 require_once(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/wp-load.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Site Audit Toolbox</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="//use.fontawesome.com/releases/v5.15.4/css/all.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous" />
    <link rel="stylesheet" type="text/css" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
	<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.13.6/css/dataTables.bootstrap.min.css" />
    <script type="text/javascript" language="javascript" src="//code.jquery.com/jquery-3.7.0.js"></script>
    <link rel="stylesheet" type="text/css" href="style.css" />
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <script type="text/javascript" language="javascript" src="//cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" language="javascript" src="//cdn.datatables.net/1.13.6/js/dataTables.bootstrap.min.js"></script>
</head>
<body>
<?php
 global $wpdb;
 $results = $wpdb->get_results("select * from {$wpdb->prefix}site_audits;", ARRAY_A);
?>

<div id="feedback"></div>

<table id="sites" class="table table-bordred table-striped" style="width:100%">
        <thead>
            <tr>
                <th>ID</th>
                <th></th>
                <th>URL</th>
                <th>Name</th>
                <th>Login</th>
                <th>Last Audit</th>
                <th>Notes</th>
                <th>Page</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach($results as $row) {
                $completed = "";
                //if longer than 30 days...
                //if(strtotime($row['last_audit_timestamp']) > strtotime('-30 days') || $row['completed'] ){
                if($row['completed']){
                    $completed = "class=\"completed\"";
                }
                echo("\t\t\t\t<tr $completed>\n");

                echo("\t\t\t\t\t<td>\n");
                echo("\t\t\t\t\t\t".$row['id']."\n");
                echo("\t\t\t\t\t</td>\n");

                echo("\t\t\t\t\t<td>\n");
                echo("\t\t\t\t\t\t");
                echo( ($row['completed']) ? '<i class="fas fa-solid fa-check"></i>' : '');
                echo("\n");
                echo("\t\t\t\t\t</td>\n");

                echo("\t\t\t\t\t<td>\n");
                echo("\t\t\t\t\t\t".$row['site_url']."\n");
                echo("\t\t\t\t\t</td>\n");

                echo("\t\t\t\t\t<td>\n");
                echo("\t\t\t\t\t\t".$row['site_name']."\n");
                echo("\t\t\t\t\t</td>\n");

                echo("\t\t\t\t\t<td>\n");
                echo("\t\t\t\t\t\t".$row['login_path']."\n");
                echo("\t\t\t\t\t</td>\n");

                echo("\t\t\t\t\t<td>\n");
                echo("\t\t\t\t\t\t".$row['last_audit_timestamp']."\n");
                echo("\t\t\t\t\t</td>\n");

                echo("\t\t\t\t\t<td>\n");
                echo("\t\t\t\t\t\t".$row['notes']."\n");
                echo("\t\t\t\t\t</td>\n");

                echo("\t\t\t\t\t<td>\n");
                echo("\t\t\t\t\t\t".$row['page_visited']."\n");
                echo("\t\t\t\t\t</td>\n");

                //actions
                echo("\t\t\t\t\t<td>\n");
                echo("\t\t\t\t\t\t");
                echo("<a href=\"https://".$row['site_url']."\" target=\"_blank\"><i alt=\"View Site\" title=\"View Site\" class=\"fas fa-laptop-code action\"></i></a>");
                echo("&nbsp;&nbsp;");
                if($row['login_path'] !== ''){
                echo("<a href=\"https://".$row['site_url'] . "/" . $row['login_path'] ."\" target=\"_blank\"><i alt=\"Open Wordpress\" title=\"Open Wordpress\" class=\"fab fa-wordpress-simple action\"></i></a>");
                echo("&nbsp;&nbsp;");
                echo("<a href=\"fetch.php?site-id=".$row['id']."\" target=\"_blank\"><i alt=\"Fetch Screenshots\" title=\"Fetch Screenshots\" class=\"fas fa-window-restore action\"></i></a>");
                echo("&nbsp;&nbsp;");
                }
                echo("<a><i alt=\"Edit\" title=\"Edit\" data-site-completed=\"".$row['completed']."\" class=\"fas fa-pencil-alt fa-flip-horizontal editor-edit action\"></i></a>");
                echo("&nbsp;&nbsp;");
                echo("<a><i alt=\"Open All\" title=\"Open All\" class=\"fas fa-external-link-alt open-all\" data-fetch=\"fetch.php?site-id=".$row['id']."\" data-url=\"https://".$row['site_url']."\" data-admin=\"https://".$row['site_url'] . "/" . $row['login_path'] ."\"></i></a>");
                echo("\t\t\t\t\t</td>");
                
                echo("\t\t\t\t</tr>\n");
            }
            ?>
        </tbody>
</table>

    <!-- BEGIN Modal -->
    <form id="form-edit" method="post" >
    <div class="modal fade" id="modal-Edit" tabindex="-1" aria-hidden="true"> 
            <div class="modal-dialog"> 
                <div class="modal-content"> 
                    <div class="modal-header"> 
                        <h5 class="modal-title" id="editModalLabel"> 
                            Edit Site Information
                        </h5> 
                          
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"> 
                            <span aria-hidden="true">×</span> 
                        </button> 
                    </div> 
  
                    <div class="modal-body"> 

                        <div class="form-group">
                            <label id="lblSiteUrl" class="control-label col-md-3">Site URL:</label>
                                <div class="col-md-9">
                                    <input type="text" value="" id="site-url" name="site-url" class="form-control" />
                                </div>
                        </div>

                        <div class="form-group">
                            <label id="lblSiteName" class="control-label col-md-3">Site Name:</label>
                                <div class="col-md-9">
                                    <input type="text" value="" id="site-name" name="site-name" class="form-control" />
                                </div>
                        </div>

                        <div class="form-group">
                            <label id="lblSiteAdmin" class="control-label col-md-3">Login Path:</label>
                                <div class="col-md-9">
                                    <input type="text" value="" id="site-admin" name="site-admin" class="form-control" />
                                </div>
                        </div>

                        <div class="form-group">
                            <label id="lblSitePage" class="control-label col-md-3">Page Visit:</label>
                                <div class="col-md-9">
                                    <input type="text" value="" id="site-page" name="site-page" class="form-control" />
                                </div>
                        </div>

                        <div class="form-group">
                            <label id="lblSiteCompleted" for="site-completed" class="control-label col-md-3">Completed:</label>
                                <div class="col-md-9">
                                    <input type="checkbox" class="move-left" value="" id="site-completed" name="site-completed" class="form-control" />
                                </div>
                        </div>

                        <div class="form-group">
                            <label id="lblSiteAdmin" class="control-label col-md-3">Notes:</label>
                                <div class="col-md-9">
                                    <textarea type="text" value="" id="site-notes" name="site-notes" class="form-control"></textarea>
                                </div>
                        </div>

                        <div class="form-group">
                            <label id="lblSiteName" class="control-label col-md-6"></label>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-success btn-sm save-form-edit" data-toggle="modal" data-action="complete-and-save" id="save"> 
                                        Mark as Complete and Save
                                    </button> 
                                    <button type="button" class="btn btn-success btn-sm save-form-edit" data-toggle="modal" data-action="save" id="submit"> 
                                        Update
                                    </button> 
                                </div>
                        </div>

                        <input type="hidden" value="" name="site-id" id="site-id">

                    </div> 
                </div> 
            </div> 
        </div> 
        
    </div> 
    </form>
    <!-- END Modal -->

<script>
    const table = new DataTable('#sites', {
        "pageLength": 200,
        columnDefs: [
        {
            //hide the WP-ADMIN column
            target: 4,
            visible: false
        },
        {
            //hide the NOTE column
            target: 6,
            visible: false
        },
        { "width": "10%", "targets": 8 }//'actions' column
        ],
        "fnDrawCallback": function( oSettings ) {
            this.on('click','tbody tr',(function(e) {
                e.preventDefault();
                e.stopPropagation();
                let data = table.row(e.target.closest('tr')).data();
                //console.log(data);
                showModal(data);
            }));
        },

    });

    table.on('click', 'i.action', function (e) {
        e.stopPropagation();
        let data = table.row(e.target.closest('tr')).data();
        showModal(data);
    });
    table.on('click', 'i.open-all', function (e) {
        e.stopPropagation();
        const elm = e.currentTarget;
        if($(elm).attr('data-fetch')){
            window.open($(elm).attr('data-fetch'));
        }
        if($(elm).attr('data-url')){//be weary of pop-up blockers
            window.open($(elm).attr('data-url'));
        }
        if($(elm).attr('data-admin')){
            window.open($(elm).attr('data-admin'));
        }
        let data = table.row(e.target.closest('tr')).data();
        //console.log(data);
        showModal(data);
    });

function showModal(data) {
    //add data
    var myModal = $('#modal-Edit');
    $('#site-id', myModal).val(data[0].trim());
    $('#site-url', myModal).val(data[2].trim());
    $('#site-name', myModal).val(data[3].trim());
    $('#site-admin', myModal).val(data[4].trim());
    $('#site-notes', myModal).val(data[6].trim());
    $('#site-page', myModal).val(data[7].trim());
    var newValue = (data[1].trim() !== '') ? 1 : 0;
    var isChecked = (data[1].trim() !== '') ? true : false;
    $('#site-completed', myModal).val(newValue);
    $('#site-completed', myModal).prop('checked', isChecked);    
    $('#site-page', myModal).focus().select();
    //display modal
    $('#modal-Edit').modal('show'); 

}

$(function(){
    $('.save-form-edit').on('click', function(e){
        e.preventDefault();
        const elm = e.currentTarget;
        if($(elm).attr('data-action') == 'complete-and-save'){
            //if marking as completed, pass this too
            $("<input />").attr("type", "hidden")
                        .attr("name", "completed")
                        .attr("value", "true")
                        .appendTo("#form-edit");
        }
        $.ajax({
            url: 'edit.php', //this is the submit URL
            type: 'POST',
            data: $('#form-edit').serialize(),
            success: function(message){
                $("#feedback").html(message);
                $('#modal-Edit').modal('hide'); 
                //alert('successfully submitted')
                //redraw the table
                document.location.reload(true);
            }
        });
    });
    $('#modal-Edit').on('shown.bs.modal', function () {
        $('input[name="site-page"]').focus();
    }); 
});
$('#site-completed').click(function() { 
    if ($('#site-completed').is(":checked") == true) { 
        $('#site-completed').val(1); 
    } else { 
        $('#site-completed').val(0); 
    } 
}); 

</script>

</body>
</html>