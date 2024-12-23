<?php

namespace NITSAN\NsInstagram\Controller;

use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * InstagramFeedsController
 */
class InstagramFeedsController extends ActionController
{
    /**
     * action getfeeeds
     */
    public function getfeeedsAction()
    {
        $typo3VersionArray = VersionNumberUtility::convertVersionStringToArray(
            VersionNumberUtility::getCurrentTypo3Version()
        );

        if ($typo3VersionArray['version_main'] >= 12) {
            // @extensionScannerIgnoreLine
            $severityClass = \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR;
        } else {
            // @extensionScannerIgnoreLine
            $severityClass = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;
        }

        $settings = $this->settings;

        if (empty($settings['graphapi'])) {
            $error = LocalizationUtility::translate('instagram.noapi', 'ns_instagram');
            $this->addFlashMessage($error, '', $severityClass);
        } else {
            $this->getAPIdataAction($settings['graphapi'], 'refresh');
            $instamedia = $this->getAPIdataAction($settings['graphapi'], 'media', $settings['graphitems']);

            if (isset($instamedia['data'])) {
                $this->view->assignMultiple([
                    'instauser' => 'true',
                    'instamedia' => $instamedia['data'],
                ]);    
            } else {
                $error = LocalizationUtility::translate('instagram.apierror', 'ns_instagram');
                // @extensionScannerIgnoreLine
                $this->addFlashMessage($error, '', $severityClass);
            }
        }

        if ($typo3VersionArray['version_main'] >= 11) {
            return $this->htmlResponse();
        }
    }

    /**
     * action getAPIdata
     */
    public function getAPIdataAction($accessToken, $additionalconfig=null, $items=null)
    {
        $url = '';
        switch ($additionalconfig) {
            
            case 'refresh':
                $url = 'https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token=' . $accessToken;
                break;

            case 'media':
                $url = 'https://graph.instagram.com/me/media?fields=media_url,thumbnail_url,caption,id,media_type,timestamp,username,permalink,children{media_url,id,media_type,timestamp,permalink,thumbnail_url}&access_token=' . $accessToken . '&limit=' . $items;
                break;
        }

        try {
            if ($url != '') {
                $apiRequest = GeneralUtility::makeInstance(RequestFactory::class);
                $apiResponse = $apiRequest->request(
                    $url,
                    'GET',
                    [
                        'User-Agent' => 'TYPO3 Extension ns_instagram',
                    ]
                );
                $apiResults = $apiResponse->getBody()->getContents();
                if (($apiResponse->getStatusCode() === 200) || empty($apiResults)) {
                    return json_decode($apiResults, true);
                }
            }
        } catch (\Exception $e) {
            return json_decode($e->getMessage(), true);
        }
    }
}
