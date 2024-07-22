<?php
/**********

This file contains code and functions for all member related operations and relationships between
membersuite user data and WordPress user profile.

***********/


/**
 * The MemberSuite ID field in WordPress User profile.
 *
 * @param $user WP_User user object
 */
function wp_usermeta_form_field_membersuite_id($user)
{
    if (current_user_can('edit_users') || current_user_can('manage_network_users')) {
        /** @see https://wordpress.org/support/article/roles-and-capabilities/ */
        ?>
        <h3>MemberSuite</h3>
        <table class="form-table">
            <tr>
                <th>
                    <label for="membersuite_id">MemberSuite ID</label>
                </th>
                <td>
                    <input type="number"
                           class="regular-text ltr"
                           id="membersuite_id"
                           name="membersuite_id"
                           value="<?= esc_attr(get_user_meta($user->ID, 'membersuite_id', true)); ?>"
                           title="Please use YYYY-MM-DD as the date format."
                    >
                    <p class="description">
                        Only Admins can manage this, find the ID in MemberSuite Individual 360
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
}

/**
 * The save action for membersuite user ID under WP user profile
 *
 * @param $user_id int the ID of the current user.
 *
 * @return bool Meta ID if the key didn't exist, true on successful update, false on failure.
 */
function wp_usermeta_form_field_membersuite_id_update($user_id)
{
    // check that the current user have the capability to edit the $user_id
    if (current_user_can('edit_users') || current_user_can('manage_network_users')) {
        /** @see https://wordpress.org/support/article/roles-and-capabilities/ */

        // create/update user meta for the $user_id
        return update_user_meta(
            $user_id,
            'membersuite_id',
            $_POST['membersuite_id']
        );
    }

    return false;
}

/* WordPress admin code for displaying and maintaining membersuite ID under user profile */
// add the field to user's own profile editing screen
add_action(
    'edit_user_profile',
    'wp_usermeta_form_field_membersuite_id'
);

// add the field to user profile editing screen
add_action(
    'show_user_profile',
    'wp_usermeta_form_field_membersuite_id'
);

// add the save action to user's own profile editing screen update
add_action(
    'personal_options_update',
    'wp_usermeta_form_field_membersuite_id_update'
);

// add the save action to user profile editing screen update
add_action(
    'edit_user_profile_update',
    'wp_usermeta_form_field_membersuite_id_update'
);


// Fundamental checks/conditions for users

/**
 * Checks whether the user is logged in to MemberSuite.
 *
 * @access public
 * @return bool
 */
function is_ms_logged_in() {
	//Using the WordPress function to determine whether
    //a person is logged in. This is safer than relying
    //upon cookies.
    $user = wp_get_current_user();
    return $user->exists();
}


/**
 * Checks whether the user is a member in MemberSuite.
 *
 * @access public
 * @return bool
 */
function is_ms_member() {
    //First check to see if the person is really logged into WP
    $user = wp_get_current_user();

    $local_data = memberSuiteUserData::getLocalData();
    // ???
	if ($user->exists() && isset($local_data['isMember'])) {
		return ($local_data['isMember'] == '1' || $local_data['isMember'] === true || $local_data['isMember'] == 1);
	}

	return false;
}

/**
 * @param string $name
 * @param mixed $value
 * @return bool
 */
function arrayHasValue($array, $key, $value=1) {
    if (isset($array[$key]) && $array[$key] == $value) {
        return true;
    }

    return false;
}
/**
 * Checks whether the user has a community in MemberSuite.
 * This is a check box inside the MemberSuite user's profile.
 *
 * @access public
 * @return bool
 */
function has_ms_community() {
    return memberSuiteUserData::isMemberSuiteCommunityMember();
}

/**
 * Checks whether the user has a course selected in MemberSuite.
 * This is a check box inside the MemberSuite user's profile.
 *
 * @access public
 * @return bool
 */
function has_lms_access() {
    return memberSuiteUserData::isLMSmember();
}

/********************
------- LMS custom fields checks for user ---------
******************* */

function has_lms_courses() {
    if ( has_lms_access() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['LMS']['LMScourses'])) {
            return $local_data['LMS']['LMScourses'];
        }
    }
    return false;
}

function has_lms_academy() {
    if ( has_lms_access() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['LMS']['LMSacademy'])) {
            return $local_data['LMS']['LMSacademy'];
        }
    }
    return false;
}

