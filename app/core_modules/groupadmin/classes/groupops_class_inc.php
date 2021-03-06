<?php

/**
 * groupops class
 *
 * All the operations for groupadmin encapsulated
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
 *
 * @category  Chisimba
 * @package   groupadmin
 * @author    Paul Scott <pscott@uwc.ac.za>
 * @copyright 2004-2007, University of the Western Cape & AVOIR Project
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id: $
 * @link      http://avoir.uwc.ac.za
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

/**
 * The group admin operations class
 *
 * @copyright  Paul Scott
 * @package    groupadmin
 * @version    0.1
 * @since      28 Jan 2009
 * @author     Paul Scott <pscott@uwc.ac.za>
 * @filesource
 */
class groupops extends object {

    /**
     * $_objUsers an association to the userDb object.
     *
     * @access public
     * @var    userDb
     */
    public $objUser;
    public $objMemcache = FALSE;
    public $objAPC = FALSE;
    protected $cacheTTL = 3600;
    public $cachePrefix;

    /**
     * Method to initialize the group operations object.
     *
     * @access public
     * @param  void
     * @return void
     */
    public function init() {
        $this->objUser = $this->getObject('user', 'security');
        $this->objLanguage = $this->getObject('language', 'language');
        $this->objGroups = $this->getObject('groupadminmodel');
        $this->objDBContext = $this->getObject('dbcontext', 'context');

        $objIcon = $this->getObject('geticon', 'htmlelements');
        $objIcon->setIcon('loader');
        $this->loading = ""; //$objIcon->show();

        $this->objDBConfig = $this->getObject('altconfig', 'config');
        // check for memcache
        if (extension_loaded('memcache')) {
            require_once $this->objDBConfig->getSiteRootPath() . 'classes/core/chisimbacache_class_inc.php';
            if ($this->objDBConfig->getenable_memcache() == 'TRUE') {
                $this->objMemcache = TRUE;
            } else {
                $this->objMemcache = FALSE;
            }
            $this->cacheTTL = $this->objDBConfig->getcache_ttl();
        }
        // check for APC
        if (extension_loaded('apc')) {
            if ($this->objDBConfig->getenable_apc() == 'TRUE') {
                $this->objAPC = TRUE;
            } else {
                $this->objAPC = FALSE;
            }
            $this->cacheTTL = $this->objDBConfig->getcache_ttl();
        }

        $this->_serverName = $this->objDBConfig->serverName();
        // set up the cache prefix for this instance
        $this->cachePrefix = $this->_serverName . "_groupadmin_";
    }

    ///////////////////////////////
    //// JSON METHODS ///////////
    ////////////////////////////
    /**
     * Method to get a list of users
     * for a group
     *
     * @param string $groupId
     * @return json
     * @access public
     *
     */
    public function jsonGetGroups($start = 0, $limit = 25) {
        $params["start"] = ($this->getParam("start")) ? $this->getParam("start") : $start;
        $params["limit"] = ($this->getParam("limit")) ? $this->getParam("limit") : $limit;
        $params["search"] = ($this->getParam("fields")) ? json_decode(stripslashes($this->getParam("fields"))) : array('group_define_name', 'title');
        $params["query"] = ($this->getParam("query")) ? $this->getParam("query") : '';
        $params["sort"] = ($this->getParam("sort")) ? $this->getParam("sort") : null;

        $where = "";
        $filter = " LIMIT " . $start . ', ' . $limit;

        if (is_array($params['search'])) {

            $where = " AND (";
            $max = count($params['search']);

            $cnt = 0;

            foreach ($params['search'] as $field) {
                $cnt++;
                $where .= " lower(" . $field . ") LIKE lower('%" . $params['query'] . "%')";
                if ($cnt < $max) {
                    $where .= " OR ";
                }
            }
            $where .= ")";
        }


        $sql = "SELECT pu.group_define_name, pu.group_id, ct.title FROM tbl_perms_groups as pu LEFT join tbl_context as ct on pu.group_define_name = ct.contextcode WHERE group_define_name NOT LIKE '%^%'" . $where;

        $groups = $this->objDBContext->getArray($sql . $filter);
        $totalCount = count($this->objDBContext->getArray($sql));

        if ($totalCount > 0) {
            $arrGroups = array();

            foreach ($groups as $group) {
                $groupId = $this->objGroups->getId($group['group_define_name']);
                $arr = array();
                $arr['group_define_name'] = $group['group_define_name'];
                $arr['title'] = $group['title'];
                $arr['id'] = strval($groupId);

                $arrGroups[] = $arr;
                $arr = null;
            }
            $arr['totalCount'] = strval($totalCount);
            $arr['groups'] = $arrGroups;
            return json_encode($arr);
        } else {
            $arr['totalCount'] = "0";
            $arr['groups'] = array();
            return json_encode($arr);
        }
    }

