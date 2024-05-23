<?php
namespace NITSAN\NsInstagram\Controller;

use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * InstagramFeedsController
 */
class InstagramFeedsController extends ActionController
{
    /**
     * action getfeeeds
     *
     * @return void
     */
    public function getfeeedsAction()
    {
        $typo3VersionArray = VersionNumberUtility::convertVersionStringToArray(
            VersionNumberUtility::getCurrentTypo3Version()
        );


        if($typo3VersionArray['version_main'] >= 12) {
            // @extensionScannerIgnoreLine
            $severityClass = \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR;
        } else {
            // @extensionScannerIgnoreLine
            $severityClass = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;
        }
        
        $settings = $this->settings;

        if (empty($settings['graphapi'])) {
            $error = LocalizationUtility::translate('instagram.noapi', 'ns_instagram');
            // @extensionScannerIgnoreLine
            $this->addFlashMessage($error, '', $severityClass);
        } else {
            $instauser = $this->getAPIdataAction($settings['graphapi'], 'user');
            if ($instauser['username']) {
                $this->getAPIdataAction($settings['graphapi'], 'refresh');
                $instamedia = $this->getAPIdataAction($settings['graphapi'], 'media', $settings['graphitems']);
                $this->view->assignMultiple([
                    'instauser' => $instauser,
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
     *
     * @return void
     */
    public function getAPIdataAction($accessToken, $additionalconfig=null, $items=null)
    {
        switch ($additionalconfig) {
            case 'user':
                $url = 'https://graph.instagram.com/me?fields=id,username,media_count&access_token=' . $accessToken;
                break;

            case 'refresh':
                $url = 'https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token=' . $accessToken;
                break;

            case 'media':
                $url = 'https://graph.instagram.com/me/media?fields=media_url,thumbnail_url,caption,id,media_type,timestamp,username,permalink,children{media_url,id,media_type,timestamp,permalink,thumbnail_url}&access_token=' . $accessToken . '&limit=' . $items;
                break;
        }
        
        try {
            $apiRequest = GeneralUtility::makeInstance(RequestFactory::class);
            $apiResponse = $apiRequest->request(
                $url,
                'GET',
                [
                    'User-Agent' => 'TYPO3 Extension ns_instagram'
                ]
            );
            $apiResults = $apiResponse->getBody()->getContents();
            if (($apiResponse->getStatusCode() === 200) || empty($apiResults)) {
                return json_decode($apiResults, true);
            }
        } catch (\Exception $e) {
            return json_decode($e->getMessage(), true);
        }
    }
}
