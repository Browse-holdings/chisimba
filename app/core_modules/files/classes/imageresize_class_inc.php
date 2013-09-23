<?php

/**
 * Class to resize images
 * 
 * Creates thumbnails from jpeg and other image file formats.
 * 
 * PHP versions 4 and 5
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
 * @package   files
 * @author Tohir Solomons
 * @copyright 2004, University of the Western Cape & AVOIR Project
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License 
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 * @see       References to other sections (if any)...
 */

/**
* Class to resize images
*
* @author Martin Konicek
* @author Tohir Solomons
*         
*         Note: The original class was written by Martin Konicek and can be found at http://www.air4web.com/files/upload/
*         I added support for other image types besides jpeg, and added a handler
*         to make the background white, as well as commenting the class.
*         
*         Where a file/image cannot be resized, a small image is created with the
*         words: "Unable to create a thumbnail from a [ext] file". - Tohir
*/
include($this->getResourcePath('imagecreatefrombmp.php', 'files'));

/**
 * Description for include
 */
include($this->getResourcePath('imagecreatefrompsd.php', 'files'));

/**
 * Class to resize images
 * 
 * Creates thumbnails from jpeg and other image file formats.
 * 
 * @category  Chisimba
 * @package   files
 * @author Tohir Solomons
 * @copyright 2004, University of the Western Cape & AVOIR Project
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License 
 * @version   Release: @package_version@
 * @link      http://avoir.uwc.ac.za
 */
class imageresize extends object
{
    
    /**
    * @var string $image Imported Content of the Image
    */
    var $image = '';
    
    /**
    * @var string $filetype File type of the image
    */
    var $filetype;
    
    /**
    * @var string $temp Variable to Hold the resized image
    */
    var $temp = '';
    
    /**
    * @var boolean $canCreateFromSouce A flag to indicate whether a thumbnail can be created from the file or not
    */
    var $canCreateFromSouce = TRUE;
    
    
    /**
    * Constructor
    */
    function init()
    {
        $this->objFileParts = $this->getObject('fileparts', 'files');
    }
    
    /**
    * Method to set the image to be resized
    * @param string $sourceFile Path of the Image to be resized
    */
    function setImg($sourceFile)
    {
        // Check if File Exists
        if (file_exists($sourceFile))
        {
            // Get Image Type
            $imagetype = $this->getImageType($sourceFile);
            
            // Default set to True
            $this->canCreateFromSouce = TRUE;
            
            // Set Image Type to Global Variable
            $this->filetype = $imagetype;
            
            switch ($imagetype) // Check which function to use
            {
                // PHP can only create thumbnails from GIF, JPG, PNG, WBMP and XBM formats
                // For all others, it will return a 100x100 image that says, unable to create thumbnail
                case 'gif': $this->image = imagecreatefromgif($sourceFile); break;
                case 'jpg': $this->image = imagecreatefromjpeg($sourceFile); break;
                case 'png': $this->image = imagecreatefrompng($sourceFile); break;
                case 'wbmp': $this->image = imagecreatefromwbmp($sourceFile); break;
                case 'xbm': $this->image = imagecreatefromxbm($sourceFile); break;
                case 'bmp': $this->image = ImageCreateFromBMP($sourceFile); break;
                case 'psd': $this->image = imagecreatefrompsd($sourceFile); break;
                default : 
                    // Cannot create from source
                    $this->canCreateFromSouce = FALSE;
                    
                    // Create Blank Image with White Background
                    $this->image = imagecreatetruecolor(100, 100);
                    $bgc = imagecolorallocate ($this->image, 255, 255, 255);
                    imagefilledrectangle ($this->image, 0, 0, 100, 100, $bgc);
                    break;
            }
        } else {
            return FALSE;
        }

    }
    
    /**
    * Method to get the type of image
    *
    * Although PHP can only create thumbnails from GIF, JPG, PNG, WBMP and XBM formats,
    * the other formats are listed here in case a developer wants to use it to pickup the type of image
    *
    * @param  string $sourceFile Path to File
    * @return string Type of file
    */
    function getImageType($sourceFile)
    {
        // Get File Image Info
        $imageInfo = getimagesize($sourceFile);
        
        // If the file is not an image, it will return FALSE, so first check if it parsed an image
        if (isset($imageInfo[2])) {
            switch ($imageInfo[2])
            {
                case '1': return 'gif'; break;
                case '2': return 'jpg'; break;
                case '3': return 'png'; break;
                case '4': return 'swf'; break;
                case '5': return 'psd'; break;
                case '6': return 'bmp'; break;
                case '7': return 'tif'; break;
                case '8': return 'tif'; break;
                case '9': return 'jpc'; break;
                case '10': return 'jp2'; break;
                case '11': return 'jpx'; break;
                case '12': return 'jb2'; break;
                case '13': return 'swc'; break;
                case '14': return 'iff'; break;
                case '15': return 'wbmp'; break;
                case '16': return 'xbm'; break;
                // There are no other types that PHP recognizes besides the above 16
                // The line below is added just in case
                default: return $this->objFileParts->getExtension($sourceFile);
            }
        } else {
            // Should be false, but here it return the extension to create an image
            // that says "Unable to create a thumbnail from a [ext] file".
            return $this->objFileParts->getExtension($sourceFile);
        }
    }
    
