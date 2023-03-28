<?php

add_action( 'admin_init', 'mssso_load_admin' );

function mssso_load_admin() {
	membersuite_sso_admin()->membersuite_sso_page_init();
}

add_action( 'admin_menu', 'mssso_admin_menu' );

function mssso_admin_menu() {
	membersuite_sso_admin()->admin_menu();
}