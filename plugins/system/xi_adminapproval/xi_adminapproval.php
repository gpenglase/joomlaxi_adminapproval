<?php 
/*
* Author : Team JoomlaXi @ Ready Bytes Software Labs Pvt. Ltd.
* Email  : shyam@joomlaxi.com
* License : GNU-GPL V2
* (C) www.joomlaxi.com
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.plugin.plugin' );
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder' );	

class plgSystemxi_adminapproval extends JPlugin
{
	const MODE_JOOMLA		= 'joomla';
	const MODE_JOMSOCIAL	= 'jomsocial';
	const MODE_JSPT			= 'jspt';
	
	/**
	 * 
	 * @param unknown_type $input
	 * @return XIAA_AdaptorJoomla
	 */
	public function getAdaptor($input)
	{
		$mode 	  = $this->params->get('mode', self::MODE_JOOMLA);
		
	}

	function onAfterRoute()
	{
		$app = JFactory::getApplication();
		
		// 1. if backend then call showAdminDebugMessage and return
		if($app->isAdmin()){
			return;
		}

		// get adaptor
		$adaptor = $this->getAdaptor($app->input);

		// 1. If password resend request 
		if($adaptor->isPasswordResendRequest()){
			$adaptor->doBlockPasswordResendRequest();
			return;
		}
		
		// 2. If activation request, then handle it
		if($adaptor->isActivationRequest() === false){
			// check for activation, if eveything is fine
			// block the user and ask admin to approve
			$adaptor->doEmailVerificationAndBlocking();			
			return;
		}

		// 3. user / Admin is here to verification	
		if($this->isAdminDoingApproval()){
			$adaptor->doAdminApprovalAndInformUser();			
			return;
		}
		
		// Do nothing
		return;
	}
	
}

