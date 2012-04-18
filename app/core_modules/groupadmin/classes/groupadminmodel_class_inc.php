<?php
/**
 * groupAdminModel class
 *
 * The group admin model class is used to maintain the groups hierachy.
 * It processes and maintains all groups data, and acts as the
 * interface for external modules, making availale all its functionality
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
 * @version   $Id$
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
 * The group admin model class is used to maintain the groups hierachy.
 * It processes and maintains all groups data, and acts as the
 * interface for external modules, making availale all its functionality.
 * <PRE>
 * Public Inteface:
 * Groups table
 *   getId             - To get the unique id for the group.
 *   getLeafId         - To get the unique id following the a path to the group.
 *   getDescription    - To get the description of a group.
 *   getFullPath       - To get the full path to the root group.
 *   getName           - To get the name of the group.
 *   setDescription    - To set the description of a group.
 *   setName           - To set the name of the group.
 *   addGroup          - To insert a group into group hierachy.
 *   deleteGroup       - To remove a group from the group hierarchy.
 *   getGroups         - To get all the groups without hierarchy.
 *   getSubgroups      - To get the descendents from this group down.
 *   getGroupsToRoot   - To get the ancestors from this group up.
 * GroupUsers table    - userId refers to unique Id use PKId( userId )
 *   addGroupUser      - To insert a user into a group in the group hierarchy.
 *   deleteGroupUser   - To remove a user from a group in the group hierarchy.
 *   getUserDirectGroups- To get all the direct groups for this user.
 *   getUserGroups     - To get all the direct and subgroups for this user.
 *   getGroupUsers     - To get all the direct users for this group.
 *   getNotGroupUsers  - To get all the users not directly in this group.
 *   getSubGroupUsers  - To get all direct and subgroups for this user.
 *   isGroupMember     - To test if the user is a member of the direct group.
 *   isSubGroupMember  - To test if the user is a member of the direct and subgroups.
 * Users table
 *   getUsers          - To get all the users.
 *</PRE>
 *
 * @copyright  (c) 2000-2004, Kewl.NextGen ( http://kngforge.uwc.ac.za )
 * @package    groupadmin
 * @subpackage service
 * @version    0.1
 * @since      22 November 2004
 * @author     Paul Scott based on methods by Jonathan Abrahams
 * @filesource
 */

class groupAdminModel extends dbTable {

    /**
     * $_objUsers an association to the userDb object.
     *
     * @access public
     * @var    userDb
     */
    public $_objUsers;

    /**
     * an association to the groupuserDb object.
     *
     * @access public
     * @var    groupuserDb $_objGroupUsers
     */
    public $_objGroupUsers;

    /**
     * Method to initialize the group admin model object.
     *
     * @access public
     * @param  void
     * @return void
     */
    public function init() {
        //if($this->objLuAdmin === null) {
        //    $this->objLuAdmin = $this->objEngine->getLuAdmin();
        //}
    }

    /**
     * Method to insert a group into group hierachy.
     * The group description should suggest who the group members are,
     * and the parent group creates the group hierarchy.
     *
     * @access public
     * @param  string       $name        the group name.
     * @param  string       $description a short description of the group, suggesting the member list.
     * @param  string       $parentId    the unique id of this groups immediate ancestor.( optional default is null=root )
     * @return string|false the newly generated unique id for this group if successful, otherwise false.
     */


    public function addGroup( $name,  $description = NULL, $parentId = null ) {
        $data = array('group_define_name' => $name, 'group_type' => LIVEUSER_GROUP_TYPE_ALL);
        $groupId = $this->objLuAdmin->perm->addGroup($data);
        return $groupId;
    }

    public function addSubGroups($contextCode, $contextGroupId) {
        // create the subgroups first
        $grps = array("Lecturers", "Students", "Guest");
        foreach($grps as $grp) {
            $grpid = $this->addGroup($contextCode."^".$grp);
            // then add them as subGroups of the parent Group.
            $data = array(
                    'group_id' => $contextGroupId,
                    'subgroup_id' => $grpid
            );
            $assign = $this->objLuAdmin->perm->assignSubGroup($data);
        }

    }

