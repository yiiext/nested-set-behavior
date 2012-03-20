<?php
return array(
	'basePath'=>dirname(__FILE__).'/..',
	'extensionPath'=>dirname(__FILE__).'/../..',

	'import'=>array(
		'application.models.*',
	),

	'components'=>array(
		'fixture'=>array(
			'class'=>'system.test.CDbFixtureManager',
			'basePath'=>dirname(__FILE__).'/../fixtures',
		),
		'db'=>array(
			'connectionString'=>'mysql:host=localhost;dbname=test',
			'username'=>'root',
			'password'=>'',
			'charset'=>'utf8',
		),
	),
);
