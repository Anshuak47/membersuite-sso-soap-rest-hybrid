<?php

/*******

This is the main file for the Membersuite SSO plugin which contains all the vital code and functionality

********/

/*
 * Activity Log
 */
add_filter( 'aal_init_roles', 'mssso_aal_init_roles' );

function mssso_aal_init_roles( $roles ) {
    $roles_existing          = $roles['manage_options'];
    $roles['manage_options'] = array_merge( $roles_existing, array( 'MemberSuite' ) );

    return $roles;
}

// add_action('','');

add_action( 'parse_request', 'mssso_logout' ,10 );
function mssso_logout() {
    
   
    if ( strpos( $_SERVER['REQUEST_URI'], '/logout' ) !== false ) {
        /* Listing all cookies */
        // $user_id = get_current_user_id();

        wp_clear_auth_cookie();
        wp_logout();
        /* After WP logout, redirect to MS logout URL to log the user out of Membersuite
         * see get_ms_url() in functions-member.php file
         */

        wp_safe_redirect( home_url() );

        exit();
       
    }
        
}

add_action('clear_auth_cookie','clear_legacy_cookies' );
function clear_legacy_cookies(){
    $legacy_cookes = [
        'ms_tokenGUID','firstName', 'lastName', 'MsUser', 'isMember', 'jobTitle',
        'jobCategory', 'hasSSLN', 'hasWMNN', 'hasWMNNMD',
        'hasWMNNOH', 'hasWMNNRI', 'hasWMNNHub',
        'hasLFAcademy2018', 'hasLFAcademy2019', 'hasLFAcademy2020', 'hasLFAcademy2021',
        'hasLFBoardofTrustees', 'hasLFDirectors', 'hasLFSeniorDirectors', 'hasLFAffiliates',
        'hasTXNSI', 'hasTXNSICollaborative', 'hasRPDC', 'LMScourses' , 'LMSacademy','LMSvirtualCoachesAcademy' ,
        'LMSvirtualMentorAcademy', 'LMSbecomingaLearningTeam', 'LMSassessingImpact', 'LMSlearningPrincipal',
        'LMSnetworks', 'LMSstaff', 'LMSinstructor',
    ];

    /* Deleting all cookies on logout */
    foreach ($legacy_cookes as $cookie_name) {
        // this "deletes" the cookie:
        unset( $_COOKIE[$cookie_name]);
        setcookie($cookie_name, '' , time()-86400, '/',get_sso_cookie_domain() ) ;
    }
}

