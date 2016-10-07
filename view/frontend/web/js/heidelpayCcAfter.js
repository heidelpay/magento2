define([
        "jquery",
        "jquery/ui"
 
 ], function($) {
	"use strict";

    $.widget('heidelpay.js', {
        _create: function() {

        	// set targetOrigin
        	var targetOrigin = getDomainFromUrl($('#payment_iframe').attr('src'));
        	
        	
        	// Setup an event listener that calls receiveMessage() when the window
        	if (window.addEventListener){  // W3C DOM
        		window.addEventListener('message', receiveMessage);
        	}else if (window.attachEvent) { // IE DOM
        		window.attachEvent('onmessage', receiveMessage);
        	}

        	// extract protocol, domain and port from url
        	function getDomainFromUrl(url) {
        		var arr = url.split("/");
        		return arr[0] + "//" + arr[2];
        	}
        	
        	// ### Receiving postMessages ###
        	function receiveMessage(e) {
        		
        		// Check to make sure that this message came from the correct domain.
        		if (e.origin !== targetOrigin){
        			return;
        		}
        		var recMsg = JSON.parse(e.data);
        		if (recMsg["POST.VALIDATION"] == "NOK") {
        			$('#pay-now').prop('disabled', false);
        		}
        	
        	}

            this.element.on('click', function(e){
            	
            	$('#pay-now').prop('disabled', true);
            	
            	var data = {};
            	document.getElementById("payment_iframe").contentWindow.postMessage(JSON.stringify(data), targetOrigin);
            	
            	
            });
        }

    });

    return $.heidelpay.js;
	}

	

);