    /**
     * Method to remove a group from the group hierarchy.
     * It cascade deletes the subgroups as well.
     *
     * @access public
     * @param  string     $groupId The unique ID of an existing group.
     * @return boolean    true if successful, otherwise false.
     */
    public function deleteGroup( $groupId ) {
        $filters = array('group_id' => $groupId);
        $removed = $this->objLuAdmin->perm->removeGroup($filters);
        if ($removed === false) {
            log_debug($admin->getErrors());
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * Method to get all the groups( no hierarchy ).
     *
     * @access public
     * @param  string      $filter ( optional ) a SQL WHERE clause.
     * @return array|false Group rows as an array of associate arrays, or FALSE on failure
     */
    public function getGroups( $filter = NULL) {
        if(isset($filter) && !empty($filter)) {
            $sql = "SELECT group_define_name, group_id FROM tbl_perms_groups $filter";
            parent::init('tbl_perms_groups ');
            $groups = $this->getArray($sql, 'tbl_perms_groups')  ;
            return $groups;
        }
        else {
            $groups = $this->objLuAdmin->perm->getGroups();
        }
        if ($groups === false) {
            log_debug($admin->getErrors());
            return FALSE;
        } elseif (empty($groups)) {
            log_debug('No groups were found');
            return FALSE;
        } else {
            return $groups;
        }
    }

    /**
     * Method to get the ancestors from this group up the hierarchy.
     *
     * @access public
     * @param  string The unique ID of an existing group.
     * @return array  the list of all groups to root excluding the given group.
     */
    public function getGroupsToRoot( $groupId ) {

    }

    /**
     * Method to get the description of a group.
     * The unique id is used to identify the group.
     *
     * @access public
     * @param  string The unique ID of an existing group.
     * @return string the group description.
     */
    public function getDescription( $groupId ) {
        return NULL;
    }

    /**
     * Method to get the unique id following the a path to the group.
     *
     * Returns the groupId of the last group name in the array with the path.
     * The path must start at the root down to the group needed, if not found
     * null is returned.
     *<PRE>
     * Example: getLeafId( array( 'myContext', 'Lectures' );
     * Returns: the Id for the Lecturers group for the context myContext.
     *</PRE>
     * to identify the row.
     *
     * @access public
     * @param  array       $arrPath an array with the path to the leaf group.
     * @return string|null returns the groupId if successful, otherwise null.
     */
    public function getLeafId( $arrPath ) {
        $groupId = $this->getId($arrPath[0]);
        //var_dump($groupId);
        if(array_key_exists(1, $arrPath)) {
            //$subGroups = $this->getSubgroups($groupId);
            $groupId = $this->getId($arrPath[0].'^'.$arrPath[1]);
        }

        return $groupId;
    }

    /**
     * Method to get the unique id for the group.
     *
     * Returns the unique id. The name(default) or description fields can be used
     * to identify the row.
     *
     * @access public
     * @param  string $pkValue any value that identifies the group based on the pkField
     * @param  string $pkField the field to find the value( optional default is group name ).
     * @return string the unique id
     */
    public function getId( $name = 'name' ) {
        $groups = $this->objLuAdmin->perm->getGroups(array('filters' => array('group_define_name' => $name)));
        if(empty($groups) || !isset($groups[0])) {
            return NULL;
        }
        else {
            return $groups[0]['group_id'];
        }
    }

    /**
     * Method to get the full path to the root group.
     *
     * The unique id is used to identify the group.
     *
     * @access public
     * @param  string The unique ID of an existing group.
     * @return string the groups full path.
     */
    public function getFullPath( $groupId ) {
        return NULL;
    }

    /**
     * Method to get the name of a group.
     *
     * The unique id is used to identify the group.
     *
     * @access public
     * @param  string The unique ID of an existing group.
     * @return string the group name
     */
    public function getName( $groupId ) {
        $groups = $this->objLuAdmin->perm->getGroups(array('filters' => array('group_id' => $groupId)));
        if(empty($groups) || !isset($groups[0])) {
            return NULL;
        }
        else {
            return $groups[0]['group_define_name'];
        }
    }

    /**
     * Method to get the descendents from this group down the hierarchy.
     *
     * The given group is the starting point, and is included in the list.
     *
     * @access public
     * @param  string The unique ID of an existing group.
     * @return array  the list of all subgroups inclusive of given group.
     */
    public function getSubgroups( $groupId ) {
        $subgroups = FALSE;
        $groups = $this->objLuAdmin->perm->getGroups(
                array(
                'select' => 'all',
                'rekey' => true,
                'filters' => array('group_id' => $groupId),
                'hierarchy' => true,
                )
        );


        foreach ($groups as $grps) {
            if(array_key_exists('subgroups', $grps)) {
                $subgroups[] = $grps['subgroups'];
            }
            else {
                $subgroups = NULL;
            }
        }

        return $subgroups;
    }

    public function getTopLevelGroups($filters = null) {

        if($filters == null) {
            $params = array(
                    'select' => 'all',
                    'rekey' => true,
                    'filters' => array(),
                    'hierarchy' => true,
            );
            $hasFilters = "";
        } else {


            $params['rekey'] = true;
            $params['hierarchy'] = true;
            $params['select'] = 'all';

            if(!empty($filters['limit'])) {
                $params['limit'] = $filters['limit'];
                $lim = ' ,'.$filters['limit'];
            } else {

            }

            if(!empty($filters['offset'])) {
                $params['offset'] = $filters['offset'];
                $off = ' LIMIT '.$filters['offset'];
            } else {
                $off = ' LIMIT 0';
            }

            if(empty($filters['offset']) && empty($filters['limit'])) {
                $off = "";
                $lim = "";
            }

            if(!empty($filters['filter'])) {
                $params['filters'] =array('group_define_name' => array('value' =>  $filters['filter'], 'op' => 'like'),
                        'group_define_name' => array('value' =>  $filters['filter'].'^%', 'op' => 'not like'));
                $fil = ' like "'.$filters['filter'].'"';
            }

            $hasFilters = "and group_define_name  " . $fil." ORDER by group_define_name ".$off.$lim ;

        }

        //@author Wesley Nitsckie
        // I had to hack the sql because code pertaining to
        // the LiveUser is a mission to have 2 filter on the same
        //field. After struggling for hours the best/easiest solution
        //for now is to do a direct query to find the top level groups .ie
        //groups that dont contain the ^ character
        $sql = "SELECT group_define_name, group_id FROM tbl_perms_groups
    				WHERE group_define_name not like '%^%'".
                $hasFilters;
        //var_dump($sql);
        //$groups = $this->objLuAdmin->perm->getGroups($params);
        parent::init('tbl_perms_groups');
        $groups = $this->getArray($sql); //, 'tbl_perms_groups'
        return $groups;

        //please uncomment the code below if a better solution is
        //find other than direct sql
        /*
      var_dump($groups);
            var_dump($sql); die;

        $grps = NULL;
        foreach($groups as $grp) {
            if(!array_key_exists(1, explode('^', $grp['group_define_name']))) {
                $grps[] = $grp;
            }
        }
        return $grps;
        */
    }

    /**
     * Method to set the description of a group.
     *
     * The unique id will not change, only the description field value.
     *
     * @access public
     * @param  string     The             unique ID of an existing group.
     * @param  string     $newDescription the updated description for this group.
     * @return true|false true if successful, otherwise false.
     */
    public function setDescription( $groupId, $newDescription ) {
        return NULL;
    }

    /**
     * Method to set the name of a group.
     *
     * The unique id will not change, only the name field value.
     *
     * @access public
     * @param  string     The      unique ID of an existing group.
     * @param  string     $newName the updated name for this group.
     * @return true|false true if successful, otherwise false.
     */
    public function setName( $groupId, $newName ) {

    }

    /**
     * Method to insert a user into a group in the group hierarchy.
     *
     * @see    groupusersdb::addGroupUser()
     *
     * @access public
     * @param  string The unique ID of an existing group.
     * @param  string The unique ID of an existing user. NB use PKid( userId ) method in user class
     * @return object
     */
    public function addGroupUser( $groupId, $userId ) {
        // add the user with perm_user_id $userId to group with $grpId
        $ret = $this->objLuAdmin->perm->addUserToGroup(array('perm_user_id' => $userId, 'group_id' => $groupId));

        return $ret;
    }

    /**
     * Method to delete a user from a group in the group hierarchy.
     *
     * @see    groupusersdb::deleteGroupUser()
     *
     * @access public
     * @param  string The unique ID of an existing group.
     * @param  string The unique ID of an existing user. NB use PKid( userId ) method in user class
     * @return true   |false TRUE on success, FALSE on failure
     */
    public function deleteGroupUser( $groupId, $userId ) {
        $filters = array(
                'group_id' => $groupId,
                'perm_user_id' => $userId,
        );
        $removed = $this->objLuAdmin->perm->removeUserFromGroup($filters);

        return $removed;
    }

    /**
     * Method to get all the direct users for this group.
     *
     * @see    groupusersdb::getGroupUsers()
     *
     * @access public
     * @param  string      The unique ID of an existing group.
     * @param  string      (   optional ) Default is unique ID of the user.
     * @param  string      (   optional ) a SQL WHERE clause.
     * @return array|false The user rows as an array of associate arrays, or FALSE on failure
     */
    public function getGroupUsers( $groupId, $fields = null, $filter = null ) {
        $params = array(
                'filters' => array(
                        'group_id' => $groupId,
                )
        );
        $usersGroup = $this->objLuAdmin->perm->getUsers($params);
        if($fields) {
            $objUser = $this->getObject('user', 'security');
            $newArr = array();
            foreach($usersGroup as $user) {
                $newArr[] = $objUser->getUserDetails($user['auth_user_id']);
            }

            return $newArr;
        }
        return $usersGroup;
    }

    /**
     * Method to get all the users not directly in this group.
     *
     * @see    groupusersdb::getNotGroupUsers()
     *
     * @access public
     * @param  string      The unique ID of an existing group.
     * @param  string      (   optional ) Default is unique ID of the user.
     * @param  string      (   optional ) a SQL WHERE clause.
     * @return array|false The user rows as an array of associate arrays, or FALSE on failure
     */
    public function getNotGroupUsers( $groupId, $fields = null, $filter = null ) {

    }

    /**
     * Method to get all direct and subgroups for this user.
     *
     * @see    groupusersdb::getSubGroupUsers()
     *
     * @access public
     * @param  string      The unique ID of the group.
     * @param  string      (   optional ) Default is unique ID of the user.
     * @param  string      (   optional ) a SQL WHERE clause.
     * @return array|false The user rows as an array of associate arrays, or FALSE on failure
     */
    public function getSubGroupUsers( $groupId, $fields = null, $filter = null ) {

    }

    /**
     * Method to get the users direct membership to groups.
     *
     * This is users direct membership only.
     * <PRE>
     * + Root
     * |-+ Group1
     *   |-- [UserA]
     *   |-+ Group2
     *     |-+ Group3
     *
     * UserA has membership to Group1(direct)
     * </PRE>
     *
     * @see    groupusersdb::getUserDirectGroups()
     * @access public
     * @param  string The unique ID of an existing user. NB use PKid( userId ) method in user class
     * @return array  The list of unique IDs for groups as an array.
     */
    public function getUserDirectGroups( $userId ) {

    }

    /**
     * Method to get the users group membership.
     * <PRE>
     * This is users direct and inherited membership.
     *
     * + Root
     * |-+ Group1
     *   |-- [UserA]
     *   |-+ Group2
     *     |-+ Group3
     *
     * UserA has membership to Group1(direct), Group2, Group3
     * </PRE>
     * @see    groupusersdb::getUserGroups()
     * @access public
     * @param  string The unique ID of the user. NB use PKid( userId ) method in user class
     * @return array  The list of unique ID for groups as an array.
     */
    public function getUserGroups( $userId ) {
        $permId = $this->getPermUserId($userId);

        parent::init('tbl_perms_groupusers ');

        $sql = "SELECT * FROM tbl_perms_groupusers AS u";
        $sql .= " LEFT JOIN tbl_perms_groups AS g";
        $sql .= " ON u.group_id = g.group_id";
        $sql .= " WHERE u.perm_user_id = '$permId'";

        $groups = $this->getArray($sql, 'tbl_perms_groupusers');

        return $groups;
    }

    /**
     * Method to test if the user is a member of this group directly.
     *
     * @see    groupusersdb::isGroupMember()
     * @access public
     * @param  string     The unique ID of the user. NB use PKid( userId ) method in user class
     * @param  string     The unique ID of the group.
     * @return true|false returns TRUE if user is a member, otherwise FALSE
     */
    public function isGroupMember( $userId, $groupId ) {
        $params = array(
                'filters' => array(
                        'group_id' => $groupId,
                )
        );
        $usersGroup = $this->objLuAdmin->perm->getUsers($params);
        foreach($usersGroup as $users) {
            if($users['auth_user_id'] == $userId) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Method to test if the user is a member of this group or its subgroups.
     *
     * @see    groupusersdb::isSubGroupMember()
     * @access public
     * @param  string     The unique ID of the user. NB use PKid( userId ) method in user class
     * @param  string     The unique ID of the group.
     * @return true|false returns TRUE if user is a member, otherwise FALSE
     */
    public function isSubGroupMember( $userId, $groupId ) {
        $params = array(
                'filters' => array(
                        'group_id' => $groupId,
                )
        );
        $usersGroup = $this->objLuAdmin->perm->getUsers($params);

        foreach($usersGroup as $group) {
            //var_dump($group);
            if($userId == $group['auth_user_id']) {
                return true;
            }
        }

        //try the subgroups
        $subGroups = $this->getSubgroups($groupId);

        if(count($subGroups[0]) > 0) {
            foreach($subGroups[0] as $subGroup) {
                $params = array(
                        'filters' => array(
                                'group_id' => $this->getId($subGroup['group_define_name'])
                        )
                );
                $usersGroup = $this->objLuAdmin->perm->getUsers($params);
                foreach($usersGroup as $group) {

                    if($userId == $group['auth_user_id']) {
                        return true;
                    }
                }
            }
        } else {
            return False;
        }
    }


    /**
     * Method to get all the users.
     *
     * The filter is applied to the user data.
     *
     * @access public
     * @param  string      ( optional ) Default is unique ID for user.
     * @param  string      ( optional ) a SQL WHERE clause.
     * @return array|false The user rows as an array of associate arrays, or FALSE on failure
     */
    public function getUsers( $fields = null, $filter = null ) {

    }

    /**
     * Method to get a field from an multi dimensional array.
     *
     * The result of a dbTable::getArray() is usually passed as rows.
     *
     * @access public
     * @param  array       is  associated array
     * @param  string      the field to get
     * @return array|false the only the required field as an array, otherwise FALSE
     */
    public function getField( $rows, $field ) {

    }

    /**
     * Method to find the children nodes for the given node.
     *
     * It returns zero to many nodes.
     * @access public
     * @param  string the reference node.
     * @return array  array|false The child group rows as an array of associate arrays, or FALSE on failure
     */
    public function getChildren ( $node ) {

    }

    /**
     * Method to find the parent node for the given node.
     *
     * It returns zero or one node.
     *
     * @access public
     * @param  string      the current node ( groupId ).
     * @return array|false The parent group rows as an array of associate arrays, or FALSE on failure
     */
    public function getParent ( $subGroupId ) {
        $subgroups = FALSE;
        $parentgroup = Null;
        $groups = $this->objLuAdmin->perm->getGroups(
                array(
                'select' => 'all',
                'rekey' => true,
                'filters' => array('subgroup_id' => $subGroupId),
                'hierarchy' => true,
                )
        );

        foreach ($groups as $grps) {
            $parentgroup = $grps["group_define_name"];
        }

        return $parentgroup;
    }

    /**
     * Method to find the root nodes.
     * It returns one to many nodes.
     *
     * @access public
     * @param  void
     * @return array  array|false The root group rows as an array of associate arrays, or FALSE on failure
     */
    public function getRoot ( ) {

    }


    /**
     * Method to recursivly follow the path down the tree.
     *
     * Returns the node Id of the last node name in the path.
     *
     * @access private
     * @param  string  the current node ( groupId ).
     * @param  array   the names of the nodes to follow down the tree.
     * @param  string  the unique ID of the group leaf node.
     */
    private function _getGroupPath( $curNode, &$path, &$leaf ) {

    }

    /**
     * Method to recursivly search up the tree.
     *
     * @access private
     * @param  string  the current node ( groupId ).
     * @param  array   the array containing all the nodes found.
     */
    private function _getGroupsToRoot( $curNode, &$toRoot ) {

    }

    /**
     * Method to recursivly search down the tree.
     *
     * @access private
     * @param  string  the current node.
     * @param  array   the array containing all the nodes found.
     */
    private function _getSubgroups( $curNode, &$subgroups ) {

    }

    /*
     *  Method that gets the perm_user_id of the user
     *
     *  @author Qhamani Fenama
     *  @access public
     *  @param string userId
     *  @return int the perm_user_id
    */
    public function getPermUserId($userId) {
        $sql = 'SELECT perm_user_id FROM tbl_perms_perm_users WHERE auth_user_id = \''.$userId.'\'';
        parent::init('tbl_perms_perm_users');
        $res = $this->getArray($sql);
        return $res[0]['perm_user_id'];
    }
}
?>
