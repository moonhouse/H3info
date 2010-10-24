<?php 
/*
Plugin Name: H3info
Plugin URI: http://www.moonhouse.se/posts/h3info
Description: Explanatory info from Wikipedia
Author: David Hall
Version: 0.0.3
Author URI: http://www.dpg.se
*/

if (!class_exists('H3info')) {

    class H3info
    {
            function H3info() {
            //$this->read_options();
            $this->actions_filters();
        }
        
        function actions_filters() {
        add_filter ('the_content', array ( &$this,'insertFootNote'));

        }
        
        function test($article_id='IKEA', $lang='en') {
        
        if (false === ($infoboxes = get_transient("h3:$article_id:$lang"))) {

         $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "http://dbpedia.org/data/$article_id.json");
    curl_setopt($ch,CURLOPT_HTTPHEADER, array ("Content-Type: application/json;charset=utf-8","Accept: application/json"));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);

    $json = curl_exec($ch);

    curl_close($ch);

    $json = json_decode($json); 

        $infoboxes = '';
        foreach ($json->{"http://dbpedia.org/resource/$article_id"}->{"http://dbpedia.org/ontology/abstract"} as $value) {
        	if($value->lang == $lang) {
		        $infoboxes.= '<div class="h3-infobox">'.$value->value."</div>  ";	
        	}
        }
        set_transient("h3:$article_id:$lang", $infoboxes, 60*60*12); 
        }
        return $infoboxes;
        }
        
        function insertFootNote($content) {
		$content .= $this->test('H%26M','sv'); 
        return $content;
}

    
        }

    $h3_info = new H3info();
}

?>
