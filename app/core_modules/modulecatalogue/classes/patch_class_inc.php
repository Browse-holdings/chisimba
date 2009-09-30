<?php
/**
 * The patch class is used to read and write module version information,
 *  as well as to apply patches to modules
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
 * @package   modulecatalogue
 * @author    Nic Appleby <nappleby@uwc.ac.za>
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 */

class patch extends dbtable {

    /**
     * Configuration object
     *
     * @var object $objConfig
     */
    public $objConfig;

    /**
     * Object to find module register file
     *
     * @var object $objModFile
     */
    protected $objModfile;

    /**
     * Object to get language elements
     *
     * @var object $objLanguage
     */
    public $objLanguage;

    /**
     * Chisimba init function
     *
     */
    public function init() {
        try {
            parent::init('tbl_modules');
            $this->objConfig = $this->getObject('altconfig','config');
            $this->objModFile = $this->getObject('modulefile','modulecatalogue');
            $this->objLanguage = $this->getObject('language','language');
        } catch (Exception $e) {
            echo customException::cleanUp($e->getMessage());
            exit(0);
        }
    }

    /**
    * This is a method to return an array of the registered modules
    * that have a more recent version in code than in the database
    * @returns array $modules
    */
    public function checkModules() {
        try {
            $modArray=$this->getAll();
            $modules=array();
            foreach ($modArray as $module) {
                $codeVersion = (float)$this->readVersion($module['module_id']);
                $dbVersion = (float)$module['module_version'];
                // Now compare the two
                //echo "{$module['module_id']} $dbVersion >= $codeVersion<br/>";
                if ($codeVersion>$dbVersion) {
                    //check for xml document
                    $description = $this->readUpdateDesc($module['module_id']);
                    if(empty($description) || $description == '') {
                        $description = $this->objLanguage->languageText('mod_modulecatalogue_newlangitems','modulecatalogue');
                    }
                    if ($updateFile = $this->objModFile->findSqlXML($module['module_id'])) {
                        $check = file_get_contents($updateFile);
                        if(!empty($check)) {
                            if (!$objXml = simplexml_load_file($updateFile)) {
                                throw new Exception($this->objLanguage->languageText('mod_modulecatalogue_badxml').' '.$updateFile);
                            }
                            $desc = $objXml->xpath("//update[version='{$codeVersion}']/description");
                            $description .= $desc[0];
                            //echo $desc[0]."<br/>";var_dump($desc);die();
                        } else {
                            log_debug("WARNING: module {$module['module_id']} has an invalid sql_updates.xml document. Ignoring.");
                        }
                    }
                    // else add in the description from the register file

                    $modules[]=array('module_id'=>$module['module_id'],'old_version'=>$dbVersion,'new_version'=>$codeVersion,'desc'=>$description);
                }
            }
            return $modules;
        } catch (Exception $e) {
            echo customException::cleanUp($e->getMessage());
            exit(0);
        }
    }

    /**
    * This method returns the version of a module in the database
    * ie: The version level of the emodule at the time it was registered.
    * @param  string $module the module to lookup
    * @return string $version the version in the database
    */
    function getVersion($module) {
        try {
            $row = $this->getRow('module_id',$module);
            if (!is_array($row)) {
                return FALSE;
            }
            return $row['module_version'];
        } catch (Exception $e) {
            echo customException::cleanUp($e->getMessage());
            exit(0);
        }
    }

