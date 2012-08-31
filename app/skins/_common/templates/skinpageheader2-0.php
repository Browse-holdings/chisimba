<?php
if (!isset($pageTitle)) {
    $pageTitle = $objConfig->getSiteName();
}

// Add Toolbar if not suppressed
if (!isset($pageSuppressToolbar)) {
    
    // Get Toolbar Object
    $menu = $this->getObject('menu','toolbar');
    $toolbar = $menu->show();
    
    // get any header params or body onload parameters for objects on the toolbar
    $menu->getParams($headerParams, $bodyOnLoad);
}

if (isset($footerStr)) {
            $this->footerNav->str = $footerStr;
} else if ($objUser->isLoggedIn()) {
	$this->loadClass('link', 'htmlelements');
	$link = new link ($this->URI(array('action'=>'logoff'),'security'));
	$link->link=$objLanguage->languageText("word_logout");
	$footerStr=$objLanguage->languageText("mod_context_loggedinas", 'context').' <strong>'.$objUser->fullname().'</strong>  ('.$link->show().')';

}
		
// Set Header Params if not defined
if (!isset($headerParams)) {
    $headerParams = array();
}

// Set Body On Load Params if not defined
if (!isset($bodyOnLoad)) {
    $bodyOnLoad = array();
}

// Set Body Params if not defined
if (!isset($bodyOnLoad)) {
    $bodyOnLoad = array();
}

//suppress header image
if (isset($pageSuppressBanner)) {
	$headerStyle = "header_no_banner";
} else {
	$headerStyle = "header";
}
	  
// Set Number of Columns if not defined
if (!isset($numColumns)) {
    $numColumns = 0;
}

$useragent = $_SERVER['HTTP_USER_AGENT'];
function isMSIE($useragent)
{
    if(eregi("msie", $useragent) && !eregi("opera",$useragent))
    {
        return TRUE;
    }
    if(eregi("microsoft internet explorer", $useragent))
    {
        return TRUE;
    }
}
$ie = isMSIE($useragent);
if($ie == TRUE)
{
    $charset = "utf-8";
    $mime = "text/html";
}
else {
    $charset = "utf-8";
    $mime = "application/xhtml+xml";
}

if (!isset($pageLanguage)) {
    $languageClass =& $this->getObject('language', 'language');
    $languageCode =& $this->getObject('languagecode', 'language');
    $pageLanguage = $languageCode->getISO($languageClass->currentLanguage());
}

function fix_code($buffer)
{
    return (preg_replace("!\s*/>!", ">", $buffer));
}

if(isset($_SERVER["HTTP_ACCEPT"]) && stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml"))
{
    if(preg_match("/application\/xhtml\+xml;q=([01]|0\.\d{1,3}|1\.0)/i",$_SERVER["HTTP_ACCEPT"],$matches))
    {
        $xhtml_q = $matches[1];
        if(preg_match("/text\/html;q=q=([01]|0\.\d{1,3}|1\.0)/i",$_SERVER["HTTP_ACCEPT"],$matches))
        {
            $html_q = $matches[1];
            if((float)$xhtml_q >= (float)$html_q)
            {
                $mime = "application/xhtml+xml";
            }
        }
    } else {
        $mime = "application/xhtml+xml";
    }
}

if (isset($pageSuppressXML)) {
    $mime = "text/html";
}
/*
if (!isset($pageSuppressSkin)) {
        if (!isset($pageSuppressToolbar)) {
            $headerParams[] = '<!--[if lte IE 6]>
                <style type="text/css">
                    body { behavior:url("skins/_common/js/ADxMenu_prof.htc"); }
                </style>
            <![endif]-->
';
        }
    }
*/
if($mime == "application/xhtml+xml")
{
    $prolog_type = "<?xml version=\"1.0\" encoding=\"$charset\" ?>\n<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"$pageLanguage\" lang=\"$pageLanguage\">\n";
} else {
    ob_start("fix_code");
    $prolog_type = "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n<html lang=\"$pageLanguage\">\n";
}
header("Content-Type: $mime;charset=$charset");
header("Vary: Accept");
print $prolog_type;

//the javascripts
 $javascripts =  $objSkin->putJavaScript($mime, $headerParams, $bodyOnLoad);

?>