<?PHP

require_once("core/init.php");

if ($id != "")
{
    $archive = preg_replace('~[^a-z0-9_]~i', '', $archive);
    if (!empty($archive) && isset($archive))
         $news_file = SERVDIR."/cdata/archives/$archive.news.arch";
    else $news_file = SERVDIR."/cdata/news.txt";

    $found = FALSE;
    $all_news = file($news_file);
    foreach ($all_news as $news_line)
    {
        $news_arr = explode("|", $news_line);
        if($news_arr[0] == $id)
        {
            $found = TRUE;
            break;
        }
    }

    if ($found == TRUE)
    {
        $title = $news_arr[2];
        $date = date("j F Y h:i A", $news_arr[0]);
        if ($news_arr[4] != "")
             $news = replace_news("show", $news_arr[4]);
        else $news = replace_news("show", $news_arr[3]);


echo <<<PRINTABLE
<HTML>
<HEAD>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<title>Printer Friendly Version</title>
</HEAD>
<BODY bgcolor="#ffffff" text="#000000" onload="window.print()">

<b>$title</b> @ <small>$date</small>
<hr>
$news
<hr>
<small>News powered by CuteNews - http://cutephp.com</small>

</BODY>
</HTML>
PRINTABLE;

    }
    else
    {
        echo lang("The news you what to print was not found");
    }

}
else
{
    echo lang("No ID", 'print');
}

?>