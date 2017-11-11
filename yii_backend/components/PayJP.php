<?php
/**
 * @author matico
 * 
 */
require_once(dirname(__FILE__). '/../vendors/payjp/init.php');
require_once(dirname(__FILE__). '/../config/payment_constant.php');
class PayJP extends CApplicationComponent
{
	public $api_key=null;
	public $webhook_token=null;
	public function init(){
		\Payjp\Payjp::setApiKey($this->api_key);
	}
	/**
	 * @author matico
	 * @param int $subscription_plan_id
	 * @param int $client_id
	 * @param string $interval 'month' or 'year'
	 * @param int $amount
	 * @param int $total_devices
	 * @return boolean true - successed, false - failed
	 */
	public function createPlan($subscription_plan_id, $client_id, $interval, $amount, $total_devices){
		Yii::log('-- REGISTER SUBSCRIPTION --', 'payment');
		Yii::log(print_r(func_get_args(), true), 'payment');
		// Check current plan
		$csp = PayjpClientSubscriptionPlan::model()->get($subscription_plan_id, $client_id, $amount, $total_devices);
		if ($csp) // exist
			if ($csp->is_active) // currently is active
			{	
				Yii::log('-- THERE IS A ACTIVE PLAN FOR CURRENT USER (SUCCESSFULLY)--', 'payment');
				return true;
			}
			elseif(PayjpClientSubscriptionPlan::model()->active($client_id, $csp->id)) { // active successfully
				Yii::log('-- ACTIVE SUCCESSFULLY --', 'payment');
				return true;
			}
			else { // active failed
				Yii::log('-- ACTIVE FAILED --', 'payment');
				return false;
			}
		// not exist => create a new => call api to create a new plan
		$plan_data = $this->_payjpCreatePlan($client_id, $interval, $amount, $total_devices);
		if (!$plan_data) // payjp return failed
			return false;
		if(PayjpClientSubscriptionPlan::model()->createSub($subscription_plan_id, $client_id, $amount, $total_devices, $plan_data['id'], json_encode($plan_data))) {
			Yii::log('-- CREATE PLAN SUCCESSFULLY --', 'payment');
			return true; // create successfully
		}
		Yii::log('-- CREATE PLAN FAILED --', 'payment_error');
		return false;
	}
	
	private function _payjpCreatePlan($client_id, $interval, $amount, $total_devices){
		// for debug
// 		return json_decode('{
// 							  "amount": '.$amount.',
// 							  "billing_day": null,
// 							  "created": '.time().',
// 							  "currency": "jpy",
// 							  "id": "pln_45dd3268a18b2837d52'.rand(1000,9999).'861716260",
// 							  "interval": "'.$interval.'",
// 							  "livemode": false,
// 							  "metadata": null,
// 							  "name": null,
// 							  "object": "plan",
// 							  "trial_days": 30
// 							}', true);
		try {
			return \Payjp\Plan::create(array(
					"amount" => $amount,
					"currency" => "jpy",
					"interval" => $interval,
					"metadata" => array('client_id'=>$client_id, 'total_devices'=>$total_devices)
			))->__toArray(true);
		} catch (Exception $e) {
			Yii::log($e->getMessage(), 'payment_error');
			return false;
		}
	}
	
	private function _payjpCreateCustomer($client_id, $employee_id){
// 		return json_decode('{
//   "cards": {
//     "count": 0,
//     "data": [],
//     "has_more": false,
//     "object": "list",
//     "url": "/v1/customers/cus_121673955bd7aa144de5a8f6c262/cards"
//   },
//   "created": 1433127983,
//   "default_card": null,
//   "description": "test",
//   "email": null,
//   "id": "cus_121673955bd7aa144de5a8f6c262",
//   "livemode": false,
//   "metadata": null,
//   "object": "customer",
//   "subscriptions": {
//     "count": 0,
//     "data": [],
//     "has_more": false,
//     "object": "list",
//     "url": "/v1/customers/cus_121673955bd7aa144de5a8f6c262/subscriptions"
//   }
// }', true);
		try {
			return \Payjp\Customer::create(array(
					//'email' => $email,
					'metadata' => array('client_id'=>$client_id, 'employee_id'=>$employee_id)
			))->__toArray(true);
		} catch (Exception $e) {
			Yii::log($e->getMessage(), 'payment_error');
			return false;
		}
	}
	
	
	private function _payjpCreateCardForCustomer($payjp_customer_id, $card_number, $card_cvc_code, $card_exp_month, $card_exp_year){
		try {
			$cu = \Payjp\Customer::retrieve($payjp_customer_id);
			if (!$cu)
				return false;
			$card = array(
					"number" => $card_number,
					'cvc' => $card_cvc_code,
					"exp_year" => $card_exp_year,
					"exp_month" => $card_exp_month
			);
			return $cu->cards->create($card)->__toArray(true);
		} catch (Exception $e) {
			Yii::log($e->getMessage(), 'payment_error');
			return false;
		}
	}
	
