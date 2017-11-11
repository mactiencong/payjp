<?php
// 0 0,12 * * * cd /var/www/html/okage/reg_payment/backend/app/ && Console/cake TrialAccountNotify > /var/www/html/okage/reg_payment/backend/TrialAccountNotify.log 2>&1
App::uses('AppConst', 'Lib');
Configure::load('trial_account_email_content_origin');
class TrialAccountNotifyShell extends AppShell {
	public $uses = array('User');
	private $mail_content_conf = null;
	public function main() {
		$this->log('--- START ---');
		$this->mail_content_conf = Configure::read ( 'TRIAL_ACCOUNT_EMAIL' );
		// 1. Load trial users from DB
		// 2. Check expire time
		// 2.1. expire after 5 days => notify email
		// 2.2. expire after 3 days => notify email
		// 2.3. expire after 1 days => notify email
		// 2.4. today is expire => notify email
		// 3. Update flag by remaining days before expire
		for($loadLoop=0; $loadLoop<AppConst::LOAD_TRIAL_MAX_LOOP; $loadLoop++) {
			// 1. Load trial users from DB
			$trial_users = null;
			$trial_users = $this->_loadTrialUser();
			if (!$trial_users)
				return null;
			foreach ($trial_users as &$user){
				$this->log('### START PROCESS USER: '. print_r($user['User']['id'], true));
				// check trial user again, maybe the user be actived this time???
// 				if (!$this->_isTrialUser($user))
// 					continue;
				$expire_time = $this->_convertDatetimeStrToTimestamp($user['User']['expire_date']);
				$this->log("expire_time=$expire_time");
				// 2. Check expire time
				$remainingDays = $this->_checkRemainingDaysBeforeExpire($expire_time);
				$this->log("remainingDays=$remainingDays");
				if ($this->_isDontProcessAgain($user, $remainingDays))
					continue; // dont process again
				switch ($remainingDays) {
					case -1:
						if(!$this->_processForExpire($user['User']['id'], $user['User']['email'])) exit(0);//error? should or not???
						break;
					case 0:
						if(!$this->_processForToDayIsExpire($user['User']['id'], $user['User']['email'])) exit(0);//error should or not???
						break;
					case 1:
						if (!$this->_processFor01DaysBeforeExpire($user['User']['id'], $user['User']['email'])) exit(0);//error should or not???
						break;
					case 3:
						if (!$this->_processFor03DaysBeforeExpire($user['User']['id'], $user['User']['email'])) exit(0);//error should or not???
						break;
					case 5:
						if (!$this->_processFor05DaysBeforeExpire($user['User']['id'], $user['User']['email'])) exit(0);//error should or not???
						break;
					default: break;
				}
				$this->log('### END PROCESS USER: ' . $user['User']['id']);
			}
			if (count($trial_users)<AppConst::LOAD_TRIAL_ACCOUNT_LIMIT) // over data
				break;
		}
		$this->log('--- COMPLETE ---');
	}
	// current day the user have be processed, do not process again
	private function _isDontProcessAgain(&$user, $remainingDays){
		if (is_numeric($user['User']['notify_day']) && intval($user['User']['notify_day']) === $remainingDays) {
			$this->log('DONOT PROCESS AGAIN');
			return true;
		}
		return false;
	}
	// 2017-05-07 16:59:52
	private function _convertDatetimeStrToTimestamp($datetime_str){
		if (!$datetime_str) return 0;
		$dtime = DateTime::createFromFormat('Y-m-d H:i:s', $datetime_str);
		if ($dtime)
			return $dtime->getTimestamp();
		return 0;
	}
	
	private function _isTrialUser(&$user){
		return ((int)$user['User']['status']) === AppConst::$USER_STATUS['TRIAL'];
	}
	
	
	private function _processFor01DaysBeforeExpire($user_id, $email){
		$this->log(__FUNCTION__);
		// 3. Update flag
		if (!$this->_updateNotifyStatusFlag($user_id, 1))
			return false;
		// 2.3. expire after 1 days => notify email
		$this->_sendMail01DaysBeforeExpire($email);
		return true;
	}
	