add_action('init','lf_login_the_ms_user',10 ,1 );
function lf_login_the_ms_user( $wp ){


    if( isset( $_COOKIE['ms_tokenGUID']  ) && !is_user_logged_in() ){
        $tokenGUID_cookie = $_COOKIE['ms_tokenGUID'];
        $tenant_id = Userconfig::read( 'tenantID' );

        // Get the tokenGuid from Cookie and hit GET to get AuthToken
        $authToken = '';

        // Call the Auth Token API
        $authToken = lf_get_ms_auth_token( $tokenGUID_cookie, $tenant_id );
       
        if( isset( $authToken ) && $authToken !=false ){
            $auth_token = $authToken;

        } 

        // Call the Whoami API
        $user_ms_data = lf_get_ms_whoami( $auth_token );
        
        
        // If user data exists check if user receives member benefits
        $other_user_data = '';
        // $is_member = '';
        if( !empty( $user_ms_data ) ){

            if( $user_ms_data['receivesMemberBenefits'] == true && $user_ms_data['receivesMemberBenefits'] != null ){
                $receivesmemberbenefitsvalue = 1;
            }else{
                $receivesmemberbenefitsvalue = 0;

            }

            $other_user_data = array(
                'membership_id' => $user_ms_data['membershipId'],
                'owner_id'      => $user_ms_data['ownerId'],
                'email'         => $user_ms_data['email'],
                'user_id'       => $user_ms_data['userId']

            );

            $individualID = $other_user_data['owner_id'];
            // Get membership data by msql query
            //Using MSQL Query Method to search for the person's

             //FirstName, LastName, and other demographic info.

            $msql = "select TOP 1 ID, localID, FirstName, LastName, Title, JobCategory__c, LoginName, Status, EmailAddress, ".

                        "HasSSLN__c, HasWMNN__c, HasWMNNMD__c, HasWMNNOH__c, HasWMNNRI__c, HasWMNNHub__c, HasLFAcademy2018__c, ".

                        "HasLFAcademy2019__c, HasLFAcademy2020__c, HasLFAcademy2021__c, HasLFBoardofTrustees__c, HasLFDirectors__c, ".

                        "HasLFSeniorDirectors__c, HasLFAffiliates__c, HasTXNSI__c, HasRPDC__c, HasTXNSICollaborative__c, _Preferred_Address_Line1, ".

                        "_Preferred_Address_Line2, _Preferred_Address_City, _Preferred_Address_State, _Preferred_Address_PostalCode, ".

                        "_Preferred_Address_Country, SchoolDistrict__c, _Preferred_PhoneNumber, LMS_Courses__c, LMS_Academy__c, LMS_AssessingImpact__c, ".

                     "LMS_BecomingaLearningTeam__c, LMS_Instructor__c, LMS_LearningPrincipal__c, LMS_Networks__c, LMS_Staff__c, LMS_VirtualCoachesAcademy__c, LMS_VirtualMentorAcademy__c, ".

                    "from Individual ".

                    "where ID = '$individualID' ".

                    "order by LastName";

            //Get details by rest endpoint
            $details_url = 'https://rest.membersuite.com/crm/v1/individuals/'.$tenant_id.'?msql='.$msql.'&page=1&pageSize=20';

            $headers = array(
                'Authorization' => 'AuthToken ' . $auth_token,
                'Content-Type' => 'application/json'
            );
            
            $args = array(
                'headers' => $headers,
                
            );

            $response_memberdetails = wp_remote_get( $details_url,$args );
            $body = wp_remote_retrieve_body( $response_memberdetails );
            $user_ms_details = json_decode( $body, 1 );

            if (!empty($user_ms_details) && isset( $user_ms_details )) {

               $user_data = [

                    'membersuite_id' => $user_ms_details[0]['localID'],

                    'firstname' => $user_ms_details[0]['firstName'],

                    'lastname' => $user_ms_details[0][ 'lastName'],

                    'jobtitle' => $user_ms_details[0][ 'title'],

                    'jobcategory' => $user_ms_details[0][ 'jobCategory__c'],

                    'msuser' => $user_ms_details[0][ 'id'] ,               

                    'loginname' => $user_ms_details[0][ 'loginName'],

                    'status' => $user_ms_details[0][ 'status'],

                    'emailaddress' => $user_ms_details[0][ 'emailAddress'],

                    'address1' => $user_ms_details[0][ '_Preferred_Address_Line1'],

                    'address2' => $user_ms_details[0][ '_Preferred_Address_Line2'],

                    'city' => $user_ms_details[0][ '_Preferred_Address_City'],

                    'state' => $user_ms_details[0][ '_Preferred_Address_State'],

                    'zip' => $user_ms_details[0][ '_Preferred_Address_PostalCode'],

                    'country' => $user_ms_details[0][ '_Preferred_Address_Country'],

                    'schooldistrict' => $user_ms_details[0][ 'schoolDistrict__c'],

                    'phone' => $user_ms_details[0][ '_Preferred_PhoneNumber'],



                    'isMember' => (bool)$receivesmemberbenefitsvalue,



                    /*Communities variables */

                    'communities' => [

                        'hasSSLN' => lf_make_boolean( $user_ms_details[0] ['hasSSLN__c'],true),

                        'hasWMNN' => lf_make_boolean( $user_ms_details[0][ 'hasWMNN__c'],true),

                        'hasWMNNMD' => lf_make_boolean( $user_ms_details[0][ 'hasWMNNMD__c'],true),

                        'hasWMNNOH' => lf_make_boolean( $user_ms_details[0][ 'hasWMNNOH__c'],true),

                        'hasWMNNRI' => lf_make_boolean( $user_ms_details[0][ 'hasWMNNRI__c'],true),

                        'hasWMNNHub' => lf_make_boolean( $user_ms_details[0][ 'hasWMNNHub__c'],true),

                        'hasLFAcademy2018' => lf_make_boolean( $user_ms_details[0][ 'hasLFAcademy2018__c'],true),

                        'hasLFAcademy2019' => lf_make_boolean( $user_ms_details[0][ 'hasLFAcademy2019__c'],true),

                        'hasLFAcademy2020' => lf_make_boolean( $user_ms_details[0][ 'hasLFAcademy2020__c'],true),

                        'hasLFAcademy2021' => lf_make_boolean( $user_ms_details[0][ 'hasLFAcademy2021__c'],true),

                        'hasLFBoardofTrustees' => lf_make_boolean( $user_ms_details[0][ 'hasLFBoardofTrustees__c'],true),

                        'hasLFDirectors' => lf_make_boolean( $user_ms_details[0][ 'hasLFDirectors__c'],true),

                        'hasLFSeniorDirectors' => lf_make_boolean( $user_ms_details[0][ 'hasLFSeniorDirectors__c'],true),

                        'hasLFAffiliates' => lf_make_boolean( $user_ms_details[0][ 'hasLFAffiliates__c'],true),

                        'hasTXNSI' => lf_make_boolean( $user_ms_details[0][ 'hasTXNSI__c'],true),

                        'hasRPDC' => lf_make_boolean( $user_ms_details[0][ 'hasRPDC__c'],true),

                     'hasTXNSICollaborative' => lf_make_boolean( $user_ms_details[0][ 'hasTXNSICollaborative__c'],true),

                    ],

                    

                 /* LMS variables */

                 'LMS' => [

                     'LMScourses'    => lf_make_boolean( $user_ms_details[0][ 'lmS_Courses__c'], true ),

                     'LMSacademy'    => lf_make_boolean( $user_ms_details[0][ 'lmS_Academy__c'],true ),

                     'LMSassessingImpact'    => lf_make_boolean( $user_ms_details[0][ 'lmS_AssessingImpact__c'],true ),

                     'LMSbecomingaLearningTeam'  => lf_make_boolean( $user_ms_details[0][ 'lmS_BecomingaLearningTeam__c'],true ),

                     'LMSinstructor' => lf_make_boolean( $user_ms_details[0][ 'lmS_Instructor__c'],true ),

                     'LMSlearningPrincipal'  => lf_make_boolean( $user_ms_details[0][ 'lmS_LearningPrincipal__c'],true ),

                     'LMSnetworks'   => lf_make_boolean( $user_ms_details[0][ 'lmS_Networks__c'],true ),

                     'LMSstaff'  => lf_make_boolean( $user_ms_details[0][ 'lmS_Staff__c'],true ),

                     'LMSvirtualCoachesAcademy'  => lf_make_boolean( $user_ms_details[0][ 'lmS_VirtualCoachesAcademy__c'],true ),

                     'LMSvirtualMentorAcademy'   => lf_make_boolean( $user_ms_details[0][ 'lmS_VirtualMentorAcademy__c'],true ),

                 ],

                ];


                /**
                 * Log in the user
                 * 
                 */

                $userobj = new WP_User();
            
                /* Load WP user data by loginname or username */
                $user = $userobj->get_data_by( 'login', $user_data['loginname'] ); 
          
                $local_id = $user_ms_details[0]['localID'];
                if (is_object($user)) {
                    $user = new WP_User($user->ID); // Attempt to load up the user with that ID
                    
                } 

                /* Find user in WP based on membersuite ID */
                else {
                    // Get WP User by MemberSuite ID:
                    /**
                     * @var WP_User_Query $user_query
                     *@see https://usersinsights.com/wordpress-user-meta-query/
                     */
                    $user_query = new WP_User_Query([
                        'meta_key' => 'membersuite_id',
                        'meta_value' => $local_id
                    ]);


                    /** @var WP_User $user */
                    foreach ($user_query->results as $user) {

                       
                        
                        // This just assigns the first user, there should be only one to the $user object
                        break;
                    }
                        
                    
                    if ($user instanceof WP_User && $user->ID > 0 && !empty($user_data['emailaddress']) && $user_data['loginname'] != $user->user_email) {
                        // update Email:
                        wp_update_user([
                            'ID' => $user->ID,
                            'user_email' => $user_data['loginname']
                        ]);

                    }
                }

                //Check to see if the user is logged into WordPress. If zero, then
                //not logged into WordPress.
                if (!($user instanceof WP_User) || $user->ID == 0 ) {
                    // The user does not currently exist in the WordPress user table.
                    // You have arrived at a fork in the road, choose your destiny wisely

                    // If you do not want to add new users to WordPress if they do not
                    // already exist uncomment the following line and remove the user creation code
                    // $user = new WP_Error( 'denied', __("ERROR: Not a valid user for this system") );

                    // Setup the minimum required user information for this example
                    $wordpress_user_data = array(
                        'user_email' => $user_data['loginname'],
                        'user_login' => $user_data['loginname'],
                        'first_name' => $user_data['firstname'],
                        'last_name' => $user_data['lastname']
                        //TODO Need to figure out how to write to an ACF field
        //                'acf[field_5a9066d110baa]' => $individualID
                    );

                    $new_user_id = wp_insert_user( $wordpress_user_data ); // A new user has been created
                    // echo $new_user_id;
                    // Load the new user info
                    $user = new WP_User( $new_user_id );

                }


                $current_membersuite_id = get_user_meta($user->ID, 'membersuite_id', true);
                if (empty($current_membersuite_id)) {
                    update_user_meta($user->ID,'membersuite_id', $local_id);
                }

           
                // Login the user to after successful SSO
                wp_clear_auth_cookie();
                wp_set_current_user( $user->ID );
                wp_set_auth_cookie( $user->ID );
                update_user_caches( $user );
                do_action( 'wp_login', $user->user_login, $user );


                update_user_meta($user->ID,'membersuite_data', json_encode($user_data));

                memberSuiteUserData::setLocalData($user_data);
                memberSuiteUserData::syncBuddyPressGroupsToMemberSuite($user->ID);

                if ( function_exists( 'aal_insert_log' ) ) {
                    aal_insert_log( array(
                        'action'      => 'membersuite_login',
                        'user_caps'   => 'administrator',
                        'user_id'     => $user->ID,
                        'object_id'   => $user->ID,
                        'object_type' => 'MemberSuite',
                        'object_name' => $portalusername,
                    ) );
                }
              
                unset($_COOKIE['ms_tokenGUID']);
                setcookie('ms_tokenGUID', '' , time()-600, '/',get_sso_cookie_domain() );
                
                setcookie( 'firstName', $user_data['firstname'], time()+36000, '/', get_sso_cookie_domain() );
                setcookie( 'lastName', $user_data['lastname'], time()+36000, '/', get_sso_cookie_domain()  );
                setcookie( 'MsUser', $user_data['msuser'], time()+36000, '/', get_sso_cookie_domain()  );
                setcookie( 'isMember', $receivesmemberbenefitsvalue, time()+36000, '/' , get_sso_cookie_domain() );
                setcookie( 'jobTitle', $user_data['jobtitle'], time()+36000, '/', get_sso_cookie_domain()  );
                setcookie( 'jobCategory', $user_data['jobcategory'], time()+36000, '/', get_sso_cookie_domain()  );
                setcookie( 'hasSSLN', $user_data['communities']['hasSSLN'], time()+36000, '/' , get_sso_cookie_domain() );
                setcookie( 'hasWMNN', $user_data['communities']['hasWMNN'], time()+36000, '/' , get_sso_cookie_domain() );
                setcookie( 'hasWMNNMD', $user_data['communities']['hasWMNNMD'], time()+36000, '/' , get_sso_cookie_domain() );
                setcookie( 'hasWMNNOH', $user_data['communities']['hasWMNNOH'], time()+36000, '/' , get_sso_cookie_domain() );
                setcookie( 'hasLFAcademy2018', $user_data['communities']['hasLFAcademy2018'], time()+36000, '/', get_sso_cookie_domain()  );
                setcookie( 'hasLFAcademy2019', $user_data['communities']['hasLFAcademy2019'], time()+36000, '/', get_sso_cookie_domain()  );
                setcookie( 'hasLFAcademy2020', $user_data['communities']['hasLFAcademy2020'], time()+36000, '/' , get_sso_cookie_domain() );
                setcookie( 'hasLFAcademy2021', $user_data['communities']['hasLFAcademy2021'], time()+36000, '/', get_sso_cookie_domain()  );
                setcookie( 'hasLFBoardofTrustees', $user_data['communities']['hasLFBoardofTrustees'], time()+36000, '/' , get_sso_cookie_domain() );
                setcookie( 'hasLFDirectors', $user_data['communities']['hasLFDirectors'], time()+36000, '/', get_sso_cookie_domain()  );
                setcookie( 'hasLFSeniorDirectors', $user_data['communities']['hasLFSeniorDirectors'], time()+36000, '/' , get_sso_cookie_domain() );
                setcookie( 'hasLFAffiliates', $user_data['communities']['hasLFAffiliates'], time()+36000, '/', get_sso_cookie_domain()  );
                setcookie( 'hasTXNSI', $user_data['communities']['hasTXNSI'], time()+36000, '/', get_sso_cookie_domain()  );
                setcookie( 'hasRPDC', $user_data['communities']['hasRPDC'], time()+36000, '/' , get_sso_cookie_domain() );
                setcookie( 'hasTXNSICollaborative', $user_data['communities']['hasTXNSICollaborative'], time()+36000, '/', get_sso_cookie_domain()  );
                setcookie( 'LMScourses', $user_data['LMS']['LMScourses'], time()+36000, '/', get_sso_cookie_domain()  );
                setcookie( 'LMSacademy', $user_data['LMS']['LMSacademy'], time()+36000, '/', get_sso_cookie_domain()  );
                setcookie( 'LMSassessingImpact', $user_data['LMS']['LMSassessingImpact'], time()+36000, '/' , get_sso_cookie_domain() );
                setcookie( 'LMSbecomingaLearningTeam', $user_data['LMS']['LMSbecomingaLearningTeam'], time()+36000, '/' , get_sso_cookie_domain() );
                setcookie( 'LMSinstructor', $user_data['LMS']['LMSinstructor'], time()+36000, '/' , get_sso_cookie_domain() );
                setcookie( 'LMSlearningPrincipal', $user_data['LMS']['LMSlearningPrincipal'], time()+36000, '/', get_sso_cookie_domain()  );
                setcookie( 'LMSnetworks', $user_data['LMS']['LMSnetworks'], time()+36000, '/', get_sso_cookie_domain()  );
                setcookie( 'LMSstaff', $user_data['LMS']['LMSstaff'], time()+36000, '/', get_sso_cookie_domain()  );
                setcookie( 'LMSvirtualCoachesAcademy', $user_data['LMS']['LMSvirtualCoachesAcademy'], time()+36000, '/', get_sso_cookie_domain()  );
                setcookie( 'LMSvirtualMentorAcademy', $user_data['LMS']['LMSvirtualMentorAcademy'], time()+36000, '/' , get_sso_cookie_domain() );
                 
                
            
                                    
            }else{
                 error_log('[MemberSuite-SSO, File: '.__FILE__.' on Line: '.__LINE__.'] '.
                    '$api->ExecuteMSQL failed to get the Individual table for WP User: '.get_current_user_id().'('.$user_ms_data['firstName'].') with MS guid ID: '.$individualID.PHP_EOL.
                    print_r($user_ms_details, true)
                );
            }
        }
    }


    if( is_user_logged_in() && $_GET['login'] == 'success' && is_page( 9 ) ){
        header("Refresh:0; url=".home_url()."");
        exit;
    }
} 


