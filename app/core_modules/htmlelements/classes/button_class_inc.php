<?php
/**
 * This file contains the button class which is used to generate
 * HTML button elements for forms
 *
 *
 * PHP version 5
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the
 * Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */
// security check - must be included in all scripts
if (!
/**
 * Description for $GLOBALS
 * @global unknown $GLOBALS['kewl_entry_point_run']
 * @name   $kewl_entry_point_run
 */
$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}

// Include the HTML base class

/**
 * Description for require_once
 */
require_once("abhtmlbase_class_inc.php");
// Include the HTML interface class

/**
 * Description for require_once
 */
require_once("ifhtml_class_inc.php");

/**
 * Button class controls the rendering of buttons on webpages or forms
 * @category  Chisimba
 * @package   htmlelements
 * @author    Wesley Nitsckie <wnitsckie@uwc.ac.za>
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 * @example:
 *            $this->objButton=new button('buttonname');
 *            $this->objButton->setValue('Button Value');
 *            $this->objButton->setOnClick('alert(\'An onclick Event\')');
 *            $this->objButton->setToSubmit();  //If you want to make the button a submit button
 */
class button extends abhtmlbase implements ifhtml
{
        /**
         *
         * @var object DOM object
         */
        public $domDoc;

        /**
    * @var string $onsubmit: The javascript to be executed on submit, if any.
    */
    public $onsubmit;

    /**
    * @var bool $issubmitbutton: True | False whether the button is a submit
    *           button or not.
    */
    public $issubmitbutton;

    /**
     * If true, the button type should be set to "reset".
     *
     * @access protected
     * @var $isresetbutton boolean
     */
    protected $isresetbutton;
    
    /**
    * @var string $iconclass Whether or not to use the sexybuttons interface elements.
    */
    public $sexyButtons = TRUE;
    

    /**
    * Initialization method to set default values
    *
    * @param string $name    : name of the button
    * @param string $value   optional :value of the button
    * @param string $onclick optional :javascript function that will be called
    */
    public function button($name=null, $value = null, $onclick = null)
    {
        $this->name = $name;
        $this->value = $value;
        $this->onclick = $onclick;
        $this->cssClass = 'button';
        //$this->cssId = 'input_'.$name;
    }

    /**
     * Method to set the action for the onclick event
     * for the button
     *
     * @param string $onclick
     * @return void
     * @access public
     */
    public function setOnClick($onclick)
    {
        $this->onclick = $onclick;
    }
    
    /**
     * Method to set the iconclass for the sexy buttons
     * Can be one of: ok, cancel, add, delete, download, download2, upload, search, find, first, prev, next, last, play, pause, 
     *                rewind, forward, stop, reload, sync, save, email, print, heart, like, dislike, accept, decline, home, 
     *                help, info, cut, copy, paste, erase, undo, redo, edit, calendar, user, settings, wrench, cart, wand
     *
     * @param string $onclick
     * @return void
     * @access public
     */
    public function setIconClass($iconclass)
    {
        $this->iconclass = $iconclass;
    }

    /**
     * Method to set the cssClass private variable
     * which determines the DOM class of the button as
     * definied in the CSS
     *
     * @param string $cssClass the class
     * @return void
     * @access public
     */
    function setCSS($cssClass)
    {
        $this->cssClass = $cssClass;
    }

    /**
     * Method to set the cssId private member
     * which determines the DOM id of the button
     *
     * @param string $cssId the Id
     * @return void
     * @access public
     */
    public function setId($cssId)
    {
        $this->cssId = $cssId;
    }

    /**
     * Method used to set the button as
     * a submit button for a form
     *
     * @return void
     * @access public
     */
    public function setToSubmit()
    {
        $this->issubmitbutton = true;
    }

    /**
     * Sets the button type to reset.
     *
     * @access public
     */
    public function setToReset()
    {
        $this->isresetbutton = true;
    }
    
    public function show() {
        if(strtolower($this->sexyButtons) == true) {
            return $this->showSexy();
        }
        else {
            return $this->showDefault();
        }
    }
    
    public function showNew($value){
            $this->domDoc = new DOMDocument('utf-8');
            $domElements['button'] = $this->domDoc->createElement('button');
            $domElements['button']->appendChild($this->domDoc->createTextNode($value));
            if(isset($this->cssClass)){
                    $domElements['button']->setAtribute('class',  $this->cssClass);
            }
            if(isset($this->cssId)){
                    $domElements['button']->setAttribute('id',  $this->cssId);
            }
            if(isset($this->onclick)){
                    $domElements['button']->setAttribute('onclick',  $this->onclick);
            }
    }

    
    /**
    * Method to render the button as an HTML string (sexybutton)
    *
    * @return string Returns the button's html
    */
    public function showSexy()
    {
        $str = '<button';
        $str .= ' value="' . $this->value . '"';
        //check if the buttons is a submit button or a normal button
        if ($this->issubmitbutton) {
            $str .= ' type="submit"';
        } elseif ($this->isresetbutton) {
            $str .= ' type="reset"';
        } else {
            $str .= ' type="button"';
        }
        if ($this->name) {
            $str .= ' name="' . $this->name . '"';
        }
        if ($this->cssId) {
            $str .= ' id="' . $this->cssId . '"';
        }
        $str .= ' class="sexybutton "';
        if ($this->onclick) {
            $str .= ' onclick="' . $this->onclick . '"';
        }
        if ($this->extra) {
            $str .= ' '.$this->extra;
        }
        if(isset($this->iconclass)) {
            $str .= '><span><span><span class="'.$this->iconclass.'">'.$this->value.'</span></span></span></button>';
        }
        else {
            $str .= '><span><span>'.$this->value.'</span></span></button>';
        }

        return $str;
    }
    
    
    /**
    * Method to render the button as an HTML string
    *
    * @return string Returns the button's html
    */
    public function showDefault()
    {
        $str = '<input';
        $str .= ' value="' . $this->value . '"';
        //check if the buttons is a submit button or a normal button
        if ($this->issubmitbutton) {
            $str .= ' type="submit"';
        } elseif ($this->isresetbutton) {
            $str .= ' type="reset"';
        } else {
            $str .= ' type="button"';
        }
        if ($this->name) {
            $str .= ' name="' . $this->name . '"';
        }
        if ($this->cssId) {
            $str .= ' id="' . $this->cssId . '"';
        }
        if ($this->cssClass) {
            $str .= ' class="' . $this->cssClass . '"';
        }
        if ($this->onclick) {
            $str .= ' onclick="' . $this->onclick . '"';
        }
        if ($this->extra) {
            $str .= ' '.$this->extra;
        }
        $str .= ' />';

        return $str;
    }
}

?>