	private function _payjpCreateSubscription($payjp_customer_id, $payjp_plan_id, $client_id, $client_subscription_plan_id){
		// debug
// 		return json_decode('{
// 							  "canceled_at": null,
// 							  "created": 1433127983,
// 							  "current_period_end": 1435732422,
// 							  "current_period_start": 1433140422,
// 							  "customer": "'.$payjp_customer_id.'",
// 							  "id": "sub_567a1e44562932ec1a7682d746e0",
// 							  "livemode": false,
// 							  "metadata": null,
// 							  "object": "subscription",
// 							  "paused_at": null,
// 							  "plan": {
// 							    "amount": 1000,
// 							    "billing_day": null,
// 							    "created": 1432965397,
// 							    "currency": "jpy",
// 							    "id": "'.$payjp_plan_id.'",
// 							    "livemode": false,
// 							    "metadata": {},
// 							    "interval": "month",
// 							    "name": "test plan",
// 							    "object": "plan",
// 							    "trial_days": 0
// 							  },
// 							  "resumed_at": null,
// 							  "start": 1433140422,
// 							  "status": "active",
// 							  "trial_end": null,
// 							  "trial_start": null,
// 							  "prorate": false
// 							}',true);
		try {
			return \Payjp\Subscription::create(array(
					"customer" => $payjp_customer_id,
					"plan" => $payjp_plan_id,
					'metadata' => array('client_id'=>$client_id,'client_subscription_plan_id'=>$client_subscription_plan_id)
			))->__toArray(true);
		} catch (Exception $e) {
			Yii::log($e->getMessage(), 'payment_error');
			return false;
		}
	}
	
	/**
	 * @author matico
	 * @param int $client_id
	 * @param int $employee_id employee logined currently
	 * @param int $client_subscription_plan_id
	 * @param string $payjp_plan_id
	 * @param string $card_number credit card info
	 * @param string $card_cvc_code credit card info
	 * @param string $card_exp_month credit card info
	 * @param string $card_exp_year credit card info
	 * @return false - failed, array - successed
	 * if success, the function will return data in format 
	 *     array(
	 *				'customer_data' => $payjp_customer, // customer data from pay.jp
	 *				'card_data' => $payjp_card, // card data from pay.jp
	 *				'subscription_data' => $payjp_subscription // subscription data from pay.jp
	 *		)
	 */
	public function registerSubscription($client_id, $employee_id, $client_subscription_plan_id, $payjp_plan_id, $card_number, $card_cvc_code, $card_exp_month, $card_exp_year){
		try {
			Yii::log('-- REGISTER SUBSCRIPTION --', 'payment');
			Yii::log(print_r(func_get_args(), true), 'payment');
			// 0. check current status
			$csp = PayjpClientSubscriptionPlan::model()->getById($client_subscription_plan_id);
			if (!$csp || !$csp['is_active']) {
				// not exist or not active
				Yii::log("NOTFOUND/NOT ACTIVE PayjpClientSubscriptionPlan[id={$client_subscription_plan_id}]", 'payment_error');
				return false;
			}
			if (!empty($csp['payjp_subscription_id'])) {
				// currently subscription already
				Yii::log("PayjpClientSubscriptionPlan[id={$client_subscription_plan_id}] IS ACTIVE currently", 'payment_error');
				return false;
			}
			//1. create customer from payjp
			$payjp_customer = $this->_payjpCreateCustomer($client_id, $employee_id);
			if (!$payjp_customer)
				return false;
			$payjp_customer_id = $payjp_customer['id'];
			//2. inculde card for customer
			$payjp_card = $this->_payjpCreateCardForCustomer($payjp_customer_id, $card_number, $card_cvc_code, $card_exp_month, $card_exp_year);
			if (!$payjp_card)
				return false;
			//3. join to subscription
			$payjp_subscription = $this->_payjpCreateSubscription($payjp_customer_id, $payjp_plan_id, $client_id, $client_subscription_plan_id);
			if (!$payjp_subscription)
				return false;
			$payjp_subscription_id = $payjp_subscription['id'];
			// next_renew_time = $payjp_subscription['current_period_end']
			$sub_data = array(
					'customer_data' => $payjp_customer,
					'card_data' => $payjp_card,
					'subscription_data' => $payjp_subscription
			);
			// 4. update DB
			PayjpClientSubscriptionPlan::model()->updateSub($client_subscription_plan_id, array('expire_time'=>$payjp_subscription['current_period_end'],'payjp_customer_id'=>$payjp_customer_id,'payjp_subscription_id'=>$payjp_subscription_id,'payjp_subscription_data'=>json_encode($sub_data)));
			Yii::log('-- REGISTER SUBSCRIPTION SUCCESSFULLY --', 'payment');
			return $sub_data;
		} catch (Exception $e) {
			Yii::log($e->getMessage(), 'payment_error');
			return false;
		}
	}
	private function _webhookGetEventData(){
		return @json_decode(file_get_contents('php://input'), true);
	}
	
