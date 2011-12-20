<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>thinglink OpenTok Demo</title>
	<link rel="stylesheet" type="text/css" href="//www.thinglink.com/css/themes/common.css" />
	<script type="text/javascript" src="http://api.thinglink.com/jse/thinglink.js"></script>
	<script src="http://staging.tokbox.com/v0.91/js/TB.min.js" ></script>
	<script>
		var recorderManager;
		var recorder;
		var player;
		var recImgData;
		var API_KEY = '1127';  
		var SESSION_ID = '[[OpenTok Session ID]]'; // Replace with your session ID.
		var TOKEN = 'moderator_token';
		var VIDEO_HEIGHT = 240;
		var VIDEO_WIDTH = 320;
		var session;
		var publisher;
		var subscribers = [];

		// Un-comment either of the following to set automatic logging and exception handling.
		// See the exceptionHandler() method below.
		// TB.setLogLevel(TB.DEBUG);
		// TB.addEventListener('exception', exceptionHandler);

		function init() {
			recorderManager = TB.initRecorderManager(API_KEY);
			createRecorder();
			show('recorderContainer');
		}

		function createRecorder() {
			var recDiv = document.createElement('div');
			recDiv.setAttribute('id', 'recorderElement');
			document.getElementById('recorderContainer').appendChild(recDiv);
			recorder = recorderManager.displayRecorder(TOKEN, recDiv.id);
			recorder.addEventListener('recordingStarted', recStartedHandler);
			recorder.addEventListener('archiveSaved', archiveSavedHandler);
		}

		function getImg(imgData) {
			var img = document.createElement('img');
			img.setAttribute('src', 'data:image/png;base64,' + imgData);
			return img;
		}

		function loadArchive(archiveId) {
			var recorderView = document.getElementById('recorderView');
			if (recorderView.style.display == 'block') {
				loadArchiveInPlayer(archiveId);
			} else {
				loadArchiveInSession(archiveId);
			}
		}

		function loadArchiveInPlayer(archiveId) {
			if (!player) {
				playerDiv = document.createElement('div');
				playerDiv.setAttribute('id', 'playerElement');
				document.getElementById('playerContainer').appendChild(playerDiv);
				player = recorderManager.displayPlayer(archiveId, TOKEN, playerDiv.id);
				show('playerContainer');
			} else {
				player.loadArchive(archiveId);
			}
		}

		function loadArchiveInSession(archiveId) {
			session.loadArchive(archiveId);
		}

		function removeStream(streamId) {
			var subscriber = subscribers[streamId];
			if (subscriber) {
				var container = document.getElementById(subscriber.id).parentNode;
				session.unsubscribe(subscriber);
				delete subscribers[streamId];

				// Clean up the subscriber's container
				subscribersContainer = document.getElementById('subscribersContainer');
				subscribersContainer.removeChild(container);
			}
		}

		/* Subscribes to a stream and adds it to the page. The type of stream,
		 *  "live" or "recorded", is added as a label below the stream display.
		 */
		function addStream(stream) {
			// Check if this is the stream that I am publishing, and if so do not subscribe.
			if (stream.connection.connectionId == session.connection.connectionId) {
				return;
			}

			// Create the container for the subscriber
			var container = document.createElement('div');
			var containerId = 'container_' + stream.streamId;
			container.setAttribute('id', containerId);
			container.className = 'swfContainer';
			var subscribersContainer = document.getElementById('subscribersContainer');
			subscribersContainer.appendChild(container);

			// Create the div that will be replaced by the subscriber
			var div = document.createElement('div');
			var divId = stream.streamId;
			div.setAttribute('id', divId);
			div.style['float'] = 'top';
			container.appendChild(div);

			// Label the stream as "Recorded" or "Live."
			var label = document.createElement('p');
			label.style.marginTop = '0px';
			switch (stream.type) {
				case 'archived' :
					label.innerHTML = 'Recorded';
					break;
				case 'basic' :
					label.innerHTML = 'Live';
					break;
			}
			container.appendChild(label);

			var subscriberProperties = {height:VIDEO_HEIGHT, width:VIDEO_WIDTH};
			subscribers[stream.streamId] = session.subscribe(stream, divId, subscriberProperties);
		}

		function unpublish() {
			if (publisher) {
				session.unpublish(publisher);
				publisher = null;
				var publisherContainer = document.getElementById('publisherContainer');
				var publisherLabel = document.getElementById('publisherLabel');
				publisherContainer.removeChild(publisherLabel);
			}
		}

		// Called when you switch connect to the OpenTok session.
		function startPublishing() {
			var publisherContainer = document.getElementById('publisherContainer');
			var publisherDiv = document.createElement('div'); 
			publisherDiv.setAttribute('id', 'opentok_publisher');
			publisherContainer.appendChild(publisherDiv);
			var publisherProperties = {height:VIDEO_HEIGHT, width:VIDEO_WIDTH};
			publisher = session.publish(publisherDiv.id, publisherProperties);
			var label = document.createElement('p');
			label.id = 'publisherLabel';
			label.style.marginTop = '0px';
			label.innerHTML = 'You';
			publisherContainer.appendChild(label);
		}

		//--------------------------------------
		//  OPENTOK EVENT HANDLERS
		//--------------------------------------

		function recStartedHandler(event) {
			recImgData = recorder.getImgData();
		}

		function archiveSavedHandler(event) {
			var archivedID = event.archives[0].archiveId;
			xmlhttp = new XMLHttpRequest();
			xmlhttp.open("GET","sendgrid.php?id="+archivedID+"",true);
			xmlhttp.send();
		}

		// This function sends the email through SendGrid, which contains a link to the recorded video
		function sendEmail(event){
			xmlhttp = new XMLHttpRequest();
			var emailID = "<?php echo $_GET['email']; ?>";
			var sendURL = "sendgrid_mailer.php?email=" + emailID;
			http://thinkingserious.com/hollywood/thinglink/video/myTag.php?email=elmer@thinkingserious.com
			xmlhttp.open("GET",sendURL,true);
			xmlhttp.send();
		}

		function archiveLoadedHandler(event) {
			archive = event.archives[0];
			archive.startPlayback();
		}

		function sessionConnectedHandler(event) {
			// Subscribe to all streams currently in the Session
			for (var i = 0; i < event.streams.length; i++) {
				addStream(event.streams[i]);
			}
			startPublishing(); 
		}

		function streamCreatedHandler(event) {
			// Subscribe to the newly created streams
			for (var i = 0; i < event.streams.length; i++) {
				addStream(event.streams[i]);
			}
		}

		function streamDestroyedHandler(event) {
			event.preventDefault();
			for (var i = 0; i < event.streams.length; i++) {
				if (session.getPublisherForStream(event.streams[i]) == publisher) {
					unpublish();
				} else {
					removeStream(event.streams[i].streamId);
				}
			}
		}

		function sessionDisconnectedHandler(event) {
			// This signals that the user was disconnected from the Session. Any subscribers and publishers
			// will automatically be removed. This default behaviour can be prevented using event.preventDefault()
			publisher = null;
			session = null;
		}

		/*
		If you un-comment the call to TB.addEventListener('exception', exceptionHandler) above, OpenTok calls the
		exceptionHandler() method when exception events occur. You can modify this method to further process exception events.
		If you un-comment the call to TB.setLogLevel(), above, OpenTok automatically displays exception event messages.
		*/
		function exceptionHandler(event) {
			alert('Exception: ' + event.code + '::' + event.message);
		}

		//--------------------------------------
		//  HELPER METHODS
		//--------------------------------------

		function toggleView() {
			var toggleViewBtn = document.getElementById('toggleViewBtn');
			if (toggleViewBtn.value == 'Open a session') {
				toggleViewBtn.value =  'Display recorder';
				switchToSessionView();
			} else {
				toggleViewBtn.value = 'Open a session';
				switchToRecorderView();
			}
		}

		function switchToSessionView() {
			hide('recorderView');
			hide('playerContainer');
			show('sessionView'); 
			if (!session) {
				session = TB.initSession(SESSION_ID);
				session.addEventListener('sessionConnected', sessionConnectedHandler);
				session.addEventListener('streamCreated', streamCreatedHandler);
				session.addEventListener('streamDestroyed', streamDestroyedHandler);
				session.addEventListener('archiveLoaded', archiveLoadedHandler);
				session.connect(API_KEY, TOKEN);
			} else {
				startPublishing();
			}
		}

		function switchToRecorderView() {
			show('recorderView'); 
			hide('sessionView');
			if (publisher) {
				unpublish();
			}
		}

		function show(id) {
			document.getElementById(id).style.display = 'block';
		}

		function hide(id) {
			document.getElementById(id).style.display = 'none';
		}
		
		function createCookie(name,value,days) {
		if (days) {
			var date = new Date();
			date.setTime(date.getTime()+(days*24*60*60*1000));
			var expires = "; expires="+date.toGMTString();
		}
		else var expires = "";
		document.cookie = name+"="+value+expires+"; path=/";
	}

	function readCookie(name) {
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for(var i=0;i < ca.length;i++) {
			var c = ca[i];
			while (c.charAt(0)==' ') c = c.substring(1,c.length);
			if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
		}
		return null;
	}

	function eraseCookie(name) {
		createCookie(name,"",-1);
	}

	</script>
</head>

<body onload="init()">
	<div id="recorderView" style="display:block; height:288px">
		<div id="recorderContainer" style="float:left; height:288px; width 329px; margin-right:8px;">
		</div>
	</div>
	Record &amp; save your message, then: <input type="button" id="call" value="Email Message" onclick="sendEmail()"/><br />
</body>
</html>