    public function getJsonGroupUsers($groupId, $start=0, $limit=25) {
        $params["search"] = json_decode(stripslashes($this->getParam("fields")));
        $params["query"] = ($this->getParam("query")) ? $this->getParam("query") : '';
        $params["sort"] = ($this->getParam("sort")) ? $this->getParam("sort") : null;

        $where = "";
        $filter = " LIMIT " . $start . ', ' . $limit;

        if (is_array($params['search'])) {

            $where = " AND (";
            $max = count($params['search']);

            $cnt = 0;

            foreach ($params['search'] as $field) {
                $cnt++;
                $where .= " lower(us." . $field . ") LIKE lower('%" . $params['query'] . "%')";
                if ($cnt < $max) {
                    $where .= " OR ";
                }
            }
            $where .= ")";
        }

        $sql = "SELECT gu.perm_user_id, pu.auth_user_id, us.firstname, us.surname, us.username, us.staffnumber, us.last_login, us.logins, us.emailAddress, us.isActive from tbl_perms_groupusers as gu INNER join tbl_perms_perm_users as pu on gu.perm_user_id = pu.perm_user_id INNER join tbl_users as us on pu.auth_user_id = us.userId WHERE group_id = " . $groupId . $where . " ORDER BY us.surname " . $filter;

        $users = $this->objDBContext->getArray($sql);
        $userCount = count($this->getUsersInGroup($groupId));

        if (count($users > 0)) {

            $arr = array();
            $arrUsers = array();
            foreach ($users as $groupUser) {
                $user = $groupUser;
                $arrUser = array();
                $arrUser['id'] = $user['perm_user_id'];
                $arrUser['userid'] = $user['auth_user_id'];
                $arrUser['username'] = $user['username'];
                $arrUser['staffnumber'] = $user['staffnumber'];
                $arrUser['firstname'] = $user['firstname'];
                $arrUser['surname'] = $user['surname'];
                $arrUser['lastloggedin'] = $user['last_login'];
                $arrUser['emailaddress'] = $user['emailaddress'];
                $arrUser['isactive'] = $user['isactive'];
                $arrUsers[] = $arrUser;
            }

            $arr['totalCount'] = strval($userCount);
            $arr['users'] = $arrUsers;
            return json_encode($arr);
        } else {
            $arr['totalCount'] = "0";
            $arr['users'] = array();
            return json_encode($arr);
        }
    }

    /**
     * Method to get the Groups
     * formatted in Json
     *
     * @return json
     * @access public
     * @author Wesley Nitsckie
     */
    public function getJsonAllGroups() {
        $limit = ($this->getParam('limit') == "") ? "" : $this->getParam('limit');
        $offset = ($this->getParam('offset') == "") ? "" : $this->getParam('offset');
        $filter = ($this->getParam('letter') == "") ? "" : $this->getParam('letter') . '%';
        $params = array('limit' => intval($limit), 'offset' => intval($offset), 'filter' => $filter);

        $groups = $this->objGroups->getTopLevelGroups($params);
        $totalCount = count($this->objGroups->getTopLevelGroups(array('filter' => $filter)));
        $noGroups = count($groups);
        if ($noGroups > 0) {
            $arrGroups = array();

            foreach ($groups as $group) {
                $groupId = $this->objGroups->getId($group['group_define_name']);
                $arr = array();
                $arr['groupname'] = $group['group_define_name'];
                $arr['grouptitle'] = $this->_getContextTitle($group['group_define_name']);
                $arr['id'] = strval($groupId);

                $arrGroups[] = $arr;
                $arr = null;
            }
            $arr['totalCount'] = strval($totalCount);
            $arr['groups'] = $arrGroups;
            return json_encode($arr);
        } else {
            $arr['totalCount'] = "0";
            $arr['groups'] = array();
            return json_encode($arr);
        }
    }

    /**
     * Method to check if a group id exist
     *
     * @param string $groupId
     * @return boolean
     */
    public function jsonCheckGroupAvailable($groupName) {

        $recordCount = $this->objUser->getArray("SELECT count(group_id) as cnt FROM tbl_perms_groups WHERE lower(group_define_name) = lower('" . $groupName . "')");
        $extjs = (intval($recordCount[0]['cnt']) > 0) ? '0' : '1';

        return json_encode(array('success' => true, 'data' => $extjs));
    }

    /**
     * Method to get the context Title
     *
     * @param string $contextCode
     * @return string
     * @access private
     */
    private function _getContextTitle($contextCode = null) {
        if (empty($contextCode)) {
            return "";
        } else {
            return htmlentities($this->objDBContext->getTitle($contextCode, false));
        }
    }

    public function jsonAddGroup($groupName) {
        $extjs = $this->objGroups->addGroup($groupName);
        return json_encode(array('success' => 'true', 'errors' => $extjs));
    }