    /**
    * This method calls a function to read the XML file
    * and walks through it, processing each update
    * @param string $modname the name of the module
    *
    */
    function applyUpdates($modname) {
        try {
            // Find the updates file
            $this->objModule = $this->getObject('modules','modulecatalogue');
            $this->objModuleAdmin = $this->getObject('modulesadmin','modulecatalogue');
            $this->objModfile = $this->getObject('modulefile','modulecatalogue');
            //check that there are no new unmet dependencies
            $rData = $this->objModfile->readRegisterFile($this->objModfile->findregisterfile($modname));
            if (isset($rData['DEPENDS'])) {
                $missing = FALSE;
                $localModules = $this->objModfile->getLocalModulelist();
                $unMetDep = $notPresentDep = array();
                foreach ($rData['DEPENDS'] as $dep) {
                    if (!$this->objModule->checkIfRegistered($dep)) {
                        $missing = TRUE;
                        if (in_array($dep,$localModules)) {
                            $unMetDep[] = $dep;
                        } else {
                            $notPresentDep[] = $dep;
                        }
                    }
                }
                if ($missing) {
                    return array('unMetDep'=>$modname,'modules'=>$unMetDep,'missing'=>$notPresentDep);
                }
            }
            $data=array();
            $file=$this->objModFile->findSqlXML($modname);
            // Apply the table changes
            $oldversion = (float)$this->getVersion($modname);
            $result = array();
            if (file_exists($file)){
                $check = file_get_contents($file);
                if (!empty($check)) {
                $objXml = simplexml_load_file($file);
                foreach ($objXml->update as $update) {
                    $ver = (float)$update->version;
                    $verStr = str_replace('.','_',$update->version);
                    if ($ver>$oldversion) {
                        foreach ($update->data as $data) {
                            foreach ($data as $opKey => $opValue) {
                                $pData = array();
                                $change = '';
                                switch ($opKey) {
                                    case 'name':
                                        $pData[$opKey] = (string)$opValue;
                                        break;
                                    case 'add':
                                        $name = (string)$opValue->name;
                                        $innerData = array();
                                        foreach ($opValue as $rowKey => $rowVal) {
                                            if ($rowKey != 'name') {
                                                $k = (string)$rowKey;
                                                $rowVal = (string)$rowVal;
                                                if (($rowKey == 'notnull')||($rowKey =='unsigned')) {
                                                            if ($this->dbType == 'pgsql') {
                                                                $rowVal = ($rowVal == 0)? 'f':'t';
                                                            }
                                                        }
                                                $v = $rowVal;
                                                $innerData[$k] = $v;
                                            }
                                        }
                                        $pData[$opKey] = array($name=>$innerData);
                                        break;
                                    case 'remove':
                                        $op = (string)$opKey;
                                        $strVal = (string)$opValue;
                                        $pData[$op] = array($strVal => array());
                                        break;
                                    case 'change':
                                        $op = (string)$opKey;
                                        $chArray = array();
                                        $change = array();
                                        foreach ($opValue as $name => $chData) {
                                            foreach ($chData as $chKey => $chVal) {
                                                $chKey = (string)$chKey;
                                                if (($chKey == 'notnull')||($chKey=='unsigned')) {
                                                    if ($this->dbType == 'pgsql') {
                                                        $chVal = (string)$chVal;
                                                        $chVal = ($chVal == 0)? 'f':'t';
                                                    }
                                                }
                                                if ($chKey == 'definition') {
                                                    $def = array();
                                                    foreach ($chVal as $inKey => $inVal) {
                                                        $inKey = (string)$inKey;
                                                        $inVal = (string)$inVal;
                                                        if (($inKey == 'notnull')||($inKey=='unsigned')) {
                                                            if ($this->dbType == 'pgsql') {
                                                                $inVal = ($inVal == 0)? 0:'t';
                                                            }
                                                        }
                                                        $def[$inKey] = $inVal;
                                                    }
                                                    $chArray[$chKey] = $def;
                                                } else {
                                                    $chArray[$chKey] = (string)$chVal;
                                                }
                                            }
                                            $change[$name]=$chArray;
                                            $pData[$op] = $change;
                                        }
                                        break;
                                    case 'rename':
                                        $op = (string)$opKey;
                                        $chArray = array();
                                        $change = array();
                                        foreach ($opValue as $name => $chData) {
                                            foreach ($chData as $chKey => $chVal) {
                                                $chKey = (string)$chKey;
                                                if ($chKey == 'definition') {
                                                    $def = array();
                                                    foreach ($chVal as $inKey => $inVal) {
                                                        $inKey = (string)$inKey;
                                                        $def[$inKey] = (string)$inVal;
                                                    }
                                                    $chArray[$chKey] = $def;
                                                } else {
                                                    if ($chKey == 'name') {
                                                        $chArray[$chKey] = (string)$chVal;
                                                    }
                                                }
                                            }
                                            $change[$name]=$chArray;
                                            $pData[$op] = $change;
                                        }
                                        break;
                                    case 'insert':
                                        $op = (string)$opKey;
                                        $change = array();

                                        foreach($opValue as $rowKey => $rowVal){
                                            $change[(string)$rowKey] = (string)$rowVal;
                                        }
                                        $pData[$op] = $change;
                                        break;

                                    default:
                                        throw new customException('error in patch data');
                                        break;
                                }

                                $field2change = array_keys($pData['change']);
                                $existfields = $this->objModuleAdmin->listTblFields($update->table);
                                foreach($field2change as $changes) {
                                    if(in_array($changes, $existfields)) {
                                        // return FALSE;
                                        // No changes were needed so we bring the mod to the version specified
                                        $patch = array('moduleid'=>$modname,'version'=>$ver,'tablename'=>$update->table,
                                                       'patchdata'=>$pData,'applied'=>$this->objModule->now());
                                        $this->objModule->insert($patch,'tbl_module_patches');
                                    }
                                }
                                if ($this->objModuleAdmin->alterTable($update->table,$pData,true) == true) {
                                    //$this->objModuleAdmin->alterTable($update->table,$pData,false);
                                    if ($this->objModuleAdmin->alterTable($update->table,$pData,false)!=MDB2_OK) {
                                        return FALSE;
                                    }
                                    $patch = array('moduleid'=>$modname,'version'=>$ver,'tablename'=>$update->table,
                                    'patchdata'=>$pData,'applied'=>$this->objModule->now());
                                    $this->objModule->insert($patch,'tbl_module_patches');
                                } else {
                                    return FALSE;
                                }
                            }
                        }
                    }
                }
            }
            }
            //update version info in db
            $regData = $this->objModfile->readRegisterFile($this->objModfile->findregisterfile($modname));
            if (!$this->objModuleAdmin->installModule($regData,TRUE)) {
                return $this->objModuleAdmin->getLastError();
            }
            $result['current'] = $this->getVersion($modname);
            $result['old'] = $oldversion;
            $result['modname'] = $modname;

            // finally reload the defaultdata
            $objModAdmin = $this->getObject('modulesadmin');
            $objModAdmin->loadData($modname);
            // Now pass along the info to the template.
            return $result;
        } catch (Exception $e) {
            echo customException::cleanUp($e->getMessage());
            exit(0);
        }
    }

