<?php
 /**
 * useradmin_model class
 *
 * This class is used by the useradmin module.
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
 * @package   security
 * @author James Scoble <jscoble@uwc.ac.za>
 * @copyright 2004-2007, University of the Western Cape & AVOIR Project
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @link      http://avoir.uwc.ac.za
 */
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run'])
{
    die("You cannot view this page directly");
}
// end security check
/**
* This class is used by the useradmin module
* @author James Scoble
*/
class useradmin_model extends dbtable
{

    public $objConfig;
    private $objUser;
    private $objLanguage;

    public function init()
    {
        parent::init('tbl_users');
        $this->objConfig=$this->getObject('altconfig','config');
        $this->objUser=$this->getObject('user','security');
        $this->objLanguage=$this->getObject('language','language');
    }

    /**
    * method to add a new user
    * @param array $info data for new user
    * @returns string $id the PKID of the new login
    */

    function addUser($info)
    {
        $sdata['userid']=$info['userId'];
        $sdata['username']=$info['username'];
        $sdata['title']=$info['title'];
        $sdata['firstname']=$info['firstName'];
        $sdata['surname']=$info['surname'];
        $sdata['pass']=sha1($info['password']);
        $sdata['creationdate']=date("Y-m-d");
        if (isset($info['howcreated'])){
            $sdata['howcreated']=$info['howCreated'];
        }
        $sdata['emailaddress']=$info['emailAddress'];
        $sdata['sex']=$info['sex'];
        $sdata['accesslevel'] =0;
        $sdata['isActive']=1;
        $sdata['country']=$info['country'];
        $id=$this->insert($sdata);
        return $id;
    }


    /**
    * Method to create a user account from getParam()
    * @param string $userId
    * @returns string $id
    */

    function createUserAccount($userId,$howcreated='selfregister')
    {
        $password=$this->getParam('password');
        if ($password==''){
            $objPassword=$this->getObject('passwords','useradmin');
            $password=$objPassword->createPassword();
        }
        $cryptpassword=sha1($password);
        $cdate=date("Y-m-d");
        $newdata=array(
            'userid'=>$userId,
            'username'=>$this->getParam('username'),
            'title'=>$this->getParam('title'),
            'firstname'=>$this->getParam('firstname'),
            'surname'=>$this->getParam('surname'),
            'pass'=>$cryptpassword,
            'creationdate'=>$cdate,
            'howcreated'=>$howcreated,
            'emailaddress'=>$this->getParam('email'),
            'sex'=>$this->getParam('gender'),
            'country'=>$this->getParam('country'),
            'accesslevel' =>0,
                'isActive'=>1
            );
        $id=$this->insert($newdata);
        $this->emailPassword($newdata['userId'],$newdata['username'],$newdata['firstname'],$newdata['surname'],$newdata['emailaddress'], $password);
        return $id;
    }

    /**
    * method to lookup list of users for admin functions
    * @author James Scoble
    *
    * @param string $how - the method of searching used - username, surname or email
    * @param string $match - the pattern to match for
    * returns array $r1
    */
     public function getUsers($how,$match,$exact=FALSE)
     {
         $sql="SELECT * FROM tbl_users";
         if (
            ($how=='username')
            ||($how=='surname')
            ||($how=='emailaddress')
            ||($how=='userid')
            ||($how=='creationdate')
            ||($how=='logins')
            ||($how=='isActive')
        )
        {
            if ($match=='listall') {
            }
            else {
                if ($exact===TRUE){
                    $sql.=" WHERE $how = '$match'";
                } else {
                    $sql.=" WHERE $how LIKE '$match%'";
                }
            }
            $sql .= " ORDER BY $how";
        }
        if ($how=='notused'){
            $sixMonthsAgo=date('Y-m-d',time()-15552000);
            $sql.=" WHERE logins='0' AND creationdate<'$sixMonthsAgo' ORDER BY creationdate";
        }
        $results=$this->getArray($sql);
        $count = count($results);
        for ($i=0;$i<$count;$i++) {
             $keys = array_keys($results[$i]);
             foreach ($keys as $key) {
                $results[$i][$key] = stripslashes($results[$i][$key]);
             }
        }
        return $results;
    }

    /**
    * This is a method to delete a group of users at once
    * using an array of userId's
    * It will not delete Site-Admin user accounts
    * @author James Scoble
    * @param array $userArray
    */
    public function batchDelete($users)
    {
        foreach ($users as $user)
        {
            $isAdmin=$this->objUser->lookupAdmin($user);
            if (!$isAdmin){
                $this->delete('userid',$user);
            }
        }
    }

    /**
    * Check if userid exists.
    * @param string $userId
    */
    public function checkUserIdAvailable($userId)
    {
        $sql="SELECT COUNT(*) AS thecount FROM tbl_users WHERE userId='$userId'";
        $count=$this->getArray($sql);
        if ($count[0]['thecount']>0) {
            return $this->objLanguage->languageText("userid_taken", 'useradmin');
        }
        else {
            return true;
        }
    }

