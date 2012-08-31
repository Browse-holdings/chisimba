<?php

/* ------------iconrequest class extends controller ---------------- */
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
// end security check

/**
 * Module to provide default pre-login environment
 * @category Chisimba
 * @package prelogin
 * @author Nic Appleby
 * @copyright GNU/GPL UWC 2006
 * @version $Id: controller.php,v 1.0 2006/01/29
 */
class prelogin extends controller {

    /**
     *  The splashscreen DB blocks object
     *
     * @var object
     */
    public $objPreloginBlocks;

    /**
     * The user security object
     *
     * @var object
     */
    public $objUser;

    /**
     *  The object to display the featurebox blocks
     *
     * @var object
     */
    public $objBlocks;

    /**
     * The language management object
     *
     * @var object
     */
    public $objLanguage;

    /**
     * Standard Chisimba init function
     *
     */
    public $TRUE;
    public $FALSE;

    public function init() {
        try {
            // Get objects
            $this->objModule = $this->getObject('modules', 'modulecatalogue');
            $this->objBlocks = $this->getObject('blocks', 'blocks');
            $this->objPLBlocks = $this->getObject('preloginblocks');
            //Check if contentblocks is installed
            $this->cbExists = $this->objModule->checkIfRegistered("contentblocks");
            if ($this->cbExists) {
                $this->objBlocksContent = $this->getObject('dbcontentblocks', 'contentblocks');
            }
            $this->objUser = $this->getObject('user', 'security');
            $this->objLanguage = $this->getObject('language', 'language');
            $this->objSysconfig = $this->getObject('dbsysconfig', 'sysconfig');
            if ($this->objPLBlocks->dbType == "pgsql") {
                $this->TRUE = 't';
                $this->FALSE = 'f';
            } else {
                $this->TRUE = 1;
                $this->FALSE = 0;
            }
            $sysType = $this->objSysconfig->getValue('SYSTEM_TYPE', 'systext');
            $contextBlock = $this->objPLBlocks->getRow('id', 'init_3');
            if ((!isset($contextBlock['content'])) && ($sysType == 'elearn')) {
                $contextBlock['content'] = 'done';
                $contextBlock['visible'] = $this->TRUE;
                $this->objPLBlocks->update('id', 'init_3', $contextBlock);
            }
        } catch (customException $e) {
            customException::cleanUp();
        }
    }

