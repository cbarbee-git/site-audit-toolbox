<?php
require_once(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/wp-load.php');

if (isset($_POST['site-id'])) {
    $site_id = strip_tags($_POST['site-id']);
	$site_name = strip_tags($_POST['site-name']);
	$site_admin = strip_tags($_POST['site-admin']);
	$site_url = strip_tags($_POST['site-url']); 
    $site_page = strip_tags($_POST['site-page']);
    $site_notes = strip_tags($_POST['site-notes']);

    global $wpdb;
    $table = "{$wpdb->prefix}site_audits";

    $fields_to_update = array(
        'site_name' => $site_name,
        'login_path' => $site_admin,
        'site_url' => $site_url,
        'page_visited' => $site_page,
        'notes' => $site_notes,
    );
    if($_POST['completed']) {
        //$fields_to_update['last_audit_timestamp'] = date('Y-m-d H:i:s',get_current_time());
        $fields_to_update['last_audit_timestamp'] = current_datetime()->format('Y-m-d H:i:s');
        $fields_to_update['completed'] = 1;
    }

    $results = $wpdb->update($table,
        $fields_to_update,
        array(
            'id' => $site_id 
        ),
    );
    if($results){
        $class = 'success';
	    $msg = "$site_url has been successfully updated!";
    }else{
        $class = 'error';
        $msg = 'Update query failed.';
    }
}else{
    $class = 'error';
    $msg = "ERROR: No ID# passed.";
}

$output = "<span class='alert alert-$class alert-dismissible'>$msg<span class=\"closebtn\" onclick=\"$('.alert').hide();\">&times;</span></span>";
echo ($output);
?>