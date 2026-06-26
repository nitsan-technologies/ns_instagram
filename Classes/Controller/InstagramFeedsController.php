<?php

namespace NITSAN\NsInstagram\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * InstagramFeedsController
 */
class InstagramFeedsController extends ActionController
{
    private const SITE_SETTING_KEYS = [
        'plugin.tx_nsinstagram_instagramfeeds.settings.graphapi',
        'ns_instagram.configuration.api.graphapi',
    ];

    /**
     * action getfeeeds
     */
    public function getfeeedsAction(): ?ResponseInterface
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

        $accessToken = $this->resolveAccessToken();

        if ($accessToken === '') {
            $error = LocalizationUtility::translate('instagram.noapi', 'ns_instagram');
            $this->addFlashMessage($error, '', $severityClass);
        } else {
            $refreshResult = $this->getAPIdataAction($accessToken, 'refresh');
            if (!empty($refreshResult['access_token'])) {
                $accessToken = (string)$refreshResult['access_token'];
            }

            $itemLimit = (int)($this->settings['graphitems'] ?? 6);
            if ($itemLimit < 1) {
                $itemLimit = 6;
            }

            $instamedia = $this->getAPIdataAction($accessToken, 'media', $itemLimit);

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

        return null;
    }

    /**
     * action getAPIdata
     */
    public function getAPIdataAction($accessToken, $additionalconfig = null, $items = null): ?array
    {
        $url = '';
        switch ($additionalconfig) {
            case 'refresh':
                $url = 'https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token=' . rawurlencode((string)$accessToken);
                break;

            case 'media':
                $fields = 'media_url,thumbnail_url,caption,id,media_type,timestamp,username,permalink,children{media_url,id,media_type,timestamp,thumbnail_url}';
                $url = 'https://graph.instagram.com/me/media?fields=' . $fields
                    . '&access_token=' . rawurlencode((string)$accessToken)
                    . '&limit=' . (int)$items;
                break;
        }

        if ($url === '') {
            return null;
        }

        try {
            $apiRequest = GeneralUtility::makeInstance(RequestFactory::class);
            $apiResponse = $apiRequest->request(
                $url,
                'GET',
                [
                    'User-Agent' => 'TYPO3 Extension ns_instagram',
                ]
            );
            $apiResults = $apiResponse->getBody()->getContents();
            if ($apiResponse->getStatusCode() === 200 && $apiResults !== '') {
                $decoded = json_decode($apiResults, true);
                return is_array($decoded) ? $decoded : null;
            }
        } catch (\Throwable $exception) {
            if (method_exists($exception, 'getResponse')) {
                $response = $exception->getResponse();
                if ($response !== null) {
                    $decoded = json_decode((string)$response->getBody(), true);
                    return is_array($decoded) ? $decoded : null;
                }
            }
        }

        return null;
    }

    private function resolveAccessToken(): string
    {
        $accessToken = trim((string)($this->settings['graphapi'] ?? ''));
        if ($accessToken !== '') {
            return $accessToken;
        }

        $site = $this->request?->getAttribute('site');
        if (!$site instanceof Site) {
            return '';
        }

        $siteSettings = $site->getSettings();
        foreach (self::SITE_SETTING_KEYS as $settingKey) {
            $candidate = trim((string)($siteSettings->get($settingKey) ?? ''));
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }
}