	private function _webhookGetEventType(&$callback_data){
		return isset($callback_data['type'])?$callback_data['type']:null;
	}
	// HTTP_X_PAYJP_WEBHOOK_TOKEN
	private function _webhookValidateToken(&$callback_data){
		$webhook_token = isset($_SERVER['HTTP_X_PAYJP_WEBHOOK_TOKEN'])?$_SERVER['HTTP_X_PAYJP_WEBHOOK_TOKEN']:null;
		return $webhook_token && ($webhook_token===$this->webhook_token)?true:false;
	}
	//https://pay.jp/docs/webhook
	/**
	 * Process webhook callback from pay.jp
	 * 
	 * @author matico
	 * @return false|mixed false - failed | mixed - successed
	 */
	public function processCallback(){
		Yii::log('-- PAYJP CALLBACK --', 'payment');
		$callback_data = $this->_webhookGetEventData();
		// 1. validate webhook token
		if (!$this->_webhookValidateToken($callback_data))
			return false;
		Yii::log('Callback data: ' . print_r($callback_data, true), 'payment');
		if (!$callback_data) //no data
			return false;
		switch ($this->_webhookGetEventType($callback_data)) {
			case 'charge.succeeded': // 2. process charge.succeeded
				return $this->_processCallbackChargeSuccessed($callback_data);
			case 'charge.failed': // 3. charge.failed
				if ($callback_data['data']['subscription']) // 3.1 - renew failed
					return $this->_processCallbackSubscriptionRenewFailed($callback_data);
				else // 3.2 charge.failed for the first charge
					return $this->_processCallbackChargeFailed($callback_data);
			case 'subscription.renewed': // 4. subscription.renewed success
				return $this->_processCallbackSubscriptionRenewed($callback_data);
			default:
				return false;
		}
		Yii::log('-- PAYJP CALLBACK DONT PROCESS --', 'payment');
	}
	
// 	{
// 		"object": "event",
// 		"livemode": false,
// 		"id": "evnt_xxxxxxxxxxxxxxxxxxxxxxxxxxx",
// 		"data": {
// 		"object": "charge",
// 		"livemode": false,
// 		"metadata": {},
// 		"id": "ch_xxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
// 		"amount": 50,
// 		"amount_refunded": 0,
// 		"currency": "jpy",
// 		"captured": true,
// 		"captured_at": 1492225927,
// 		"customer": null,
// 		"description": null,
// 		"expired_at": null,
// 		"failure_code": null,
// 		"failure_message": null,
// 		"paid": true,
// 		"refunded": false,
// 		"refund_reason": null,
// 		"subscription": null,
// 		"created": 1492225927,
// 		"card": {
// 		"object": "card",
// 		"livemode": false,
// 		"metadata": {},
// 		"id": "car_xxxxxxxxxxxxxxxxxxxxxxxxxxxx",
// 		"brand": "Visa",
// 		"exp_year": 2020,
// 		"exp_month": 12,
// 		"name": null,
// 		"country": null,
// 		"customer": null,
// 		"cvc_check": "unchecked",
// 		"address_zip": null,
// 		"address_zip_check": "unchecked",
// 		"address_state": null,
// 		"address_city": null,
// 		"address_line1": null,
// 		"address_line2": null,
// 		"last4": 1234,
// 		"fingerprint": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
// 		"created": 1492225927
// 	}
// 	},
// 	"pending_webhooks": 0,
// 	"created": 1492225927,
// 	"type": "charge.succeeded"
// 	}
	