    /**
     * Standard Chisimba dispatch function
     *
     * @return string The template to display
     */
    public function dispatch($action) {
        try {
            switch ($action) {
                case 'admin':
                    if (!$this->objUser->isAdmin()) {
                        return 'notadmin_tpl.php';
                    } else {
                        $contentSmallBlocks = "";
                        $contentWideBlocks = "";
                        if ($this->cbExists) {
                            $contentSmallBlocks = $this->objBlocksContent->getBlocksArr('content_text');
                            $this->setVarByRef('contentSmallBlocks', $contentSmallBlocks);

                            $contentWideBlocks = $this->objBlocksContent->getBlocksArr('content_widetext');
                            $this->setVarByRef('contentWideBlocks', $contentWideBlocks);
                        }
                        return 'admin_tpl.php';
                    }
                case 'editblock':
                    if (!$this->objUser->isAdmin()) {
                        return 'notadmin_tpl.php';
                    } else {
                        $this->setVar('heading', $this->objLanguage->languageText('mod_prelogin_editblock', 'prelogin'));
                        $block = $this->objPLBlocks->getRow('id', $this->getParam('id'));
                        $this->setVar('blockName', $block['title']);
                        $this->setVar('blockContent', $block['content']);
                        $this->setVar('location', $block['side']);
                        $this->setVar('block', array('module' => $block['blockmodule'], 'name' => $block['blockname']));
                        $this->setVar('id', $block['id']);
                        $bType = ($block['isblock'] == $this->TRUE) ? 'block' : 'nonblock';
                        $this->setVar('bType', $bType);
                        $contentSmallBlocks = "";
                        $contentWideBlocks = "";
                        if ($this->cbExists) {
                            $contentSmallBlocks = $this->objBlocksContent->getBlocksArr('content_text');
                            $this->setVarByRef('contentSmallBlocks', $contentSmallBlocks);

                            $contentWideBlocks = $this->objBlocksContent->getBlocksArr('content_widetext');
                            $this->setVarByRef('contentWideBlocks', $contentWideBlocks);
                        }
                        return 'addblock_tpl.php';
                    }
                case 'addblock':
                    if (!$this->objUser->isAdmin()) {
                        return 'notadmin_tpl.php';
                    } else {
                        $this->setVar('heading', $this->objLanguage->languageText('mod_prelogin_addblock', 'prelogin'));
                        return 'addblock_tpl.php';
                    }
                case 'submitblock':
                    if (!$this->objUser->isAdmin()) {
                        return 'notadmin_tpl.php';
                    } else {
                        $title = $this->getParam('title');
                        if ($title == '') {
                            $title = 'untitled';
                        }
                        $side = $this->getParam('side');
                        $bType = ($this->getParam('type') == 'block') ? $this->TRUE : $this->FALSE;
                        $content = htmlentities($this->getParam('content'), ENT_QUOTES);
                        //var_dump($content); die();
                        $block = $this->getParam('moduleblock');
                        if ($block) {
                            $arrBlock = explode('|', $block);
                            $blockModule = $arrBlock[0];
                            $blockName = $arrBlock[1];
                        } else {
                            $blockModule = '';
                            $blockName = '';
                        }
                        $data = array('title' => $title, 'side' => $side, 'content' => $content, 'isblock' => $bType, 'blockname' => $blockName, 'blockmodule' => $blockModule);
                        if ($id == $this->getParam('id')) {
                            $result = $this->objPLBlocks->updateBlock($id, $data);
                        } else {
                            $result = $this->objPLBlocks->insertBlock($data);
                        }
                        //echo $result;
                        return $this->nextAction('admin', array('change' => '2'));
                    }
                //move a block up
                case 'moveup':
                    if (!$this->objUser->isAdmin()) {
                        return 'notadmin_tpl.php';
                    } else {
                        $this->objPLBlocks->moveRecUp($this->getParam('id'));
                        return $this->nextAction('admin', array('change' => '2'));
                    }
                //move a block down
                case 'movedown':
                    if (!$this->objUser->isAdmin()) {
                        return 'notadmin_tpl.php';
                    } else {
                        $this->objPLBlocks->moveRecDown($this->getParam('id'));
                        return $this->nextAction('admin', array('change' => '2'));
                    }
                //delete a block record
                case 'delete':
                    if (!$this->objUser->isAdmin()) {
                        return 'notadmin_tpl.php';
                    } else {
                        $this->objPLBlocks->delete('id', $this->getParam('id'));
                        return $this->nextAction('admin', array('change' => '2'));
                    }
                case 'update':
                    if (!$this->objUser->isAdmin()) {
                        return 'notadmin_tpl.php';
                    } else {
                        $contentSmallBlocks = "";
                        $contentWideBlocks = "";
                        if ($this->cbExists) {
                            //Get all wide blocks
                            $contentWideBlocks = $this->objBlocksContent->getBlocksArr('content_widetext');
                            $bType = 0;
                            foreach ($contentWideBlocks as $contentWideBlock) {
                                $data = array('title' => $contentWideBlock["title"],
                                    'side' => 'middle', 'content' => $contentWideBlock["blocktext"],
                                    'isblock' => $bType, 'blockname' => "context", 'blockmodule' => "contentblocks");
                                // _mc_vis => middle content

                                $id = $contentWideBlock['id'] . '_mc';

                                // Update content block data for PLBs
                                if ($this->getParam($contentWideBlock['id'] . '_mc_vis') == "on") {
                                    $data['visible'] = $this->TRUE;
                                } else {
                                    $data['visible'] = $this->FALSE;
                                }
                                $result = $this->objPLBlocks->updateContentBlock($id, $data);
                            }
                            //Get all side blocks
                            $contentSmallBlocks = $this->objBlocksContent->getBlocksArr('content_text');
                            foreach ($contentSmallBlocks as $csBlock) {
                                $data = array('title' => $csBlock["title"],
                                    'content' => $csBlock["blocktext"],
                                    'isblock' => $bType, 'blockname' => "context", 'blockmodule' => "contentblocks");

                                // _mc_vis => left content
                                $id = $csBlock['id'] . '_lc';
                                // Update content block data for PLBs
                                if ($this->getParam($csBlock['id'] . '_lc_vis') == "on") {
                                    $data['visible'] = $this->TRUE;
                                } else {
                                    $data['visible'] = $this->FALSE;
                                }
                                $data['side'] = "left";

                                $result = $this->objPLBlocks->updateContentBlock($id, $data);

                                // _mc_vis => right content
                                $id = $csBlock['id'] . '_rc';
                                // Update content block data for PLBs
                                if ($this->getParam($csBlock['id'] . '_rc_vis') == "on") {
                                    $data['visible'] = $this->TRUE;
                                } else {
                                    $data['visible'] = $this->FALSE;
                                }
                                $data['side'] = "right";

                                $result = $this->objPLBlocks->updateContentBlock($id, $data);
                            }
                        }
                        $vibe = array();
                        $blocks = $this->objPLBlocks->getAll();
                        if (isset($blocks)) {
                            foreach ($blocks as $block) {
                                ($this->getParam($block['id'] . '_vis') == 'on') ? $vis = $this->TRUE : $vis = $this->FALSE;
                                //var_dump($block);var_dump($vis);
                                if ($block['visible'] !== $vis) {
                                    $this->objPLBlocks->updateVisibility($block['id'], $vis);
                                }
                            }
                        }
                        return $this->nextAction('admin', array('change' => '2'));
                    }
                default:
                    // Load JavaScript to validate login and place in header
                    //$js='lib/javascript/find_validate.js';
                    //$this->setVar('jsLoad', array($js));
                    // Suppress Toolbar - user isn't logged in yet
                    $this->setVar('pageSuppressToolbar', TRUE);
                    //Set Layout Template To Null
                    $this->setLayoutTemplate(NULL);
                    return 'prelogin_tpl.php';
            }
        } catch (customException $e) {
            customException::cleanUp();
        }
    }

    /**
     * Overridden method to determine whether or not login is required
     *
     * @return FALSE
     */
    public function requiresLogin() {
        switch ($this->getParam('action')) {
            case 'admin':
            case 'update':
            case 'delete':
            case 'moveup':
            case 'movedown':
            case 'addblock':
            case 'editblock':
            case 'submitblock':
                return TRUE;
            default:
                return FALSE;
        }
    }

}

?>
