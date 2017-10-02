

if( window.jQuery ){
  jQuery(document).ready(function() {
    jQuery('#".$contentuid."-AlpinePhotoTiles_container').addClass('loading');
  });
}

// Use self invoking function expression
(function() {// Wait for window to load. Them start plugin.
  var alpinePluginLoadingFunction = function(method){
    // Check for jQuery and on() ( jQuery 1.7+ )
    if( window.jQuery ){
      if(jQuery.isFunction( jQuery(window).on ) ){jQuery(window).on('load', method);
      // Check for jQuery and bind()
      }else{jQuery(window).bind('load', method);}
    // Check for addEventListener
    }else if( window.addEventListener ){window.addEventListener('load', method, false);
    // Check for attachEvent
    }else if ( window.attachEvent ){window.attachEvent('on' + 'load', method);}
}
  alpinePluginLoadingFunction(function(){
    if( window.jQuery ){
      if( jQuery().AlpineAdjustBordersPlugin ){
        jQuery('#".$parentID."').AlpineAdjustBordersPlugin({highlight:'".$highlight."'});
      }else{
        var css = '".$siteurl."typo3conf/ext/ns_instagram_gallery/Resources/Public/Phototile/Css/AlpinePhotoTiles_style.css';
        var link = jQuery(document.createElement('link')).attr({'rel':'stylesheet','href':css,'type':'text/css','media':'screen'});
        jQuery.getScript('".$siteurl."typo3conf/ext/ns_instagram_gallery/Resources/Public/Phototile/Js/AlpinePhotoTiles_script.js', function(){
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
        }); // Close getScript
      }
    }else{console.log('Alpine plugin failed because jQuery never loaded');}
  });
})();";