    /**
    * Method to resize an image
    * @param int     $width       Width of Thumbnail
    * @param int     $height      Height of Thumbnail
    * @param boolean $aspectratio Flag to indicate whether to main aspect ratio of image
    */
    function resize($width = 100, $height = 100, $aspectratio = TRUE)
    {
        // Get Original Width and Height
        $o_wd = imagesx($this->image);
        $o_ht = imagesy($this->image);
        
        // If Aspect Ratio is required, calculate width and height of thumbail
        // to fit in with given size
        if(isset($aspectratio)&& $aspectratio) {
            $w = round($o_wd * $height / $o_ht);
            $h = round($o_ht * $width / $o_wd);
//            if(($height-$h)<($width-$w)){
//                $width =& $w;
//            } else {
//                $height =& $h;
//            }
        }
        
        // Create Thumbnail Image
        $this->temp = imagecreatetruecolor($width, $height);
        
        if ($this->filetype == 'gif' || $this->filetype == 'png')
        {
            // set transparent for png and gif
            imagealphablending($this->temp , false);
            imagesavealpha($this->temp , true);
            $transparent = imagecolorallocatealpha($this->temp, 255, 255, 255, 127);
            imagefilledrectangle ($this->temp, 0, 0, $width, $height, $transparent);
        }
        else
        {
            // Setup Interlacing, Progessive JPG
            imageinterlace($this->temp, 1);

            // Fill with White
            $bgc = imagecolorallocate ($this->temp, 255, 255, 255);
            imagefilledrectangle ($this->temp, 0, 0, $width, $height, $bgc); 
        }
        
        // Check whether Thumnail image can be used
        if ($this->canCreateFromSouce) {
            // Add Original Image - Uses resample instead of resize which delivers a better image
            imagecopyresampled($this->temp, $this->image, 0, 0, 0, 0, $width, $height, $o_wd, $o_ht);
        } else {
            // Else add message 
            imagestring ($this->temp, 4, 5, 0, 'Unable to ', 0 );
            imagestring ($this->temp, 4, 5, 20, 'Create a', 0 );
            imagestring ($this->temp, 4, 5, 40, 'Thumbnail', 0 );
            imagestring ($this->temp, 4, 5, 60, 'From a', 0 );
            imagestring ($this->temp, 5, 5, 80, strtoupper($this->filetype), 0 );
            imagestring ($this->temp, 4, 40, 80, 'File', 0 );
        }
        
        $this->sync();
        return;
    }
    
    /**
    * Method to sync image variable
    */
    function sync()
    {
        $this->image =& $this->temp;
        unset($this->temp);
        $this->temp = '';
        return;
    }
    
    /**
    * Method to show thumbnail in browser
    */
    function show()
    {
        $this->_sendHeader();
        if ($this->filetype == 'gif' || $this->filetype == 'png')
        {
            imagepng($this->image);
        }
        else
        {
            imagejpeg($this->image);
        }
        
        return;
    }
    
    /**
    * Method to send Header Parameters.
    * @access private
    */
    function _sendHeader(){
        if ($this->filetype == 'gif' || $this->filetype == 'png')
        {
            header('Content-Type: image/png');
        }
        else
        {
            header('Content-Type: image/jpeg');
        }
    }
    
    /**
    * Method to save the image to the filesystem
    * @param string $file Name of the File
    * @param boolean $appendExtension Append the image's existing file extension to $file.
    */
    function store($file, $appendExtension = FALSE)
    {
        if ($appendExtension) {
            $file .= $this->filetype == 'jpg' ? '.jpg' : '.png';
        }
        if ($this->filetype == 'gif' || $this->filetype == 'png') {
            return @imagepng($this->image, $file);
        } else {
            return @imagejpeg($this->image, $file);
        }
    }
    
    /*
    // This function existed in the original file
    function watermark($pngImage, $left = 0, $top = 0)
    {
        ImageAlphaBlending($this->image, true);
        $layer = ImageCreateFromPNG($pngImage); 
        $logoW = ImageSX($layer); 
        $logoH = ImageSY($layer); 
        ImageCopy($this->image, $layer, $left, $top, 0, 0, $logoW, $logoH); 
    }*/
}
?>