    public function jsonEditGroup($id, $newgroupname, $oldgroupname) {

        $this->updateSubGroup($newgroupname, $oldgroupname);
        $sql = "UPDATE tbl_context SET contextcode = '" . $newgroupname . "' WHERE contextcode = '" . $oldgroupname . "'";
        $this->objDBContext->getArray($sql);

        $sql = "UPDATE tbl_perms_groups SET group_define_name = '" . $newgroupname . "' WHERE group_id = " . $id;
        $this->objDBContext->getArray($sql);

        return json_encode(array('success' => 'true'));
    }

    public function updateSubGroup($newgroupname, $oldgroupname) {
        $sql = "SELECT group_define_name FROM tbl_perms_groups WHERE group_define_name LIKE '" . $oldgroupname . "^%'";
        $subgrp = $this->objDBContext->getArray($sql);

        foreach ($subgrp as $grp) {
            $pieces = explode("^", $grp['group_define_name']);
            $sql = "UPDATE tbl_perms_groups SET group_define_name = '" . $newgroupname . "^" . $pieces[1] . "' WHERE group_define_name = '" . $grp['group_define_name'] . "'";
            $this->objDBContext->getArray($sql);
        }
        //error_log(var_export($sql, true));
        //$sql = "UPDATE tbl_perms_groups SET group_define_name = '".$newgroupname."' WHERE group_id = ".$id;
        //$this->objDBContext->getArray($sql);
    }

    public function jsonGetGroup($id) {

        $sql = "Select group_define_name from tbl_perms_groups WHERE group_id = '" . $id . "'";
        $arr = array();
        $singlegroup = array();
        $groups = $this->objUser->getArray($sql);
        $totalCount = count($groups);
        if (count($groups) > 0) {
            foreach ($groups as $group) {
                $arr['groupname'] = $group['group_define_name'];
            }
            return json_encode(array('success' => true, 'data' => $arr));
        } else {
            $arr[] = array();
            return json_encode(array('success' => false, 'data' => $arr));
        }
    }

    /**
     * Method that returns the subgroups
     * for a given group in json format
     *
     * @param string $groupId
     * @return unknown
     */
    public function getJsonSubGroups($groupId = null) {
        $arr = array();
        if (!empty($groupId)) {
            $subGroups = $this->objGroups->getSubgroups($groupId);
            //var_dump($subGroups);
            $arr = array();
            if ($subGroups) {
                $cnt = 0;
                $keys = array_keys($subGroups[0]);
                foreach ($subGroups[0] as $subgroup) {

                    //var_dump($keys);
                    $groupId = $keys[$cnt]; //array_keys($subGroups[0][$cnt]);
                    $arr[] = array('groupid' => $groupId,
                            'name' => $this->formatGroupName($subgroup['group_define_name'])
                    );
                    $cnt++;
                }
            }
        }

        return json_encode(array('subgroups' => $arr));
    }

    /**
     * Method to remove users from
     *
     * @param integer $groupId
     * @param string $userIds
     * @return unknown
     */
    public function jsonRemoveUsers($groupId, $userIds) {

        if ($groupId && $userIds) {
            $userIds = substr_replace($userIds, "", strlen($userIds) - 1);
            //error_log('Success '.$groupId.'\n'.$userIds);
            $users = explode(',', $userIds);
            //error_log(var_export(count($users)), true);
            foreach ($users as $id) {
                //echo 'here';
                //error_log('here');

                if ($id) {
                    //var_dump($id);
                    $res = $this->removeUser($groupId, $id);
                    //var_dump($res);
                    //error_log(var_export($res));
                }
            }

            $extjs['success'] = true;
        } else {
            $extjs['success'] = false;
            $extjs['errors']['message'] = 'Unable to connect to DB';
        }

        return json_encode($extjs);

        if (empty($userIds)) {
            error_log('Error' . $groupId . '\n' . $userIds);
            return "failure"; //json_encode(array('success' => 'false', 'msg' => 'Error'.$groupId.'\n'.$userIds));
        } else {
            error_log('Success' . $groupId . '\n' . $userIds);
            return "success"; //json_encode(array('success' => 'true', 'msg' => 'Success'.$groupId.'\n'.$userIds ));
        }
    }

