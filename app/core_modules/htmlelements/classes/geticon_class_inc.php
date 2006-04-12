<?php
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}


/**
* Class getIcon class to retrieve an icon by name
*
* It uses a naming and file location convention
* Files are in the /kngicon/ folder as specified in the configuration
* Icons are named file.type (e.g. home.gif)
*
* @package getIcon
* @category HTML Controls
* @copyright 2004, University of the Western Cape & AVOIR Project
* @license GNU GPL
* @version $Id$;
* @author Derek Keats
* @author Megan Watson
* @author Wesley Nitsckie
* @example :
*/
class getIcon extends object
{

    /**
    * @var string $type: The extension for the filename for the icon
    */
    var $type;
    /**
    * @var string $name: The name of the icon file, without the extension
    */
    var $name;
    /**
    * @var string $iconfolder: The icon folder for the icons (used so that
    *   it can be set to other than the default
    */
    var $iconfolder;
    /**
    * @var string $_objConfig: string for the configuration object
    */
    var $_objConfig;
    /**
    * @var string $align: optional align property
    */
    var $align;
    /**
    * @var string $alt: The alt text for the icon image
    */
    var $alt;
    /**
    * @var string $title: The title text for the icon image
    */
    var $title;
    /**
    * @var string $extra: Additional attributes for the icon image
    */
    var $extra;

    /**
    * Standard init method
    */
    function init()
    {
        $this->_objConfig = & $this->getObject('altconfig','config');
        $this->_objLanguage = & $this->getObject('language', 'language');
        $this->_objSkin = & $this->getObject('skin','skin');
        
        $this->align="absmiddle";
    }


    /**
    * Method to set the icon parameters
    * @param string $name The name of the icon file before the 'extension',
    *   but not including the extension. For
    *   example, for the icon "help.gif", $name would be set to "help".
    * @param string $type The file type / extension (usually gif or png). For
    *   example, for the icon "help.gif", $type would be set to "gif" or left out.
    * @param string $iconfolder The iconfolder to use, defaults to the
    *  one specified in the config file for KNG
    *
    */
    function setIcon($name, $type = 'gif', $iconfolder='icons/')
    {
        $this->name = $name;
        
        // Just to be explicit - Tohir
        if ($type == NULL) {
            $this->type = 'gif';
        } else {
            $this->type = $type;
        }
        
        // Before Setting the Icon Folder, check if file exists
        $this->_checkIconInSkin ($iconfolder);
    }
    
    /**
    * Method to set the icon folder, depending on whether the file exists in a skin
    * It sets the folder for the icon
    *
    * @param string $folder Folder to check for file
    * @access private
    */
    function _checkIconInSkin ($folder)
    {
        // Check if last character of folder is a slash, else add one
        if (substr($folder, -1) != '/') {
            $folder .= '/';
        }
        
        // Prepare Filename - Folder + name + Extension
        $filename = $folder.$this->name.'.'.$this->type;
        
        // Check if file exists in the current skin
        if (file_exists($this->_objSkin->getSkinLocation().$filename)) {
            $this->iconfolder = $this->_objSkin->getSkinUrl().$folder;
        } else {
            // else set folder to be the _common skin
            $this->iconfolder = $this->_objConfig->getskinRoot().'/_common/'.$folder;
        }
    }

    /**
    * Method to set the module icon parameters
    * @param string $name The name of the icon file before the 'extension',
    *   but not including the extension. For
    *   example, for the icon "help.gif", $name would be set to "help".
    */
    function setModuleIcon($name)
    {
        // Use Internal Method to set Icon
        $this->setIcon($name, NULL, 'icons/modules/');
    }

    /**
    * Method to return edit linked icon
    * @param string $url The uri generated path for the task to be performed
    */
    function getEditIcon($url)
    {
        $this->setIcon('edit_sm');
        // Set title to be the word delete
        $this->title = $this->_objLanguage->languagetext('word_edit','security');
        $objLink = $this->newObject('link', 'htmlelements');
        $objLink->href=$url;
        $objLink->link=$this->show();

        return $objLink->show();
    }

    /**
   * Method to return delete linked icon
   * @param string $url The uri generated path for the task to be performed
   */
    function getDeleteIcon($url)
    {
        $this->setIcon('delete');
        // Set title to be the word delete
        $this->title = $this->_objLanguage->languagetext('word_delete','security');
        $objLink = $this->newObject('link', 'htmlelements');
        $objLink->href=$url;
        $objLink->link=$this->show();

        return $objLink->show();
    }

