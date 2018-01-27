<?php
header("Content-type: application/json;");
$plugin_slug = strtolower( strip_tags( htmlspecialchars( $_GET['slug'] ) ) );;
if(empty($plugin_slug))
        die('{ "error": "No slug in query string" }');
if(stristr($plugin_slug, ".") === FALSE) {
        if(file_exists("wp-cache/" . $plugin_slug . ".cached")
                && filemtime("wp-cache/" . $plugin_slug . ".cached") > time() - 3600)
        {
                $plugin_data = file_get_contents("wp-cache/" . $plugin_slug . ".cached");
        } else {
                $plugin_data = file_get_contents("https://translate.wordpress.org/projects/wp-plugins/$plugin_slug/contributors");
                file_put_contents("wp-cache/" . $plugin_slug . ".cached", $plugin_data);
        }

        preg_match_all('/<h4><span class="locale-name">(.*?)<span>[\s\S]*?<span class="locale-code">(.*?)<\/span><\/a>[\s\S]*?<\/h4>([\s\S]+?)<\/div>/', $plugin_data, $glot_matches);
        $plugin_info = new stdClass;
        $plugin_info->slug = $plugin_slug;
        $plugin_info->fetched_data = date("Y-m-d H:i:s", filemtime("wp-cache/" . $plugin_slug . ".cached"));

        preg_match('/<li class="project-name">(.*?)<\/li>/', $plugin_data, $projectName);
        $plugin_info->name = $projectName[1];

        $plugin_info->locales = array();

        for($i = 0; $i < count($glot_matches[0]); $i++)
        {
                $locale = new stdClass;
                $locale->name = $glot_matches[1][$i];
                $locale->code = $glot_matches[2][$i];
                $locale->editors = array();
                $locale->contributors = array();

                $editors = '/<p><strong>Editors:<\/strong>([\s\S]*?)<\/p>/';
                $contributors = '/<p><strong>Contributors:<\/strong>([\s\S]*?)<\/p>/';
                $user_info_regex = '/<a href="https:\/\/profiles.wordpress.org\/(.*?)\/">(.*?)<\/a>/';

                $html = $glot_matches[3][$i];
                preg_match($editors, $html, $_editors);
                preg_match_all($user_info_regex, $_editors[1], $Editors);
                for($x = 0; $x < count($Editors[0]); $x++) {
                        $ed = new stdClass;
                        $ed->username = $Editors[1][$x];
                        $ed->fullname = $Editors[2][$x];
                        $ed->link = "https://profiles.wordpress.org/" . $ed->username . "/";
                        $locale->editors[] = $ed;
                }
                preg_match($contributors, $html, $_contributors);
                preg_match_all($user_info_regex, $_contributors[1], $Contributors);
                for($x = 0; $x < count($Contributors[0]); $x++) {
                        $ed = new stdClass;
                        $ed->username = $Contributors[1][$x];
                        $ed->fullname = $Contributors[2][$x];
                        $ed->link = "https://profiles.wordpress.org/" . $ed->username . "/";
                        $locale->contributors[] = $ed;
                }

                $plugin_info->locales[$locale->code] = $locale;
        }

        echo json_encode($plugin_info);
} else {
        echo '{ "error": "I\'m sorry Dave, I cannot let you do that." }';
}
