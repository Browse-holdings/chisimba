<?php

// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
// end security check

/**
 * This class is used to gnerate an image of a marked paper
 *
 * @category  Chisimba
 * @package   utilities
 * @author    Tohir Solomons <tsolomons@uwc.ac.za>
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General
Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 */

class markimage extends object 
{
    
    /**
     * @var int $value Value of item
     */
    public $value=0;
    
    public $percentage = FALSE;
    
    /**
     * @var string $path Local Path to the image
     */
    private $path;
    
    /**
     * @var string $path Local Path to the image
     */
    private $fullPath;
    
    /**
     * Constructor
     */
    public function init()
    {
        $this->objConfig = $this->getObject('altconfig', 'config');
    }
    
    /**
     * Method to generate the mark
     */
    public function show()
    {
        // Setup Path
        $this->setupPaths();
        
        $path = $this->value;
        
        if ($this->percentage) {
            $path .= '%';
        }
        
        // If not already generated, generate it
        if (!file_exists($this->fullPath.md5($path).'.png')) {
            $this->generate($this->value);
        }
        
        // Return $path
        return '<img src="'.$this->path.md5($path).'.png" />';
    }
    
    /**
     * Method to setup paths
     * This function sets up valid paths, also checks that directory for storing
     * Image exists
     */
    private function setupPaths()
    {
        $objCleanUrl = $this->getObject('cleanurl', 'filemanager');
        
        $filePath = $this->objConfig->getcontentBasePath().'markimage/';
        $filePath = $objCleanUrl->cleanUpUrl($filePath);
        
        $objMkdir = $this->getObject('mkdir', 'files');
        $objMkdir->mkdirs($filePath, 0777);
        
        $this->fullPath = $filePath;
        
        $filePath = $this->objConfig->getcontentPath().'markimage/';
        $filePath = $objCleanUrl->cleanUpUrl($filePath);
        
        $this->path = $filePath;
    }
    
    /**
     * Method to generate the mark
     * @param int $value Value/Mark
     */
    private function generate($value)
    {
        // Font Size
        $fontsize = 25;
        
        // Font
        $font = $this->getResourcePath('markimage/SteveHand.ttf');
        
        if ($this->percentage) {
            $value .= '%';
        }
        
        // Some adjustment of left placement
        switch(strlen($value))
        {
            case 1: $left = 35; break;
            case 2: $left = 30; break;
            case 3: $left = 20; break;
            default: $left = 5; break;
        }
        
        $top = 65;
        
        // Load Background Image
        $img = imagecreatefromgif($this->getResourcePath('markimage/bkg.gif'));
        
        // Generate Red Color
        $red = imagecolorallocate($img, 255, 0, 0);
        
        // Add mark
        imagettftext($img, $fontsize, 0, $left, $top, $red, $font, $value);
        
        // Store to file system
        imagepng($img, $this->fullPath.md5($value).'.png');
    }
}
?>