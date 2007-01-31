<?php
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run'])
{
    die("You cannot view this page directly");
}
// end security check

/**
* error module Controller
*
* @author Paul Scott
* @copyright (c) 2004 University of the Western Cape
* @package httpclient
* @version 1
*/
class errors extends controller
{
    public $objLog;
    public $objConfig;
    public $objLanguage;
    public $objMail;
    public $objUser;

	/**
	* Constructor method to instantiate objects and get variables
	*/
    public function init()
    {
        try {
        	$this->objConfig = $this->getObject('altconfig','config');
        	$this->objLanguage = $this->getObject('language','language');
        	$this->objUser = $this->getObject('user', 'security');
        }
        catch (customException $e)
        {
        	customException::cleanUp();
        }
    }

    public function requiresLogin() {
    	return FALSE;
    }

   /**
	* Method to process actions to be taken
    *
    * @param string $action String indicating action to be taken
	*/
    public function dispatch($action=Null)
    {
        switch ($action)
        {
            default:
            	return 'noaction_tpl.php';
            	break;
            case 'dberror':
            	//check for a dead database as well
            	$dsn = KEWL_DB_DSN;
				$dsn = $this->parseDSN($dsn);
				// Connect to the database
				require_once 'MDB2.php';
				//MDB2 has a factory method, so lets use it now...
				$checkdb = &MDB2::connect($dsn);
				//Check for errors on the connect method
				if (PEAR::isError($checkdb)) {
					$devmsg = $checkdb->getMessage();
					$usrmsg = $checkdb->getUserInfo();
					$this->setVarByRef('devmsg',$devmsg);
	            	$this->setVarByRef('usrmsg',$usrmsg);
    	        	return 'dberror_tpl.php';
        	    	break;
				}
            	$devmsg = $this->getParam('devmsg');
            	$usrmsg = $this->getParam('usrmsg');
            	$this->setVarByRef('devmsg',$devmsg);
            	$this->setVarByRef('usrmsg',$usrmsg);
            	return 'dberror_tpl.php';
            	break;

            case 'errormail':
            	$hidmsg = $this->getParam('error');
            	if(empty($hidmsg))
            	{
            		//possible spam usage!!!
            		return 'spam_tpl.php';
            		exit();
            	}
            	$text = $this->getParam('comments');
            	try {
            		//load up the mail class
            		$this->objMail = $this->newObject('email', 'mail');
       				//set up the mailer
       				$objMailer = $this->getObject('email', 'mail');
					$objMailer->setValue('to', array('fsiu-dev@uwc.ac.za', $this->objConfig->getsiteEmail(), 'pscott@uwc.ac.za', 'fsiu@uwc.ac.za'));
					$objMailer->setValue('from', $this->objUser->email());
					$objMailer->setValue('fromName', $this->objUser->fullname());
					$objMailer->setValue('subject', $this->objLanguage->languageText("mod_errors_errsubject", "errors"));
					$objMailer->setValue('body', $text . "  " . $hidmsg);
					$objMailer->send();
					return 'thanks_tpl.php';
					break;

            	}
            	catch (customException $e)
            	{
            		customException::cleanUp();
            		exit;
            	}
            	//$this->objMail->
            	//echo $hidmsg . "<br /><br />" . $text;

            	break;
            case 'syserr':
            	$mess = $this->getParam('msg');
            	$mess = urldecode(htmlentities($mess));
            	$this->setVarByRef('mess', $mess);
            	return "syserror_tpl.php";
            	break;

        }
    }

    	/**
     * Method to parse the DSN from a string style DSN to an array for portability reasons
     *
     * @access public
     * @param string $dsn
     * @return void
     * @TODO get the port settings too!
     */
	public function parseDSN($dsn)
	{
		$parsed = NULL;
		$arr = NULL;
		if (is_array($dsn)) {
			$dsn = array_merge($parsed, $dsn);
			return $dsn;
		}
		//find the protocol
		if (($pos = strpos($dsn, '://')) !== false) {
			$str = substr($dsn, 0, $pos);
			$dsn = substr($dsn, $pos + 3);
		} else {
			$str = $dsn;
			$dsn = null;
		}
		if (preg_match('|^(.+?)\((.*?)\)$|', $str, $arr)) {
			$parsed['phptype']  = $arr[1];
			$parsed['phptype'] = !$arr[2] ? $arr[1] : $arr[2];
		} else {
			$parsed['phptype']  = $str;
			$parsed['phptype'] = $str;
		}

		if (!count($dsn)) {
			return $parsed;
		}
		// Get (if found): username and password
		if (($at = strrpos($dsn,'@')) !== false) {
			$str = substr($dsn, 0, $at);
			$dsn = substr($dsn, $at + 1);
			if (($pos = strpos($str, ':')) !== false) {
				$parsed['username'] = rawurldecode(substr($str, 0, $pos));
				$parsed['password'] = rawurldecode(substr($str, $pos + 1));
			} else {
				$parsed['username'] = rawurldecode($str);
			}
		}
		//server
		if (($col = strrpos($dsn,':')) !== false) {
			$strcol = substr($dsn, 0, $col);
			$dsn = substr($dsn, $col + 1);
			if (($pos = strpos($strcol, '+')) !== false) {
				$parsed['hostspec'] = rawurldecode(substr($strcol, 0, $pos));
			} else {
				$parsed['hostspec'] = rawurldecode($strcol);
			}
		}

		//now we are left with the port and databsource so we can just explode the string and clobber the arrays together
		$pm = explode("/",$dsn);
		$parsed['hostspec'] = $pm[0];
		$parsed['database'] = $pm[1];
		$dsn = NULL;

		$parsed['hostspec'] = str_replace("+","/",$parsed['hostspec']);

		return $parsed;
	}


}
?>