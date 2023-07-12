<?php
ini_set('error_reporting', E_ALL & ~E_NOTICE); // Show all errors except NOTICE
error_reporting(E_ALL & ~E_NOTICE);
/* This script allows you to receive data from forms to your email */

/* SETTINGS */

// URL of this file using https
$scriptUrl = "https://".$_SERVER["HTTP_HOST"].$_SERVER["SCRIPT_NAME"]; // used for Viber integration only
// Email "subject"
$title = 'New message from my Landing page';
// Email field "From" - name of sender (e.g. your first & last name)
$from_name = "Designmodo leads notifier";
// Email field "From" - email of sender (e.g. "robot@domain.com")
$from_email = "robot@domain.com";
// Email to receive message - PUT YOUR EMAIL HERE (or leave it blank if you won't receive emails)
$to = ""; // e.g. my@email.com"
// Telegram integration: token of created bot
//(leave string empty if you don't want to use Telegram integration or check how to get Token here: https://designmodo.com/startup/documentation/#telegram)
$telegramToken ='';
// Telegram integration: ID of chat with created bot to send message
//(leave string empty if you don't want to use Telegram integration or check how to get Chat ID here: https://designmodo.com/startup/documentation/#telegram)
$telegramChatId = '';
// Viber integration: token of created bot
//(leave string empty if you don't want to use Viber integration or check how to get Token here: https://designmodo.com/startup/documentation/#viber)
$viberToken = '';
// MailChimp integration: Your API key 
//(leave string empty if you don't want to use MailChimp integration or check how to get your API key here: https://designmodo.com/startup/documentation/#mailchimp)
$MailChimpAPIkey = '';
// MailChimp integration: an ID of a list where contact will be added to
//(leave string empty if you don't want to use MailChimp integration or check how to get ID of list here: https://designmodo.com/startup/documentation/#mailchimp)
$MailChimpListID = '';
// SendInBlue integration: Your API key 
//(leave string empty if you don't want to use SendInBlue integration or check how to get your API key here: https://designmodo.com/startup/documentation/#sendinblue)
$sendInBlueAPIkey = '';
// SendInBlue integration: an ID of a list where contact will be added to
//(leave string empty if you don't want to use SendInBlue integration or check how to get ID of list here: https://designmodo.com/startup/documentation/#sendinblue)
$sendInBlueListIDs = array(); // IDs of lists to add contact to; separate by comma
// HubSpot integration: Your API key 
//(leave string empty if you don't want to use HubSpot integration or check how to get your API key here: https://designmodo.com/startup/documentation/#hubspot)
$hubSpotAPIkey = '';
// Google ReCaptcha "secret". Please, get it on https://www.google.com/recaptcha/admin/create
//(leave string empty if you don't want to use gReCaptcha in your forms; learn more about gReCaptcha integration: https://designmodo.com/startup/documentation/#grecaptcha)
$gRecaptchaSecret = '';

/* END OF SETTINGS */

$message = '';
$result = array("success"=>false,"error"=>false);

