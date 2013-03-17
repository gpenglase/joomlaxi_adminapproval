<?php 
/*
* Author : Team JoomlaXi @ Ready Bytes Software Labs Pvt. Ltd.
* Email  : shyam@joomlaxi.com
* License : GNU-GPL V2
* (C) www.joomlaxi.com
*/

// no direct access

class XIAA_AdaptorJoomla
{
	public $name = 'Joomla';
	
	public function init()
	{
		// core joomla objects
		$this->app 			= JFactory::getApplication();
		$this->db 			= JFactory::getDbo();	
		$this->input 		= JFactory::getApplication()->input;
		
		// com_user configurations
		$this->userconfig 	= JComponentHelper::getParams( 'com_users' );
	}
	
	public function isApprovalRequired($args)
	{
		
	}
	
	// I am in frontend and user came to activate 
	// index.php?option=com_users&task=registration.activate	
	public function isActivationRequest()
	{
		$option	= $input->getCmd('option');
		$task	= $input->getCmd('task');
		return ($option == 'com_users' && $task =='registration.activate');
	}
	
	public function isPasswordResendRequest()
	{
		$option	= $input->getCmd('option');
		$task	= $input->getCmd('task');
		return ($option =='com_users' && $task =='reset.request');
	}
	
	
	public function doBlockPasswordResendRequest($email=null)
	{
		//jimport('joomla.user.helper');
		$query	= ' SELECT `id` FROM `#__users` '
				. ' WHERE `email` = '.$this->db->quote($email);
					
		$id  = $this->db->setQuery($query)->loadResult();

		// user exist & email is verified => block it
		if($id && JUser::getInstance((int)$id)->getParam('email_verified')){						
			// admins approval is pending, so no resets
			// 	and tell user to wait for admin approval
			$this->app->redirect('index.php', JText::_('PLG_MSG_WAIT_FOR_ADMIN_APPROVE_YOUR_ACCOUNT'));			
		}
		
		// else do nothing, joomla will take care
		return;
	}
	
	//user came to verify his email , check, mark and block user, inform admin	
	public function doEmailVerificationAndBlocking()
	{
/*		jimport('joomla.user.helper');

			// this plugin should also work without JS Profile Types
			$MY_PATH_ADMIN_JSPT	  = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jsprofiletypes';
			$this->jsptExist 	  = JFolder::exists($MY_PATH_ADMIN_JSPT);
					
			if($this->jsptExist && $this->mode==3)
			{				
				require_once (JPATH_BASE. DS.'components'.DS.'com_community'.DS.'libraries'.DS.'profiletypes.php');
				// some issue here
				$pID = CProfiletypeLibrary::getUserProfiletypeFromUserID($this->activationUserID);
				$profiletypeName = CProfiletypeLibrary::getProfileTypeNameFrom($pID);
				// TODO : what to do for $pId =0 
				
				// if admin approval NOT required, then do nothing let the joomla handle
				if($pID && CProfiletypeLibrary::getProfileTypeData($pID,'approve') == false)
				{
					if($this->debugMode)
						$this->displayMessage('ProfileType='.$pID.' and approval not required');
					return;
				}
			}
			
			$MY_PATH_ADMIN_XIPT	  = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_xipt';
			$this->xiptExist 	  = JFolder::exists($MY_PATH_ADMIN_XIPT);
			
			if($this->xiptExist && $this->mode==3){
				require_once (JPATH_BASE. DS.'components'.DS.'com_xipt'.DS.'api.xipt.php');
				// some issue here 
				$pID = XiptAPI::getUserProfiletype($this->activationUserID);
				$profiletypeName = XiptAPI::getUserProfiletype($this->activationUserID,'name');
				// TODO : what to do for $pId =0 
				$allCondition = array();
				$allCondition = XiptAPI::getProfiletypeInfo($pID);
				// if admin approval NOT required, then do nothing let the joomla handle
				if($allCondition){
					if($pID &&  $allCondition[0]->approve == false){
						if($this->debugMode)
							$this->displayMessage('ProfileType='.$pID.' and approval not required');
						return;
					}
				}		
			}
			
			// --- mark
			$user = JUser::getInstance($this->activationUserID);
			$user->setParam('emailVerified','1');
			
			// --- also block the user
			$user->set('block', '1');
			
			$newActivationKey=JUtility::getHash( JUserHelper::genRandomPassword());
			// generate new activation 
			// save new activation key by which our admin can enable user
			$user->set('activation',$newActivationKey);
			$this->activation =  $newActivationKey;
			
			if(!$user->save()){
				JError::raiseWarning('', JText::_( $user->getError()));
				$this->redirectUrl('index.php',JText::_('PLG_DEBUG_USER_SAVE_ERROR'));
				exit();
			}
					
			// send an email to admin  with a ativation link
			// and profile of user.
			$this->sendEmails('PLG_EMAIL_EMAIL_TO_ADMIN_FOR_APPROVAL');
			
			// show message to user
			$this->displayMessage(JText::_('PLG_MSG_EMAIL_VERIFIED_AND_ADMIN_WILL_APPROVE_YOUR_ACCOUNT'));
*/			
	}
	
