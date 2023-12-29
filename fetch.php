<?php
require_once(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/wp-load.php');
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;

if (!isset($_GET['site-id']) || !is_numeric($_GET['site-id'])) {
    die('invalid site-id error.');
}else{
    $site_id = $_GET['site-id'];
}

global $wpdb;
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}site_audits WHERE id = %d",
        $site_id
    ),
    ARRAY_A   
);

//assign values needed
//set array of viewport sizes
$viewport_array = array(
    'desktop'   => array('width' => 1440, 'height' => 1000),
    'mobile'    => array('width' => 360, 'height' => 1000)
);

//prepare directories
$needed_directories = array('temp_dir' => 'temp', 'output_dir' =>'reports');

///add directories for individual viewports
foreach($viewport_array as $label => $viewport){
    if(!in_array($label, $needed_directories)){
        $path = $needed_directories['temp_dir'].'/'.$label;
        $needed_directories[$label] = $path;
    }
}

//create all needed directories
foreach($needed_directories as $key => $directory){
    if(!file_exists($directory)){
        mkdir($directory);
    }
}

/**TODO: Add a conditional for WP check */
//if(IS_WORDPRESS()){
$api_endpoint = "/wp-json/wp/v2/pages/";

foreach($results as $result) {
    //get the curl url needed - with protocol and www
    $prepend_url = (str_starts_with( $result['site_url'], 'www' ) ? 'https://' : 'https://www.');
    $request_url = $prepend_url . $result['site_url'] . $api_endpoint;
    $full_site_url = $prepend_url . $result['site_url'];
    $response = call_curl($request_url);
    if($response === NULL){
        //don't die just yet...
        //die("Unable to reach API Endpoint. Is Site Wordpress?");
        //likely NOT a Wordpress site or malformed JSON, but grab the homepage at least
        $response = array(array('link' => $full_site_url ));
    }
    if(is_array($response)){
        //loop through each page
        foreach($response as $page){
        $file_name = str_replace(array("https://".$result['site_url'], "https://www.".$result['site_url']), array('','') , $page['link']);
            //if key doesn't exists (NOT WP Site)   OR  - Does NOT contain iframe loading breaks (timeout?, chrome browser security - ignore these for now)
            if(!array_key_exists('content', $page ) || !str_contains($page['content']['rendered'],'<iframe')){
                //clean up names -                        homepage            remove extra slashes
                $file_name = ($file_name == '/') ? $result['site_url'] : str_replace('/','_',ltrim(rtrim($file_name, '/'),'/'));
                ScreenShot($page['link'],$file_name . '.png',$needed_directories['temp_dir'],$viewport_array);
            }
        }
        ZipScreenShots($result['site_url'],$needed_directories['output_dir'],$needed_directories['temp_dir']);
        RemoveDirectory($needed_directories['temp_dir']);
        echo('<p>' . $result['site_url'] . ' is done. ('.current_datetime()->format('Y-m-d H:i:sa').')</p>');
    }else{
        //this should never run. $response will always be an array of at least the homepage
        echo('<p>Response was not valid.</p>');
        echo('<pre>');
        var_dump($response);
        echo('</pre>');
    }
}
//}//if WORDPRESSS


function call_curl($request_url) {
    $curl = curl_init($request_url);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_VERBOSE, 1);
    curl_setopt($curl, CURLOPT_POST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36");
    
    $response = curl_exec($curl);

    curl_close($curl);
    //clean up any erronious info inside the response
    $response = strstr($response, "[{" );
    
    $return = json_decode($response,TRUE);
    $error = json_last_error();
    //echo json response to be sure call returns correctly formatted json
    echo("<p>JSON : ");
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
          echo "No errors";
          break;
        case JSON_ERROR_DEPTH:
          echo "Maximum stack depth exceeded";
          break;
        case JSON_ERROR_STATE_MISMATCH:
          echo "Invalid or malformed JSON";
          break;
        case JSON_ERROR_CTRL_CHAR:
          echo "Control character error";
          break;
        case JSON_ERROR_SYNTAX:
          echo "Syntax error";
          break;
        case JSON_ERROR_UTF8:
          echo "Malformed UTF-8 characters";
          break;
        default:
          echo "Unknown error";
          break;
    }
      echo("</p>");
    return $return;
}

function ScreenShot($urlToCapture,$file_name,$temp_file_path = 'temp',$viewport_array){
    require_once(__DIR__ . "/vendor/autoload.php");
    //use HeadlessChromium\Page;
    $browser = (new HeadlessChromium\BrowserFactory()) -> createBrowser();
    foreach ($viewport_array as $label => $viewport){
        try {
            $page = $browser -> createPage();
            $page -> setViewport($viewport['width'],$viewport['height']);
            $page -> navigate($urlToCapture) -> waitForNavigation(Page::DOM_CONTENT_LOADED, 120000);
            $page -> screenshot() -> saveToFile($temp_file_path.'/'.$label.'/'.$file_name);
        
        }
        catch (\Exception $ex) {
            // Something went wrong
            var_dump($ex);
            die("<p>ERROR !!</p>");
        }
        //finally {
            //$browser -> close();
        //}
    }
    $browser -> close();
  }

function ZipScreenShots($site_zip_name, $zip_file_path = 'reports', $temp_file_path = 'temp' ){
    $tempRootPath = realpath($temp_file_path);
    $zip = new ZipArchive;
    $zip->open($zip_file_path . '/' . $site_zip_name.".zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);

    // Create recursive directory iterator
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tempRootPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file)
    {
        // Skip directories (they would be added automatically)
        if (!$file->isDir())
        {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($tempRootPath) + 1);

            // Add current file to archive
            $zip->addFile($filePath, $relativePath);
        }
    }
    // Zip archive will be created only after closing object
    $zip->close();
}

function RemoveDirectory($dir){
    //remove the temp directory and files.
    if(file_exists($dir)){
        $di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ( $ri as $file ) {
            $file->isDir() ?  rmdir($file) : unlink($file);
        }
        rmdir($dir);
    }
}