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
        // Never expose the access token to Fluid (custom templates / {_all}).
        $publicSettings = $settings;
        unset($publicSettings['graphapi']);
        $this->view->assign('settings', $publicSettings);

        if (empty($settings['graphapi'])) {
            $error = LocalizationUtility::translate('instagram.noapi', 'ns_instagram');
            $this->addFlashMessage($error, '', $severityClass);
        } else {
            $accessToken = trim((string)$settings['graphapi']);
            $this->fetchInstagramApiData($accessToken, 'refresh');
            $instamedia = $this->fetchInstagramApiData(
                $accessToken,
                'media',
                $this->sanitizeItemLimit($settings['graphitems'] ?? 6)
            );

            if (isset($instamedia['data']) && is_array($instamedia['data'])) {
                $this->view->assignMultiple([
                    'instauser' => 'true',
                    'instamedia' => $this->sanitizeMediaForView($instamedia['data']),
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
     * Fetch data from the Instagram Graph API.
     *
     * Meta documents access_token as a required query parameter for these GET
     * endpoints (media + refresh_access_token). Do not remove it from the URL
     * without verifying Bearer-only auth against a live token.
     *
     * @param string $accessToken
     * @param string|null $additionalconfig
     * @param int|null $items
     * @return array|null
     */
    protected function fetchInstagramApiData($accessToken, $additionalconfig = null, $items = null)
    {
        $url = '';
        switch ($additionalconfig) {
            case 'refresh':
                $url = 'https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token='
                    . rawurlencode($accessToken);
                break;

            case 'media':
                // Field list stays unencoded (same as before); only the token is encoded.
                $url = 'https://graph.instagram.com/me/media?fields=media_url,thumbnail_url,caption,id,media_type,timestamp,username,permalink,children{media_url,id,media_type,timestamp,permalink,thumbnail_url}'
                    . '&access_token=' . rawurlencode($accessToken)
                    . '&limit=' . (int)$items;
                break;
        }

        try {
            if ($url !== '') {
                $apiRequest = GeneralUtility::makeInstance(RequestFactory::class);
                $apiResponse = $apiRequest->request(
                    $url,
                    'GET',
                    [
                        'headers' => [
                            'User-Agent' => 'TYPO3 Extension ns_instagram',
                        ],
                    ]
                );
                $apiResults = $apiResponse->getBody()->getContents();
                if (($apiResponse->getStatusCode() === 200) || empty($apiResults)) {
                    $decoded = json_decode($apiResults, true);
                    return is_array($decoded) ? $decoded : null;
                }
            }
        } catch (\Exception $e) {
            // Do not surface exception messages (may contain request URI + token).
            return null;
        }

        return null;
    }

    /**
     * Clamp FlexForm / TypoScript item count to a safe integer range.
     *
     * @param mixed $items
     */
    protected function sanitizeItemLimit($items): int
    {
        $limit = (int)$items;
        if ($limit < 1) {
            return 6;
        }
        if ($limit > 100) {
            return 100;
        }
        return $limit;
    }

    /**
     * Prepare API media for Fluid: keep plain caption for title/alt, and a
     * pre-escaped captionHtml for Fancybox data-caption (Fancybox uses .html()).
     */
    protected function sanitizeMediaForView(array $mediaItems): array
    {
        foreach ($mediaItems as &$item) {
            if (!is_array($item)) {
                continue;
            }
            $caption = isset($item['caption']) ? (string)$item['caption'] : '';
            $item['caption'] = $caption;
            // Double-encoding via Fluid attribute escaping makes Fancybox .html() safe.
            $item['captionHtml'] = htmlspecialchars($caption, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        unset($item);

        return $mediaItems;
    }
}
