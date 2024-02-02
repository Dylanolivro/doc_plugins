<?php

header('Content-Type: text/html; charset=utf-8');
ini_set('user_agent', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9');
ini_set("display_errors", 1);

include_once("_lib.php");
include_once("parser_engine/_itemclass.php");
include_once("../_api/v2_MAPI_Tools.php");

$items = array();

$url = '';
$items = crawl($url, $items);

function crawl($url, $items)
{
    // Récupéeration du code source d'une url
    $content = file_get_contents($url);
    /* transformation en objet dom parsable */
    $content = str_get_html($content);
    $articles_path = 'article';
    $title_path = 'h3';
    $url_path = 'a';
    $date_path = '.date';
    $abstract_path = '.content p';

    $divs = $content->find($articles_path);
    echo count($divs);
    foreach ($divs as $idx => $div) {
        /* si j'ai pas de titre je skip */
        $item = new MYNEWS();
        $item->_Titre = MAPI_Tools::String_Clean(trim(html_entity_decode($div->find($title_path, 0)->plaintext, ENT_QUOTES, 'utf-8')));
        // $item->_URL = htmlspecialchars(html_entity_decode($div->find($url_path, 0)->href, ENT_COMPAT, 'utf-8'), ENT_COMPAT, "utf-8");

        $url = trim(htmlspecialchars(html_entity_decode($div->find($url_path, 0)->href, ENT_COMPAT, 'utf-8'), ENT_COMPAT, "utf-8"));
        if (strpos($url, "") !== false) {
            $item->_URL = $url;
        } else {
            $item->_URL = "" . $url;
        }

        /* Date du jour  si pas de date */
        // $date = date('d-m-Y');
        $date = trim($div->find($date_path, 0)->plaintext);
        $item->_Date = MYNEWS::DATE_Conversion($date);

        $item->_Abstract = MAPI_Tools::String_Clean(trim(html_entity_decode($div->find($abstract_path, 0)->plaintext, ENT_QUOTES, 'utf-8')));
        array_push($items, $item);
    }

    return $items;
}

if (isset($_GET['output']) && strtolower($_GET['output']) == 'rss')
    MYCRAWL::Export_RSS($items, false, htmlentities($url), 'Mytwip IPJC (' . count($items) . ')', '#', 'utf-8');
else
    MYCRAWL::Export($items);
