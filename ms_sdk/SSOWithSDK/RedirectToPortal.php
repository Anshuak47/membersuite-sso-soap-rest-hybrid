<html>
<head>

<?php

//Main MemberSuite class
include_once($_SERVER['DOCUMENT_ROOT'].'/wp-content/plugins/membersuite-sso/ms_sdk/src/MemberSuite.php'); // Use the SRC Directory
include_once('./ConciergeApiHelper.php');
include_once('./config.php');

session_start();
//ob_start();

// See https://stackoverflow.com/questions/4503135/php-get-site-url-protocol-http-vs-https
function siteURL() {
	$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
	$domainName = $_SERVER['HTTP_HOST'].'';
	return $protocol.$domainName;
}

//Matt's code for grabbing the return URL info.
if(isset($_COOKIE['returnURL'])) {
	$websiteURLReturn = $_COOKIE['returnURL'];
}

elseif(isset($_COOKIE['websiteURL'])) {
	$websiteURLReturn = $_COOKIE['websiteURL'];
}

else {
	$websiteURLReturn = siteURL();
}
//End Matt's code for grabbing the current URL

?>
<style>
	.loading {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%,-50%);
		width: 70%;
		padding-top: 40px;
		background: url(<?php echo siteURL(); ?>/wp-content/plugins/membersuite-sso/images/loading.gif) center top no-repeat;
	}

	.loading p {
		text-align: center;
		font-family: 'myriad-pro', sans-serif;
	}
</style>
</head>
<body>
	<div class="loading">
		
		<p>Please wait while we verify your credentials</p>
	</div>

<?php
$api = new MemberSuite();
$helper = new ConciergeApiHelper();

$api->accesskeyId = Userconfig::read('AccessKeyId');
$api->associationId = Userconfig::read('AssociationId');
$api->secretaccessId = Userconfig::read('SecretAccessKey');

$response = $api->WhoAmI();

if($response->aSuccess=='false')
{
	header( 'location:' . $websiteURL . '/login?error=mserror' );
	exit();
}


if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$portalusername = $_POST['portalusername'];
	$portalpassword = $_POST['portalpassword'];
	

	$api->portalusername = $portalusername;
	$api->portalPassword = $portalpassword;
	$api->signingcertificateId = Userconfig::read('SigningcertificateId');

	// Get Private XML Content
	$xmlPath = Userconfig::read('SigningcertificatePath');
	if(file_exists($xmlPath))
	{       
	   
	   $value = file_get_contents($xmlPath);
	   $rsaXML = mb_convert_encoding($value , 'UTF-8' , 'UTF-16LE');
			
	}
	else{
		
		$_SESSION['loginerr'] = 'Signing certificate file does not exists.';
		header("location:index.php?error=credentialerror");
		exit(); 
		
	}
	
	if(!isset($_POST['varifycredentials'])){
	 
		// Varify username and password
		$response = $api->LoginToPortal($api->portalusername,$api->portalPassword);

		if($response->aSuccess == 'false'){
			
			//$loginarr = $response->aErrors->bConciergeError->bMessage;
			//$_SESSION['loginerr'] = $loginarr;
			header( 'location:' . $websiteURL . '/login?error=credentialerror' );
			exit();
		}

		//Matt: Searching for the aPortalUser's Owner field,
		// which contains the GUID of the person logging in
		$portalUser = $response->aResultValue->aPortalUser->bFields->bKeyValueOfstringanyType;

		foreach($portalUser as $portalValue) {
			//Checking to define the GUID variable (aka Owner)
			if ( $portalValue->bKey == 'Owner' ) {
				$guid = $portalValue->bValue;
			}

			//Checking to define the SessionID variable
			elseif ( $portalValue->bKey == 'ID' ) {
				$sessionId = $portalValue->bValue;
			}
		}
		//Matt: end of searching for the GUID
	}
	// Use helper class to generate signature

	$api->digitalsignature = $helper->DigitalSignature($api->portalusername,$rsaXML);
	
	
	// Create Token for sso
	$response = $api->CreatePortalSecurityToken($api->portalusername,$api->signingcertificateId,$api->digitalsignature);
	
	 if($response->aSuccess=='false')
	{
		$_SESSION['loginerr'] = $response->aErrors->bConciergeError->bMessage;
	  	header( 'location:' . $websiteURL . '/login?error=tokenerror' );
		exit();
	}
	
	$securityToken = $response->aResultValue;

	//****** Matt's code ******

	//Setting the variable with the GUID info.
	$individualID = $guid;

	//Using the Object Query Model to search for the person's
	//ReceivesMemberBenefits status
	$s = new Search();
	$expr = new Expr();
	$s->Type='Membership'; //Type is mandatory

	$s->AddOutputColumn('Status');
	$s->AddOutputColumn('FirstName');
	$s->AddOutputColumn('ReceivesMemberBenefits');
	$s->AddCriteria($expr->Equals('Individual.Id', $individualID));

	$objectquery = $api->ExecuteSearch($s, 0, 1);

	$objectqueryresponse = $objectquery->aResultValue->aTable->diffgrdiffgram->NewDataSet;

	//Creating the variable with the value information
	$receivesmemberbenefits = $objectqueryresponse->Table->ReceivesMemberBenefits;

