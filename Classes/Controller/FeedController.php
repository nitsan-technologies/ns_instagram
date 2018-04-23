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

/**
 * FeedController
 */
class FeedController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        // Add Css and Js File
        /** @var $pageRenderer \TYPO3\CMS\Core\Page\PageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $css = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ns_instagram') . 'Resources/Public/Feed/Css/instagram.css';
        $font_awesom = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ns_instagram') . 'Resources/Public/Feed/Css/font-awesome.min.css';    
        $js = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ns_instagram') . 'Resources/Public/Feed/Js/instagram.js';
        $jquery = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ns_instagram') . 'Resources/Public/JS/jquery-2.2.4.min.js';

        $pageRenderer->addCssFile($css,$rel = 'stylesheet',$media = 'all',$compress = true,$forceOnTop = false);
        $pageRenderer->addCssFile($font_awesom,$rel = 'stylesheet',$media = 'all',$compress = true,$forceOnTop = false);
        $pageRenderer->addJsFooterFile($js,$type = 'text/javascript');
        if($this->settings['javascript']['jquery']){
            $pageRenderer->addJsFile($jquery,$type='text/javascript',$compress = true,$forceOnTop = true);
        }
        
        // Get Content Object from tt_content
        $this->contentObj = $this->configurationManager->getContentObject();
        $data = $this->contentObj->data;
        /* Instagram Data Variable*/ 
        $accessToken = $this->settings['accessToken'];
        
        try{
            $user_id =$this->getInstaID($this->settings['username']);
            /* Instagram Data Variable End*/
        
            $showbio = ($this->settings['showbio'] == '1')? $this->settings['showbio']='true' : $this->settings['showbio']='false';
            $showcaption = ($this->settings['showcaption'] == '1')? true: false;
            $showlikes = ($this->settings['showlikes'] == '1')? on : off;
            $showfollowers = ($this->settings['numfollow'] == '1') ? $this->settings['numfollow'] = 'true' : $this->settings['numfollow'] = 'false';
            $carousel = ($this->settings['carousel'] == '1')? 'true' : 'false';
            $carouselarrows = ($this->settings['carouselarrows'] == '1')? 'true' : 'false';
            $carouselpag = ($this->settings['carouselpag'] == '1')? 'true' : 'false';
            $carouselautoplay =($this->settings['carouselautoplay'] == '1')? 'true' : 'false'; 
            $carouseltime = ($this->settings['carousel'] == '1')? $this->settings['carouseltime'] : '5000';        

            if($this->settings['cols'] == 1) 
                $styles = 'max-width: 640px; ';
            if ( !empty($this->settings['width'])) 
                $styles .= 'width:' . $this->settings['width'] . $this->settings['widthunit'] .'; ';
            if ( !empty($this->settings['height']) && $this->settings['height'] != '0' ) 
                $styles .= 'height:' . $this->settings['height'] . $this->settings['heightunit'] .'; ';
            if ( !empty($this->settings['background']) ) 
                $styles .= 'background-color: ' . $this->settings['background'] . '; ';
            if ( !empty($this->settings['imagepadding']) ) 
                $styles .= 'padding-bottom: ' . (2*intval($this->settings['imagepadding'])).$this->settings['imagepaddingunit'] . '; ';
            
            $hovertextcolor = $this->hex2rgb($this->settings['hovertextcolor']);
            $hovercolor = $this->hex2rgb($this->settings['hovercolor']);

            $instagram = array(
                'user_id' => $user_id,
                'styles' => $styles,
                'showcaption' => $showcaption,
                'showlikes' => $showlikes,
                'showbio' => $showbio,
                'showfollowers' => $showfollowers,
                'carousel' => $carousel,
                'carouselarrows' => $carouselarrows,
                'carouseltime' => $carouseltime,
                'carouselpag' => $carouselpag,
                'carouselautoplay' => $carouselautoplay,
                'hovercolor' => $hovercolor,
                'hovertextcolor' => $hovertextcolor,
            );
            $pageRenderer->addHeaderData('
                <script type="text/javascript">
                $(document).ready(function(){
                    insta_init();
                });
                </script>
                <script type="text/javascript">
                    var ns_instagram_js_options = {"ns_instagram_at":"'.trim($accessToken).'"};
                </script>
            ');
            $this->view->assign('instagram',$instagram);
        }
        catch(\Exception $e){
            $version = GeneralUtility::makeInstance(VersionNumberUtility::class);
            $versionNum = $version->getNumericTypo3Version();
            $explode = explode(".", $versionNum);
              
            if($explode[0] == 7 || $explode[0] < 7){
                $statusCode = $e->getCode();
                if($statusCode == '400'){
                    $jsonBody = json_decode($e->getMessage(),1);
                    $this->addFlashMessage('Error ' . $jsonBody['meta']['code'] .' : '.$jsonBody['meta']['error_type'] . ' , '. $jsonBody['meta']['error_message'],'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
                }
                else{
                    if($statusCode == '404'){
                        $this->addFlashMessage('Error : '.$statusCode. ' '.$e->getMessage(),'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
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
                    $this->addFlashMessage('Error ' . $jsonBody['meta']['code'] .' : '.$jsonBody['meta']['error_type'] . ' , '. $jsonBody['meta']['error_message'],'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
                }
                else{
                    if($statusCode == '404'){
                        $this->addFlashMessage('Error : '.$statusCode. ' ' .$response->getReasonPhrase(),'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
                    }
                    else{
                        $this->addFlashMessage($e,'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
                      }
                }
            }
        }
        $this->view->assign('settings' , $this->settings);
        $this->view->assign('data',$data);
    }
    
    // Send request to API and get response
    public function connectAPI($url, $method = 'GET', $params = NULL, $json = TRUE, $headers = NULL){
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

    // Get Instagram userId from username
    public function getInstaID( $username ) {

        $username = strtolower( $username );
        $url = "https://www.instagram.com/".trim($username)."/";
        $response = $this->connectAPI($url,'GET',NULL,FALSE,NULL);
        $header = $response->getHeader();
        $body = $response->getBody();
        $shards = explode( 'window._sharedData = ', $body);
        $json = explode( ';</script>', $shards[1] );
        $data = json_decode( $json[0], TRUE );
        $user_id = $data['entry_data']['ProfilePage'][0]['graphql']['user']['id'];
        return $user_id;
    }

    // Convert color code 
    public function hex2rgb( $colour ) {
        if ( $colour[0] == '#' ) {
                $colour = substr( $colour, 1 );
        }
        if ( strlen( $colour ) == 6 ) {
                list( $r, $g, $b ) = array( $colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5] );
        } elseif ( strlen( $colour ) == 3 ) {
                list( $r, $g, $b ) = array( $colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2] );
        } else {
                return false;
        }
        $r = hexdec( $r );
        $g = hexdec( $g );
        $b = hexdec( $b );
        return array($r, $g, $b );
    }
}