	public function doAdminApprovalAndInformUser()
	{
/*
		// 6. is a admin, we should enable a blocked user				
			$user = JUser::getInstance($this->activationUserID);
			// activate user and enable
			$user->setParam('emailVerified','0');
			$user->set('block', '0');
			$user->set('activation','');
			if (!$user->save()){
					JError::raiseWarning('', JText::_( $user->getError()));
					$this->redirectUrl('index.php',JText::_('PLG_DEBUG_USER_SAVE_ERROR'));
					exit();
			}
			// inform user
			$this->sendEmails('PLG_EMAIL_ACCOUNT_ACTIVATION_EMAIL_TO_USER');

			// show a message to admin
			$this->displayMessage(JText::_('PLG_MSG_USER_HAS_BEEN_APPROVED_BY_ADMIN'));
*/			
	}
	
	const MESSAGE_APPROVED = 1;
	const MESSAGE_APPROVAL = 2;
	
	function sendMessage($user_id, $type=self::MESSAGE_APPROVAL)
	{
		//prepare basic vars
		$config = 	JFactory::getConfig();
		
		$site_name 			= $config->get('sitename');
		$site_url			= JURI::base();
		
		$email_from 		= $config->get('mailfrom');
		$email_fromname		= $config->get('fromname');
		
		// populate email content
		$data = $this->prepareMessage($user_id,$type);
		$email_subject = $data['subject'];
		$email_body	  = $data['message'];
		
		// decide whom to email
		switch($type)
		{
			case self::MESSAGE_APPROVAL :
				
					$admins = $this->_getAdminEmails();
					// Send mail to all users with users creating permissions and receiving system emails
					foreach($admins as $admin)
					{
						$return = JFactory::getMailer()->sendMail(
										$email_from, $email_fromname, $admin->email, 
										$email_subject, $email_body
									);
	
						// Check for an error.
						if ($return !== true) {
							$this->setError(JText::_('PLG_XIAA_SEND_MAIL_FAILED'));
							continue;
						}
					}
			
				break;
				
			case self::MESSAGE_APPROVED :
				
				$email	= JFactory::getUser($user_id)->email;
				$return = JFactory::getMailer()->sendMail(
										$email_from, $email_fromname, $email, 
										$email_subject, $email_body
								);
	
				// Check for an error.
				if ($return !== true) {
					$this->setError(JText::_('PLG_XIAA_SEND_MAIL_FAILED'));
				}
				
				break;
		}
		
		return;
	}
	
	function _getAdminEmails()
	{
			// get all admin users
			$query = 'SELECT name, email, sendEmail, id' .
						' FROM #__users' .
						' WHERE sendEmail=1';

			return $this->db->setQuery( $query )->loadObjectList();	
	}
	
	
	function prepareMessage($user_id, $type=self::MESSAGE_APPROVAL)
	{
		$data = array();
		$obj = array();
		$this->populateUserData($obj, $user_id);
		
		switch($type)
		{
			case self::MESSAGE_APPROVAL :
				$data['subject'] = JText::_('PLG_XIAA_YOUR_ACCOUNT_APPROVED');
				
				ob_start();
					$vars = $obj;
					include('..'.DS.'tmpl'.DS.'email_approval.php' );
				$data['subject'] = ob_get_contents();
				ob_end_clean();
				
				break;
				
			case self::MESSAGE_APPROVED :
				$data['subject'] = JText::_('PLG_XIAA_APPROVAL_REQUIRED_FOR_ACCOUNT');
				
				ob_start();
					$vars = $obj;
					include('..'.DS.'tmpl'.DS.'email_approved.php' );
				$data['subject'] = ob_get_contents();
				ob_end_clean();
				break;
		}
		
		$data['subject'] 	= html_entity_decode($data['subject'], ENT_QUOTES);
		$data['message'] 	= html_entity_decode($data['message'], ENT_QUOTES);
		return $data; 
	}
	

	/**
	 * populate user's basic data
	 * @param unknown_type $obj
	 * @param unknown_type $user_id
	 */
	public function populateUserData($data=array(), $user_id)
	{
		// common infrmation
		$user 			= JUser::getInstance((int)$user_id);
		$data['name']		= $user->name;
		$data['email']		= $user->email;
		$data['username']	= $user->username;
		$data['link']		= JRoute::_('index.php?option=com_users&task=registration.activate&token='.$obj->activation, false);

		return $obj;
	}


	//	find user id from activation key
	function getUser($activationKey) 
	{
		$query = 'SELECT id  FROM #__users'
				. ' WHERE '.$this->db->quoteName('activation').' = '.$this->db->Quote($activationKey)
				. ' AND block = '.$this->db->Quote('1');
				
		return intval($this->db->setQuery( $query )->loadResult());
	}
	
	
	/**
	 * The function will check if the user email is already verfied 
	 */
	function isUserEmailVerified($activationKey) 
	{
		$id = $this->getUser($activationKey);	
		$user = JUser::getInstance((int)$id);
		return $user->getParam('email_verified');
	}
	
	
	/**
	 * Gives debug message for configuration
	 * @param $config
	 */
	function debugConfiguration($config) 
	{
		$return = JText::_('PLG_XI_ADMINAPPROVAL_USER_REGISTRATION_CONFIGURATION_OK');	
		
		//enqueue message that registration is not working 
		if(!$config->get('allowUserRegistration')){
			$return = JText::_('PLG_XI_ADMINAPPROVAL_USER_REGISTRATION_CONFIGURATION_DISABLED');
		}
		
		// enqueu Message : admin approval plugin cannot work without activation
		if(! $config->get('userActivation')){
			$reurn = JText::_('PLG_XI_ADMINAPPROVAL_USER_ACTIVATION_REQUIRED');
		}
		
		return $return;
	}
	
}