    /**
    * Check if username exists.
    * @param string $username
    */
    public function checkUsernameAvailable($username)
    {
        $sql="SELECT COUNT(*) AS thecount FROM tbl_users WHERE username='$username'";
        $count=$this->getArray($sql);
        if ($count[0]['thecount']>0) {
            return $this->objLanguage->languageText("username_taken", 'useradmin', 'This username is taken');
        }
        else {
            return true;
        }
    }

    /**
    * This is a method to change SQL password for specified userId
    * @author James Scoble
    * @param string $userId
    * @param string $oldpassword
    * @param string $newpassword
    * @returns TRUE|FALSE
    *
    * This function checks the supplied password against the one in the database.
    * Only if it matches does it change to the new one.
    */
    public function changePassword($userId,$oldpassword,$newpassword)
    {
        $data=$this->getUsers('userid',$userId,TRUE);
        //print_r($data);
        if (strtolower($data[0]['pass'])==strtolower(sha1($oldpassword)))
        {
            // here we proceed to actually do the change
            $cryptpassword=sha1($newpassword);
            //$sql="update tbl_users set password='".$cryptpassword."' where userId='".$userId."'";
            $this->update('userid',$userId,array('password'=>$cryptpassword));
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }


    /**
    * Method to compose and send email for resetting of password
    * @param string $firstname - data to send
    * @param string $surname - data to send
    * @param string $userId - data to send
    * @param string $username - data to send
    * @param string $title - data to send
    * @param string $email - data to send
    * @param string $password - data to send
    */

    function emailPassword($userId,$username,$firstname,$surname,$email,$password)
    {
        $info=$this->siteURL();
        $emailtext=str_replace('SURNAME',$surname,str_replace('FIRSTNAME',$firstname,$this->objLanguage->languageText('mod_useradmin_greet1','useradmin')))."\n"
        .$this->objLanguage->languageText('mod_useradmin_greet4','useradmin')."\n"
        .$this->objLanguage->languageText('word_userid').": $userId\n"
        .$this->objLanguage->languageText('phrase_firstname').": $firstname\n"
        .$this->objLanguage->languageText('word_surname').": $surname\n"
        .$this->objLanguage->languageText('word_username').": $username\n"
        .$this->objLanguage->languageText('word_password').": $password\n"
        .$this->objLanguage->languageText('phrase_emailaddress').": $email\n"
        .$this->objLanguage->languageText('mod_useradmin_greet7','useradmin')." "
        .$info['link']." (".$info['url'].")\n"
        .$this->objLanguage->languageText('word_sincerely')."\n"
        .$this->objLanguage->languageText('mod_useradmin_greet5','useradmin')."\n";
        $subject=$this->objLanguage->languageText('mod_useradmin_greet6','useradmin');
        $emailtext=str_replace('Chisimba',$info['sitename'],$emailtext);
        $subject=str_replace('Chisimba',$info['sitename'],$subject);
        $header="From: ".$this->objLanguage->languageText('mod_useradmin_greet5','useradmin').'<noreply@'.$info['server'].">\r\n";
        @mail($email,$subject,$emailtext,$header);
    }


    /**
    * Method to determine site URL for email and other purposes
    * @returns array $kngdata an array of the info on the site
    */

    function siteURL()
    {
        $KNGname=$this->objConfig->getSitename();
        $WWWname=$this->objConfig->getSiteName();
        $KNGpath=$this->objConfig->getsiteRoot();

        if ($KNGpath==''){
            $KNGpath=$_SERVER['PHP_SELF'];
        }
        $url=$KNGpath;
        return array(
            'url'=>$url,
            'sitename'=>$KNGname,
            'link'=>" <a href='$url'>$KNGname</a> ",
            'server'=>$WWWname
            );
    }


    /**
    * Is a user an LDAP user.
    * @param string $userId
    * @returns boolean
    */
    public function isLDAPUser($userId)
    {
        $data=$this->getUsers('userId',$userId);
        return $data[0]['pass']==sha1('--LDAP--');
    }

    /**
    * method to create user folder
    * @author James Scoble, Paul Scott
    * @param string $userId
    */
    /*
    function makeUserFolder($userId)
    {
        // First we check that the 'userfiles' folder exists
        $courses = $this->objConfig->getcontentBasePath();
        if (!(file_exists($courses))){
            $oldumask = umask(0);
            @mkdir($courses, 0777); // or even 01777 so you get the sticky bit set
            umask($oldumask);
        }
        // Then we create the user folder
        $dir = $this->objConfig->getcontentBasePath().$userId;
        if (!(file_exists($dir))){
            $oldumask = umask(0);
            @mkdir($dir, 0777); // or even 01777 so you get the sticky bit set
            umask($oldumask);
        }
    }
    */

    /**
    * Method to set a user to Active or InActive status
    * @param string $userId;
    * @param char $newstate;
    * @returns TRUE|FALSE
    */

    /*
    function setActive($userId,$newstate)
    {
        if ($this->valueExists('userid',$userId)){
            return $this->update('userid',$userId,array('isactive'=>$newstate));
        } else {
            return FALSE;
        }
    }
    */

    /**
    * Method to get the details of a user by providing the id (not userid)
    * @param string $id Id of the User
    * @return array|boolean Array if the user exists, else FALSE
    */
    public function getUserDetails($id)
    {
        return $this->getRow('id', $id);
    }


} // end of class sqlUsers

?>
