
plugin.tx_nsinstagram_instagramfeeds {
    view {
        templateRootPaths.0 = EXT:ns_instagram/Resources/Private/Templates/
        templateRootPaths.1 = {$plugin.tx_nsinstagram_instagramfeeds.view.templateRootPath}
        partialRootPaths.0 = EXT:ns_instagram/Resources/Private/Partials/
        partialRootPaths.1 = {$plugin.tx_nsinstagram_instagramfeeds.view.partialRootPath}
        layoutRootPaths.0 = EXT:ns_instagram/Resources/Private/Layouts/
        layoutRootPaths.1 = {$plugin.tx_nsinstagram_instagramfeeds.view.layoutRootPath}
    }
    settings {
        v1api = {$plugin.tx_nsinstagram_instagramfeeds.settings.v1api}
        graphapi = {$plugin.tx_nsinstagram_instagramfeeds.settings.graphapi}
    }
}

page {
    includeCSS {
		nsinstagramFontAwesomeCss = {$plugin.tx_nsinstagram_instagramfeeds.settings.fontawesome}
		nsinstagramFontAwesomeCss.if.isTrue = {$plugin.tx_nsinstagram_instagramfeeds.settings.fontawesome}
        nsinstagramFancyboxCss = typo3conf/ext/ns_instagram/Resources/Public/Css/jquery.fancybox.min.css
		nsinstagramCustomcss = {$plugin.tx_nsinstagram_instagramfeeds.settings.customcss}
		nsinstagramCustomcss.if.isTrue = {$plugin.tx_nsinstagram_instagramfeeds.settings.customcss}
	}
    includeJS{
        nsinstagramjQuery = {$plugin.tx_nsinstagram_instagramfeeds.settings.jquery}
        nsinstagramjQuery.if.isTrue = {$plugin.tx_nsinstagram_instagramfeeds.settings.jquery}
    }
    includeJSFooter{
        nsinstagramlibrary = typo3conf/ext/ns_instagram/Resources/Public/Js/jquery.instagramFeed.min.js
        nsinstagramFancyboxJs = typo3conf/ext/ns_instagram/Resources/Public/Js/jquery.fancybox.min.js
    }
}