	private function _processCallbackChargeSuccessed(&$callback_data){
		Yii::log('-- CHARGE.SUCCESSED CALLBACK --', 'payment');
		return $this->_processCallbackCharge($callback_data);
	}
// 	{
// 		"object": "event",
// 		"livemode": false,
// 		"id": "evnt_xxxxxxxxxxxxxxxxxxxxxxxxxxx",
// 		"data": {
// 		"object": "charge",
// 		"livemode": false,
// 		"metadata": {},
// 		"id": "ch_xxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
// 		"amount": 50,
// 		"amount_refunded": 0,
// 		"currency": "jpy",
// 		"captured": false,
// 		"captured_at": null,
// 		"customer": null,
// 		"description": null,
// 		"expired_at": null,
// 		"failure_code": "card_declined",
// 		"failure_message": "Card declined",
// 		"paid": false,
// 		"refunded": false,
// 		"refund_reason": null,
// 		"subscription": null,
// 		"created": 1492225851,
// 		"card": {
// 		"object": "card",
// 		"livemode": false,
// 		"metadata": {},
// 		"id": "car_xxxxxxxxxxxxxxxxxxxxxxxxxxxx",
// 		"brand": "Visa",
// 		"exp_year": 2020,
// 		"exp_month": 12,
// 		"name": null,
// 		"country": null,
// 		"customer": null,
// 		"cvc_check": "unchecked",
// 		"address_zip": null,
// 		"address_zip_check": "unchecked",
// 		"address_state": null,
// 		"address_city": null,
// 		"address_line1": null,
// 		"address_line2": null,
// 		"last4": 1234,
// 		"fingerprint": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
// 		"created": 1492225851
// 	}
// 	},
// 	"pending_webhooks": 0,
// 	"created": 1492225851,
// 	"type": "charge.failed"
// 	}
	private function _processCallbackChargeFailed(&$callback_data){
		Yii::log('-- CHARGE.FAILED (FIRST CHARGE) CALLBACK --', 'payment');
		return $this->_processCallbackCharge($callback_data);
	}
	
	private function _processCallbackCharge(&$callback_data){
		Yii::log('PayjpTransaction::model()->updateCallbackDataForCharging', 'payment');
		sleep(3); // wait 3 seconds for surely having PayjpTransaction record.
		// Because LichLN request charge to payjp then save transaction to db
		// => update callback_data to PayjpTransaction table
		return PayjpTransaction::model()->updateCallbackDataForCharging($callback_data['data']['id'], json_encode($callback_data));
	}
	
// 	{
// 		"object": "event",
// 		"livemode": false,
// 		"id": "evnt_xxxxxxxxxxxxxxxxxxxxxxxxxxx",
// 		"data": {
// 		"object": "subscription",
// 		"livemode": false,
// 		"metadata": {},
// 		"id": "sub_xxxxxxxxxxxxxxxxxxxxxxxxxxxx",
// 		"canceled_at": null,
// 		"current_period_end": 1497496379,
// 		"current_period_start": 1494817979,
// 		"paused_at": null,
// 		"resumed_at": null,
// 		"customer": "cus_xxxxxxxxxxxxxxxxxxxxxxxxxxxx",
// 		"plan": {
// 		"object": "plan",
// 		"livemode": false,
// 		"metadata": {},
// 		"id": "pln_xxxxxxxxxxxxxxxxxxxxxxxxxxxx",
// 		"name": null,
// 		"amount": 0,
// 		"currency": "jpy",
// 		"interval": null,
// 		"billing_day": null,
// 		"trial_days": 0,
// 		"created": 1492225979
// 	},
// 	"start": null,
// 	"status": "active",
// 	"trial_start": null,
// 	"trial_end": null,
// 	"prorate": null,
// 	"created": 1492225979
// 	},
// 	"pending_webhooks": 0,
// 	"created": 1492225979,
// 	"type": "subscription.renewed"
// 	}
	private function _processCallbackSubscriptionRenewed(&$callback_data){
		Yii::log('-- SUBSCRIPTION.RENEWED CALLBACK --', 'payment');
		// 1. update PayjpClientSubscriptionPlan: expire_time & last_charge
		$payjp_subscription_id = $callback_data['data']['id'];
		$client_subscription_plan_id = isset($callback_data['data']['metadata']['client_subscription_plan_id'])?$callback_data['data']['metadata']['client_subscription_plan_id']:0;
		$client_id = isset($callback_data['data']['metadata']['client_id'])?$callback_data['data']['metadata']['client_id']:0;
		Yii::log('PayjpClientSubscriptionPlan::model()->updateSub', 'payment');
		PayjpClientSubscriptionPlan::model()->updateSub($client_subscription_plan_id, array('expire_time'=>$callback_data['data']['current_period_end'],'last_charge'=>$callback_data['data']['current_period_start']));
		// 2. log transaction
		Yii::log('PayjpClientSubscriptionPlan::model()->log', 'payment');
		PayjpTransaction::model()->log(TRANSACTION_TYPE_RENEW, $client_id, $client_subscription_plan_id, $callback_data['data']['plan']['amount'], $callback_data['data']['current_period_start'], TRANSACTION_CHARGE_SUCCESS_STATUS, null, json_encode($callback_data));
		Yii::log('-- SUBSCRIPTION.RENEWED CALLBACK SUCCESSED--', 'payment');
		return true;
	}
	