    public function jsonGetAllUsers($groupId = null, $start = 0, $limit = 25) {

        $params["start"] = ($this->getParam("start")) ? $this->getParam("start") : null;
        $params["limit"] = ($this->getParam("limit")) ? $this->getParam("limit") : null;
        $params["search"] = ($this->getParam("fields")) ? json_decode(stripslashes($this->getParam("fields"))) : null;
        $params["query"] = ($this->getParam("query")) ? $this->getParam("query") : null;
        $params["sort"] = ($this->getParam("sort")) ? $this->getParam("sort") : null;
        //$params["dir"] = isset($_REQUEST["dir"]) ? $_REQUEST["dir"] : null;
        //$params['fields'] = $_REQUEST["fields"];
        $where = "";

        if (is_array($params['search'])) {
            $max = count($params['search']);

            $cnt = 0;

            foreach ($params['search'] as $field) {
                $cnt++;
                $where .= " lower(" . $field . ") LIKE lower('" . $params['query'] . "%')";
                if ($cnt < $max) {
                    $where .= " OR ";
                }
            }

            $where = ' WHERE ' . $where;
        }

        $filter = " LIMIT $start , $limit";
        $sql = "SELECT pu.perm_user_id, us.firstName, us.surname, us.username, us.last_login, us.logins, us.emailAddress FROM tbl_users as us INNER join tbl_perms_perm_users as pu on us.userId = pu.auth_user_id" . $where . " ORDER BY us.surname " . $filter;
        //error_log(var_export($sql, true));
        //die($sql);
        $users = $this->objDBContext->getArray($sql);
        $countSQL = "SELECT username FROM tbl_users";
        $userCount = count($this->objDBContext->getArray($countSQL));

        if (count($users > 0)) {

            $arr = array();
            $arrUsers = array();
            foreach ($users as $groupUser) {
                if ($groupUser['perm_user_id'] != "") {
                    $user = $groupUser; //$this->objUser->getUserDetails($groupUser['auth_user_id']);
                    $arrUser = array();
                    $arrUser['id'] = $user['perm_user_id'];
                    $arrUser['userid'] = $user['username'];
                    $arrUser['username'] = $user['username'];
                    $arrUser['firstName'] = $user['firstname'];
                    $arrUser['surname'] = $user['surname'];
                    $arrUser['lastloggedin'] = $user['last_login'];
                    $arrUser['emailAddress'] = $user['emailaddress'];
                    $arrUsers[] = $arrUser;
                }
            }

            $arr['totalCount'] = strval($userCount);
            $arr['users'] = $arrUsers;
            return json_encode($arr);
        } else {
            $arr['totalCount'] = "0";
            $arr['users'] = array();
            return json_encode($arr);
        }
    }

    /**
     * Method to add users to a group via
     * json
     *
     * @param unknown_type $groupId
     * @param unknown_type $userIds
     * @return unknown
     */
    public function jsonAddUsers($groupId, $userIds) {
        if ($groupId && $userIds) {
            $userIds = substr_replace($userIds, "", strlen($userIds) - 1);

            $users = explode(',', $userIds);
            //error_log(var_export($users), true);
            foreach ($users as $id) {
                if ($id) {
                    ///error_log(var_export($id, true));
                    $res = $this->objGroups->addGroupUser($groupId, $id);
                }
            }

            $extjs['success'] = true;
        } else {
            $extjs['success'] = false;
            $extjs['errors']['message'] = 'Unable to connect to DB';
        }

        return json_encode($extjs);
    }

//////NEW METHODS
    /**
     * Method to get the groups
     */
    public function getGroups() {
        $groups = $this->objGroups->getTopLevelGroups(); //$this->objGroups->getGroups();

        if (count($groups) > 0) {
            $str = '<div id="accordion">';
            foreach ($groups as $group) {
                $groupId = $this->objGroups->getId($group['group_define_name']);
                $subGroups = $this->objGroups->getSubgroups($groupId);
                //var_dump($subGroups);
                $str .='<div>
								<h3 id="' . $groupId . '"><a href="#">' . $group['group_define_name'] . '</a></h3>

								<div style="height:175px;" id="tab_' . $groupId . '">
									<div class="siteadminlist">';

                if ($subGroups) {
                    $str .= $this->doSubGroups($groupId, $subGroups);
                } else {
                    $str .= '<div id="' . $groupId . '_list">' . $this->loading . '</div>';
                }
                $str .= '</div></div>
					</div>';
            }
            return $str . '</div>';
        }
    }

    /**
     * Method to build a list of user
     * so that multiple users can be selected
     * and added to a particular group
     */
    public function getUserList($groupId) {

        $users = $this->getAllUsers();
        $objTable = $this->getObject('htmltable', 'htmlelements');
        $objLink = $this->getObject('link', 'htmlelements');
        $objIcon = $this->getObject('geticon', 'htmlelements');
        $objIcon->setIcon('add', 'png');
        $objLink->link = $objIcon->show();
        $arr = array();
        foreach ($users as $user) {
            if (!$this->isGroupMember($groupId, $user['auth_user_id'])) {
                $objTable->startRow();
                $objTable->addCell($this->objUser->fullname($user['auth_user_id']));

                $objLink->extra = " onclick=addUser('" . $groupId . "','" . $user['handle'] . "')";
                $objLink->href = '#';
                $objTable->addCell($objLink->show());
                $arr[$this->objUser->fullname($user['auth_user_id'])] = $user['handle']; //$user['userid'];
                $objTable->endRow();
            }
        }

        return $objTable->show();
    }