   /**
   *
   * Method to return a delete icon linked to confirm
   *
   * @param string $id The id of the item to be deleted
   * @param string $deleteArray The array of parameters for the querystring
   * @param string $callingModule THe module which is calling it.
   *
   */
    function getDeleteIconWithConfirm($id, $deleteArray=NULL, $callingModule=NULL, $deletephrase='phrase_confirmdelete')
    {
        //Set up the delete array
        if ($deleteArray == NULL) {
            $deleteArray = array(
              'action' => $action,
              'confirm'=>'yes',
              'action' => 'managelist',
              'id' => $id);
        }
        //Set the delete icon
        $this->setIcon("delete");
        // Set title to be the word delete
        $this->title = $this->_objLanguage->languagetext('word_delete','security');
        //Get the delete icon for the confirm object
        $delIcon = $this->show();
        //Create an instance of the confirm object
        $objConfirm = $this->newObject('confirm','utilities');
        if ($callingModule) {
            $location = $this->uri($deleteArray, $callingModule);
        } else {
            $location = $this->uri($deleteArray);
        }

        if ($deletephrase == 'phrase_confirmdelete') {
            $deletephrase = $this->_objLanguage->languageText($deletephrase,'security');
        }

        $objConfirm->setConfirm($delIcon, $location, $deletephrase);
        return $objConfirm->show();
    }

    /**
    * Method to return delete linked icon
    * @param string $url The uri generated path for the task to be performed
    */
    function getAddIcon($url)
    {
        $this->setIcon("add");
        $objLink = $this->newObject('link', 'htmlelements');
        $objLink->href=$url;
        $objLink->link=$this->show();
        return $objLink->show();
    }

     /**
    * Method to return upload linked icon
    * @param string $url The uri generated path for the task to be performed
    */
    function getUploadIcon($url)
    {
        $this->setIcon("folder_up");
        $objLink = $this->newObject('link', 'htmlelements');
        $objLink->href=$url;
        $objLink->link=$this->show();
        return $objLink->show();
    }

     /**
    * Method to return upload linked icon
    * @param string $url The uri generated path for the task to be performed
    */
    function getDownloadIcon($url)
    {
        $this->setIcon("download");
        $objLink = $this->newObject('link', 'htmlelements');
        $objLink->href=$url;
        $objLink->link=$this->show();
        return $objLink->show();
    }

     /**
    * Method to return upload linked icon
    * @param string $url The uri generated path for the task to be performed
    */
    function getViewIcon($url)
    {
        $this->setIcon("bookopen");
        $objLink = $this->newObject('link', 'htmlelements');
        $objLink->href=$url;
        $objLink->link=$this->show();
        return $objLink->show();
    }

    /**
    *
    * Method to return an icon with a link
    *
    * @param string $url The uri generated path for the task to be performed
    * @param string $name The name of the icon file before the 'extension',
    *   but not including the extension. For
    *   example, for the icon "help.gif", $name would be set to "help".
    * @param string $type The file type / extension (usually gif or png). For
        example, for the icon "help.gif", $type would be set to "gif" or left out.
    * @param string $iconfolder The iconfolder to use, defaults to the
    *  one specified in the config file for KNG
    *
    */
    function getLinkedIcon($url, $name, $type = 'gif', $iconfolder='icons/')
    {
        $this->setIcon($name, $type, $iconfolder);
        $objLink = $this->newObject('link', 'htmlelements');
        $objLink->href=$url;
        $objLink->link=$this->show();
        return $objLink->show();
    }

    /**
    * Method to show the icon
    * @return the icon URL as a string
    */
    function show()
    {
        $ret = "<img src=\"" . $this->iconfolder
            . $this->name . "." . $this->type
            . "\" border=\"0\"";
        if ($this->align) {
            $ret .= " align=\"".$this->align."\"";
        }
        //Try alt first, if not try title, and as a last resort use name
        if ($this->alt) {
            $ret .= " alt=\"".$this->alt."\"";
        } else {
            if ($this->title) {
                $ret .= " alt=\"".$this->title."\"";
            } else {
                $ret .= " alt=\"".$this->name."\"";
            }
        }
        // try title first, then alt, and as a last resort use name
        if ($this->title) {
            $ret .= " title=\"".$this->title."\"";
        } else {
            if ($this->alt) {
                $ret .= " title=\"".$this->alt."\"";
            } else {
                $ret .= " title=\"".$this->name."\"";
            }
        }
    // additional attributes
    if($this->extra) {
        $ret .= $this->extra;
    }
        $ret .=" />";
        return $ret;
    }
}
?>