function has_lms_assessing_impact() {
    if ( has_lms_access() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['LMS']['LMSassessingImpact'])) {
            return $local_data['LMS']['LMSassessingImpact'];
        }
    }
    return false;
}

function has_lms_becoming_learningteam() {
    if ( has_lms_access() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['LMS']['LMSbecomingaLearningTeam'])) {
            return $local_data['LMS']['LMSbecomingaLearningTeam'];
        }
    }
    return false;
}

function has_lms_instructor() {
    if ( has_lms_access() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['LMS']['LMSinstructor'])) {
            return $local_data['LMS']['LMSinstructor'];
        }
    }
    return false;
}

function has_lms_learningprincipal() {
    if ( has_lms_access() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['LMS']['LMSlearningPrincipal'])) {
            return $local_data['LMS']['LMSlearningPrincipal'];
        }
    }
    return false;
}

function has_lms_networks() {
    if ( has_lms_access() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['LMS']['LMSnetworks'])) {
            return $local_data['LMS']['LMSnetworks'];
        }
    }
    return false;
}

function has_lms_staff() {
    if ( has_lms_access() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['LMS']['LMSstaff'])) {
            return $local_data['LMS']['LMSstaff'];
        }
    }
    return false;
}

function has_lms_virtualcoaches_academy() {
    if ( has_lms_access() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['LMS']['LMSvirtualCoachesAcademy'])) {
            return $local_data['LMS']['LMSvirtualCoachesAcademy'];
        }
    }
    return false;
}

function has_lms_virtualmentor_academy() {
    if ( has_lms_access() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['LMS']['LMSvirtualMentorAcademy'])) {
            return $local_data['LMS']['LMSvirtualMentorAcademy'];
        }
    }
    return false;
}

/**
 * Checks whether the user has the WMNN in MemberSuite.
 * This is a check box inside the MemberSuite user's profile.
 *
 * @access public
 * @return bool
 */
function has_wmnn_community() {
    //See if the user has the HasWMNN__c MemberSuite community.
    if ( has_ms_community() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['communities']['hasWMNN'])) {
            return $local_data['communities']['hasWMNN'];
        }
    }

    return false;
}

/**
 * Checks whether the user has the SSLN in MemberSuite.
 * This is a check box inside the MemberSuite user's profile.
 *
 * @access public
 * @return bool
 */
function has_ssln_community() {
    //See if the user has the HasSSLN__c MemberSuite community.
    if ( has_ms_community() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['communities']['hasSSLN'])) {
            return $local_data['communities']['hasSSLN'];
        }
    }
    return false;
}

/**
 * Checks whether the user has the LF Academy 2018 in MemberSuite.
 * This is a check box inside the MemberSuite user's profile.
 *
 * @access public
 * @return bool
 */
function has_lf_academy_2018_community() {
    //See if the user has the HasLFAcademy2018__c MemberSuite community.
    if ( has_ms_community() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['communities']['hasLFAcademy2018'])) {
            return $local_data['communities']['hasLFAcademy2018'];
        }
    }
    return false;
}

/**
 * Checks whether the user has the LF Academy 2019 in MemberSuite.
 * This is a check box inside the MemberSuite user's profile.
 *
 * @access public
 * @return bool
 */
function has_lf_academy_2019_community() {
    //See if the user has the HasLFAcademy2019__c MemberSuite community.
    if ( has_ms_community() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['communities']['hasLFAcademy2019'])) {
            return $local_data['communities']['hasLFAcademy2019'];
        }
    }
    return false;
}

/**
 * Checks whether the user has the LF Academy 2020 in MemberSuite.
 * This is a check box inside the MemberSuite user's profile.
 *
 * @access public
 * @return bool
 */
function has_lf_academy_2020_community() {
    //See if the user has the HasLFAcademy2020__c MemberSuite community.
    if ( has_ms_community() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['communities']['hasLFAcademy2020'])) {
            return $local_data['communities']['hasLFAcademy2020'];
        }
    }
    return false;
}

/**
 * Checks whether the user has the LF Academy 2021 in MemberSuite.
 * This is a check box inside the MemberSuite user's profile.
 *
 * @access public
 * @return bool
 */
function has_lf_academy_2021_community() {
    //See if the user has the HasLFAcademy2021__c MemberSuite community.
    if ( has_ms_community() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['communities']['hasLFAcademy2021'])) {
            return $local_data['communities']['hasLFAcademy2021'];
        }
    }
    return false;
}