if (!empty($_POST)){
	
	/* CHECK RECAPCHA RESPONSE */
	// TO DO: write in documentation about gRecaptcha setup (we started to use V3 grecaptcha and data-sitekey attr should be added to the form tag
	if(!empty($gRecaptchaSecret)){
		if(!empty($_POST["g-recaptcha-response"])){
			$params = array("secret"=>$gRecaptchaSecret, "response"=>$_POST["g-recaptcha-response"]);
			$query = http_build_query($params, '', '&');
			$ch = curl_init("https://www.google.com/recaptcha/api/siteverify");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, 'POST');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
			$response = curl_exec($ch);
			if(json_decode($response)->success!==true){
				die('Error: wrong gRecaptcha.');
			}
		}else{
			die('Error: The gRecaptcha token was not received. Please check if this form has a correct data-sitekey attribute.');
		}
	}
	/* END OF CHECK RECAPCHA */
	
	/* COLLECT DATA FROM FORM FIELDS */
	foreach($_POST as $fieldKey=>$fieldValue){
		if(!empty($fieldValue) && $fieldKey!="g-recaptcha-response"){
			$message .= "<b>".str_replace("_"," ",ucfirst($fieldKey)).":</b> ".str_replace("\n", "<br />", $fieldValue).'<br />';
			switch(strtolower($fieldKey)){
				case "name":
					if(empty($MailChimpContact["FNAME"]) && empty($_POST["firstname"])) $MailChimpContact["FNAME"] = $fieldValue;
					if(empty($sendInBlueContact["attributes"]["FIRSTNAME"]) && empty($_POST["firstname"])) $sendInBlueContact["attributes"]["FIRSTNAME"] = $fieldValue;
					if(empty($hubSpotContact["firstname"]) && empty($_POST["firstname"])) $hubSpotContact["firstname"] = $fieldValue;
					continue 2;
				case "firstname":
					if(empty($MailChimpContact["FNAME"])) $MailChimpContact["FNAME"] = $fieldValue;
					if(empty($sendInBlueContact["attributes"]["FIRSTNAME"])) $sendInBlueContact["attributes"]["FIRSTNAME"] = $fieldValue;
					if(empty($hubSpotContact["firstname"]) && empty($_POST["firstname"])) $hubSpotContact["firstname"] = $fieldValue;
					continue 2;
				case "lastname":
					$MailChimpContact["LNAME"] = $fieldValue;
					$sendInBlueContact["attributes"]["LASTNAME"] = $fieldValue;
					$hubSpotContact["lastname"] = $fieldValue;
					continue 2;
				case "phone":
					$MailChimpContact["PHONE"] = $fieldValue;
					$sendInBlueContact["attributes"]["sms"] = str_replace(' ','',str_replace('-','',str_replace('(','',str_replace(')','',$fieldValue))));
					$hubSpotContact["phone"] = $fieldValue;
					continue 2;
				case "email":
					$sendInBlueContact["email"] = $fieldValue;
					$hubSpotContact["email"] = $fieldValue;
					continue 2;
				case "username":
					if(empty($MailChimpContact["FNAME"]) && empty($_POST["name"]) && empty($_POST["firstname"])){
						$MailChimpContact["FNAME"] = $fieldValue;
					}
					if(empty($sendInBlueContact["attributes"]["FIRSTNAME"]) && empty($_POST["name"]) && empty($_POST["firstname"])){
						$sendInBlueContact["attributes"]["FIRSTNAME"] = $fieldValue;
					}
					if(empty($hubSpotContact["firstname"]) && empty($_POST["name"]) && empty($_POST["firstname"])){
						$hubSpotContact["firstname"] = $fieldValue;
					}
					continue 2;
				case "username2":
					if(empty($MailChimpContact["LNAME"]) && empty($_POST["lastname"])){
						$MailChimpContact["LNAME"] = $fieldValue;
					}
					if(empty($sendInBlueContact["attributes"]["LASTNAME"]) && empty($_POST["lastname"])){
						$sendInBlueContact["attributes"]["LASTNAME"] = $fieldValue;
					}
					if(empty($hubSpotContact["lastname"]) && empty($_POST["lastname"])){
						$hubSpotContact["lastname"] = $fieldValue;
					}
					continue 2;
				case "company_size":
					$hubSpotContact["company_size"] = $fieldValue;
					continue 2;
				case "zip":
					$MailChimpContact["ADDRESS"]['zip'] = $fieldValue;
					$sendInBlueContact["attributes"]["ZIP_CODE"] = $fieldValue;
					$hubSpotContact["zip"] = $fieldValue;
					continue 2;
				case "country":
					$MailChimpContact["ADDRESS"]['country'] = $fieldValue;
					$hubSpotContact["country"] = $fieldValue;
					continue 2;
				case "city":
					$MailChimpContact["ADDRESS"]['city'] = $fieldValue;
					$sendInBlueContact["attributes"]["CITY"] = $fieldValue;
					$hubSpotContact["city"] = $fieldValue;
					continue 2;
				case "address":
					$MailChimpContact["ADDRESS"]['addr1'] = $fieldValue;
					$sendInBlueContact["attributes"]["ADDRESS"] = (!empty($_POST["country"])) ? $_POST["country"].$fieldValue : $fieldValue;
					$hubSpotContact["address"] = $fieldValue;
					continue 2;
				case "message":
					$hubSpotContact["message"] = $fieldValue;
					continue 2;
				default:
					@$hubSpotContact["hs_content_membership_notes"] .= str_replace("_"," ",ucfirst($fieldKey)).': '.$fieldValue."\n";
			}
		}
	}
	/* END OF COLLECT DATA FROM FORM FIELDS */
	
	/* SEND DATA FROM FORMS TO YOUR EMAIL */
	if(!empty($to)){
		$from = empty($_POST["email"]) ? $from_email : $_POST["email"];
		
		$subject = $title;
		$headers = "Content-Type: text/html; charset=UTF-8\r\n";
		$headers .= "From: \"".$from_name."\" <".$from.">\r\n";
		$headers .= "Reply-To: \"".$from_name."\" <".$from.">\r\n";
		
		$mail = mail($to, $subject, $message, $headers); // send email
		
		if($mail && !$result["error"]){
			$result["success"] = true;
		}else{
			$result["error"] = 'Error: the PHP mail() function returned "false". Usually it happens when your hosting doesn\'t support PHP.';
		}
	}
	
	/* MAILCHIMP INTEGRATION */
	
	if(!empty($MailChimpAPIkey) && !empty($MailChimpListID)){
		
		if(!empty($_POST['email'])){
			$MailChimpSubdomain = explode("-",$MailChimpAPIkey)[1];
			$MailChimpAddRequestUrl = 'https://'.$MailChimpSubdomain.'.api.mailchimp.com/3.0/lists/'.$MailChimpListID.'/members/';
			$MailChimpEditRequestUrl = 'https://'.$MailChimpSubdomain.'.api.mailchimp.com/3.0/lists/'.$MailChimpListID.'/members/'.md5(strtolower($_POST['email']));
			
			// The data to send to the API
			if(!empty($MailChimpContact["ADDRESS"])){
				// Check required fields for address, they should not be empty
				if(empty($MailChimpContact["ADDRESS"]["addr1"])) $MailChimpContact["ADDRESS"]["addr1"]='Address not set';
				if(empty($MailChimpContact["ADDRESS"]["city"])) $MailChimpContact["ADDRESS"]["city"]='City not set';
				if(empty($MailChimpContact["ADDRESS"]["state"])) $MailChimpContact["ADDRESS"]["state"]='State not set';
				if(empty($MailChimpContact["ADDRESS"]["zip"])) $MailChimpContact["ADDRESS"]["zip"]='ZIP not set';
			}
			$SubscriberData = array(
				"email_address" => $_POST['email'], 
				"status" => "subscribed",
			);
			if(!empty($MailChimpContact)){
				$SubscriberData["merge_fields"] = $MailChimpContact;
			}

			// Setup cURL
			$ch = curl_init($MailChimpAddRequestUrl);
			curl_setopt_array($ch, array(
				CURLOPT_POST => TRUE,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_HTTPHEADER => array(
					'Authorization: apikey '.$MailChimpAPIkey,
					'Content-Type: application/json'
				),
				CURLOPT_POSTFIELDS => json_encode($SubscriberData),
			));
			// Send the request
			$MailChimpResult = json_decode(curl_exec($ch));
			curl_close($ch);
			
			// if this member already in your MailChimp list, update his info
			if($MailChimpResult->status==400 && $MailChimpResult->title=="Member Exists"){
				$ch = curl_init($MailChimpEditRequestUrl);
				curl_setopt_array($ch, array(
					CURLOPT_USERPWD => 'user:'.$MailChimpAPIkey,
					CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
					CURLOPT_RETURNTRANSFER => TRUE,
					CURLOPT_CUSTOMREQUEST  => "PUT",
					CURLOPT_SSL_VERIFYPEER  => false,
					CURLOPT_POSTFIELDS => json_encode($SubscriberData),  
				));
				// Send the request
				$MailChimpResult = json_decode(curl_exec($ch));
				curl_close($ch);
			}
			if($MailChimpResult->id && !$result["error"]){
				$result["success"] = true;
			}else{
				$result["error"] = 'Error: Data was not sent to MailChimp. MailChimp error message: "'.$MailChimpResult->title.'. '.$MailChimpResult->detail.'". Suggestion: check if "$MailChimpAPIkey" and "$MailChimpListID" are set correctly, form has &lt;input name="email"&gt; field filled.';
			}
		}else{
			$result["error"] = 'Error: Data was not sent to MailChimp, "email" field is empty.';
		}
		
	}
	
	/* END OF MAILCHIMP INTEGRATION */
	
	/* SENDINBLUE INTEGRATION */
	
	if(!empty($sendInBlueAPIkey) && !empty($sendInBlueListIDs)){
		if(!empty($_POST['email']) || !empty($_POST['phone'])){
			$APIurl = 'https://api.sendinblue.com/v3/contacts';
			$sendInBlueContact["listIds"]=$sendInBlueListIDs;

			// Setup cURL
			$ch = curl_init($APIurl);
			curl_setopt_array($ch, array(
				CURLOPT_POST => TRUE,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_HTTPHEADER => array(
					'api-key: '.$sendInBlueAPIkey,
					'content-type: application/json',
					'accept: application/json',
				),
				CURLOPT_POSTFIELDS => json_encode($sendInBlueContact),
			));
			// Send the request
			$sendInBlueResult = json_decode(curl_exec($ch));
			curl_close($ch);
			
			if($sendInBlueResult->id){
				$sendInBlueResult = "ok";
			}
			
			// if this member already in your SendInBlue contacts, update his info
			if($sendInBlueResult->code=="duplicate_parameter"){
				if(empty($sendInBlueContact["email"])){
					$sendInBlueContact["email"] = str_replace('+','',$sendInBlueContact["attributes"]["sms"])."@mailin-sms.com";
				}
				$ch = curl_init($APIurl.'/'.urlencode($sendInBlueContact["email"]));
				curl_setopt_array($ch, array(
					CURLOPT_CUSTOMREQUEST  => "PUT",
					CURLOPT_RETURNTRANSFER => TRUE,
					CURLOPT_HTTPHEADER => array(
						'api-key: '.$sendInBlueAPIkey,
						'content-type: application/json',
						'accept: application/json',
					),
					CURLOPT_POSTFIELDS => json_encode($sendInBlueContact),
				));
				// Send the request
				$sendInBlueResult = json_decode(curl_exec($ch));
				$sendInBlueResponseCode = curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
				if($sendInBlueResult==NULL && $sendInBlueResponseCode==204){
					$sendInBlueResult = "ok";
				}
				curl_close($ch);
			}
			
			if($sendInBlueResult=="ok" && !$result["error"]){
				$result["success"] = true;
			}else{
				$result["error"] = 'Error: Data was not sent to SendInBlue. MailChimp SendInBlue message: "'.$sendInBlueResult->message.' (error code = '.$sendInBlueResult->code.')". Suggestion: check if your form has &lt;input name="email"&gt; or &lt;input name="phone"&gt; field filled. ';
			}
		}else{
			if(empty($_POST['email'])){
				$result["error"] = 'Error: Data was not sent to SendInBlue. The "email" field is empty.';
			}
			if(empty($_POST['phone'])){
				$result["error"] = 'Error: Data was not sent to SendInBlue. The "phone" field is empty.';
			}
		}
	}
	
	/* END OF SENDINBLUE INTEGRATION */
	
	/* HUBSPOT INTEGRATION */

	if(!empty($hubSpotAPIkey)){
		if(!empty($hubSpotContact)){
			foreach($hubSpotContact as $key=>$value){
				$properties[] = array(
					'property' => $key,
					'value' => $value
				);
			}
			$properties = array('properties' => $properties);
			$properties = json_encode($properties);
			$ch = @curl_init();
			@curl_setopt($ch, CURLOPT_POST, true);
			@curl_setopt($ch, CURLOPT_POSTFIELDS, $properties);
			@curl_setopt($ch, CURLOPT_URL, 'https://api.hubapi.com/contacts/v1/contact?hapikey='.$hubSpotAPIkey);
			@curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			@curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$hubSpotResult = @curl_exec($ch);
			$status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
			@curl_close($ch);
			if($status_code==200){
				$hubSpotResult = "ok";
			}else{
				$hubSpotResult = json_decode($hubSpotResult);
				if($hubSpotResult->error == "CONTACT_EXISTS"){ // Then update existing contact
					$ch = @curl_init();
					@curl_setopt($ch, CURLOPT_POST, true);
					@curl_setopt($ch, CURLOPT_POSTFIELDS, $properties);
					@curl_setopt($ch, CURLOPT_URL, 'https://api.hubapi.com/contacts/v1/contact/vid/'.$hubSpotResult->identityProfile->vid.'/profile?hapikey='.$hubSpotAPIkey);
					@curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
					@curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$hubSpotResult = @curl_exec($ch);
					$status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
					@curl_close($ch);
					if($status_code==204){
						$hubSpotResult = "ok";
					}else{
						$hubSpotResult = json_decode($hubSpotResult);
						$hubSpotResult = 'Error: Message was not sent to HubSpot. Error message: '.$hubSpotResult->message.'.';
					}
				}else{
					$hubSpotResult = 'Error: Message was not sent to HubSpot. Error message: '.$hubSpotResult->message.'.';
				}
			}
		}else{
			$hubSpotResult = 'Error: Message was not sent to HubSpot. Please, add at least one field to your form that could be added to HubSpot.';
		}
		if($hubSpotResult=="ok" && !$result["error"]){
			$result["success"] = true;
		}else{
			$result["error"] = $hubSpotResult;
		}
	}
	
	/* END OF HUBSPOT INTEGRATION */
	
	/* TELEGRAM INTEGRATION */
	
	if(!empty($telegramChatId) && !empty($telegramToken)){
		$telegramMessage = $message;
		$telegramMessage = str_replace("<br>","\n",$telegramMessage);
		$telegramMessage = str_replace("<br/>","\n",$telegramMessage);
		$telegramMessage = str_replace("<br />","\n",$telegramMessage);
		if(!empty($telegramMessage)){
			$curl = curl_init();
			curl_setopt_array($curl, [
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => 'https://api.telegram.org/bot'.$telegramToken.'/sendMessage?chat_id='.$telegramChatId.'&text='.urlencode($telegramMessage).'&parse_mode=HTML',
			]);
			$telegramResult = json_decode(curl_exec($curl));
			curl_close($curl);
			if($telegramResult->ok && !$result["error"]){
				$result["success"] = true;
			}else{
				$result["error"] = 'Error: Data was not sent to Telegram. Telegram error message: "'.$telegramResult->description.'". Suggestion: check if "$telegramToken" and "$telegramChatId" are set correctly.';
			}
		}else{
			$result["error"] = 'Error: Data was not sent to Telegram. The message is empty.';
		}
	}

	/* END OF TELEGRAM INTEGRATION */
	
	/* VIBER INTEGRATION */
	
	if(!empty($viberToken)){
		if(file_exists("viberUserID.txt") && file_exists("viberWebHook.txt")){
			$viberUserID = file_get_contents("viberUserID.txt");
			$viberMessage = $message;
			$viberMessage = str_replace("<br>","\n",$viberMessage);
			$viberMessage = str_replace("<br/>","\n",$viberMessage);
			$viberMessage = str_replace("<br />","\n",$viberMessage);
			$viberMessage = str_replace("<b>","",$viberMessage);
			$viberMessage = str_replace("</b>","",$viberMessage);
			$ch = curl_init("https://chatapi.viber.com/pa/send_message");
			curl_setopt_array($ch, array(
				CURLOPT_POST => TRUE,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_HTTPHEADER => array(
					"X-Viber-Auth-Token: ".$viberToken,
				),
				CURLOPT_POSTFIELDS => json_encode(array(
					"receiver"=>$viberUserID,
					"type"=>"text",
					"sender"=>array(
						"name"=>"Startup Notification Bot"
					),
					"text"=>$viberMessage,
				)),
			));
			// Send the request
			$viberResult = json_decode(curl_exec($ch),true);
			if($viberResult["status"]==0 && $viberResult["status_message"]=="ok"){
				$viberResult = "ok";
			}else{
				$viberResult = 'Error: Message was not sent. Viber error message: '.$viberResult["status_message"].'.';
			}
		}else{
			if(!file_exists("viberWebHook.txt")){ // webhook is not set
				$ch = curl_init("https://chatapi.viber.com/pa/set_webhook");
				curl_setopt_array($ch, array(
					CURLOPT_POST => TRUE,
					CURLOPT_RETURNTRANSFER => TRUE,
					CURLOPT_HTTPHEADER => array(
						"X-Viber-Auth-Token: ".$viberToken,
					),
					CURLOPT_POSTFIELDS => json_encode(array(
						"url"=>$scriptUrl,
						"event_types"=>array("message", "subscribed", "unsubscribed", "conversation_started"),
						"send_name"=> true,
						"send_photo"=> true
					)),
				));
				// Send the request
				$viberResult = json_decode(curl_exec($ch), true);
				if($viberResult["status_message"]=="ok"){
					$f = fopen("viberWebHook.txt","w");
					fclose($f);
					clearstatcache();
					$viberResult = 'Viber webhook successfully set! Now you need to subscribe to your Viber bot and send it a message.';	
				}else{
					$viberResult = 'Error: Viber webhook is not set! Viber error message: '.$viberResult["status_message"].'.';
				}
			}else{
				$viberResult = 'Error: file viberUserID.txt does not exist or empty. You need to subscribe to your Viber bot and send it a message. ';				
			}
		}
		if($viberResult=="ok" && !$result["error"]){
			$result["success"] = true;
		}else{
			$result["error"] = $viberResult;
		}
	}
	
	/* END OF VIBER INTEGRATION */
	
}else{
	$result["error"] = 'Error: $_POST PHP variable is empty (no data received from form). Check if your &lt;form&gt; tag has method="POST" attribute.';
}

