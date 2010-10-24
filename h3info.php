<?php 
/*
Plugin Name: H3info
Plugin URI: http://www.moonhouse.se/posts/h3info
Description: Explanatory info from Wikipedia
Author: David Hall
Version: 0.0.2
Author URI: http://www.dpg.se
*/

if (!class_exists('H3info')) {

    class H3info
    {
            function H3info() {
            //$this->read_options();
            //$this->actions_filters();
        }
        
        function test($article_id='IKEA', $lang='en') {
         $ch = curl_init();

    // set URL and other appropriate options
    curl_setopt($ch, CURLOPT_URL, "http://dbpedia.org/data/$article_id.json");
    curl_setopt($ch,CURLOPT_HTTPHEADER, array ("Content-Type: application/json;charset=utf-8","Accept: application/json"));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);

    // grab URL and store it
    $json = curl_exec($ch);

    // close cURL resource, and free up system resources
    curl_close($ch);

    //decode json array
    $json = json_decode($json); 

//print_r($json->{"http://dbpedia.org/resource/IKEA"}->{"http://dbpedia.org/ontology/abstract"});
        
        foreach ($json->{"http://dbpedia.org/resource/$article_id"}->{"http://dbpedia.org/ontology/abstract"} as $value) {
        	if($value->lang == $lang) {
		        print '<div>'.$value->value."</div> ";	
        	}
        }
        }
    
        }

    $h3_info = new H3info();
}

?>
