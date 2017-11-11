<?php
/* 1. Constant for mail content */
const MAIL_FROM = 'support@okagekk.com';

// renew failed
const RENEW_FAILED_MAIL_SUBJECT = 'Renew failed subject';
const RENEW_FAILED_MAIL_BODY = 'Renew failed body';


// first charge failed
const FIRST_CHARGE_FAILED_MAIL_SUBJECT = 'First charge failed subject';
const FIRST_CHARGE_MAIL_BODY = 'First charge failed body';

/* 2. API get registered user detail */
const API_KEY = 'shiugyfdit94589dyty460rf78ssfhi428fdnkt';
const API_GET_REGISTERED_USER_DETAIL = 'http://163.172.115.3:8686/user/detail/';
const API_SET_ACTIVE_USER_TO_FRONTEND = 'http://163.172.115.3:8686/user/active/';

/* 3. Others */
const TRANSACTION_CHARGE_SUCCESS_STATUS = 1;
const TRANSACTION_CHARGE_FAILED_STATUS = 0;

const TRANSACTION_TYPE_RENEW = 0;
const TRANSACTION_TYPE_FIRST_CHARGE = 1;

const SUBSCRIPTION_ACTIVE_STATUS = 1;
const SUBSCRIPTION_DEACTIVE_STATUS = 0;

const CLIENT_CARD_ACTIVE_STATUS = 1;
const CLIENT_CARD_DEACTIVE_STATUS = 0;