/* RETURN THE RESULTS OF SENDING DATA */

if(!$result["success"] && !$result["error"]){
	$result["error"] = "Error: not any integration is set. Please check the form-handler.php file to setup form data handling.";
}
echo json_encode($result);

/* END OF RETURN THE RESULTS OF SENDING DATA */
	
/* Viber webhook */

if(!empty($viberToken)){
	$viberUserFile = "viberUserID.txt";
	if(!file_exists($viberUserFile)){ // create file if not exists
		$f = fopen($viberUserFile,"w");
		fclose($f);
		clearstatcache();		
	}
	$viberUserID = file_get_contents($viberUserFile);
	if(strlen($viberUserID)==0){ // if no stored users to send messages
		$callback = file_get_contents("php://input"); // callback from viber
		$callbackArray = json_decode($callback,true);
		if(!empty($callbackArray["event"])){
			if($callbackArray["event"]=="message" || $callbackArray["event"]=="conversation_started" || $callbackArray["event"]=="subscribed"){
				// save ID of user who have interaction with bot
				if($callbackArray["event"]!="message"){
					$userId = $callbackArray["user"]["id"];
				}else{
					$userId = $callbackArray["sender"]["id"];
				}
				file_put_contents($viberUserFile,$userId);
				$viberUserID = $userId;
			}
		}
	}
}