    /**
     * Method to get the groups
     */
    public function doSubGroups($groupId, $subGroups) {
        $str = "";

        if ($subGroups) {
            //if the group has sub group then generate
            //a multi tabbed box
            $tabs = "";
            $tabcontents = "";

            $scripts = '<script type="text/javascript">
						$(function(){

							// Tabs
							$(\'#' . $groupId . '_tabs\').tabs({
								select: function(event, ui) {
									id = stripId(ui.panel.id);
									loadGroupTab(id);
								},
								remote: true,
								fxAutoHeight: true,
								fxShow: { height: \'show\', opacity: \'show\' },

								});

						});

					</script>';


            $this->appendArrayVar('headerParams', $scripts);
            $str .= '<div id="' . $groupId . '_tabs">
							<ul>';

            foreach ($subGroups[0] as $subgroup) {
                //var_dump($subgroup);
                $subgroupId = $this->objGroups->getId($subgroup['group_define_name']);
                //var_dump($groupId);
                $tabs .= '
								<li>
									<a href="#' . $subgroupId . '_list">
										<span>' . $this->formatGroupName($subgroup['group_define_name']) . '</span>
									</a>
								</li>';
                $tabcontents .= $this->getSubGroupInterface($subgroupId);
            }
            $str .=$tabs . '</ul>' . $tabcontents . '</div>';

            //$str .= '</div>';
        }

        return $str;
    }

    /**
     * Method to generate the tabbed box of the subgroup
     * @param string $subGroupId
     */
    public function getSubGroupInterface($subGroupId) {
        $str = '
					<div id="' . $subGroupId . '_list">
						' . $this->loadGroupContent($subGroupId) . '
					</div>
					';
        return $str;
    }

    /**
     * Method to format the name of the group by removing the ^
     */
    public function formatGroupName($groupName) {
        return substr_replace($groupName, "", 0, strpos($groupName, "^") + 1);
    }

    /**
     * Method to get first groupd Id
     */
    public function getFirstGroupId() {
        if ($this->objMemcache == TRUE) {
            if (chisimbacache::getMem()->get(md5($this->cachePrefix . getFirstGroupId))) {
                $cache = chisimbacache::getMem()->get(md5($this->cachePrefix . getFirstGroupId));
                $ret = unserialize($cache);
            } else {
                $groups = $this->objLuAdmin->perm->getGroups();
                $ret = $groups[0]['group_id'];
                chisimbacache::getMem()->set(md5($this->cachePrefix . getFirstGroupId), serialize($ret), MEMCACHE_COMPRESSED, $this->cacheTTL);
            }
        } elseif ($this->objAPC == TRUE) {
            $ret = apc_fetch($this->cachePrefix . getFirstGroupId);
            if ($ret == FALSE) {
                $groups = $this->objLuAdmin->perm->getGroups();
                $ret = $groups[0]['group_id'];
                apc_store($this->cachePrefix . getFirstGroupId, $ret, $this->cacheTTL);
            }
        } else {
            $groups = $this->objLuAdmin->perm->getGroups();
            $ret = $groups[0]['group_id'];
        }

        return $ret;
    }

    /**
     * Method to get the list of users to be searched
     */
    public function getSearchableUsers() {
        $users = $this->getAllUsers();

        $arr = array();
        foreach ($users as $user) {
            $arr[$this->objUser->fullname($user['auth_user_id'])] = $user['handle']; //$user['userid'];
        }

        return $arr;
    }

    /**
     * Method to check if a user is a
     * group
     * @param string $groupId
     */
    public function isGroupMember($groupId, $userId) {
        $arr = $this->getUsersInGroup($groupId);
        if (count($arr) > 0) {
            foreach ($arr as $user) {
                if ($userId == $user['auth_user_id']) {
                    return TRUE;
                }
            }

            return FALSE;
        } else {
            return FALSE;
        }
    }

    /**
     * Method to generate the display for a group
     * @param string $groupId
     */
    public function loadGroupContent($groupId) {
        //show the users list
        //$arr = $this->objGroups->getGroupUsers($groupId);
        $arr = $this->getUsersInGroup($groupId);

        return $this->generateList($arr, $groupId);
        $str = '
		<div class="groupadmincontent">
							<div class="siteadminlist">
								<div id="' . $groupId . '_list">' . $this->layoutUsers($arr) . '</div>
							</div>
							<div id="siteadmintoolbox" >' . $this->searchUsersBox($groupId) . '</div>
						</div>';

        //$str = $this->generateList($arr);
        //show the search box
        //$str .= $this->searchUsersBox($groupId);
        //return $this->
        //var_dump($usersGroup);
        return $str;
    }

    /**
     * Method to get the left menu
     */
    public function getLeftMenu() {
        //side bar navigation object
        $objSideBar = $this->getObject('sidebar', 'navigation');

        //set the menu items -> Site Groups , Course Groups

        $nodes = array();

        $nodes[0]['text'] = $this->objLanguage->languageText("mod_groupadmin_sitegroups", "groupadmin");
        $nodes[0]['uri'] = $this->uri(array('action' => 'sitegroups'));

        $nodes[1]['text'] = ucwords($this->objLanguage->code2Txt('mod_groupadmin_contextgroups', 'groupadmin', NULL, '[-context-] Groups'));
        $nodes[1]['uri'] = $this->uri(array('action' => 'contextgroups'));

        return $objSideBar->show($nodes);
    }

