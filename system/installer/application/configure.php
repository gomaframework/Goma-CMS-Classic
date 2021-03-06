<?php defined("IN_GOMA") OR die("Call application.php --configure\n");

if(!isCommandLineInterface()) return;

$data = getCommandLineArgs();

$required = array("directory", "mysql.user", "mysql.password", "mysql.db", "user", "pwd");
foreach($required as $info) {
    if(!isset($data[$info])) {
        echo ("Configure requires all required parameters. " . implode(", ", $required) . "\n");
        exit(1);
    }
}

if(!is_dir(ROOT . $data["directory"])) {
    die("Directory must be existing\n");
}

writeSystemConfig(array(
    "apps" => array(
        0 => array(
            "directory" => $data["directory"]
        )
    )
));

$info = array(
    "db" => array(
        "user" => $data["mysql.user"],
        "pass" => $data["mysql.password"],
        "db"   => str_replace("-", "_", $data["mysql.db"]), // we use str_replace to fix issues with bamboo keys
        "host" => isset($data["mysql.host"]) ? $data["mysql.host"] : "127.0.0.1",
        "prefix" => isset($data["mysql.prefix"]) ? $data["mysql.prefix"] : "goma_"
    )
);
if(!SQL::test("mysqli", $info["db"]["user"], $info["db"]["db"], $info["db"]["pass"], $info["db"]["host"])) {
    echo ("Connection to MySQL-database could not be created.\n");
    exit(2);
}

writeProjectConfig($info, $data["directory"]);

if(file_exists($data["directory"] . "/temp/" . CLASS_INFO_DATAFILE)) {
    FileSystem::delete($data["directory"] . "/temp/" . CLASS_INFO_DATAFILE);
}

Core::addToLocalHook("onBeforeShutdown", function() use($data) {
    logging("Creating user with " . $data["user"] . " and password ***");

    DefaultPermission::checkDefaults();

    $group = DataObject::get_one(Group::class, array("type" => 2));

    $user = new User(array(
        "nickname" => $data["user"]
    ));
    $user->password = $data["pwd"];
    $user->groups()->add($group);
    $user->writeToDB(false, true);
});
