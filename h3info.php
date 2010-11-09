<?php 
/*
Plugin Name: H3info
Plugin URI: http://www.moonhouse.se/posts/h3info
Description: Explanatory info from Wikipedia
Author: David Hall (based on an idea by Heidi Harman)
Version: 0.0.6
Author URI: http://www.dpg.se
*/

if (!class_exists('H3info')) {

    class H3info
    {

        var $opt_key = 'dpgse_hthreeinfo_options';

        var $default_options = array(
            'cachetime' => 12,
            'language' => 'en',
            'excerptlength' => 140,
            'revision' => 2);

        var $o = array();


        function H3info() {
            $this->read_options();
            $this->actions_filters();
        }


        function read_options() {
            $this->o = get_option($this->opt_key);
        }


        function install_plugin(){
            $this->o = get_option($this->opt_key);

            if (!is_array($this->o) || empty($this->o) ) {
                update_option($this->opt_key, $this->default_options);
                $this->o = get_option($this->opt_key);
            }
            else {
                $this->o = $this->o + $this->default_options;
                $this->o["revision"] = $this->default_options["revision"];
                update_option( $this->opt_key, $this->o);
            }
        }

        function settings_menu(){
            add_options_page('H3info Settings', 'H3info', 'manage_options', 'hthreeinfo',
                array ( &$this, 'options_page' ));
        }

        function options_page(){
            echo "<div>";
            echo "<h2>H3info Settings</h2>";
            echo '<form action="options.php" method="post">';
            settings_fields('dpgse_hthreeinfo_options');
            do_settings_sections('hthreeinfo');
            echo '<input name="Submit" type="submit" value="'. esc_attr('Save Changes') .'" />
</form></div>';
        }

        function details_text(){
            echo "<p>H3info is a plugin that gets excerpts from Wikipedia articles.</p>";
        }

        function admin_init(){
            register_setting( 'dpgse_hthreeinfo_options', 'dpgse_hthreeinfo_options', array ( &$this, 'options_validate' ));
            add_settings_section('dpgse_hthreeinfo', '',  array ( &$this, 'details_text' ), 'hthreeinfo');
            add_settings_field('dpgse_hthreeinfo_field_1', 'Cache time (hours)', array ( &$this, 'field_display'), 'hthreeinfo',
                'dpgse_hthreeinfo','cachetime');
            add_settings_field('dpgse_hthreeinfo_field_2', 'Language (2 chars)', array ( &$this, 'field_display'), 'hthreeinfo',
                'dpgse_hthreeinfo','language');
                add_settings_field('dpgse_hthreeinfo_field_3', 'Excerpt length (chars)', array ( &$this, 'field_display'), 'hthreeinfo',
                'dpgse_hthreeinfo','excerptlength');


        }

        function field_display($field){
            switch ($field) {
                case "cachetime":
                    echo "<input id='dpgse_hthreeinfo_field' name='dpgse_hthreeinfo_options[cachetime]' size='20' type='text'";
                    echo "value='{$this->o['cachetime']}' />";
                    break;
                case "language":
                    echo "<input id='dpgse_hthreeinfo_field' name='dpgse_hthreeinfo_options[language]' size='20' type='text'";
                    echo "value='{$this->o['language']}' />";
                    break;
                case "excerptlength":
                    echo "<input id='dpgse_hthreeinfo_field' name='dpgse_hthreeinfo_options[excerptlength]' size='5' type='text'";
                    echo "value='{$this->o['excerptlength']}' />";
                    break;
            }

        }

        function options_validate($input){
            preg_match("/[a-z]{2,2}/", $input['language'], $matches);
            $newinput['language'] = $matches[0];
            $newinput['cachetime'] = intval($input['cachetime']);
            $newinput['excerptlength'] = intval($input['excerptlength']);
            return $newinput;
        }


        function actions_filters() {
            add_filter ('the_content', array ( &$this,'insertFootNote'));
            register_activation_hook(__FILE__, array ( &$this, 'install_plugin' ));
            add_action('admin_init', array ( &$this, 'admin_init' ));
            add_action('admin_menu', array ( &$this, 'settings_menu' ));
            wp_enqueue_style('h3info',plugins_url( 'h3info' ).'/style.css', false, $this->o['revision']);
        }

        function lookup($article_id, $lang='en') {
            //todo: follow redirects
            if (false === ($infoboxes = get_transient("h3a:$article_id:$lang"))) {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, "http://dbpedia.org/data/$article_id.json");
                curl_setopt($ch,CURLOPT_HTTPHEADER, array ("Content-Type: application/json;charset=utf-8","Accept: application/json"));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, 0);

                $json = curl_exec($ch);

                curl_close($ch);

                $json = json_decode($json);
                $infoboxes = '';
                if(isset($json->{"http://dbpedia.org/resource/$article_id"}->{"http://dbpedia.org/ontology/abstract"})) {
                foreach ($json->{"http://dbpedia.org/resource/$article_id"}->{"http://dbpedia.org/ontology/abstract"} as $value) {
                    if($value->lang == $lang) {
                        $infoboxes.= $value->value;
                    }
                }
                foreach ($json->{"http://dbpedia.org/resource/$article_id"}->{"http://www.w3.org/2000/01/rdf-schema#label"} as $value) {
                    if($value->lang == $lang) {
                        if($infoboxes!='') {
                        	if(strlen($infoboxes) > $this->o["excerptlength"])
                        		$infoboxes = substr($infoboxes, 0, $this->o["excerptlength"]).'&#8230';
                        	$infoboxes.= "<br/><em>Information from Wikipedia/DBpedia, <a href=\"http://dbpedia.org/resource/$article_id\">article</a></em>";
                            $infoboxes ='<div class="h3-infobox">'.$infoboxes.'</div>';
                        }
                    }
                }
                

                }
                                
                set_transient("h3:$article_id:$lang", $infoboxes, 60*60*$this->o['cachetime']);
            }
            return $infoboxes;
        }

        function insertFootNote($content) {
            //todo: work with links in other languages than English
            if(preg_match_all("/http:\/\/([a-z]*)\.wikipedia\.org\/wiki\/([^\"]*)/", $content, $matches)) {
                $ids = $matches[2];
                foreach($ids as $id) {
	               $content .= $this->lookup($id, $this->o["language"]);
                }

            }
            return $content;
        }


    }

    $h3_info = new H3info();
}

?>