    /**
     * Method to load the content
     */
    public function loadContent($groupId) {

        $str = '<div class="groupadmincontent">
							<div class="siteadminlist">
								<div class="siteadminscontent"  id="' . $groupId . 'content">' . $this->loading . '</div>
							</div>
							<div class="siteadmintoolbox" id="' . $groupId . 'toolbox" >' . $this->searchUsersBox() . '</div>
						</div>';
        return $str;
    }

    /**
     * Method to get the search box
     *//*
      public function searchUsersBox()
      {


      return '<form autocomplete="off" >

      <p>
      <label>Search Users:</label><br/>
      <input type="text" id="suggest4"><br/>
      <input type="hidden" id="hiddensuggest4"><br/>
      <input type="button" value="Add to Group" />
      </p>
      </form>';


      }
    */

    /**
     * Method to generate a list
     */
    public function generateList($arr, $groupId) {
        if (count($arr) > 0) {
            $objIcon = $this->getObject('geticon', 'htmlelements');
            //$this->loadClass('link', 'htmlelements');
            $objIcon->setIcon('delete', 'png');


            $str = '<div class="nicelist"><table>';
            foreach ($arr as $list) {
                $objLink = $this->newObject('link', 'htmlelements');
                $objLink->href = '#';
                $objLink->link = $objIcon->show();
                $objLink->extra = ' onclick="removeUser(\'' . $groupId . '\', \'' . $list['auth_user_id'] . '\') "';
                $str .= '<tr><td style="margin-right:0px;">' . $username = $this->objUser->fullName($list['auth_user_id']) . '</td><td style="margin-left:0px;">' . $objLink->show() . '</td ></tr>';
                $objLink = null;
            }
            $str .='</table></div>';
            return $str;
        } else {
            return '<span class="subdued">' . $this->objLanguage->languageText('mod_groupadmin_nousers', 'groupadmin') . '</span>';
        }
    }

////////////////////////////////////////


    public function getAllGroups() {
        if ($this->objMemcache == TRUE) {
            if (chisimbacache::getMem()->get(md5($this->cachePrefix . getAllGroups))) {
                $cache = chisimbacache::getMem()->get(md5($this->cachePrefix . getAllGroups));
                $ret = unserialize($cache);
            } else {
                $ret = $this->objLuAdmin->perm->getGroups();
                chisimbacache::getMem()->set(md5($this->cachePrefix . getAllGroups), serialize($ret), MEMCACHE_COMPRESSED, $this->cacheTTL);
            }
        } elseif ($this->objAPC == TRUE) {
            $ret = apc_fetch($this->cachePrefix . getAllGroups);
            if ($ret == FALSE) {
                $ret = $this->objLuAdmin->perm->getGroups();
                apc_store($this->cachePrefix . getAllGroups, $ret, $this->cacheTTL);
            }
        } else {
            $ret = $this->objLuAdmin->getGroups();
        }

        return $ret;
    }

    public function getAllUsers() {
        if ($this->objMemcache == TRUE) {
            if (chisimbacache::getMem()->get(md5($this->cachePrefix . getAllUsers))) {
                $cache = chisimbacache::getMem()->get(md5($this->cachePrefix . getAllUsers));
                $ret = unserialize($cache);
            } else {
                $ret = $this->objLuAdmin->getUsers();
                chisimbacache::getMem()->set(md5($this->cachePrefix . getAllUsers), serialize($ret), MEMCACHE_COMPRESSED, $this->cacheTTL);
            }
        } elseif ($this->objAPC == TRUE) {
            $ret = apc_fetch($this->cachePrefix . getAllUsers);
            if ($ret == FALSE) {
                $ret = $this->objLuAdmin->getUsers();
                apc_store($this->cachePrefix . getAllUsers, $ret, $this->cacheTTL);
            }
        } else {
            $ret = $this->objLuAdmin->getUsers();
        }

        return $ret;
    }

    public function getAllPermUsers() {
        if ($this->objMemcache == TRUE) {
            if (chisimbacache::getMem()->get(md5($this->cachePrefix . getAllPermUsers))) {
                $cache = chisimbacache::getMem()->get(md5($this->cachePrefix . getAllPermUsers));
                $ret = unserialize($cache);
            } else {
                $ret = $this->objLuAdmin->perm->getUsers();
                chisimbacache::getMem()->set(md5($this->cachePrefix . getAllPermUsers), serialize($ret), MEMCACHE_COMPRESSED, $this->cacheTTL);
            }
        } elseif ($this->objAPC == TRUE) {
            $ret = apc_fetch($this->cachePrefix . getAllPermUsers);
            if ($ret == FALSE) {
                $ret = $this->objLuAdmin->perm->getUsers();
                apc_store($this->cachePrefix . getAllPermUsers, $ret, $this->cacheTTL);
            }
        } else {
            $ret = $this->objLuAdmin->perm->getUsers();
        }

        return $ret;
    }

