<?php
/*
 This class contains user global information used for API connection and login management between MS and WP
 
  
*/
class Userconfig{
	
	public static function read($name){

		$membersuite_sso_options = get_option( 'membersuite_sso_option_name' );
		
		$config = array('AccessKeyId' =>  $membersuite_sso_options['accesskeyid_0'],
						'AssociationId' => $membersuite_sso_options['associationid_1'],
						'SecretAccessKey' => $membersuite_sso_options['secretaccesskey_2'],
						'SigningcertificateId' => $membersuite_sso_options['signingcertificateid_3'],
						'SigningcertificateXml' => $membersuite_sso_options['singingcertificatexml_4'],
						'PortalUrl' => $membersuite_sso_options['portalurl_5'],
                        'wmnnicoheretxtpassword' => $membersuite_sso_options['wmnnicoheretxtpassword'],
                        'wmnnicoherecseq' => $membersuite_sso_options['wmnnicoherecseq'],
                        'sslnicoheretxtpassword' => $membersuite_sso_options['sslnicoheretxtpassword'],
                        'sslnicoherecseq' => $membersuite_sso_options['sslnicoherecseq'],
                        'rpdcicoheretxtpassword' => $membersuite_sso_options['rpdcicoheretxtpassword'],
                        'rpdcicoherecseq' => $membersuite_sso_options['rpdcicoherecseq'],
                        'icoherehidpassthroughreturn' => $membersuite_sso_options['icoherehidpassthroughreturn'],
                        'icoherehidpassthroughsource' => $membersuite_sso_options['icoherehidpassthroughsource'],

						);

		
		return $config[$name];

	}    

}