//    echo 'receivesmemberbenefits';
//    var_dump($receivesmemberbenefits);
//    die;

	//Setting the condition so that if true that receives member benefits,
	// then return a value of 1; I need this set to an integer because of
	//code already on WordPress looking for the cookie to have an integer of 1
	if($receivesmemberbenefits == true){
		$receivesmemberbenefitsvalue = 1;
	}else{
		$receivesmemberbenefitsvalue = 0;
	}

	//Using MSQL Query Method to search for the person's
	//FirstName, LastName, and other demographic info.
	$msql = "select TOP 1 ID, FirstName, LastName, Title, JobCategory__c, LoginName, Status, from Individual where ID = '$individualID' order by LastName";

	$Startrecord = "0";
	$Maxrecord = "1";

	$msqlResult = $api->ExecuteMSQL($msql, 0, null);

	if($msqlResult !="") {
		$msqlFinalResult = $msqlResult->aResultValue->aSearchResult->aTable->diffgrdiffgram->NewDataSet;

	//Creating the variables with the value information
	$firstname = $msqlFinalResult->Table->FirstName;
	$lastname = $msqlFinalResult->Table->LastName;
	$jobtitle = $msqlFinalResult->Table->Title;
	$jobcategory = $msqlFinalResult->Table->JobCategory__c;
	$msuser = $msqlFinalResult->Table->ID;
	$loginname = $msqlFinalResult->Table->LoginName;
	$status = $msqlFinalResult->Table->Status;


	//Creating the Session variables so I can then grab them on the index page
	$_SESSION['api'] = $api;
	$_SESSION['response'] = $response;
	$_SESSION['firstname'] = $firstname;
	$_SESSION['lastname'] = $lastname;
	$_SESSION['jobtitle'] = $jobtitle;
	$_SESSION['jobcategory'] = $jobcategory;
	$_SESSION['msuser'] = $msuser;
	$_SESSION['loginname'] = $loginname;
	$_SESSION['username'] = $api->portalusername;
	$_SESSION['status'] = $status;
	$_SESSION['receivesmemberbenefits'] = $receivesmemberbenefitsvalue;
	$_SESSION['guid'] = $guid;
	$_SESSION['sessionid'] = $sessionId;
	$_SESSION['portalvalue'] = $portalUser;
	}

	//****** End Matt's code ******
	setcookie( 'firstName', $firstname, time()+3600, '/' , 'learningforward.org' );
	setcookie( 'lastName', $lastname, time()+3600, '/', 'learningforward.org' );
	setcookie( 'MsUser', $msuser, time()+3600, '/' , 'learningforward.org' );
	setcookie( 'isMember', $receivesmemberbenefitsvalue, time()+3600, '/' , 'learningforward.org' );
}

?>


<form name="LoginForm" method="post" id="LoginForm" action="<?php echo Userconfig::read('PortalUrl');?>Login.aspx">
	<input type="hidden" name="Token" id="Token" value="<?php echo $securityToken;?>" />
		
	<!--Once logged into Membersuite, jump to this URL-->
<!--	<input type="hidden" name="NextUrl" id="NextUrl" />-->
	<input type="hidden" name="NextUrl" id="NextUrl" value="<?php echo $websiteURLReturn; ?>" />


	<!--In the MemberSuite Portal header, provide a return link to a custom URL-->
	<input type="hidden" name="ReturnUrl" id="ReturnUrl" value="default.aspx" />
	<input type="hidden" name="ReturnText" id="ReturnText" />
	
	<!--On logout from the MemberSuite Portal, redirect to this URL rather than the default login page-->
	<input type="hidden" name="LogoutUrl" id="LogoutUrl" />

</form>
<script>
	document.LoginForm.submit();
</script>
</body>
</html>