	private function _processCallbackSubscriptionRenewFailed(&$callback_data){
		Yii::log('-- CHARGE.FAILED (RENEW FAILED) CALLBACK --', 'payment');
		$payjp_subscription_id = $callback_data['data']['subscription'];
		$csp = PayjpClientSubscriptionPlan::model()->getByPayjpSubscriptionId($payjp_subscription_id);
		if (!$csp)
			return false;// not exist PayjpClientSubscriptionPlan
		// 1. update PayjpClientSubscriptionPlan: is_active=0
			PayjpClientSubscriptionPlan::model()->updateSub($csp->id, array('is_active'=>SUBSCRIPTION_DEACTIVE_STATUS));
		// 2. log transaction
		Yii::log('PayjpClientSubscriptionPlan::model()->log', 'payment');
		PayjpTransaction::model()->log(TRANSACTION_TYPE_RENEW, $csp->client_id, $csp->id, $callback_data['data']['amount'], $callback_data['created'], TRANSACTION_CHARGE_FAILED_STATUS, null, json_encode($callback_data));
		// 3. send mail to notify to admin
		$this->_sendMailRenewFailed();
		return true;
	}
	
	private function _processCallbackSubscriptionPaused(&$callback_data){
		Yii::log('-- SUBSCRIPTION.PAUSED CALLBACK --', 'payment');
		$client_subscription_plan_id = isset($callback_data['data']['metadata']['client_subscription_plan_id'])?$callback_data['data']['metadata']['client_subscription_plan_id']:0;
		if (!$client_subscription_plan_id)
			return false;
		Yii::log('PayjpClientSubscriptionPlan::model()->updateSub', 'payment');
		PayjpClientSubscriptionPlan::model()->updateSub($client_subscription_plan_id, array('paused_at'=>$callback_data['created']));
		return true;
	}
	// @author LichNH
    public function createCharge($client_id,$client_subscription_plan_id,$token, $amount, $metadata = array()){
        try {
            $callback_data = array();
            $result = \Payjp\Charge::create(array(
                "card" => $token,
                "amount" => $amount,
                "currency" => "jpy",
                "capture" => false,
                'metadata' => $metadata
            ))->__toArray(true);
            //check paid success
            $paid = isset($result['paid']) ? $result['paid'] : '';
            $time = isset($result['created']) ? $result['created'] : 0;
            $payjp_charge_id = isset($result['id']) ? $result['id'] : '';
            if ($paid) {
                Yii::log('PayjpClientSubscriptionPlan::model()->log', 'payment');
                PayjpTransaction::model()->log(TRANSACTION_TYPE_FIRST_CHARGE, $client_id, $client_subscription_plan_id, $amount, $time, TRANSACTION_CHARGE_SUCCESS_STATUS, json_encode($result), json_encode($callback_data),$token,$payjp_charge_id);
                // CongMT: Set active for registered user in frontend
                $this->_registeredUserFrontendSetActive();
                PayjpClientSubscriptionPlan::model()->updateSub($client_subscription_plan_id, array('last_charge'=>$time,'first_charge'=>time()));
                Yii::log('-- SUBSCRIPTION.FIRST  --SUCCESS--', 'payment');
                return true;
            }
            Yii::log('PayjpClientSubscriptionPlan::model()->log', 'payment');
            PayjpTransaction::model()->log(TRANSACTION_TYPE_FIRST_CHARGE, $client_id, $client_subscription_plan_id, $amount, $time, TRANSACTION_CHARGE_FAILED_STATUS, json_encode($result), json_encode($callback_data),$token,$payjp_charge_id);
            Yii::log('-- SUBSCRIPTION.FIRST  --FAIL--', 'payment_error');
            // CongMT: send mail to admin
            $this->_sendMailFirstChargeFailed();
            return false;
        } catch (Exception $e) {
            Yii::log($e->getMessage(), 'payment_error');
            // CongMT: send mail to admin
            $this->_sendMailFirstChargeFailed();
            return false;
        }

    }
    
