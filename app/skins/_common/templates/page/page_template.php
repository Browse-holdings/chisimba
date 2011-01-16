<?php

// get any header params or body onload parameters for objects on the toolbar
if (!isset($headerParams)) {
    $headerParams = array();
}
if (!isset($bodyOnLoad)) {
    $bodyOnLoad = array();
}

// Get the site breadcrumbs
$tools = $this->getObject('tools','toolbar');
$siteBreadcrumbs = $tools->siteNavigation();
if (!isset($pageSuppressToolbar)) {
   // get toolbar object
   $menu = $this->getObject('menu','toolbar');
   $toolbar = $menu->show();

   
   $menu->getParams($headerParams, $bodyOnLoad);
}


$useragent = $_SERVER['HTTP_USER_AGENT'];

$browser = $this->getObject('browser', 'skin');
if ($browser->isMSIE() || $browser->isSafari()) {
	$charset = "utf-8";
	$mime = "text/html";
} else {
	$charset = "utf-8";
	$mime = "application/xhtml+xml";
}

if (!isset($pageLanguage)) {
    $languageClass = $this->getObject('language', 'language');
    $languageCode = $this->getObject('languagecode', 'language');
    $pageLanguage = $languageCode->getISO($languageClass->currentLanguage());
}

function fix_code($buffer) {
    return (preg_replace("!\s*/>!", ">", $buffer));
}

if (isset($_SERVER["HTTP_ACCEPT"]) && stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml")) {
    if (preg_match("/application\/xhtml\+xml;q=([01]|0\.\d{1,3}|1\.0)/i",$_SERVER["HTTP_ACCEPT"],$matches)) {
       	$xhtml_q = $matches[1];
        if (preg_match("/text\/html;q=q=([01]|0\.\d{1,3}|1\.0)/i",$_SERVER["HTTP_ACCEPT"],$matches)) {
            $html_q = $matches[1];
            if ((float)$xhtml_q >= (float)$html_q) {
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

if ($mime == "application/xhtml+xml") {
	$prolog_type = "<?xml version=\"1.0\" encoding=\"$charset\" ?>\n<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"$pageLanguage\" lang=\"$pageLanguage\">\n";
} else {
	ob_start("fix_code");
        $prolog_type = "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n<html lang=\"$pageLanguage\">\n";
}
header("Content-Type: $mime;charset=$charset");
header("Vary: Accept");
print $prolog_type;

if (!isset($pageTitle)) {
    $pageTitle = $objConfig->getSiteName();
}
$pageTitle .= ($siteBreadcrumbs==''?'':' [ '.strip_tags($siteBreadcrumbs).' ] ');
?>
    <head>
    
        <title>
<?php
    echo $pageTitle;
    
?>
        </title>
<?php
    // set up for Twitter @Anywhere as a global thing. Care has to be taken not to cripple offline installs
    //sys-config object
    $this->objSysConfig = $this->getObject('dbsysconfig', 'sysconfig');
    if( strtolower($this->objSysConfig->getValue('enable_atanywhere', 'twitter')) == 'true' ) {
        $apikey = $this->objSysConfig->getValue('mod_twitter_key', 'twitter');
        echo '<script src="http://platform.twitter.com/anywhere.js?id='.$apikey.'&v=1"></script>';
        // This will enable hovercards on all twitter names
        echo '<script type="text/javascript">
			      twttr.anywhere(function (twitter) {
		              twitter.hovercards({ infer: true });
		          });
		      </script>';
    }
    
    if (!isset($pageSuppressSkin)) {
        echo $objSkin->putSkinCssLinks();
        if (!isset($pageSuppressToolbar)) {
            echo '<!--[if lte IE 6]>
                <style type="text/css">
                    body { behavior:url("skins/_common/js/ADxMenu_prof.htc"); }
                </style>
            <![endif]-->
';
        }
    }

    echo $objSkin->putJavaScript($mime, $headerParams, $bodyOnLoad);

?>
    </head>
<?php
    if (isSet($bodyParams)) {
        echo '<body ' . $bodyParams . '>';
        echo ' <!--This site is powered by Chisimba-->';
    } else {
        echo '<body>';
        echo ' <!--This site is powered by Chisimba-->';
    }
    // echo Chisimba
    
    if (!isset($pageSuppressContainer)) {
        echo '<div id="container">';
    }
    if (!isset($pageSuppressBanner)) {
?>
        <div id="header">
            <h1 id="sitename">
                <span>
<?php
        echo '<a href="'.$objConfig->getSiteRoot().'">'.$objConfig->getsiteName().'</a>';
?>
                </span>
            </h1>
<?php
        if (!isset($pageSuppressSearch)) {

        	echo $objSkin->siteSearchBox();

        }
        if (!isset($pageSuppressToolbar)) {
            //$menu= $this->getObject('menu','toolbar');
		  echo $toolbar; //$menu->show();
        }

        // For developers on localhost. Please leave in.
        // Comment this out for your local use.
        // Comment in for production use.
        //echo '['.KEWL_DB_DSN.']';
?>
        </div>

<?php
    }
    header("Content-type: text/html; charset=utf-8")
    // get content
    echo $this->getLayoutContent();

    if (!isset($suppressFooter)) {
         // Create the bottom template area
        $this->footerNav =  $this->newObject('layer', 'htmlelements');
        $this->footerNav->id = 'footer';
        $this->footerNav->cssClass='';
        $this->footerNav->position='';
        if (isset($footerStr)) {
            $this->footerNav->str = $footerStr;
        } else if ($objUser->isLoggedIn()) {
            $this->loadClass('link', 'htmlelements');
            $link = new link ($this->URI(array('action'=>'logoff'),'security'));
            $link->link=$objLanguage->languageText("word_logout");
            $str=$objLanguage->languageText("mod_context_loggedinas", 'context').' <strong>'.$objUser->fullname().'</strong>  ('.$link->show().')';
            $this->footerNav->str = $str;
        }
        echo $this->footerNav->show();
    }
    if (!isset($pageSuppressContainer)) {
	   echo '</div>';
    }
    $this->putMessages();
?>
    <!-- This will put a tweet box in the "mytweetbox" HTML element. To use, simply create a mytweetbox div somewhere -->
    <script type="text/javascript">
		
		    twttr.anywhere(function (T) {
		
		        T("#mytweetbox").tweetBox({
		            height: 40,
		            width: 550,
		            defaultContent: "Reading: " + jQuery.trim(document.title),
		            label: "Tweet this post!"
		        });
		
		    });
		
	</script>
    </body>
</html>
