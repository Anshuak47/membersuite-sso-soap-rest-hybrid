<?php

/**********

This file contains code that pulls MemberSuite user data from profile and custom fields
to be used on the WordPress platform for several conditional based operations

**********/

class memberSuiteUserData
{
    /** @var int  */
    protected static $user_id = 0;

    /** @var bool|array */
    protected static $local_data = false;

    /** @var int  */
    protected static $current_membersuite_id = 0;

    protected static $group_map = [
        // WP BuddyBoss->Group->Slug => MS Code in functions.php
        // See: wp-content/plugins/membersuite-sso/inc/functions.php
            // Around line: 266, the MS SQL: $msql = "...
            // Around line: 296, creating the WP $user_data = []
        'txnsi' => 'hasTXNSI',
        'learning-forward-affiliates' => 'hasLFAffiliates',
        // '[slug] => learning-forward-academy', ???

        'learning-forward-academy-class-of-2017' => 'hasLFAcademy2017',
        'academy-class-of-2018' => 'hasLFAcademy2018',
        'learning-forward-academy-class-of-2019' => 'hasLFAcademy2019',
        'learning-forward-academy-class-of-2020' => 'hasLFAcademy2020',
        'academy-class-of-2021' => 'hasLFAcademy2021',

        /* Others ??
        'hasSSLN',
        'hasWMNN',
        'hasWMNNMD',
        'hasWMNNOH',
        'hasWMNNRI',
        'hasWMNNHub',
        'hasLFBoardofTrustees',
        'hasLFDirectors',
        'hasLFSeniorDirectors',
        'hasRPDC',
		'hasTXNSICollaborative'
        */
    ];
    /**
     * @return array|bool
     */
    public static function getLocalData()
    {
        if (!static::$local_data) {
            $user = wp_get_current_user();

            if (!static::$local_data && $user->ID > 0) {
                static::$local_data = json_decode(get_user_meta($user->ID, 'membersuite_data', true), true);
            }

            // security check:
            if (isset(static::$local_data['membersuite_id']) && static::getCurrentMemberSuiteID() == static::$local_data['membersuite_id']) {
                return static::$local_data;
            }

        }

        return static::$local_data;
    }

    /**
     * @param array $data
     */
    public static function setLocalData($data=[])
    {
        static::$local_data = $data;
    }

    /**
     * @return int
     */
    public static function getCurrentMemberSuiteID()
    {
        if (static::$current_membersuite_id > 0) {
            return static::$current_membersuite_id;
        }

        // set it:
        $user = wp_get_current_user();
        static::$current_membersuite_id = (int)get_user_meta($user->ID, 'membersuite_id', true);

        return static::$current_membersuite_id;
    }

    /**
     * @param string $code - key ex: hasWMNNOH
     * @return bool|mixed
     */
    public static function isCommunityMember($code)
    {
        $local_data = static::getLocalData();

        if (isset($local_data['communities']) && isset($local_data['communities'][$code])) {
            return $local_data['communities'][$code];
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
    public static function isMemberSuiteCommunityMember() {
        $local_data = static::getLocalData();
        if (is_array($local_data)) {

            if (isset($local_data['communities'])) {
                foreach ($local_data['communities'] as $community => $access) {
                    if ($access) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
	
	/* To check if there's access to any course in LMS */
	public static function isLMSmember() {
        $local_data = static::getLocalData();
        if (is_array($local_data)) {

            if (isset($local_data['LMS'])) {
                foreach ($local_data['LMS'] as $courses => $allowAccess) {
                    if ($allowAccess) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param int $user_id
     * @return bool
	 * Syncing BuddyBoss Data with MemberSuite
     */
    public static function syncBuddyPressGroupsToMemberSuite($user_id)
    {
        $current_blog_id = get_current_blog_id();

        $sites = get_sites(['domain__in' => ['domain', 'domain']]);
        if (is_array($sites) && isset($sites[0]) && $sites[0] instanceof WP_Site) {
            /** @var WP_Site $communities */
            $communities = $sites[0];
            if (1==2) {
                switch_to_blog($communities->id);
            }
        } else {
            return false;
        }

        /* if ( !function_exists( 'groups_join_group' ) ) {
            error_log('MemberSuite-SSO requires BuddyBoss Platform -> Social Groups to be installed and active!');
        } */

        $group_ids_map = static::makeGroupIDMap();

        $user_social_groups = static::getUserCurrentBuddyBossSocialGroups($user_id);

        if (static::isMemberSuiteCommunityMember()) {
            // are they already in the community?
            if (!is_user_member_of_blog($user_id, $communities->id)) {
                add_user_to_blog($communities->id, $user_id, 'subscriber');
            }

            foreach ($group_ids_map as $ms_community_key => $buddy_boss_social_group_id) {
                if (static::isCommunityMember($ms_community_key) && !isset($user_social_groups[$buddy_boss_social_group_id])) {
                    groups_join_group($buddy_boss_social_group_id, $user_id);

                } elseif (!static::isCommunityMember($ms_community_key) && isset($user_social_groups[$buddy_boss_social_group_id])) {
                    groups_leave_group($buddy_boss_social_group_id, $user_id);
                }
            }

        } elseif (is_user_member_of_blog($user_id, $communities->id)) {
            // Leave the communities blog:
            remove_user_from_blog($user_id, $communities->id);

            // leave any active groups:
            foreach ($user_social_groups as $buddy_boss_social_group_id => $status) {
                groups_leave_group($buddy_boss_social_group_id, $user_id);
            }
        }

      /*  if ( !function_exists( 'groups_get_groups' ) ) {
            error_log('MemberSuite-SSO requires BuddyBoss Platform -> Social Groups to be installed and active!');
        } */

        switch_to_blog($current_blog_id);
    }

    /**
     * @return array
     */
    protected static function makeGroupIDMap()
    {
        if ( !function_exists( 'groups_get_groups' ) ) {
            return [];
        }

        $buddyBossSocialGroups = groups_get_groups();

        $map = [];

        /** @var BP_Groups_Group $bossSocialGroup */
        foreach ($buddyBossSocialGroups['groups'] as $bossSocialGroup) {
            if (isset(static::$group_map[$bossSocialGroup->slug])) {
                $map[static::$group_map[$bossSocialGroup->slug]] = $bossSocialGroup->id;
            }
        }

        return $map;
    }

    /**
     * @param int $user_id
     * @return array
     */
    protected static function getUserCurrentBuddyBossSocialGroups($user_id)
    {
        if ( !function_exists( 'groups_get_groups' ) ) {
            return [];
        }

        $buddyBossSocialGroups = groups_get_groups(['user_id' => $user_id]);

        $user_social_groups = [];

        /** @var BP_Groups_Group $bossSocialGroup */
        foreach ($buddyBossSocialGroups['groups'] as $bossSocialGroup) {
            $user_social_groups[$bossSocialGroup->id] = true;
        }

        return $user_social_groups;
    }
}