	private function _processFor03DaysBeforeExpire($user_id, $email){
		$this->log(__FUNCTION__);
		// 3. Update flag
		if (!$this->_updateNotifyStatusFlag($user_id, 3))
			return false;
		// 2.3. expire after 1 days => notify email
		$this->_sendMail03DaysBeforeExpire($email);
		return true;
	}
	
	private function _processFor05DaysBeforeExpire($user_id, $email){
		$this->log(__FUNCTION__);
		// 3. Update flag
		if(!$this->_updateNotifyStatusFlag($user_id, 5))
			return false;
		// 2.3. expire after 1 days => notify email
		$this->_sendMail03DaysBeforeExpire($email);
		return true;
	}
	
	private function _processForToDayIsExpire($user_id, $email){
		$this->log(__FUNCTION__);
		if(!$this->_updateNotifyStatusFlag($user_id, 0))
			return false;
		// send mail to user and admin
		$this->_sendMailToDayIsExpire($email);
		return true;
	}
	
	private function _processForExpire($user_id, $email){
		$this->log(__FUNCTION__);
		// set status = deactive
		if(!$this->_deactiveUserAfterExpire($user_id))
			return false;
		return true;
	}
	
	private function _loadTrialUser(){
		$this->log(__FUNCTION__);
		return $this->User->find('all', array(
				'conditions'=>array('User.status'=>AppConst::$USER_STATUS['TRIAL']),
				'limit'=>AppConst::LOAD_TRIAL_ACCOUNT_LIMIT,
				'order'=>array('User.modified_date ASC')
		));
	}
	
	private function _checkRemainingDaysBeforeExpire($expire_time){
		$this->log(__FUNCTION__);
		$timediff = $expire_time - time();
		if ($timediff>=0)
			return intval(floor($timediff/ 86400)); // 60 * 60 * 24
		return -1;
	}
	
	private function _updateNotifyStatusFlag($user_id, $remainingDays){
		$this->log(__FUNCTION__);
		$this->User->id = $user_id;
		$this->User->saveField('notify_day', $remainingDays);
		$this->User->saveField('modified_date', date('Y-m-d H:i:s'));
		return true;
	}
	
	private function _deactiveUserAfterExpire($user_id){
		$this->log(__FUNCTION__);
		$this->User->id = $user_id;
		$this->User->saveField('status', AppConst::$USER_STATUS['DEACTIVE']);
		$this->User->saveField('modified_date', date('Y-m-d H:i:s'));
		return true;
	}
	
	private function _sendMail01DaysBeforeExpire($to){
		$this->log(__FUNCTION__);
		$this->_sendMail($to, $this->mail_content_conf['01days_before_expire']['subject'], $this->mail_content_conf['01days_before_expire']['body']);
		return true;
	}
	
	private function _sendMail03DaysBeforeExpire($to){
		$this->log(__FUNCTION__);
		$this->_sendMail($to, $this->mail_content_conf['03days_before_expire']['subject'], $this->mail_content_conf['03days_before_expire']['body']);
		return true;
	}
	
	private function _sendMail05DaysBeforeExpire($to){
		$this->_sendMail($to, $this->mail_content_conf['05days_before_expire']['subject'], $this->mail_content_conf['05days_before_expire']['body']);
		$this->log(__FUNCTION__);
		return true;
	}
	
	private function _sendMailToDayIsExpire($to){
		$this->log(__FUNCTION__);
		//1. mail to user
		$this->_sendMail($to, $this->mail_content_conf['today_expire']['subject'], $this->mail_content_conf['today_expire']['body']);
		//2. mail to admin
		$this->_sendMail($this->mail_content_conf['admin_email'], $this->mail_content_conf['today_expire']['subject'], $this->mail_content_conf['today_expire']['body']);
		return true;
	}
	
	private function _sendMail($to, $subject, $body){
		$this->log(__FUNCTION__);
		App::import('Controller', 'Mail');
		$mail_controller = new MailController();
		$rs = $mail_controller->send($to, $subject, $body);
		return $rs && $rs['status']===0;
	}
	
	public function log($msg, $type='trial_notify', $scope=null){
		$this->out($msg);
		parent::log($msg, $type, $scope);
	}
}