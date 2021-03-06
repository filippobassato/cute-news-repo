<?php

    /*  External arguments:
        array  $category
        string $archive  = null or numeric id
        $_GET['ucat']    - same as $category

    */
    $NotHeaders = true;
    require_once ('core/init.php');

    // plugin tells us: he is fork, stop
    if ( hook('fork_news', false) ) return;

    // Check including
    $Uri = '//'.dirname( $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
    if (strpos($config_http_script_dir, $Uri) !== false && strpos($PHP_SELF, 'show_news.php') !== false) die_stat(403, 'Wrong including show_news.php! Check manual to get more information about this issue.');

    // If we are showing RSS, include some need variables.
    if ( $template == 'rss' ) include( SERVDIR.'/cdata/rss_config.php' );

    // definition FB comments if uses
    if ( $config_use_fbcomments == 'yes' and !isset($_CACHE['__first_time__']) )
    {
        echo str_replace('{appID}', $config_fb_appid, read_tpl('fb_comments'));
        $_CACHE['__first_time__'] = true;
    }

    // use static path to all links
    if ( empty($static_path) == false ) $PHP_SELF = $static_path;

    // filter
    extract(filter_request('ucat,subaction,archive'), EXTR_OVERWRITE);

    // Linked cats
    if (isset($_GET['cid']) && $_GET['cid']) $category = $_GET['cid'];
    hook('show_news_init');

    // Prepare requested categories
    if (preg_match('/[a-z]/i', $category))
    {
        die_stat(false, "<b>Error</b>!<br>CuteNews has detected that you use \$category = \"".htmlspecialchars($category)."\";
                         but you can call the categories only with their <b>ID</b> numbers and not with names<br>example:<br>
                         <blockquote>&lt;?PHP<br>\$category = \"1\";<br>include(\"path/to/show_news.php\");<br>?&gt;</blockquote>");
    }

    $requested_cats = array();
    $category       = preg_replace("/\s/", "", $category);
    $tmp_cats_arr   = explode(",", $category);

    foreach ($tmp_cats_arr as $key => $value) if ($value) $requested_cats[$value] = true;

    if ($archive)
    {
        $news_file = SERVDIR."/cdata/archives/$archive.news.arch";
        $comm_file = SERVDIR."/cdata/archives/$archive.comments.arch";
    }
    else
    {
        $news_file = SERVDIR."/cdata/news.txt";
        $comm_file = SERVDIR."/cdata/comments.txt";
    }

    $allow_add_comment  = false;
    $allow_full_story   = false;
    $allow_active_news  = false;
    $allow_comments     = false;
    $is_in_category     = false;

    // article is in multiple categories
    $ucat = isset($_GET['ucat']) && $_GET['ucat']? $_GET['ucat'] : $category;
    if (strstr($ucat, ','))
    {
        $article_cat_arr = explode(',', $ucat);
        foreach ($article_cat_arr as $one_cat)
        {
            if (isset($requested_cats[$one_cat]) && $requested_cats[$one_cat])
                $is_in_category = true;
        }
    }
    elseif (isset($requested_cats[$ucat]) && $requested_cats[$ucat]) $is_in_category = true;

    // Default variables
    if (empty($number)) $number = 10;
    if (empty($template)) $template = 'Default';

    // <<<------------ Determine what user want to do
    hook('show_news_determine_before');
    if ( empty($CN_HALT) and empty($static) and ($subaction == "showcomments" or $subaction == "showfull" or $subaction == "addcomment") and (empty($category) or $is_in_category) )
    {
        if ($subaction == "addcomment")
        {
            $allow_add_comment  = true;
            $allow_comments     = true;
        }
        elseif ($subaction == "showcomments")
            $allow_comments     = true;

        elseif ($subaction == "showfull")
            $allow_full_story   = true;

        // Tuning
        if (($subaction == "showcomments" or $allow_comments == true) and $config_show_full_with_comments == "yes")
            $allow_full_story   = true;

        if ($subaction == "showfull" and $config_show_comments_with_full == "yes")
            $allow_comments     = true;
    }
    else
    {
        $allow_active_news = true;
        if ($config_reverse_active == "yes") $reverse = true;
    }
    hook('show_news_determine_after');
    //----------- >>> Determine what user want to do

    require(SERVDIR."/inc/shows.inc.php");

    // Save archive value
    $archive = isset($_GET['archives']) && $_GET['archives']? $_GET['archive'] : $archive;
    unset ($static, $template, $requested_cats, $category, $cat, $reverse, $in_use, $archives_arr, $number, $no_prev, $no_next, $i, $showed, $prev, $used_archives, $only_active, $user, $user_by);

    echo '<!-- News Powered by CuteNews: http://cutephp.com/ -->';
?>