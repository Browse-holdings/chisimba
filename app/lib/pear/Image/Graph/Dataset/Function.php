<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Image_Graph - PEAR PHP OO Graph Rendering Utility.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This library is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2.1 of the License, or (at your
 * option) any later version. This library is distributed in the hope that it
 * will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser
 * General Public License for more details. You should have received a copy of
 * the GNU Lesser General Public License along with this library; if not, write
 * to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 * 02111-1307 USA
 *
 * @category   Images
 * @package    Image_Graph
 * @subpackage Dataset
 * @author     Jesper Veggerby <pear.nosey@veggerby.dk>
 * @copyright  Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Image_Graph
 */

/**
 * Include file Image/Graph/Dataset.php
 */
require_once 'Image/Graph/Dataset.php';

/**
 * Function data set, points are generated by calling an external function.
 *
 * The function must be a single variable function and return a the result,
 * builtin  functions that are fx sin() or cos(). User defined function can be
 * used if they are such, i.e: function myFunction($variable)
 *
 * @category   Images
 * @package    Image_Graph
 * @subpackage Dataset
 * @author     Jesper Veggerby <pear.nosey@veggerby.dk>
 * @copyright  Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: 0.7.1
 * @link       http://pear.php.net/package/Image_Graph
 */
class Image_Graph_Dataset_Function extends Image_Graph_Dataset
{

    /**
     * The name of the function
     * @var string
     * @access private
     */
    var $_dataFunction;

    /**
     * Image_Graph_FunctionDataset [Constructor]
     *
     * @param double $minimumX The minimum X value
     * @param double $maximumX The maximum X value
     * @param string $function The name of the function, if must be a single
     * parameter function like fx sin(x) or cos(x)
     * @param int $points The number of points to create
     */
    function Image_Graph_Dataset_Function($minimumX, $maximumX, $function, $points)
    {
        parent::Image_Graph_Dataset();
        $this->_minimumX = $minimumX;
        $this->_maximumX = $maximumX;
        $this->_dataFunction = $function;
        $this->_count = $points;
        $this->_calculateMaxima();
    }

    /**
     * Add a point to the dataset.
     *
     * You can't add points to a function dataset
     *
     * @param int $x The X value to add
     * @param int $y The Y value to add, can be omited
     * @param var $ID The ID of the point
     */
    function addPoint($x, $y = false, $ID = false)
    {
    }

    /**
     * Gets a Y point from the dataset
     *
     * @param var $x The variable to apply the function to
     * @return var The function applied to the X value
     * @access private
     */
    function _getPointY($x)
    {
        $function = $this->_dataFunction;
        return $function ($x);
    }

    /**
     * The number of values in the dataset
     *
     * @return int The number of values in the dataset
     * @access private
     */
    function _count()
    {
        return $this->_count;
    }

    /**
     * The interval between 2 adjacent Y values
     *
     * @return var The interval
     * @access private
     */
    function _stepX()
    {
        return ($this->_maximumX - $this->_minimumX) / $this->_count();
    }

    /**
     * Calculates the Y extrema of the function
     *
     * @access private
     */
    function _calculateMaxima()
    {
        $x = $this->_minimumX;
        while ($x <= $this->_maximumX) {
            $y = $this->_getPointY($x);
            $this->_minimumY = min($y, $this->_minimumY);
            $this->_maximumY = max($y, $this->_maximumY);
            $x += $this->_stepX();
        }
    }

}

?>