    /**
    * This method reads a register.conf file
    * And returns the module version number
    * @param string $module the module id
    * @return string The module version
    */
    private function readVersion($module) {
        try {
            //Check that the register file is there.
            if (!$regdata = file($this->objModFile->findRegisterFile($module))) {
                return FALSE;
            }
            //var_dump($regdata);
            // Now look up the version number from that file
            foreach  ($regdata as $line) {
                $array = explode(': ',$line);
                //var_dump($array); //die();
                switch ($array[0]) {
                    case 'MODULE_VERSION':
                        return $array[1];
                        break;
                    default:
                        break;
                }
            }
        } catch (Exception $e) {
            echo customException::cleanUp($e->getMessage());
            exit(0);
        }
    }

   /**
    * This method reads a register.conf file
    * And returns the module update description
    *
    * @param string $module the module id
    * @return string The module version
    */
    private function readUpdateDesc($module) {
        try {
            //Check that the register file is there.
            if (!$regdata = file($this->objModFile->findRegisterFile($module))) {
                return FALSE;
            }
            //var_dump($regdata);
            // Now look up the description from that file
            foreach  ($regdata as $line) {
                $array = explode(': ',$line);
                //var_dump($array); //die();
                switch ($array[0]) {
                    case 'UPDATE_DESCRIPTION':
                        return $array[1];
                        break;
                    default:
                        break;
                }
            }
        } catch (Exception $e) {
            echo customException::cleanUp($e->getMessage());
            exit(0);
        }
    }
}
?>