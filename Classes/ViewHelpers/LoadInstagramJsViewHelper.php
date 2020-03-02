<?php
namespace NITSAN\NsInstagram\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class LoadInstagramJsViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize
     *
     * @return void
     */
    public function initializeArguments()
    {
        //$this->registerArgument('sliderType', 'string', 'Determine type of slider', true);
        parent::initializeArguments();
        $this->registerArgument('settings', 'string', 'Get all settings', true);
        $this->registerArgument('contentId', 'string', 'Get content Id', true);
    }

    public function render()
    {
        $arguments = $this->arguments;
        // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($arguments);exit;
        // Create pageRender instance.
        $pageRender = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);

        if ($arguments['settings']['viewusing']=='username') {
            $type =  "'username': '" . $arguments['settings']['username'] . "'";
            $arguments['settings']['items'] = 12;
        } else {
            $type = "'tag': '" . $arguments['settings']['hashtag'] . "'";
        }

        if ($arguments['settings']['feedlisttype']=='igtv') {
            $feedlisttype =  "'display_gallery': false,
                                          'display_igtv': true";
        } else {
            $feedlisttype = "'display_gallery': true,
                                          'display_igtv': false";
        }

        $codeBlock = ' 
                    (function() {
                        new InstagramFeed({
                            ' . $type . ",
                            'container': document.getElementById('nsinstagram-feed-" . $arguments['contentId'] . "'),
                            'display_profile': " . $arguments['settings']['display_profile'] . ",
                            'display_biography': " . $arguments['settings']['display_biography'] . ',
                            ' . $feedlisttype . ",
                            'callback': null,
                            'styling': true,
                            'items': " . $arguments['settings']['items'] . ",
                            'items_per_row': " . $arguments['settings']['items_per_row'] . ",
                            'margin': " . $arguments['settings']['margin'] . '
                        });
                    })();
                    ';
        $pageRender->addJsFooterInlineCode('instagram-config' . $arguments['contentId'], $codeBlock, true);
    }
}
