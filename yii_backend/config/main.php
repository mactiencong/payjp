<?php

// This is the main Web application configuration. Any writable
// CWebApplication properties can be configured here.
return array(
		'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
		'name'=>'My Web Application',
		//'theme' => 'abound',
		
		// preloading 'log' component
		'preload'=>array('log'),
		
		// autoloading model and component classes
		'import'=>array(
				'application.models.*',
				'application.components.*',
				'application.components.widgets.*',
				'application.extensions.*'
		),
		// application components
		'components'=>array(
				
				'log'=>array(
						'class'=>'CLogRouter',
						'routes'=>array(
								array(
										'class'=>'CFileLogRoute',
										'levels'=>'error, warning',
								),
								array(
										'class'=>'CFileLogRoute',
										'levels'=>'payment,payment_error',
										'logFile'=>'payment' . date('Ymd') . '.log'
								)
						),
				),
				'payjp' => array(
						'class' => 'PayJP',
						'api_key' => 'sk_test_64c236d440a78c05ee208f02',
						'webhook_token'=>'whook_b475ba38725a1623c4aad5d449'
				)
		)
		
);