/**
 * Make boolean
 */

function lf_make_boolean( $array_value , $make_bool = false ){
    if ($make_bool) {
        return filter_var($array_value, FILTER_VALIDATE_BOOLEAN );
    }

    return $array_value;
}

/**
 * Get Whoami Data
 */

function lf_get_ms_whoami( $auth_token ){

    $user_ms_data = '';
    if( isset( $auth_token ) ){
        $url = 'https://rest.membersuite.com/platform/v2/whoami';
        
        $headers = array(
            'Authorization' => 'AuthToken ' . $auth_token,
            'Content-Type' => 'application/json'
        );
        
        $args = array(
            'headers' => $headers,
            
        );
        $response_whoami = wp_remote_get( $url,$args );

        if (is_wp_error( $response_whoami ) ) {
            // Error handling
             error_log('[MemberSuite-SSO, File: '.__FILE__.' on Line: '.__LINE__.'] '.
                    '$api->ExecuteMSQL failed to get the user info from endpoint : '.$url.PHP_EOL
                    // .print_r($response_whoami->get_error_message(), true)
                );
            // wp_die('<pre>' .$response_whoami->get_error_message() . '</pre>', 'Curl error? ' . __LINE__);

        } else {
            // Success, do something with the response_whoami data
            $body = wp_remote_retrieve_body( $response_whoami );
            $user_ms_data = json_decode( $body, 1 );
        }
    }

    return $user_ms_data;
}
/**
 * Get Membersuite AuthToken
 */
