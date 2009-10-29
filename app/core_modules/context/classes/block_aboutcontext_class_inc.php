<?php

/**
 * About Context block
 *
 * This class generates a block that displays the about information of a context
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
 * @category  Chisimba
 * @package   context
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2007 Tohir Solomons
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */


// security check - must be included in all scripts
if (!
/**
 * Description for $GLOBALS
 * @global entry point $GLOBALS['kewl_entry_point_run']
 * @name   $kewl_entry_point_run
 */
$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
// end security check


/**
 * About Context block
 *
 * This class generates a block that displays the about information of a context
 *
 * @category  Chisimba
 * @package   context
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2007 Tohir Solomons
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   Release: @package_version@
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
class block_aboutcontext extends object {
/**
 * @var string $title The title of the block
 */
    public $title;

    /**
     * @var object $objLanguage String to hold the language object
     */
    private $objLanguage;

    /**
     * Standard init function to instantiate language object
     * and create title, etc
     */
    public function init() {

        try {
            $this->loadClass('link','htmlelements');
            $this->objLanguage =  $this->getObject('language', 'language');
            $this->title = ucWords($this->objLanguage->code2Txt('mod_context_aboutcontext', 'context'));

        } catch (customException $e) {
            customException::cleanUp();
        }
    }

    /**
     * Standard block show method.
     */
    public function show() {
        $objWashout = $this->getObject('washout', 'utilities');
        $objContext = $this->getObject('dbcontext');
        $objIcon= $this->newObject('geticon','htmlelements');
        $objUser = $this->getObject ( 'user', 'security' );
        $objContextGroups = $this->getObject('managegroups', 'contextgroups');
        $showEdit='';
        if ($objUser->isAdmin () || $objContextGroups->isContextLecturer()) {
            $objIcon->setIcon('edit');
            $link=new link($this->uri(array('action'=>'edit','contextcode'=>$objContext->getContextCode()),'contextadmin'));
            $link->link=$objIcon->show();
            $showEdit=$link->show();
        }
        return $showEdit.$objWashout->parseText($objContext->getAbout());
    }
}
?>
