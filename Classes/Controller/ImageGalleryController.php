<?php
namespace NS\NsInstagram\Controller;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Extbase\Utility\DebuggerUtility as debug;

/**
 * ImageGalleryController
 */
class ImageGalleryController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        
        if($this->settings['photo_border']=='1'){
            $photo_border = "true";
        }
        else{
            $photo_border = "false";
        }

        if($this->settings['show_infos']=='1'){
            $show_infos = "true";
        }
        else{
            $show_infos = "false";
        }

        if($this->settings['widget_border']=='1'){
            $widget_border = "true";
        } 
        else{
            $widget_border = "false";
        }

        $background = trim($this->settings['background'],"#.");
        $bordercolor = trim($this->settings['border-color'],"#.");
        $text = trim($this->settings['text'],"#.");
        $width = $this->settings['width'];
        $height = $this->settings['height'];

        if($this->settings['choice']=='myfeed'){
            $queryString = "choice={$this->settings['choice']}&username={$this->settings['username']}&show_infos={$show_infos}&width={$this->settings['width']}&height={$this->settings['height']}&title={$this->settings['title']}&title-align={$this->settings['title-align']}&responsive=false&mode={$this->settings['mode']}&padding={$this->settings['imagepadding']}&photo_border={$photo_border}&background={$background}&text={$text}&widget_border={$widget_border}&radius={$this->settings['radius']}&border-color={$bordercolor}";
        }
        else{
            $queryString = "choice={$this->settings['choice']}&hashtag={$this->settings['tagged']}&show_infos={$show_infos}&width={$this->settings['width']}&height={$this->settings['height']}&title={$this->settings['title']}&title-align={$this->settings['title-align']}&responsive=false&mode={$this->settings['mode']}&padding={$this->settings['imagepadding']}&photo_border={$photo_border}&background={$background}&text={$text}&widget_border={$widget_border}&radius={$this->settings['radius']}&border-color={$bordercolor}";
        }

        $url = "https://pro.iconosquare.com/widget/gallery?" . $queryString;

        return '<iframe width="'.$width.'" height="'.$height.'" src="'.$url.'" allowTransparency="true" frameborder="0" scrolling="no" style="border:none; overflow:hidden; width:'.$this->settings['width'].'px; height:'.$this->settings['height'].'px;"></iframe>';
    }
}
