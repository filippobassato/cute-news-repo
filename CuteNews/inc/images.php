<?PHP

if ($member_db[UDB_ACL] > ACL_LEVEL_JOURNALIST or ($member_db[UDB_ACL] != ACL_LEVEL_ADMIN and $action == "doimagedelete"))
    msg("error", "Access Denied", "You don't have permission to manage images");

$allowed_extensions = array("gif", "jpg", "png", "bmp", "jpe", "jpeg");

// ********************************************************************************
// Show Preview of Image
// ********************************************************************************
if ($action == "preview")
{
    echo proc_tpl('images/preview', array( 'config_http_script_dir' => $config_http_script_dir, 'image' => $image ) );
}
// ********************************************************************************
// Show Images List
// ********************************************************************************
elseif ($action != "doimagedelete")
{
    if ($subaction != 'upload') $CSRF = CSRFMake();

    $CKEditorFuncNum = $_REQUEST['CKEditorFuncNum'];
    if ($action == "quick")
    {
        echo proc_tpl
        (
             'images/quick.up',
             array('area' => $area,
                   'CKEditorFuncNum' => $CKEditorFuncNum,
                   'config_http_script_dir' => $config_http_script_dir
             ),
             array('WYSYWIG' => $wysiwyg && $_REQUEST['CKEditorFuncNum'])
        );
    }
    else
    {
        echoheader("images", "Manage Images");
    }

    // ********************************************************************************
    // Upload Image(s)
    // ********************************************************************************

    if ($subaction == "upload")
    {
        CSRFCheck();
        $CSRF = CSRFMake();
        for ($image_i = 1; $image_i < ($images_number+1); $image_i++)
        {
            $current_image  = 'image_'.$image_i;
            $image          = $_FILES[$current_image]['tmp_name'];
            $image_name     = $_FILES[$current_image]['name'];
            $image_name     = str_replace(" ", "_", $image_name);
            $img_name_arr   = explode(".",$image_name);
            $type           = end($img_name_arr);

            if (empty($image_name)) $img_result .= "<br><span style='color: red;'>$current_image -> No File Specified For Upload!</span>";
            elseif( !isset($overwrite) and file_exists(SERVDIR."/cdata/upimages/".$image_name)){ $img_result .= "<br><span style='color: red;'>$image_name -> Image already exist!</span>";}
            elseif( !(in_array($type, $allowed_extensions) or in_array(strtolower($type), $allowed_extensions)) )
            {
                $img_result .= "<br><span style='color:red;'>$image_name ->This type of file is not allowed!</span>";
            }
            else
            {
                // Image is OK, upload it
                copy($image, SERVDIR."/cdata/upimages/".$image_name) or $img_result .= "<br><span style='color: red;'>$image_name -> Couldn't copy image to server</span><br />Check if file_uploads is allowed in the php.ini file of your server";
                if (file_exists(SERVDIR."/cdata/upimages/".$image_name))
                {
                    $img_result .= "<br><span style='color: green;'>$image_name -> Image was uploaded</span>";
                    if ($action == "quick")
                        $img_result .= " <a title=\"Insert this image in the $my_area\" href=\"javascript:insertimage('$image_name');\">[insert it]</a>";

                }
                // if file is uploaded succesfully
            }
        }
    }

    // Add the JS for multiply image upload.
    echo proc_tpl
    (
        'images/multi',
        array
        (
            'CSRF'                  => $CSRF,
            'img_result'            => $img_result,
            'wysiwyg'               => $wysiwyg,
            'CKEditorFuncNum'       => $CKEditorFuncNum,
            'area'                  => $area,
            'action'                => $action,
        ),
        array('QUICK' => ($action == "quick" && $wysiwyg == false)? 1:0)
    );

    $i = 0;
    $img_dir = opendir(SERVDIR."/cdata/upimages");
    while ($file = readdir($img_dir))
    {
        //Yes we'll store them in array for sorting
         $images_in_dir[] = $file;
    }

    natcasesort($images_in_dir);
    reset($images_in_dir);
    foreach ($images_in_dir as $file)
    {
        $img_name_arr = explode(".",$file);
        $img_type     = end($img_name_arr);
        if ( (in_array($img_type, $allowed_extensions) or in_array(strtolower($img_type), $allowed_extensions)) and $file != ".." and $file != "." and is_file(SERVDIR."/cdata/upimages/".$file))
        {
            $i++;
            $this_size =  filesize(SERVDIR."/cdata/upimages/".$file);
            $total_size += $this_size;
            $img_info = getimagesize(SERVDIR."/cdata/upimages/".$file);
            if ( $i%2 != 0 ) $bg = "bgcolor=#F7F6F4"; $bg = "";
            if ($action == "quick")
            {
                $my_area = str_replace("_", " ", $area);
                echo
                    "<tr $bg>
                    <td height=16 width=52> <a title='Preview this image' href=\"javascript:PopupPic('".$file."')\"><img style='border:0px;' src='skins/images/view_image.gif'></a></td>
                    <td height=16><a title=\"Insert this image in the $my_area\" href=\"javascript:insertimage('$file')\">$file</a></td>
                    <td height=16 align=right>$img_info[0]x$img_info[1]&nbsp;&nbsp;</td>
                    <td height=16 width=72 align=right>&nbsp;". formatsize($this_size) ."</td>
                </tr>";
            }
            else
            {
                echo "<tr $bg>
                        <td height=16><a target=_blank href=\"". $config_http_script_dir ."/cdata/upimages/$file\">$file</a></td>
                        <td width=100 height=16 align=right>$img_info[0]x$img_info[1]</td>
                        <td width=72 height=16 align=right>&nbsp;". formatsize($this_size) ."</td>
                        <td width=48 height=16 align=center><input type=checkbox name=images[$file] value=\"$file\"></td>
                     </tr>";
            }
        }
    }

    if ($i > 0)
    {
        echo "<tr><td height=16>";
        if($action != "quick")
        {
            echo "<td colspan=2 align=right><br><input type=submit value='".lang('Delete Selected Images')."'></tr>";
        }

        echo "<tr height=1>
                <td width=14>&nbsp;</td>
                <td align=right><br/><b>".lang('Total size')."</b></td>
                <td align=right><br/><b>". formatsize($total_size) .'</b></td>
                <td>&nbsp;</td>
            </tr>';

    }

    echo '</table><input type=hidden name=action value=doimagedelete></form></table>';

    if ($action != "quick") echofooter();

}
// ********************************************************************************
// Delete Image
// ********************************************************************************
elseif ($action == "doimagedelete")
{
    CSRFCheck();

    if(!isset($images))
        msg("info", lang("No Images selected"), lang("You must select images to be deleted"), $PHP_SELF."?mod=images");

    foreach ($images as $image)
        unlink(SERVDIR."/cdata/upimages/".$image) or print(lang("Could not delete image")." <b>$file</b>");

    msg("info", lang("Image(s) Deleted"), lang("The image was successfully deleted"), $PHP_SELF."?mod=images");

}
?>