/**
 * Checks whether the user has the LF Board of Trustees in MemberSuite.
 * This is a check box inside the MemberSuite user's profile.
 *
 * @access public
 * @return bool
 */
function has_lf_board_of_trustees_community() {
    //See if the user has the HasLFBoardofTrustees__c MemberSuite community.
    if ( has_ms_community() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['communities']['hasLFBoardofTrustees'])) {
            return $local_data['communities']['hasLFBoardofTrustees'];
        }
    }
    return false;
}

/**
 * Checks whether the user has the LF Directors in MemberSuite.
 * This is a check box inside the MemberSuite user's profile.
 *
 * @access public
 * @return bool
 */
function has_lf_directors_community() {
    //See if the user has the HasLFDirectors__c MemberSuite community.
    if ( has_ms_community() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['communities']['hasLFDirectors'])) {
            return $local_data['communities']['hasLFDirectors'];
        }
    }
    return false;
}

/**
 * Checks whether the user has the LF Senior Directors in MemberSuite.
 * This is a check box inside the MemberSuite user's profile.
 *
 * @access public
 * @return bool
 */
function has_lf_senior_directors_community() {
    //See if the user has the HasLFSeniorDirectors__c MemberSuite community.
    if ( has_ms_community() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['communities']['hasLFSeniorDirectors'])) {
            return $local_data['communities']['hasLFSeniorDirectors'];
        }
    }
    return false;
}


/**
 * Checks whether the user has the LF Affiliates community in MemberSuite.
 * This is a check box inside the MemberSuite user's profile.
 *
 * @access public
 * @return bool
 */
function has_lf_affiliates_community() {
    //See if the user has the HasLFAffiliates__c MemberSuite community.
    if ( has_ms_community() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['communities']['hasLFAffiliates'])) {
            return $local_data['communities']['hasLFAffiliates'];
        }
    }
    return false;
}

/**
 * Checks whether the user has the TXSNI community in MemberSuite.
 * This is a check box inside the MemberSuite user's profile.
 *
 * @access public
 * @return bool
 */
function has_txnsi_community() {
    //See if the user has the HasTXNSI__c MemberSuite community.
    if ( has_ms_community() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['communities']['hasTXNSI'])) {
            return $local_data['communities']['hasTXNSI'];
        }
    }
    return false;
}

/**
 * Checks whether the user has the RPDC community in MemberSuite.
 * This is a check box inside the MemberSuite user's profile.
 *
 * @access public
 * @return bool
 */
function has_rpdc_community() {
    //See if the user has the HasRPDC__c MemberSuite community.
    if ( has_ms_community() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['communities']['hasRPDC'])) {
            return $local_data['communities']['hasRPDC'];
        }
    }
    return false;
}

/**
 * Checks whether the user has the hasTXNSICollaborative community in MemberSuite.
 * This is a check box inside the MemberSuite user's profile.
 *
 * @access public
 * @return bool
 */
function has_tnsic_community() {
    //See if the user has the HasRPDC__c MemberSuite community.
    if ( has_ms_community() ) {
        $local_data = memberSuiteUserData::getLocalData();

        if (isset($local_data['communities']['hasTXNSICollaborative'])) {
            return $local_data['communities']['hasTXNSICollaborative'];
        }
    }
    return false;
}



/**
 * Gets the member first name.
 *
 * @access public
 * @return string
 */
function get_ms_community() {
    //See if the user has a community. If yes, show URL.
    if ( has_ms_community() ) {
        return '/portal/communities';
    }

    return '';
}

/**
 * Gets the member first name.
 *
 * @access public
 * @return string
 */
function get_ms_member_first_name() {
    //First check to see if the person is really logged into WP
    $user = wp_get_current_user();
    $local_data = memberSuiteUserData::getLocalData();
	if ( isset($local_data['firstname'] ) && $user->exists() ) {
		return $local_data['firstname'];
	}

	return '';
}

/**
 * Gets MemberSuite URL.
 *
 * @access public
 * @return string
 */
function get_ms_url() {
    //First check to see if the person is really logged into WP
    $user = wp_get_current_user();
	return 'https://domain.com';
}

/**
 * Gets the cookie domain.
 *
 * @access public
 * @return string
 */
function get_sso_cookie_domain() {
    //First check to see if the person is really logged into WP
    $user = wp_get_current_user();


	return 'domain.com';
}