function lf_get_ms_auth_token( $tokenGUID_cookie, $tenant_id ){

    $authToken = '';
    if( !empty( $tokenGUID_cookie ) && !empty( $tenant_id ) ){

        $url = 'https://rest.membersuite.com/platform/v2/regularSSO';
        $data = array(
            'partitionKey' => $tenant_id,
            'tokenGUID' => $tokenGUID_cookie,
            
        );
        
        $request_url = add_query_arg($data, $url);
        $response = wp_remote_get($request_url);

        if (is_wp_error($response) || '400' == $response['response']['code']) {
                
           error_log('[MemberSuite-SSO, File: '.__FILE__.' on Line: '.__LINE__.'] '.
                    '$api->ExecuteMSQL failed to get the auth Token from endpoint: '.$url.PHP_EOL
                    // .print_r($response_whoami->get_error_message(), true)

                );
            // wp_die('<pre>' .$response->get_error_message() . '</pre>', 'Curl error? ' . __LINE__);


        }else{
            $authToken = wp_remote_retrieve_body( $response ); // Get location field

        }
    }

    return $authToken;
}



/* User Login function */
add_action( 'parse_request', 'mssso_portal_login' );

function mssso_portal_login( $wp ) {
    global $mssso_messages;
    
    /* Check if login form is submitted */
    
    if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset( $_POST['portalpassword'] ) && isset( $_POST['portalusername'] ) ) {

        /* MS user login credentials */
        $portalusername = $_POST['portalusername'];
        $portalpassword = $_POST['portalpassword'];

        // Verify credentials
        /** @var MemberSuite $api */
        $api = new MemberSuite();
        $current_user = wp_get_current_user();

        $returnURL = home_url().'?login=success';
       

        // get our query strings and check if we have a returnURL parameter
        parse_str( $_SERVER['QUERY_STRING'], $query_string );
        if( strpos( $_SERVER['REQUEST_URI'], '/sso-test' ) == true ){
            $returnURL = home_url().'?login=success';

        }

        if ( isset( $_SERVER['QUERY_STRING'] ) && isset( $query_string['returnURL'] ) ) {
            if ( $query_string['returnURL'] ) {
                $returnURL = $query_string['returnURL'].'?login=success';
            }
        }
       /* Redirect after successful login to the set redirect parameter for WP Members */ 
         if(isset($_POST['redirect_to'])){
            $returnURL = $_POST['redirect_to'].'?login=success';
         } 

        /* The MS API information */
        $helper = new ConciergeApiHelper();
        $api->accesskeyId = Userconfig::read( 'AccessKeyId' );
        $api->associationId = Userconfig::read( 'AssociationId' );
        $api->secretaccessId = Userconfig::read( 'SecretAccessKey' );
        $api->portalusername = $portalusername;
        $api->portalPassword = $portalpassword;
        $api->signingcertificateId = Userconfig::read( 'SigningcertificateId' );
        // $rsaXML = html_entity_decode( Userconfig::read( 'SigningcertificateXml' ) );
        $xmlPath = Userconfig::read( 'SigningcertificatePath' ) ;
        
        if(file_exists($xmlPath))
        {       
           
            $value = file_get_contents($xmlPath);
            $rsaXML =  mb_convert_encoding($value , 'UTF-8' , 'UTF-16LE');

        }
        else{
            
           if (defined('WP_DEBUG') && WP_DEBUG) {
                wp_die('<pre>' . Curl::getCurlError() . '</pre>', 'Curl error? ' . __LINE__);
            }
            header( 'location:' . home_url() . '/login?error=certificate-error' );
            error_log('[MemberSuite-SSO, '.__FILE__.' on Line: '.__LINE__.'] No User? for username: '.
                get_current_user_id().'('.$portalusername.') '.PHP_EOL
            );
            exit();
            
        }

        $rsaXMLDecoded = htmlspecialchars_decode( $rsaXML );


        // Verify username and password

        $response = $api->LoginToPortal( $api->portalusername, $api->portalPassword );



        if ( $response->aSuccess == 'false' || !$response->aSuccess ) {



            $loginarr = $response->aErrors->bConciergeError->bMessage;



            if ( function_exists( 'aal_insert_log' ) ) {

                $description = 'Username: '.$portalusername .': '.$loginarr;

                if (strlen($description) > 255) {

                    $description = substr($description, 0, 254);

                }

                aal_insert_log( array(

                    'action'      => 'membersuite_login_error',

                    'user_caps'   => 'administrator',

                    'object_type' => 'MemberSuite',

                    'object_name' => $description,

                ) );

            }



            if (defined('WP_DEBUG') && WP_DEBUG) {

                wp_die('<h3>Curl ERROR message, line: '.__LINE__.'</h3> <pre>' . print_r(Curl::getCurlError(), true) . '</pre>' .

                    '<h3>Response:</h3><pre>' . print_r($response, true) . PHP_EOL .

                    //print_r(get_option( 'membersuite_sso_option_name' ), true).

                    '</pre>', 'Failed login ' . __LINE__);

            }



            header( 'location:' . home_url() . '/login?error=credential-error');


            error_log('[MemberSuite-SSO, '.__FILE__.' on Line: '.__LINE__.'] No User? for username: '.

                get_current_user_id().'('.$portalusername.') '.PHP_EOL

                // .print_r(Curl::getCurlError(), true)

            );

            exit();

        }



        //Searching for the aPortalUser's Owner field,

        // which contains the GUID of the person logging in

        $portalUser = $response->aResultValue->aPortalUser->bFields->bKeyValueOfstringanyType;



        // @TODO Error invalid: This is noted in the error log followed by a LOGIN error:

        $guid = false;

        $local_id = false;

        if (is_array($portalUser)) {

            foreach ($portalUser as $portalValue) {

                switch ($portalValue->bKey) {

                    case 'Owner':

                        // Checking to define the GUID variable (aka Owner)

                        $guid = $portalValue->bValue;

                        break;

                    case 'ID':

                        // Checking to define the SessionID variable

                        $sessionId = $portalValue->bValue;

                        break;

                }

            }

        }



        if (isset($response->aResultValue->aPortalEntity->bFields->bKeyValueOfstringanyType)) {

            foreach ($response->aResultValue->aPortalEntity->bFields->bKeyValueOfstringanyType as $value_pair) {

                switch ($value_pair->bKey) {

                    case 'LocalID':

                        // Human readable ID and visible in the Membersuite Portal for Individual 360 view

                        $local_id = $value_pair->bValue;

                }

            }

        }



        if (!$guid) {

            error_log('[MemberSuite-SSO, '.__FILE__.' on Line: '.__LINE__.'] No User? for username: '.

                get_current_user_id().'('.$portalusername.') '.PHP_EOL 
                // .print_r($response->aResultValue->aPortalUser, true)

            );



            if (defined('WP_DEBUG') && WP_DEBUG) {

                wp_die(print_r($response, true), 'Failed login');

            }



            header( 'location:' . home_url() . '/login?error=credential-error'.__LINE__);

            wp_die( 'Invalid login credentials', 'Portal Login' );

        }

        //end of searching for the GUID





        // Use helper class to generate signature

        $api->digitalsignature = $helper->DigitalSignature( $api->portalusername, $rsaXML );

        // Create Token for sso

        $response = $api->CreatePortalSecurityToken( $api->portalusername, $api->signingcertificateId, $api->digitalsignature );

        if ( $response->aSuccess=='false' ) {

            wp_die( $response->aErrors->bConciergeError->bMessage, 'Portal Login' );

            return $response->aErrors->bConciergeError->bMessage;

        }


        $securityToken = $response->aResultValue;

        $individualID = $guid;

        $associationId = Userconfig::read( 'AssociationId' );

        $url = 'https://rest.membersuite.com/platform/v2/regularSSO';
        $data = array(
            'token' => $securityToken,
            'nextURL' => $returnURL
           
        );

        $headers = array(
            'Content-Type: application/x-www-form-urlencoded',
        );

        $response_wp = wp_remote_post($url, array(
            'method' => 'POST',
            'headers' => $headers,
            'body' => $data,
            'timeout' => 45,
            'redirection' => 0
        ));

        $location = '';

        if (is_wp_error($response_wp)) {
            // Handle error...
            error_log('[MemberSuite-SSO, file: '.__FILE__.' on '.__LINE__.'] API Request can not be completed for SSO Token with MS guid ID ');
        } else {
            $location = wp_remote_retrieve_header($response_wp, 'location'); // Get location field
            // Process response and location...
            parse_str(parse_url($location, PHP_URL_QUERY), $query_params);


            $tokenGUID = $query_params['tokenGUID'];

            // Set tokenGUID Cookie for 5 mins

            $tenant_id = Userconfig::read( 'tenantID' );
            $get_auth_token = lf_get_ms_auth_token( $tokenGUID, $tenant_id );

            // Get ms user data
            // Call the Auth Token API
           
            if( isset( $get_auth_token ) && $get_auth_token !=false ){
                $auth_token = $get_auth_token;

            } 

            // Call the Whoami API
            $user_ms_data = lf_get_ms_whoami( $auth_token );
            
            $ms_user = $user_ms_data['ownerId'];
            // If user data exists check if user receives member benefits
            // $other_user_data = '';

            setcookie( 'ms_tokenGUID', $tokenGUID, time()+300, '/' , get_sso_cookie_domain() );
            setcookie( 'lf_return_url', $returnURL, time()+300, '/' , get_sso_cookie_domain() );

            // Set cookie for Podi
            setcookie( 'MsUser',$ms_user, time()+300, '/', get_sso_cookie_domain()  );
            
            
            header("Location: ".$location  );
            exit;
        }   


        ?>
        <!-- Process the MS login form in background  -->
        <html>
        <head>
            <style>
                .loading {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%,-50%);
                    width: 70%;
                    padding-top: 40px;
                    background: url(<?php echo home_url(); ?>/wp-content/plugins/membersuite-sso/images/loading.gif) center top no-repeat;
                }

                .loading p {
                    text-align: center;
                    font-family: 'myriad-pro', sans-serif;
                }
                body{
                    margin: 0 !important;
                    max-width: 100% !important;
                }
                .wp-die-message{
                    display: none;
                }
            </style>
        </head>
        <body>
            <div class="loading">
                <p>Please wait while we verify your credentials</p>
            </div>
            
            <form name="LoginForm" method="post" id="LoginForm" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <input type="hidden" name="token" id="Token" value="<?php echo $securityToken; ?>" />

               
                <input type="hidden" name="NextUrl" id="NextUrl" value="<?php $returnURL; ?>" />

                
                <input type="hidden" name="ReturnUrl" id="ReturnUrl" value="<?php echo get_site_url(); ?>" />
                <input type="hidden" name="ReturnText" id="ReturnText" />

                
                <input type="hidden" name="LogoutUrl" id="LogoutUrl" value="<?php echo home_url(); ?>" />

            </form>
            <script>
                //always submit the main MemberSuite login form first
                document.LoginForm.submit();
            </script>

        </body>
        </html>
        <?php

        wp_die('Portal Login', 'Portal Login', ['response' => 200]);
        return 'Portal Login';
    }
}

