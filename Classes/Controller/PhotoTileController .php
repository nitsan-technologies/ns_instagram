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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Http\HttpRequest;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Configuration;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * PhotoTileController
 */
class PhotoTileController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    
  protected $pagerender;

  public function initializeAction(){
    $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

    $custom = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ns_instagram') . 'Resources/Public/Phototile/Css/custom.css';
    $default = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ns_instagram') . 'Resources/Public/Phototile/Css/AlpinePhotoTiles_style.css';
    $js = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ns_instagram') . 'Resources/Public/Phototile/Js/AlpinePhotoTiles_script.js';
    $jquery = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ns_instagram') . 'Resources/Public/JS/jquery-2.2.4.min.js';
    $pageRenderer->addCssFile($default,$rel = 'stylesheet',$media = 'all',$compress = true,$forceOnTop = false);
    $pageRenderer->addCssFile($custom,$rel = 'stylesheet',$media = 'all',$compress = true,$forceOnTop = false);
    $pageRenderer->addJsFooterFile($js,$type = 'text/javascript');
    if($this->settings['javascript']['jquery']){
         $pageRenderer->addJsFile($jquery,$type='text/javascript',$compress = true,$forceOnTop = true);
    }
  }
  /*
  *
  * list Action
  *
  */
  public function listAction(){
    
    $token = $this->settings['accessToken'];
    $num = (empty($this->settings['noofimage'])) ? '5' : $this->settings['noofimage'];
    
    $this->contentObj = $this->configurationManager->getContentObject();
    $data = $this->contentObj->data;
    $uid = $data['uid'];
    $contentuid = "content-".$uid;
    $title = $data['header'];
    
    try{
      $user_id  = $this->getInstaID($this->settings['user']);
      $request = $this->get_instagram_setting($token,$user_id,$num);
      if( $request ) {
        $photos = $this->get_json($request, $num);
        if($photos == 1){
          $this->view->assign('error',$photos);
          $this->view->assign('request',$request);
          $this->view->assign('title',$title);
        }
        else{
          $results = $this->display_photo($photos,$request);
          return $results;
        }
      }
    }
    catch(\Exception $e){
      $version = GeneralUtility::makeInstance(VersionNumberUtility::class);
      $versionNum = $version->getNumericTypo3Version();
      $explode = explode(".", $versionNum);
      
      if($explode[0] == 7 || $explode[0] < 7){
        $statusCode = $e->getCode();
        if($statusCode == '400'){
          $jsonBody = json_decode($e->getMessage(),1);
          $this->addFlashMessage(LocalizationUtility::translate('error','ns_instagram') . $jsonBody['meta']['code'] .' : '.$jsonBody['meta']['error_type'] . ' , '. $jsonBody['meta']['error_message'],'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
        }
        else{
          if($statusCode == '404'){
            $this->addFlashMessage(LocalizationUtility::translate('error','ns_instagram') .$statusCode. ' : '.$e->getMessage(),'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
          }
          else{
            $this->addFlashMessage($e,'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
          }
        }
      }
      else{
        $response = $e->getResponse();
        $statusCode = $response->getStatusCode();
        $jsonBody = json_decode($response->getBody()->getContents(),1);
        if($statusCode == '400'){
          $this->addFlashMessage(LocalizationUtility::translate('error','ns_instagram') . $jsonBody['meta']['code'] .' : '.$jsonBody['meta']['error_type'] . ' , '. $jsonBody['meta']['error_message'],'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
        }
        else{
          if($statusCode == '404'){
            $this->addFlashMessage(LocalizationUtility::translate('error','ns_instagram').$statusCode. ' : ' .$response->getReasonPhrase(),'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
          }
          else{
            $this->addFlashMessage($e,'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
          }
        }
      }
    }
  }
  
  // Get Instagram userId from username
  function getInstaID( $username ) {
      $username = strtolower( $username );     
      $url = "https://www.instagram.com/".trim($username)."/";

      $response = $this->connectAPI($url,'GET',NULL,FALSE,NULL);

      $header = $response->getHeader();
      $body = $response->getBody();
      
      $shards = explode( 'window._sharedData = ', $body);
      $json = explode( ';</script>', $shards[1] );
      $data = json_decode( $json[0], TRUE );

      $user_id = $data['entry_data']['ProfilePage'][0]['user']['id'];
      return $user_id;     
  }

  // Get Instagram API Url 
  function get_instagram_setting( $token, $user_id, $num){
    if( isset($this->settings['source']) ){
          switch ($this->settings['source']) {
            case 'user_recent':
              $request = 'https://api.instagram.com/v1/users/'.$user_id.'/media/recent/?access_token='.$token.'&count='.$num;
            break;
            case 'user_liked':
              $request = 'https://api.instagram.com/v1/users/self/media/liked?access_token='.$token.'&count='.$num;
            break;
            case 'global_popular':
              $request = 'https://api.instagram.com/v1/media/popular?access_token='.$token.'&count='.$num;
            break;
            case 'global_tag':
              $instagram_tag = empty($this->settings['tag']) ? '' : $this->settings['tag'];
              $request = 'https://api.instagram.com/v1/tags/'.$instagram_tag.'/media/recent?access_token='.$token.'&count='.$num;
            break;
          }
      }
      return $request;
  }

  // Get Instagram Json Data 
  function get_json($request,$num){

    $instagram_json = $this->fetch_instagram_feed($request);

    $repeat = true;
    $record = array();
    $repeat_limit = 3;
    $loop_count = 0;
    $blocked = "";

    if(empty($instagram_json) || !isset($instagram_json['data']) || empty($instagram_json['data']) ){
      $error = 1;
      return $error;
    }
    else{
      $photos = array();
      $instagram_tag = $this->settings['tag'] ? $this->settings['tag']: '';
      while( !empty($repeat) && count($photos)<$num ){
          if( $repeat_limit == $loop_count ){
            $error = 1;
            break;
          }
          $loop_count++;

          $data = $instagram_json['data'];
          foreach( $data as $key=>$imageinfo ){

            $url = isset($imageinfo['images']['low_resolution']['url'])?$imageinfo['images']['low_resolution']['url']:$key;
            $username = $imageinfo['user']['username'];

            if( 'thumb' == $this->settings['photo_size'] && isset($imageinfo['images']['thumbnail']['url']) ){
              $url = $imageinfo['images']['thumbnail']['url'];
            }elseif( 'large' == $this->settings['photo_size'] && isset($imageinfo['images']['standard_resolution']['url']) ){
              $url = $imageinfo['images']['standard_resolution']['url'];
            }
            
            if( empty($record[ $url ]) && count($photos)<$num ){
              $record[ $url ] = true;
              $the_photo = array();
              $the_photo['image_link'] = (string) isset($imageinfo['link'])?$imageinfo['link']:'';
              $the_photo['image_title'] = (string) isset($imageinfo['caption']['text'])?$imageinfo['caption']['text']:'';
              $the_photo['image_title'] = @strip_tags( $the_photo['image_title'] );  
              $the_photo['image_title'] = $this->removeEmoji( $the_photo['image_title']  );
              $the_photo['image_title'] = @str_replace('"','',@str_replace("'",'',$the_photo['image_title']));  
              $the_photo['image_caption'] = "";
              $the_photo['image_source'] = (string) $url;
              $the_photo['image_original'] = (string) isset($imageinfo['images']['standard_resolution']['url'])?$imageinfo['images']['standard_resolution']['url']:$the_photo['image_source'];
              $photos[] = $the_photo;
            }
          } 
          $next_url = (isset($instagram_json['pagination']['next_url'])) ? $instagram_json['pagination']['next_url'] : null;
          if( count($photos)<$num && !empty($next_url) ){
            echo count($photos).' of '.$num.' photos found. Make another request.';
            $instagram_json = $this->fetch_instagram_feed($next_url);
          }elseif( count($photos)<$num && 'global_popular' == $this->settings['source']){
            echo count($photos).'of '.$num.' photos found. Make another request.';
            $instagram_json = $this->fetch_instagram_feed($request);
          }else{
            $repeat = false;
          }
          return $photos;
      }
      if( $this->settings['show_link'] && $this->settings['link_text']) {
        $link  = 'http://instagram.com/'.$username;
      }
    }
  }

  // Fetch Instagram Feed
  function fetch_instagram_feed($request){
    
    $response = $this->connectAPI($request,'GET',NULL,FALSE,NULL);
    // Get the content as a string on a successful request
    $body = $response->getBody();
    if(!empty($body)){
      $content = $body;
    }
    if(function_exists('json_decode')){
      $instagram_json = json_decode($content,true);
      if(empty($instagram_json['data'])){
        $this->addFlashMessage(LocalizationUtility::translate('phototile.errorMessage','ns_instagram',array('request'=> $request)),'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
      }
      else{
        return $instagram_json;
      }
    }
  }

  function connectAPI($url, $method = 'GET', $params = NULL, $json = TRUE, $headers = NULL){
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class);
        $versionNum = $version->getNumericTypo3Version();
        $explode = explode(".", $versionNum);
        
        if($explode[0] == 7 || $explode[0] < 7){
          // Http request for typo3 version 7 and lower than 7
          $request = GeneralUtility::makeInstance(HttpRequest::class,$url,$method);
          /** @var \HTTP_Request2_Response $response */
          $response = $request->send();
          // HEAD was not allowed, now trying GET
          if (isset($response) && $response->getStatus() === 200) {
            $request->setMethod('GET');
            /** @var \HTTP_Request2_Response $response */
            $response = $request->send();
            return $response;
          }
          else{
            if($response->getStatus() === 404){
              throw new \Exception($response->getReasonPhrase(), $response->getStatus());
            }
            else{
              throw new \Exception($response->getBody() , $response->getStatus());
            }
          }
        }
        else{
            $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
            // Return a PSR-7 compliant response object
            $response = $requestFactory->request($url, 'GET');
            return $response;
        }      
  }

  // Remove some Emoji from username
  function removeEmoji($text) {

      $clean_text = "";
      
      // Match Emoticons
      $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
      $clean_text = preg_replace($regexEmoticons, '', $text);

      // Match Miscellaneous Symbols and Pictographs
      $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
      $clean_text = preg_replace($regexSymbols, '', $clean_text);

      // Match Transport And Map Symbols
      $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
      $clean_text = preg_replace($regexTransport, '', $clean_text);

       // Match JS Emoticons (Find 1 '\' followed by 1 'u' followed by 4 characters (0 to 9 or a to f)
      $regexEmoticons = '/\\\\{1}u{1}[a-f0-9]{4}/';
      $clean_text = preg_replace($regexEmoticons, '', $clean_text);
      
      return $clean_text;
  }

  // Check number of images found
  function update_count($photos){
      $found = ($photos) && is_array($photos)?count($photos):0;
      $num = (empty($this->settings['noofimage'])) ? '5' : $this->settings['noofimage'];
      $numofphoto = min( $num, $found );
      return $numofphoto;
  }

  // Display Photos
  function display_photo($photos,$request){
      $numofphoto = $this->update_count($photos); 
      $siteurl = $this->uriBuilder->getRequest()->getRequestUri();
      $shadow = ($this->settings['shadow']?'AlpinePhotoTiles-img-shadow':'AlpinePhotoTiles-img-noshadow');
      $border = ($this->settings['border']?'AlpinePhotoTiles-img-border':'AlpinePhotoTiles-img-noborder');
      $curves = ($this->settings['curve']?'AlpinePhotoTiles-img-corners':'AlpinePhotoTiles-img-nocorners');
      $highlight = ($this->settings['highlight']?'AlpinePhotoTiles-img-highlight':'AlpinePhotoTiles-img-nohighlight');
     
      $this->contentObj = $this->configurationManager->getContentObject();
      $data = $this->contentObj->data;
      $uid = $data['uid'];
      $contentuid = "content-".$uid;
      $title = $data['header'];
      $instagram_content = '<h2 class="title">'.$title.'</h2>';
      $instagram_content .= '<div id="'.$contentuid.'-AlpinePhotoTiles_container" class="AlpinePhotoTiles_container_class">';
      $css = $this->set_css();

      if($this->settings['style_option']=='vertical'){
        $instagram_content .= '<div id="'.$contentuid.'-vertical-parent" class="AlpinePhotoTiles_parent_class" style="'.$css.'">';
        $css = "margin:1px 0 5px 0;padding:0;max-width:100%;";
        for($i = 0;$i<$numofphoto;$i++){
        $instagram_content .= $this->add_image($i,$siteurl,$contentuid,$shadow,$border,$curves,$highlight,$css,$photos);
        }
      }
      elseif($this->settings['style_option']=='cascade'){
        $instagram_content .= '<div id="'.$contentuid.'-cascade-parent" class="AlpinePhotoTiles_parent_class" style="'.$css.'">';
        $css = "margin:1px 0 5px 0;padding:0;max-width:100%;";
        $width = (100/$this->settings['cols']);
        for($col = 0; $col<$this->settings['cols'];$col++){
            $instagram_content .= '<div class="AlpinePhotoTiles_cascade_column" style="width:'.$width.'%;float:left;margin:0;">';
            $instagram_content .= '<div class="AlpinePhotoTiles_cascade_column_inner" style="display:block;margin:0 3px;overflow:hidden;">';
            for($i = $col;$i<$numofphoto;$i+=$this->settings['cols']){
              $instagram_content .= $this->add_image($i,$siteurl,$contentuid,$shadow,$border,$curves,$highlight,$css,$photos); // Add image
            }
            $instagram_content .='</div></div>';
          }          
      }else{
        $instagram_content .= '<div id="'.$contentuid.'-hidden-parent" class="AlpinePhotoTiles_parent_class" style="'.$css.'">';
        $instagram_content .= '<div id="'.$contentuid.'-image-list" class="AlpinePhotoTiles_image_list_class" style="display:none;visibility:hidden;">';
        for($i=0;$i<$numofphoto;$i++){
          $instagram_content .= $this->add_image($i,$siteurl,$contentuid,$shadow,$border,$curves,$highlight,$css,$photos);

          if( isset($this->settings['style_option']) && "gallery" == $this->settings['style_option']){
            // Load original image size
            foreach ($photos as $key => $value) {
              $original = $photos['image_original'];  
            }
          
            if( !empty( $original ) ){
              $instagram_content .= '<img class="AlpinePhotoTiles-original-image" src="' . $original . '" />';
            }
          }
        }
      }
        $instagram_content .= '</div>';
        $instagram_content .= '<div class="AlpinePhotoTiles_breakline"></div>';
        $instagram_content .= $this->add_follow_link($contentuid,$request);
        $instagram_content .= '</div>';

        if($this->settings['style_option'] == 'cascade'){
          $parentID = $contentuid."-cascade-parent";
          $borderCall = $this->get_borders_call( $parentID );
          if( !empty($this->settings['shadow']) || !empty($this->settings['border']) || !empty($this->settings['highlight'])  ){
            $instagram_content .= "<script type='text/javascript'>".$borderCall."</script>";  
          }
        }
        elseif($this->settings['style_option'] == 'vertical'){
          $parentID = $contentuid."-vertical-parent";
          $borderCall = $this->get_borders_call( $parentID );
          if( !empty($this->settings['shadow']) || !empty($this->settings['border']) || !empty($this->settings['highlight'])  ){
            $instagram_content .= "<script type='text/javascript'>".$borderCall."</script>";  
          }
        }else{
          $lightScript = '';
          $lightStyle = '';
          $lightbox = $this->settings['lightbox'];
          if(isset($this->settings['image_link']) && $this->settings['image_link'] == 'lightbox' ){
            $lightScript = $this->add_script( $lightbox );
            $lightStyle = $this->add_style( $lightbox );
            if( !empty($lightScript) && !empty($lightStyle) ){
              $hasLight = true;
            }
          }
          $pluginCall = $this->get_loading_call($contentuid,$lightbox,$hasLight,$lightScript,$lightStyle);
          $instagram_content .= "<script type='text/javascript'>";
          $instagram_content .= "
            if( window.jQuery ){
              jQuery(document).ready(function() {
                jQuery('#".$contentuid."-AlpinePhotoTiles_container').addClass('loading');
              });
            }
            </script>
          ";        
          $instagram_content .= "<script type='text/javascript'>
            (function(){
              var alpinePluginLoadingFunction = function(method){
                if(window.jQuery){
                  if(jQuery.isFunction( jQuery(window).on )){
                    jQuery(window).on('load', method);
                  
                  }else{jQuery(window).bind('load', method);}

                }else if( window.addEventListener ){
                  window.addEventListener('load', method, false);
                
                }else if ( window.attachEvent ){
                  window.attachEvent('on' + 'load', method);
                }
              };
              alpinePluginLoadingFunction( function(){
                if( window.jQuery ){
                  ".$pluginCall."
                }else{ 
                  console.log('Alpine plugin failed because jQuery never loaded'); 
                }
              });
            })();</script>";
        } 
        if($this->settings['image_link']=='lightbox'){
          $instagram_content .= $this->add_lightbox($contentuid);
        }
        return $instagram_content;
  }

  // Set css
  function set_css(){
      
      $max = $this->settings['max_width']?$this->settings['max_width']:100;
      $return = 'width:100%;max-width:'.$max.'%;padding:0px;';
      $align = $this->settings['align']?$this->settings['align']:'';
      if( 'center' == $align ){ 
        $css .= 'margin:0px auto;text-align:center;';
      }
      elseif( 'right' == $align  || 'left' == $align  ){                          
        $css .= 'float:' . $align  . ';text-align:' . $align  . ';';
      }
      else{
        $css .= 'margin:0px auto;text-align:center;';
      }
      return $return . $css;
  }

  // Add Images to template
  function add_image($i,$siteurl,$contentuid,$shadow,$border,$curves,$highlight,$css="",$photos){
      $imagetitle = $photos[$i]['image_title'];
      $imagesrc = $photos[$i]['image_original'];
      
      $has_link = $this->get_link($i,$imagetitle,$contentuid,$photos); // Add link
      $inside = $has_link;
      $inside .= '<img id="'.$contentuid.'-tile-'.$i.'" class="AlpinePhotoTiles-image '.$shadow.' '.$border.' '.$curves.' '.$highlight.'" src="'. $imagesrc .'" ';
      $inside .= 'title=" '. $imagetitle .' " alt=" '. $imagetitle .' " border="0" hspace="0" vspace="0" style="'.$css.'" />'; // Careful about caps with ""
        if( $has_link ){ $inside .='</a>'; } // Close link
        return $inside;
  }

  // Get link
  function get_link($i,$imagetitle,$contentuid,$photos){
      
      $ilink = $this->settings['image_link'];
      if( 'lightbox' == $ilink ){
        $originalurl = $photos[$i]['image_original'];
        $this->set_lightbox_rel($contentuid);
        if( !empty($originalurl) ){
          $link ='<a href="' . $originalurl . '" class="AlpinePhotoTiles-link AlpinePhotoTiles-lightbox" title=" '. $imagetitle .' " alt=" '. $imagetitle .'" >';
          return $link;
        }
      }elseif( ('instagram' == $ilink) ){
          $linkurl = $photos[$i]['image_link'];
          if( !empty($linkurl) ){
            $link ='<a href="' . $linkurl . '" class="AlpinePhotoTiles-link" target="_blank" title=" '. $imagetitle .' " alt=" '. $imagetitle .'" >';
            return $link;
          }
      }elseif( 'link' == $ilink ){
        $url = $this->settings['link_url'];
        if( !empty($url) ){
          $parsed = parse_url($url);
          if (empty($parsed['scheme'])) {
              $url = 'http://' . ltrim($url, '/');
          }
          $link ='<a href="' . $url . '" target="_blank" class="AlpinePhotoTiles-link" title=" '. $imagetitle .' " alt=" '. $imagetitle .' ">'; 
          return $link;
        }
      }elseif( 'original' == $ilink ){
          $photourl = $photos[$i]['image_source'];      
        if( $ssl ){ $photourl = str_replace("http:", "https:", $photourl, $temp = 1); }
        if( !empty($photourl) ){
          $link ='<a href="' . $photourl . '" class="AlpinePhotoTiles-link" target="_blank" title=" '. $imagetitle .' " alt=" '. $imagetitle .' ">';
          return $link;
        }
      }
      return false;    
  }

  // Set Follow link text
  function add_follow_link($contentuid,$request){
    $instagram_json = $this->fetch_instagram_feed($request);
    $username = $instagram_json['data'][0]['user']['username'];

    if($this->settings['show_link'] && $this->settings['link_text']) {
       $userlink= 'http://instagram.com/'.$username;
      }
    if($userlink){
      if($this->settings['align'] == 'center'){ //  Optional: Set text alignment (left/right) or center
        $followlink = '<div id="'.$contentuid.'-display-link" class="AlpinePhotoTiles-display-link-container"';
        $followlink .= 'style="width:100%;margin:0px auto;"><a href="'.$userlink.'" target="_blank">'.$this->settings['link_text'].'</a></div>';
      }
      else{
        $followlink = '<div id="'.$contentuid.'-display-link" class="AlpinePhotoTiles-display-link-container"';
        $followlink .= 'style="float:'.$this->settings['align'].';max-width:'.$this->settings['max_width'].'%;"><center><a href="'.$userlink.'" target="_blank">'.$this->settings['link_text'].'</a></center></div>'; 
        $followlink .='<div class="AlpinePhotoTiles_breakline"></div>'; // Only breakline if floating

      }
      return $followlink;
    }
    else{
      return false;
    }
  }

  // Add lightbox to image
  function add_lightbox($contentuid){
    $lightbox = $this->settings['lightbox'];
    $lightScript = $this->add_script($lightbox);
    $lightStyle = $this->add_style($lightbox);
    
    $check = ($lightbox=='fancybox'?'fancybox':($lightbox=='prettyphoto'?'prettyPhoto':($lightbox=='colorbox'?'colorbox':'fancyboxForAlpine')));
    
    $lightCall = $this->get_lightbox_call($contentuid);
    $lightboxSetup = "
        if( !jQuery().".$check." )
        {
          var css = '".$lightStyle."';
          var link = jQuery(document.createElement('link')).attr({'rel':'stylesheet','href':css,'type':'text/css','media':'screen'});
          
          jQuery.getScript('".($lightScript)."',function(){
            if(document.createStyleSheet){
              document.createStyleSheet(css);
            }
            else{
              jQuery('head').append(link);
            }
            ".$lightCall."
          });
        }
        else{
          ".$lightCall."
        }
      ";
    $script = "<script type='text/javascript'>
    (function(){
      var alpinePluginLoadingFunction = function(method){
        if( window.jQuery ){
          if( jQuery.isFunction( jQuery(window).on ) ){
            jQuery(window).on('load', method);
          }
          else{
            jQuery(window).bind('load', method);
          }
        }else if( window.addEventListener ){
          window.addEventListener('load', method, false);
        }else if ( window.attachEvent ){
          window.attachEvent('on' + 'load', method);
        }
      }
      alpinePluginLoadingFunction( function(){
        if( window.jQuery ){
          ".$lightboxSetup."
        }else{
            console.log('Alpine plugin failed because jQuery never loaded');
          }
      });
    })();</script>";

    return $script;
  }

  // Add Javascript file
  function add_script($string){
    $siteurl = $this->uriBuilder->getRequest()->getRequestUri();
    if($string == 'fancybox'){
    $file = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ns_instagram') . 'Resources/Public/Phototile/lightbox/fancybox/jquery.fancybox.js';
    }elseif($string == 'prettyphoto'){
    $file = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ns_instagram') . 'Resources/Public/Phototile/lightbox/prettyPhoto/js/jquery.prettyPhoto.js';
    }elseif($string == 'colorbox'){
    $file = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ns_instagram') . 'Resources/Public/Phototile/lightbox/colorbox/jquery.colorbox.js';
    }else{
      return false;
    }
    
    $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
    $pageRenderer->addJsFooterFile($file,$type = 'text/javascript');
    return $siteurl.$file;
  }

  // Add Css style file
  function add_style($string){
    $siteurl = $this->uriBuilder->getRequest()->getRequestUri();
    if( 'fancybox' == $string ){
    $file = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ns_instagram') . 'Resources/Public/Phototile/lightbox/fancybox/jquery.fancybox.css';
    }elseif( 'prettyphoto' == $string ){
    $file = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ns_instagram') . 'Resources/Public/Phototile/lightbox/prettyPhoto/css/prettyPhoto.css';
    }elseif( 'colorbox' == $string ){
    $file = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ns_instagram') . 'Resources/Public/Phototile/lightbox/colorbox/colorbox.css';
    }else{
      return false;
    }
    
    $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
    $pageRenderer->addCssFile($file,$rel = 'stylesheet',$media = 'all',$compress = true,$forceOnTop = false);
    return $siteurl.$file;
  }

  // Style for border
  function get_borders_call( $parentID ){
    $highlight = $this->settings['highlight_color'];
    $highlight = (!empty($highlight)?$highlight:'#64a2d8');
    $return ="
    (function() {
      var alpinePluginLoadingFunction = function(method){
        if( window.jQuery ){
          if(jQuery.isFunction( jQuery(window).on ) )
          {
            jQuery(window).on('load', method);
          }
          else
          {
            jQuery(window).bind('load', method);
          }
        }
        else if( window.addEventListener ){
          window.addEventListener('load', method, false);
        }
        else if ( window.attachEvent ){
          window.attachEvent('on' + 'load', method);
        }
      }
      alpinePluginLoadingFunction(function(){
        if( window.jQuery ){
          if( jQuery().AlpineAdjustBordersPlugin ){
            jQuery('#".$parentID."').AlpineAdjustBordersPlugin({highlight:'".$highlight."'});
          }else{
            var css = '".$siteurl."typo3conf/ext/ns_instagram/Resources/Public/Phototile/Css/AlpinePhotoTiles_style.css';
            var link = jQuery(document.createElement('link')).attr({'rel':'stylesheet','href':css,'type':'text/css','media':'screen'});
            jQuery.getScript('".$siteurl."typo3conf/ext/ns_instagram/Resources/Public/Phototile/Js/AlpinePhotoTiles_script.js', function(){
              if(document.createStyleSheet){
                document.createStyleSheet(css);
              }else{
                jQuery('head').append(link);
              }
              if( jQuery().AlpineAdjustBordersPlugin ){
                jQuery('#".$parentID."').AlpineAdjustBordersPlugin({
                  highlight:'".$highlight."'
                });
              }
            });
          }
        }else{console.log('Alpine plugin failed because jQuery never loaded');}
      });
    })();";
    return $return;
  }

  // Get lightbox Call
  function get_lightbox_call($contentuid){
      $rel = $this->set_lightbox_rel($contentuid);
      $lightbox = $this->settings['lightbox'];
      $lightbox_style = str_replace( array("{","}"), "", $lightbox_style);
      
      $setRel = "jQuery( '#".$contentuid."-AlpinePhotoTiles_container a.AlpinePhotoTiles-lightbox' ).attr( 'rel', '".$rel."' );";
      
      if( 'fancybox' == $lightbox ){

        $default = "
          fitToview: false, 
          openEffect: 'none', 
          closeEffect: 'none', 
          autoSize: false, 
          closeClick: false, 
          titleShow: true, 
          titlePosition: 'inside', 
          ";
        $lightbox_style = (!empty($lightbox_style)? $default.','.$lightbox_style : $default );

        return $setRel."if(jQuery().fancybox){jQuery( 'a[rel^=\'".$rel."\']' ).fancybox( { ".$lightbox_style." } );}";

      }elseif( 'prettyphoto' == $lightbox ){

        $default = "theme:'pp_default',social_tools:false, show_title:true";
        $lightbox_style = (!empty($lightbox_style)? $default.','.$lightbox_style : $default );

        return $setRel."if(jQuery().prettyPhoto){jQuery( 'a[rel^=\'".$rel."\']' ).prettyPhoto({ ".$lightbox_style." });}";  

      }elseif( 'colorbox' == $lightbox ){
        $default = "
        maxHeight:'85%'";
        $lightbox_style = (!empty($lightbox_style)? $default.','.$lightbox_style : $default );
        
        return $setRel."if(jQuery().colorbox){jQuery( 'a[rel^=\'".$rel."\']' ).colorbox( {".$lightbox_style."} );}";

      }elseif( 'alpine-fancybox' == $lightbox ){
        $default = "titleShow: false, overlayOpacity: .8, overlayColor: '#000', titleShow: true, titlePosition: 'inside'";
        $lightbox_style = (!empty($lightbox_style)? $default.','.$lightbox_style : $default );
        
        return $setRel."if(jQuery().fancyboxForAlpine){jQuery( 'a[rel^=\'".$rel."\']' ).fancyboxForAlpine( { ".$lightbox_style." } );}";  
      }

      return "";
  }

  // Set loader for load images
  function get_loading_call($contentuid,$lightbox,$hasLight,$lightScript,$lightStyle){
    $siteurl = $this->uriBuilder->getRequest()->getRequestUri();
      $return ="
      jQuery('#".$contentuid."-AlpinePhotoTiles_container').removeClass('loading');
        var alpineLoadPlugin = function(){
          ".$this->get_plugin_call($contentuid,$hasLight)."
        }
        if( jQuery().AlpinePhotoTilesPlugin ){
          alpineLoadPlugin();
        }else{ 
          var css = '".$siteurl."typo3conf/ext/ns_instagram/Resources/Public/Phototile/Css/AlpinePhotoTiles_style.css';
          var link = jQuery(document.createElement('link')).attr({'rel':'stylesheet','href':css,'type':'text/css','media':'screen'});
          
          jQuery.getScript('".$siteurl."typo3conf/ext/ns_instagram/Resources/Public/Phototile/Js/AlpinePhotoTiles_script.js', function(){
              if(document.createStyleSheet){
                document.createStyleSheet(css);
              }else{
                jQuery('head').append(link);
              }";
              if( $hasLight ){
              $check = ($lightbox=='fancybox'?'fancybox':($lightbox=='prettyphoto'?'prettyPhoto':($lightbox=='colorbox'?'colorbox':'fancyboxForAlpine')));    
              $return .="if( !jQuery().".$check." ){
                  jQuery.getScript('".$lightScript."', function(){
                    css = '".$lightStyle."';
                    link = jQuery(document.createElement('link')).attr({'rel':'stylesheet','href':css,'type':'text/css','media':'screen'});
                    if(document.createStyleSheet){
                      document.createStyleSheet(css);
                    }else{
                      jQuery('head').append(link);
                    }
                    alpineLoadPlugin();
                  }); 
                }else{alpineLoadPlugin();}";
              }else{
                $return .= "alpineLoadPlugin();";
              }
                $return .= "});
            }";
      return $return;
  }

  // Get plugin call setting
  function get_plugin_call($contentuid,$hasLight){
      $highlight = isset($this->settings['highlight_color'])?$this->settings['highlight_color']:'#64a2d8';      
      $return = "jQuery('#".$contentuid."-hidden-parent').AlpinePhotoTilesPlugin({
            id:'".$contentuid."',
            style:'".(isset($this->settings['style_option'])?$this->settings['style_option']:'windows')."',
            shape:'".(isset($this->settings['style_shape'])?$this->settings['style_shape']:'square')."',
            perRow:".(isset($this->settings['img_per_row'])?$this->settings['img_per_row']:'3').",
            imageBorder:".(!empty($this->settings['border'])?'1':'0').",
            imageShadow:".(!empty($this->settings['shadow'])?'1':'0').",
            imageCurve:".(!empty($this->settings['curve'])?'1':'0').",
            imageHighlight:".(!empty($this->settings['highlight'])?'1':'0').",
            lightbox:".((isset($this->settings['image_link']) && $this->settings['image_link'] == 'lightbox')?'1':'0').",
            galleryHeight:".(isset($this->settings['gallery_height'])?$this->settings['gallery_height']:'0').",//Keep for Compatibility
            galRatioWidth:".(isset($this->settings['ratio_width'])?$this->settings['ratio_width']:'800').",
            galRatioHeight:".(isset($this->settings['ratio_height'])?$this->settings['ratio_height']:'600').",
            highlight:'".$highlight."',
            pinIt:".(!empty($this->settings['pinterest_pin_it_button'])?'1':'0').",
            siteURL:'".$this->uriBuilder->getRequest()->getRequestUri()."',
            callback:".(!empty($hasLight)?'function(){'.$this->get_lightbox_call($contentuid).'}':"''")."
          });";
      return $return;
  }

  // Set lightbox relation
  function set_lightbox_rel($contentuid){
      $lightbox = $this->settings['lightbox'];  
      
      if( $lightbox == 'fancybox'){
        $rel = 'fancybox-'.$contentuid;
      }elseif( $lightbox ==  'prettyphoto'){
        $rel = 'prettyPhoto['.$contentuid.']';
      }elseif( $lightbox ==   'colorbox'){
        $rel = 'colorbox['.$contentuid.']';
      }else{
        $rel = 'fancybox-safemode-'.$contentuid;
      }
      return $rel;
  }  

  function get_active_option($string){
      if(isset($this->settings[$string])){
        return $this->settings[$string];
      }else{
        return false;
      }
  }

  function set_active_option($string,$val){
    $this->settings[$string] = $val;
  } 
  
}