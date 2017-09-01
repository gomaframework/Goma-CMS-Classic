<?php

Director::addRules(array(
	"install//\$Action" => "InstallController",
	""					=> "HomePageController"
));