    private function _sendMailFirstChargeFailed(){
    	Yii::log(__FUNCTION__, 'payment');
    	return $this->_sendMail(MAIL_FROM, $this->_registeredUserGetEmail(), FIRST_CHARGE_FAILED_MAIL_SUBJECT, FIRST_CHARGE_MAIL_BODY);
    }
    
    private function _sendMailRenewFailed(){
    	Yii::log(__FUNCTION__, 'payment');
    	return $this->_sendMail(MAIL_FROM, $this->_registeredUserGetEmail(), RENEW_FAILED_MAIL_SUBJECT, RENEW_FAILED_MAIL_BODY);
    }
    
    private function _sendMail($from, $to, $subject, $body){
    	Yii::log('-- SEND MAIL --', 'payment');
    	if (!$to) return false; // email empty
    	Yii::log("From: $from", 'payment');
    	Yii::log("To: $to", 'payment');
    	Yii::log("Subject: $subject", 'payment');
    	Yii::log("Body: $body", 'payment');
    	Yii::import('ext.yii-mail.YiiMailMessage');
    	$message = new YiiMailMessage();
    	$message->subject = $subject;
    	$message->setBody($body, 'text');
    	$message->from =$from;
    	$message->addTo($to);
    	try {
    		return Yii::app()->mail->send($message);
    	} catch (Exception $e) {
    		Yii::log($e->getMessage(), 'payment_error');
    		return false;
    	}
    }
    
    private function _registeredUserGetEmail(){
    	try {
    		// 1. get from db UserRegistrationPayment.email
    		Yii::log('Get from DB', 'payment');
    		$user_reg_payment = UserRegistrationPayment::model()->find('client_id=:client_id',array(':client_id'=>$_SESSION['client_id']));
    		if ($user_reg_payment && $user_reg_payment->email) { // found from db
    			Yii::log("Email from DB: {$user_reg_payment->email}", 'payment');
    			return $user_reg_payment->email;
    		}
    		// not found => request api to front-end
    		$email = $this->_registeredUserFrontendGetEmail();
    		if (!$email)
    			return false;
    		Yii::log("Email from API: $email", 'payment');
    		// update to db for next request
    		UserRegistrationPayment::model()->updateAll(array('email'=>$email),'client_id=:client_id',array(':client_id'=>$_SESSION['client_id']));
    		return $email;
    	} catch (Exception $e) {
    		Yii::log($e->getMessage(), 'payment_error');
    		return false;
    	}
    }
    
    private function _registeredUserFrontendGetEmail(){
    	$user_detail = $this->_registeredUserFrontendGetDetail();
    	return !$user_detail || !isset($user_detail['email']) || !$user_detail['email']?null:$user_detail['email'];
    }
    
    private function _registeredUserFrontendGetDetail(){
    	Yii::log(__FUNCTION__, 'payment');
    	$api = API_GET_REGISTERED_USER_DETAIL.$_SESSION['client_cd'];
    	Yii::log("API: $api", 'payment');
    	try {
    		$user_detail = @json_decode(@file_get_contents($api), true);
    		Yii::log('API Response:' . print_r($user_detail, true), 'payment');
    		if(!$user_detail || !$user_detail['status']) return false; // not found user
    		return isset($user_detail['data'])?$user_detail['data']:null;
    	} catch (Exception $e) {
    		Yii::log($e->getMessage(), 'payment_error');
    		return false;
    	}
    }
    
    private function _registeredUserFrontendSetActive(){
    	Yii::log(__FUNCTION__, 'payment');
    	$api = API_GET_REGISTERED_USER_DETAIL.$_SESSION['client_cd'].'/'.API_KEY;
    	Yii::log("API: $api", 'payment');
    	try {
    		$resp = @file_get_contents($api);
    		Yii::log("API Response: $resp", 'payment');
    		return true;
    	} catch (Exception $e) {
    		Yii::log($e->getMessage(), 'payment_error');
    		return false;
    	}
    }
}