    public function getNonGrpUsers() {
        if ($this->objMemcache == TRUE) {
            if (chisimbacache::getMem()->get(md5($this->cachePrefix . getNonGrpUsers))) {
                $cache = chisimbacache::getMem()->get(md5($this->cachePrefix . getNonGrpUsers));
                $ret = unserialize($cache);
            } else {
                $ret = $this->objLuAdmin->getUsers(array('container' => 'auth'));
                chisimbacache::getMem()->set(md5($this->cachePrefix . getNonGrpUsers), serialize($ret), MEMCACHE_COMPRESSED, $this->cacheTTL);
            }
        } elseif ($this->objAPC == TRUE) {
            $ret = apc_fetch($this->cachePrefix . getNonGrpUsers);
            if ($ret == FALSE) {
                $ret = $this->objLuAdmin->getUsers(array('container' => 'auth'));
                apc_store($this->cachePrefix . getNonGrpUsers, $ret, $this->cacheTTL);
            }
        } else {
            $ret = $this->objLuAdmin->getUsers(array('container' => 'auth'));
        }

        return $ret;
    }

    public function getUserByUserId($userId) {
        $params = array(
                'filters' => array(
                        'auth_user_id' => $userId,
                )
        );

        if ($this->objMemcache == TRUE) {
            if (chisimbacache::getMem()->get(md5($this->cachePrefix . getUserByUserId . $userId))) {
                $cache = chisimbacache::getMem()->get(md5($this->cachePrefix . getUserByUserId . $userId));
                $ret = unserialize($cache);
            } else {
                $user = $this->objLuAdmin->perm->getUsers($params);
                $ret = $user[0];
                chisimbacache::getMem()->set(md5($this->cachePrefix . getUserByUserId . $userId), serialize($ret), MEMCACHE_COMPRESSED, $this->cacheTTL);
            }
        } elseif ($this->objAPC == TRUE) {
            $ret = apc_fetch($this->cachePrefix . getUserByUserId . $userId);
            if ($ret == FALSE) {
                $user = $this->objLuAdmin->perm->getUsers($params);
                $ret = $user[0];
                apc_store($this->cachePrefix . getUserByUserId . $userId, $ret, $this->cacheTTL);
            }
        } else {
            $user = $this->objLuAdmin->perm->getUsers($params);
            $ret = $user[0];
        }

        return $ret;
    }

    public function getUsersInGroup($groupid) {
        $params = array(
                'filters' => array(
                        'group_id' => $groupid,
                )
        );

        if ($this->objMemcache == TRUE) {
            if (chisimbacache::getMem()->get(md5($this->cachePrefix . getUsersInGroup . $groupid))) {
                $cache = chisimbacache::getMem()->get(md5($this->cachePrefix . getUsersInGroup . $groupid));
                $ret = unserialize($cache);
            } else {
                $ret = $this->objLuAdmin->perm->getUsers($params);
                chisimbacache::getMem()->set(md5($this->cachePrefix . getUsersInGroup . $groupid), serialize($ret), MEMCACHE_COMPRESSED, $this->cacheTTL);
            }
        } elseif ($this->objAPC == TRUE) {
            $ret = apc_fetch($this->cachePrefix . getUsersInGroup . $groupid);
            if ($ret == FALSE) {
                $ret = $this->objLuAdmin->perm->getUsers($params);
                apc_store($this->cachePrefix . getUsersInGroup . $groupid, $ret, $this->cacheTTL);
            }
        } else {
            $ret = $this->objLuAdmin->perm->getUsers($params);
        }

        return $ret;
    }

    public function layoutGroups($groups, $numperRow = 5) {
        $gtable = $this->newObject('htmltable', 'htmlelements');
        $gtable->cellpadding = 5;
        $inners = NULL;
        $row = 0;
        $gtable->startRow();
        foreach ($groups as $group) {
            $itable = $this->newObject('htmltable', 'htmlelements');
            $itable->cellpadding = 2;
            $icon = $this->newObject('geticon', 'htmlelements');
            $icon->setIcon('groupadmingrps');
            $href = $this->loadClass('href', 'htmlelements');
            $lnk1 = new href($this->uri(array('action' => 'editgrp', 'id' => $group['group_id'])), $icon->show(), NULL);
            $lnk = new href($this->uri(array('action' => 'editgrp', 'id' => $group['group_id'])), $group['group_define_name'], NULL);
            $grpname = $lnk->show();
            $itable->startRow();
            $itable->addCell($lnk1->show());
            $itable->endRow();
            $itable->startRow();
            $itable->addCell($grpname);
            $itable->endRow();
            // if the $row var is divisible by 4, start a new row
            if (is_int($row / $numperRow)) {
                $gtable->endRow();
                $gtable->startRow();
            }
            $gtable->addCell($itable->show());

            $row++;
        }
        $gtable->endRow();

        return $gtable->show();
    }