/**
 * @param string $cookie_name
 * @param string $table_value - the value of $cookie_name from the table object
 * @param mixed $value - if valid set this value for the cookie
 * @return bool|mixed
 */
function setUserTableCookieOnlyIfValid($cookie_name, $table_value, $value=1) {
    if ($table_value == 'true' || $table_value === true) {
        setcookie( $cookie_name, $value, time()+36000, '/' , get_sso_cookie_domain() );
        return $value;
    }

    return false;
}

/**
 * @param stdClass $object
 * @param string $name
 * @param bool $default_value
 * @param bool $make_bool
 * @return bool|mixed
 */
function getObjectPropertyValue($object, $name, $default_value=false, $make_bool=false) {
    if (property_exists($object, $name)) {
        if ($make_bool) {
            return filter_var($object->$name, FILTER_VALIDATE_BOOLEAN);
        }

        return $object->$name;
    }

    return $default_value;
}

/* BrightSpace SSO Login Section */

/* Generate BrightSpace SSO GUID  */
function get_d2l_guid(){
    
    $user_info = wp_get_current_user();
    $usrname = $user_info->user_email;
    
    
            $curl = curl_init();
              curl_setopt_array($curl, array(
              CURLOPT_URL => "https://learn.learningforward.org/d2l/guids/D2L.Guid.2.asmx/GenerateExpiringGuid?guidType=SSO&orgId=6606&installCode=055544DA-32AB-419E-9167-E674E43E988E&TTL=30&data=". $usrname ."&key=CPNTLbKYS1iBdMsDFzIkHOZp0865WXqafn7Uhxe2GwuygvRrlEAVtoJc9mQ3j4",
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_TIMEOUT => 30000,
             
              CURLOPT_CUSTOMREQUEST => "GET",
              CURLOPT_SSLVERSION    => 6,
              CURLOPT_HTTPHEADER => array(
                "content-type: application/x-www-form-urlencoded"
              ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);
            

            if ($response){
                
                $xmlR = simplexml_load_string($response);
                return $xmlR;
                
            }           
            else{
                return FALSE;
            } 
            
            
}

/* Use this shortcode anywhere on the site to display the LMS login button */       
add_shortcode('d2l_sso','d2l_sso_code');
function d2l_sso_code(){
    ob_start();
    $user_info = wp_get_current_user();
    $usrname = $user_info->user_email;
    
        if(has_lms_courses() || has_lms_academy() || has_lms_assessing_impact() || has_lms_becoming_learningteam() || has_lms_instructor() || has_lms_learningprincipal() || has_lms_networks() || has_lms_staff() || has_lms_virtualcoaches_academy() || has_lms_virtualmentor_academy() ){
            echo '<a style="background:orange;" href="https://learn.learningforward.org/d2l/lp/auth/login/ssoLogin.d2l?Username='. $usrname .'&guid=' . get_d2l_guid() . '&orgId=6606&logoutURL=https://learningforward.org" class="button" target="_blank" id="lms-login">Access Learning Studio</a>';
            echo '<br> <a id="lms-reload" class="button" href="javascript:void(0)" style="display:none;">Reload Page</a>';
        }else{
            return;
        }
        return ob_get_clean();
}

/* Auto Refresh the learning studio page to make sure GUID is not expired */
add_action('wp_head','auto_refresh_page',10);
function auto_refresh_page(){
    if(is_page(75438)){
        if(has_lms_courses() || has_lms_academy() || has_lms_assessing_impact() || has_lms_becoming_learningteam() || has_lms_instructor() || has_lms_learningprincipal() || has_lms_networks() || has_lms_staff() || has_lms_virtualcoaches_academy() || has_lms_virtualmentor_academy() ){
            ?>
                <script>
                    jQuery(document).ready(function(){
                        jQuery('#lms-login').delay(28000).fadeOut(300);
                        jQuery('#lms-reload').delay(28000).fadeIn(300);
                        
                        jQuery('#lms-reload').click(function() {
                            location.reload();
                        });
                    });
                </script>
            <?php
        }
    }
    
} 

/* Pathable SSO */
function pathSSO(){
    $Cuser_info = wp_get_current_user();
    $Cusrname = $Cuser_info->user_login;
    
    $curl = curl_init();
              curl_setopt_array($curl, array(
              CURLOPT_URL => "https://learnfwd21.us2.pathable.com/api/v1/communities/rvTgAfAadLi8fnQpB/session.json?api_token=MJtznCePn9HcsdFqA-eqhJznRLzCnTLGif8&primary_email=".$Cusrname."",
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_TIMEOUT => 30000,
             
              CURLOPT_CUSTOMREQUEST => "GET",
              CURLOPT_SSLVERSION    => 6,
              CURLOPT_HTTPHEADER => array(
                "content-type: application/json"
              ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);
            

            if ($response){
                
                $pToken = json_decode($response, true);
                return $pToken['authentication_token'];
                
            }           
            else{
                return FALSE;
                }   
}

/* Use [pathable-sso] in HTML to display the login button for Pathable */
add_shortcode('pathable-sso','pathable_login_button');
function pathable_login_button(){
    ob_start();
    if(is_user_logged_in()){
        if(pathSSO() != ''){            
            echo '<a class="button" href="https://learnfwd21.us2.pathable.com?authToken='.pathSSO() .'" target="_blank">Enter the conference</a>';
        }else{
            echo '<a class="button" href="https://conference.learningforward.org/conference-registration-error/" target="_blank">Enter the conference</a>';
        }
    }else{
        echo '<a class="button" href="https://learningforward.org/login?returnURL=https://learningforward.org/" target="_blank">Enter the conference</a>';
    }
        
    return ob_get_clean();
}

/* Academy 2020, 2021 group links */
add_shortcode('user_communities','user_communities_code');
function user_communities_code(){
    ob_start();
        if(has_lf_academy_2020_community()){
            echo '<div class="clearfix"><a class="button orange-button" href="https://communities.learningforward.org/groups/academy-class-of-2020/" target="_blank">Academy Class of 2020 Community</a></div><br>';
        }
        if(has_lf_academy_2021_community()){
            echo '<div class="clearfix"><a class="button orange-button" href="https://communities.learningforward.org/groups/academy-class-of-2021/" target="_blank">Academy Class of 2021 Community</a></div><br>';
        }
        return ob_get_clean();
}

add_action('wp_footer','test_script');
function test_script(){
    $user_id = get_current_user_id();
    $user_meta = get_user_meta( 15415 ,'membersuite_data',true );
    // echo "<pre>".print_r( $user_meta,1 )."</pre>";
}