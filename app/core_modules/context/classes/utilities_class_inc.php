<?php

/**
 * Utilities
 *
 * Context Utilities
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
 * @author    Wesley Nitsckie <wnitsckie@uwc.ac.za>
 * @copyright 2007 Wesley Nitsckie
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
/* ----------- data class extends dbTable for tbl_context_usernotes------------*/
// security check - must be included in all scripts
if (! /**
 * Description for $GLOBALS
 * @global string $GLOBALS['kewl_entry_point_run']
 * @name   $kewl_entry_point_run
 */
$GLOBALS ['kewl_entry_point_run']) {
    die ( "You cannot view this page directly" );
}

/**
 * Utilities
 *
 * Context Utilities
 *
 * @category  Chisimba
 * @package   context
 * @author    Wesley Nitsckie <wnitsckie@uwc.ac.za>
 * @copyright 2007 Wesley Nitsckie
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   Release: @package_version@
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
class utilities extends object {

    /**
     * @var object $objDBContext
     */
    public $objDBContext;

    /**
     * @var object $objConfig
     */
    public $objConfig;

    /**
     * @var object $objDBContext
     */
    public $contextCode;

    /**
     * Constructor method to define the table
     */
    public function init() {
        $this->objDBContext = $this->getObject ( 'dbcontext', 'context' );
        $this->objLink = $this->getObject ( 'link', 'htmlelements' );
        $this->objIcon = $this->getObject ( 'geticon', 'htmlelements' );
        $this->objConfig = $this->getObject ( 'config', 'config' );
        $this->objDBContextModules = $this->getObject ( 'dbcontextmodules', 'context' );
        $this->objDBContextParams = $this->getObject ( 'dbcontextparams', 'context' );
        $this->objLanguage = $this->getObject ( 'language', 'language' );
        $this->contextCode = $this->objDBContext->getContextCode ();
        $this->_objContextModules = $this->getObject ( 'dbcontextmodules', 'context' );

    }

    /**
     * Method to get the sliding context menu
     *
     * @return string
     */
    public function getHiddenContextMenu($selectedModule, $showOrHide = 'none', $showOrHideContent = 'none') {
        $str = '';
        $icon = $this->newObject ( 'geticon', 'htmlelements' );
        $icon->setModuleIcon ( 'toolbar' );
        $toolsIcon = $icon->show ();
        $icon->setModuleIcon ( 'context' );
        $contentIcon = $icon->show ();

        $str .= "<a href=\"#\" onclick=\"Effect.toggle('contextmenu','slide', adjustLayout());\">" . $toolsIcon . " Tools</a>";
        $str .= '<div id="contextmenu"  style="width:150px;overflow: hidden;display:' . $showOrHide . ';"> ';
        $str .= $this->getPluginNavigation ( $selectedModule );
        $str .= '</div>';

        $content = $this->getContextContentNavigation ();
        if ($content != '') {
            $str .= "<br/><a href=\"#\" onclick=\"Effect.toggle('contextmenucontent','slide', adjustLayout());\">" . $contentIcon . " Content</a>";
            $str .= '<div id="contextmenucontent"  style="width:150px;overflow: hidden;display:' . $showOrHideContent . ';"> ';
            $str .= $content;
            $str .= '</div>';
        }

        $objFeatureBox = $this->getObject ( 'featurebox', 'navigation' );

        return $objFeatureBox->show ( 'Toolbox', $str, 'contexttoolbox' );
    }

    /**
     * Method to get the left Navigation
     * with the context plugins
     *
     * @param  string $contextCode
     * @access public
     * @return string
     */
    public function getPluginNavigation($selectedModule = NULL) {
        $objSideBar = $this->newObject ( 'sidebar', 'navigation' );
        $objModule = $this->newObject ( 'modules', 'modulecatalogue' );
        //$objContentLinks = $this->getObject('dbcontextdesigner','contextdesigner');
        $objIcon = $this->getObject ( 'geticon', 'htmlelements' );

        $arr = $this->_objContextModules->getContextModules ( $this->objDBContext->getContextCode () );
        $isregistered = '';

        //create the nodes array
        $nodes = array ();
        $children = array ();
        $nodes [] = array ('text' => $this->objDBContext->getMenuText () . ' - Home', 'uri' => $this->uri ( NULL, 'context' ), 'nodeid' => 'context' );
        if (is_array ( $arr )) {
            foreach ( $arr as $contextModule ) {
                //$modInfo =$objModule->getModuleInfo($plugin['moduleid']);
                if ($contextModule ['moduleid'] == 'cms') {
                    $isregistered = TRUE;
                } else {
                    $modInfo = $objModule->getModuleInfo ( $contextModule ['moduleid'] );
                    $moduleLink = $this->uri ( NULL, $contextModule ['moduleid'] ); //$this->uri(array('action' => 'contenthome', 'moduleid' => $contextModule['moduleid']));
                    $nodes [] = array ('text' => ucwords ( $modInfo ['name'] ), 'uri' => $moduleLink, 'nodeid' => $contextModule ['moduleid'] );
                }
            }

            return $objSideBar->show ( $nodes, $selectedModule );
        } else {
            return '';
        }
    }

    /**
     * Method to get the navigation menu
     * for the content section of the context
     *
     * @access public
     * @param  string $selectedLink The link that you are currently on
     * @return string
     */
    public function getContextContentNavigation($selectedLink = NULL) {
        $objSideBar = $this->getObject ( 'sidebar', 'navigation' );
        $objModule = $this->getObject ( 'dbcontextmodules', 'context' );
        //create the nodes array
        $nodes = array ();

        return '';
    }

    /**
     * Method to check if a user can join a
     * context
     * @param  string  $contextCode The context Code
     * @return boolean
     * @access public
     * @author Wesley Nitsckie
     */
    public function canJoin($contextCode) {
        // TODO


        //check if the user is logged in to access an open context


        //check if the user is registered to the context and he is logged in


        //if the context is public then the user can access the context , but only limited access


        return TRUE;
    }

    /**
     * Method to create a link to the course home
     *
     * @return string
     */
    function getContextLinks() {
        $this->objIcon->setIcon ( "home" );
        $this->objIcon->alt = $this->objLanguage->languageText ( "mod_context_coursehome", 'context' );
        $this->objIcon->align = "absmiddle";

        $this->objLink->href = $this->URI ( NULL, 'context' );
        $this->objLink->link = $this->objIcon->show ();
        $str = $this->objLink->show ();

        return $str;
    }

    /**
     * Method to create links to the contents
     * and to the course
     *
     * @return string
     */
    function getContentLinks() {
        $this->objIcon->setModuleIcon ( "content" );
        $this->objIcon->alt = $this->objLanguage->languageText ( "mod_context_coursecontent", 'context' );
        $this->objIcon->align = "absmiddle";

        $params = array ('nodeid' => $this->getParam ( 'nodeid' ), 'action' => 'content' );
        $this->objLink->href = $this->URI ( $params, 'context' );
        $this->objLink->link = $this->objIcon->show ();
        $str = $this->objLink->show ();

        return $str;
    }

    /**
     * Method to create links to the course admin
     *
     * @return string
     */
    function getCourseAdminLink() {
        $this->objIcon->setModuleIcon ( "contextadmin" );
        $this->objIcon->alt = $this->objLanguage->languageText ( "mod_context_courseadmin", 'context' );
        $this->objIcon->align = "absmiddle";

        $params = array ('action' => 'courseadmin' );
        $this->objLink->href = $this->URI ( $params, 'contextadmin' );
        $this->objLink->link = $this->objIcon->show ();
        $str = $this->objLink->show ();

        return $str;
    }

    /**
     * Method used to get the path to the course folder
     *
     * @param  string $contextCode The context code
     * @return string
     */
    function getContextFolder($contextCode = NULL) {
        if ($contextCode == NULL) {
            $contextCode = $this->contextCode;
        }
        $str = $this->objConfig->siteRootPath () . 'usrfiles/content/' . $contextCode . '/';

        return $str;

    }

    /**
     * Method used to get the path to the images  folder
     * for a given course code
     *
     * @param  string $contextCode The context code
     * @return string
     */
    function getImagesFolder($contextCode = NULL) {
        return $this->getContextFolder ( $contextCode ) . 'images/';
    }

    /**
     * Method used to get the path to the maps  folder
     * for a given course code
     *
     * @param  string $contextCode The context code
     * @return string
     */
    function getMapsFolder($contextCode = NULL) {
        return $this->getContextFolder ( $contextCode ) . 'maps/';
    }

    /**
     * Method to get the context menu
     *
     * @return string
     * @param  void
     * @access public
     */
    public function getContextMenu() {
        try {
            //initiate the objects
            $objSideBar = $this->newObject ( 'sidebar', 'navigation' );
            $objModules = $this->newObject ( 'modules', 'modulecatalogue' );

            //get the contextCode
            $this->objDBContext->getContextCode ();

            //create the nodes array
            $nodes = array ();

            //get the section id
            $section = $this->getParam ( 'id' );

            //create the home for the context
            $nodes [] = array ('text' => $this->objDBContext->getMenuText () . ' -  ' . $this->objLanguage->languageText ( "word_home", 'system', 'Home' ), 'uri' => $this->uri ( NULL, "_default" ) );

            //get the registered modules for this context
            $arrContextModules = $this->objDBContextModules->getContextModules ( $this->contextCode );

            foreach ( $arrContextModules as $contextModule ) {
                $modInfo = $objModules->getModuleInfo ( $contextModule ['moduleid'] );

                $nodes [] = array ('text' => $modInfo ['name'], 'uri' => $this->uri ( array ('action' => 'contenthome', 'moduleid' => $contextModule ['moduleid'] ) ), 'sectionid' => $contextModule ['moduleid'] );
            }

            return $objSideBar->show ( $nodes, $this->getParam ( 'id' ) );

        } catch ( Exception $e ) {
            echo 'Caught exception: ', $e->getMessage ();
            exit ();
        }
    }
}
?>