    public function layoutUsers($users, $grId, $numperRow = 5) {
        $utable = $this->newObject('htmltable', 'htmlelements');
        $utable->cellpadding = 5;
        $inners = NULL;
        $row = 0;
        $utable->startRow();
        foreach ($users as $user) {
            $itable = $this->newObject('htmltable', 'htmlelements');
            $itable->cellpadding = 2;
            $icon = $this->newObject('geticon', 'htmlelements');
            $icon->setIcon('delete');
            $href = $this->loadClass('href', 'htmlelements');
            $lnk = new href($this->uri(array('action' => 'removeuser', 'id' => $user['perm_user_id'], 'grid' => $grId)), $icon->show(), NULL);
            $image = $this->objUser->getUserImage($user['auth_user_id']);
            $username = $this->objUser->fullName($user['auth_user_id']);
            $itable->startRow();
            $itable->addCell($image);
            $itable->startRow();
            $itable->addCell($username . " " . $lnk->show());
            $itable->endRow();
            // if the $row var is divisible by 4, start a new row
            if (is_int($row / $numperRow)) {
                $utable->endRow();
                $utable->startRow();
            }
            $utable->addCell($itable->show());

            $row++;
        }
        $utable->endRow();

        return $utable->show();
    }

    public function getGroupInfo($groupid) {
        if ($this->objMemcache == TRUE) {
            if (chisimbacache::getMem()->get(md5($this->cachePrefix . getGroupInfo . $groupid))) {
                $cache = chisimbacache::getMem()->get(md5($this->cachePrefix . getGroupInfo . $groupid));
                $ret = unserialize($cache);
            } else {
                $ret = $this->objLuAdmin->perm->getGroups(array('filters' => array('group_id' => $groupid)));
                chisimbacache::getMem()->set(md5($this->cachePrefix . getGroupInfo . $groupid), serialize($ret), MEMCACHE_COMPRESSED, $this->cacheTTL);
            }
        } elseif ($this->objAPC == TRUE) {
            $ret = apc_fetch($this->cachePrefix . getGroupInfo . $groupid);
            if ($ret == FALSE) {
                $ret = $this->objLuAdmin->perm->getGroups(array('filters' => array('group_id' => $groupid)));
                apc_store($this->cachePrefix . getGroupInfo . $groupid, $ret, $this->cacheTTL);
            }
        } else {
            $ret = $this->objLuAdmin->perm->getGroups(array('filters' => array('group_id' => $groupid)));
        }

        return $ret;
    }

    public function addUserForm($grpId) {
        $this->loadClass('form', 'htmlelements');
        $objForm = new form('adduser', $this->uri(array('action' => 'editgrp', 'id' => $grpId))); //,'htmlelements');
        // Create the selectbox object
        // $this->loadClass('selectbox','htmlelements');
        $objSelectBox = $this->newObject('selectbox', 'htmlelements');
        // Initialise the selectbox.
        $objSelectBox->create($objForm, 'leftList[]', 'Available Users', 'rightList[]', 'Users to add');

        // Populate the selectboxes
        //$objData = &$this->getObject('data');
        $data = $this->getAllUsers();


        $currentUsers = $this->getUsersInGroup($grpId);
        foreach ($data as $i => $user) {
            foreach ($currentUsers as $currentUser) {
                if ($currentUser['auth_user_id'] == $user['auth_user_id']) {
                    unset($data[$i]);
                    break 1;
                }
            }
        }
        $userArr = array();
        foreach ($data as $user) {
            $usr['label'] = $this->objUser->fullName($user['auth_user_id']);
            $usr['value'] = $user['perm_user_id'];
            $userArr[] = $usr;
        }
        $objSelectBox->insertLeftOptions($userArr, 'value', 'label');
        $objSelectBox->insertRightOptions(array());

        // Insert the selectbox into the form object.
        $objForm->addToForm($objSelectBox->show());

        // Get and insert the save and cancel form buttons
        $arrFormButtons = $objSelectBox->getFormButtons();
        $objForm->addToForm(implode(' / ', $arrFormButtons));

        // Show the form
        return $objForm->show();
    }

    public function addUserToGroup($users, $groupId) {
        $this->objGroups = $this->getObject('groupadminmodel', 'groupadmin');
        if (is_array($users)) {
            foreach ($users as $user) {
                $this->objGroups->addGroupUser($groupId, $user);
            }
        } else {
            $this->objGroups->addGroupUser($groupId, $users);
        }
    }

    public function removeUser($grid, $id) {
        //$this->objGroups = $this->getObject('groupadminmodel', 'groupadmin');
        return $this->objGroups->deleteGroupUser($grid, $id);
    }

}

?>
