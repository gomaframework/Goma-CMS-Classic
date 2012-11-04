<?php
/**
  *@package goma framework
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2012 Goma-Team
  * last modified: 31.08.2012
*/

defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

$lang = array(
	/*  OTHER THINGS*/
	"okay"											=> "OK",
	"confirm"										=> "Confirm...",
	"prompt"										=> "Enter Text...",
	"mysql_error"                                 	=> "<div class=\"error\"><strong>Error</strong><br />Database-Query failed</div>",
	"mysql_connect_error"							=> "No Connection to the database",
	'mysql_error_small'								=> "Database-Query failed",
	"visitor_online_multi"                          => "There are %user% visitors online!",
	"visitor_online_1"                             	=> "There is one visitor online!",
	"livecounter"									=> "Realtime-Visitor-Counter",
	"visitors"                                     	=> "Visitors",
	"page_views"                                   	=> "Seitenaufrufe",
	"username"                                     	=> "username",
	"email_or_username"								=> "email or username",
	"register_disabled"								=> "Singing up is not avaialable on this site.",
	"db_edit"                                      	=> "database",
	"more"                                         	=> "more",
	'content'										=> "content",
	'version'										=> "version",
	"installed_version"								=> "Installed version",
	"password"                                     	=> "password",
	'smilies'										=> "emoticons",
	"edit_password_ok"                             	=> "The password was successfully changed!",
	"homepage"                                     	=> "home",
	"page"											=> "page",
	"switch_view_edit_off"							=> "Disable edit-mode",
	"switch_view_edit_on"							=> "Enable edit-mode",
	"switch_ansicht"                               	=> "Switch view",
	'support'										=> "support",
	"pic"                                          	=> "picture",
	'description'                                  	=> "description",
	'expertmode'									=> "Expert-mode",
	"smiliecode"                                   	=> "Emotion-code",
	"smilie_add"                                   	=> "Add an emotion...",
	"smilie_title"									=> "Name of emotion",
	"register"                                     	=> "Sign Up",
	"administration"                               	=> "administration",
	"manage_website"								=> "manage website",
	'default_admin'									=> "normal administration",
	"my_account"                                   	=> "my account" ,
	"really welcome"                               	=> "Welcome",
	"perform_login"                                	=> "Sign In",
	"edit_page"                                    	=> "edit page",
	"edit_this_page"								=> "edit this page",
	"logout"                                       	=> "logout",
	"email"                                        	=> "email",
	"name"                                         	=> "name",
	"str"                                          	=> "street",
	"ort"                                          	=> "town",
	"imprint"                                      	=> "imprint",
	"contact"                                      	=> "contact us",
	"user_groups"									=> "users & groups",
	"users"											=> "Users",
	"groups"										=> "Groups",
	"upload file"                                  	=> "upload a file",
	"plaese insert text into the mandatory field." 	=> "please enter text in all mandatory fields.",
	"subject"                                      	=> "subject",
	"message"                                      	=> "message",
	"mandatory field"                              	=> "mandatory field",
	"rate_site"                                    	=> "rate site..",
	"php_not_run"                                  	=> "<div class=\"error\"><strong>Error</strong><br />PHP-Code is not valid.</div>",
	"mail_not_sent"                                 => "There was an error sending the email.",
	"sent"                                         	=> "The email was successfully sent.",
	"error"                                        	=> "error",
	"whole"                                        	=> "overall",      // like Insgesamt x Stimmen
	"votes"                                        	=> "Vote(s)",
	"vote"                                         	=> "Vote!",
	"kontakt_email"                                	=> "Your email",
	"back"                                         	=> "back",
	"new_messages"                                 	=> "new messages",
	"user_online"                                  	=> "users online:",
	"you_are_here"                                 	=> "You're here",
	"stats"                                        	=> "Statistics",
	"hits"                                         	=> "Hits",
	"edit"                                         	=> "edit",
	"yes"                                          	=> "Yes",
	"no"                                           	=> "No",
	"code"                                         	=> "Code",
	/* ACCOUNT START */
	"signatur"                                     	=> "signature",
	"passwords_not_match"                          	=> "The passwords don't match.",
	"password_wrong"                               	=> "The password is wrong.",
	"new_password"                                 	=> "New password",
	"old_password"                                 	=> "Old Password",
	/* ACCOUNT END REGISTRATION START */
	"register_code_ok"                             	=> "The Registration-Code is correct!",
	"repeat"                                       	=> "repeat",
	"reg_code"                                     	=> "registration-code",
	"register_code_wrong"                          	=> "The Registration-Code is wrong!",
	"register_username_bad"                        	=> "The username is already assigned.",
	"register_username_free"                       	=> "The username is available.",
	"user_creat_bad"                               	=> "Error while creating.",
	/* ADMIN AREA  */
	/* boxes START  */
	"new_box"                                      => "Create new box..",
	"edit_box"                                     => "Edit this box",
	"del_box"                                      => "Delete this box",
	"ansicht_page"                                 => "You're current View:",
	"box_edited_ok"                                => "The box was successfully edited.",
	"close_window"                                 => "Close window",
	"box_title"                                    => "Title (let it black to disable it):",
	"confirm_delbox"                               => "Do you really want to delete this box?",
	/* umfrage START */
	"question"                                     	=> "question:",
	"answers"                                      	=> "answers (Every line one answer)",
	"reset"                                        	=> "reset",
	'result'										=> "result",
	/* umfrage END */
	"border"                                       	=> "border",
	/* BOX START */
	"add_box"                                      	=> "Create box",
	"textarea"                                     	=> "Text",
	"umfrage"                                      	=> "Poll",
	"php_script"                                   	=> "PHP Script",
	"min_rights"                                   	=> "Minimum permissions",
	/* ADDBOX END */
	/* BOXES END */
	"time"                                         	=> "Time",
	"update_ok"                                    	=> "The Operation was successfully completed.",   // if updated
	/* SETTINGS START */
	"settings"                                     	=> "Settings",       // setting for admin
	"edit_settings"                                	=> "Edit settings",
	"registercode"                                 	=> "Registration-code",
	"register_code"                                	=> "Registration-code",
	"title"                                        	=> "Title",
	"title_page"									=> "Title of the page",
	/* SETTINGS END TPL START */
	"available_styles"                             	=> "Available designs",
	"available_adminstyles"                        	=> "available designs for admin",
	/* TPL END USERGROUPS START */
	"rights"                                       	=> "permissions",
	"rights_manage"									=> "manage permissions",
	"delete"                                       	=> "delete",
	"group"											=> "group",
	/* USERGROUPS END USERS START */
	"rank"                                         	=> "rank",
	"status"                                       	=> "status",
	"edit_rights"                                  	=> "edit permissions",
	"user_del_confirm"                            	=> "Do you really want to delete this user?",
	"user"											=> "user",
	/* USERS END MAINBAR START */
	"mainbar"                                      	=> "mainbar",
	"all"                                          	=> "every",
	"open_in"                                      	=> "open in",
	"same_window"                                  	=> "same window",
	"new_window"                                   	=> "new window",
	"url"                                          	=> "URL",
	/* MAINBAR END SITES START */
	"rename"                                       	=> "rename",
	'site'											=> "page",
	"text"                                         	=> "text",
	"menupoint_title"								=> "navigation-title",
	"menupoint_title_info"							=> "This is the title of the page in the navigation.",
	"js_disable_editor"                            	=> "You have to activate JavaScript to user the Editor.",
	"overview"                                     	=> "Overview",
	"own_css"                                      	=> "Own CSS (For Developers)",
	"less_rights"                                  	=> "You don't have the permission to use this!",
	"wrong_login"                                  	=> "<strong>Error</strong><br />Unknowen user or wrong password!",
	"edit_profile"                                 	=> "edit profile",
	"show_profile"                                 	=> "show profile",
	"save"                                         	=> "save",
	"cancel"                                       	=> "cancel",
	"userstatus_admin"                             	=> "Status: <strong>Adminstrator</strong>",
	'lang'                                         	=> "Language",
	'switchlang'									=> "Change Language",
	"select_lang"									=> "Select Your Language",
	'filemanager'                                  	=> "filemanager",
	"del_cache"                                    	=> "empty cache",
	"cache_deleted"									=> "Cache is now empty.",
	"cache_del_info"								=> "If you empty the cache, Goma regenerates some files, which are cached, so the performance may go down, but data is after that latest.",
	"login"                                        	=> "Sign In" ,
	/* infos */
	"usergroups_info"								=> "<h3>Welcome</h3>Here you can manage Users & Groups. Click on one of the points in the tree to edit it.",
	"email_info"									=> "You can separate multiple e-mail-adresses with commas.",
	"email_correct_info"							=> "This email-adresse should be yours.",
	'dragndrop_info'								=> "Drag elements to sort them.",
	"noscript"                                      => "Please activate JavaScript to use this function!",
	"checked"                                       => "Selected",
	/* edit tue 3.11.09 11:10 */
	'login_locked'                                  => "The account is disabled by admin.",
	"login_not_unlocked"							=> "The account wasn't activated right now.",
	"not_unlocked"									=> "Not yet activated",
	"page_wartung"                                  => "This site is currently under maintenance. Please come back later.",
	"wartung"                                       => "maintenance active",
	"site_status"                                   => "status of the website",
	"site_exists"									=> "A page with this filename already exists.",
	"normal"                                        => "normal",
	"phone"                                         => "phone",
	"settings_normal"                               => "General settings",
	"editor_toggle"                                 => "Toggle editor",
	"keywords"                                      => "website-keywords",
	"web_description"                               => "website-description",
	"site_keywords"									=> "keywords of the site",
	"site_description"								=> "description of the site",
	"every"                                         => "every",
	"captcha"                                       => "security-code",
	"captcha_wrong"                                 => "The security-code was wrong",
	"captcha_reload"                                => "reload",
	'path'                                          => "path",
	"successful_saved"                              => "The data was successfully saved!",
	'sites_edit'									=> "edit and create sites",
	'admin_rating'									=> "edit rating",
	'admin_smilies'									=> "manage emotions",
	'admin_boxes'									=> "manage boxes",
	'save_error'									=> "There was an error!",
	// lost password lanuages (lp_)
	'lp_key_not_right'								=> "You're code is wrong!",
	'lost_password'									=> "forget password",
	'lp_edit_pass'									=> "edit password",
	'lp_email_or_user'								=> "username or email",
	'lp_submit'										=> "send",
	'lp_update_ok'									=> "the password was successfully changed",
	'lp_not_found'									=> "There was nobody found with your data.",
	'lp_sent'										=> "you submitted a request for resetting your password.",
	'hello'											=> "Hello",
	'lp_text'										=> "You submitted a request to reset your password. If you forgot your password, click on the following link.",
	'lp_at'											=> " on ",
	'lp_mfg'										=> "Best Regards<br />The team",
	'lp_mail_sent'									=> "The email was sent." ,
	"lp_know_password"								=> "You know your password.",
	"lp_deny"										=> "Click to the follwing link, if you didn't initiate this request.",
	"lp_deny_okay"									=> "The request was successfully deleted!",
	/*admin nitices*/
	'maintenance'									=> "Site is under maintenance.",
	'pagetree'										=> "Sitetree",
	'meta'											=> "Search-Engines",
	'end'											=> "Extension",
	'parentpage'									=> "Parent site",
	'no_parentpage'									=> "Root-Page",
	"subpage"										=> "Child-Page",
	'boxes_page'									=> "Page with box-system",
	'view_site'										=> "view site",
	'show_in_search'								=> "Show in search",
	'redirect'										=> "Weiterleitung",
	'legend'										=> "legend",
	'nomainbar'										=> "no menupoint",
	'mainbar'										=> "with menupoint",
	'just_content_page'								=> "Default-Page",
	'pagetype'										=> "pagetype",
	"boxtype"										=> "boxtype",
	'confirm_change_url'							=> "Do you want to edit the filename of this page to the following?",
	'filename'										=> "filename",
	'search'										=> "search...",
	'close'											=> "close",
	'welcome_content_area'							=> "<h3>Welcome</h3><p>Here you see the content of you page. Please select one page in the tree on the side to edit it.</p>",
	'db_update'										=> "validate database",
	'upload'										=> "upload",
	'db_update_info'								=> "If you validate the database, some not installed or not fully installed extensions will be installed.",
	'online'										=> "Online",
	'offline'										=> "Offline",
	'locked'										=> 'locked',
	'not_locked'									=> "unlocked",
	'edit_rights_groups'							=> "Who can edit this data?",
	"disabled"										=> "disabled",
	"active"										=> "active",
	'page_not_active'								=> "This page isn't active!",
	'unload_lang'									=> "Do you really wish to leave this page?\n\nAttention: The modifications hasn\\\'t saved, yet.\n\nPress OK to continue or Cancel to stay in this page.",
	"unload_not_saved"								=> "Attention: The modifications hasn\'t saved, yet.",
	"date_format"									=> "Date-format",
	"timezone"										=> "timezone",
	"errorpage"										=> "errorpage",
	"errorcode"										=> "errorcode",
	"general"										=> "general",
	"delete_confirm"								=> "Do you really want to delete this data?",
	"pages_delete"									=> "delete page",
	"pages_edit"									=> "edit page",
	"pages_add"										=> "create page",
	// default DataObjects
	"dataobject_all"								=> "Full access to various dataobjects.",
	"dataobject_edit"								=> "Edit data of various dataobjects.",
	"dataobject_add"								=> "Add data to various dataobjects.",
	"dataobject_delete"								=> "Delete data from various dataobjects.",

	// right-mangement
	"following_groups"								=> "Following groups",
	"editor_groups"									=> "edit-groups",
	"viewer_groups"									=> "viewer-groups",
	"content_edit_users"							=> "every edit-group",
	"editors"										=> "Permission to edit",
	"viewer_types"									=> "Permission to view",
	"everybody"										=> "Everyone",
	"login_groups"									=> "Everyone, who can login",
	"following_rights"								=> "Following Permissions",
	"version_available"								=> "There is a new version avaialable",
	"upgrade_to_next"								=> "Download update to next version",
	"version_current"								=> "Your version is the newest!",
	"Create"										=> "Create",
	"no_result"										=> "No result",
	"delete_okay"									=> "The data was successfully deleted!",
	
	/*mobile*/
	"classic_version"								=> "standard view",
	"mobile_version"								=> "mobile view",
	
	/* new groups*/
	"groups"										=> "Groups",
	
	/*check*/
	"checkall"										=> "Select all",
	"uncheckall"									=> "Unselect all",
	"checked"										=> "Selected",
	"edit_record"									=> "Edit Record",
	"add_record"									=> "Add Record",
	"del_record"									=> "Delete Record",
	"loading"										=> "loading...",
	"waiting"										=> "waiting…",
	"view_website"									=> "Go to Website",
	"dashboard"										=> "Dashboard",
	"visitors_by_day"								=> "per day",
	"visitors_by_month"								=> "per month",
	"tree"											=> "Tree",
	/* settings */
	"register_enabled"								=> "Registration",
	"register_require_email"						=> "Activation-mail",
	"gzip"											=> "g-zip",
	"style"											=> "Design",
	/* infos for settings */
	"registercode_info"								=> "Visitors have to know this code to register. Leave blank to disable it.",
	"date_format_info"								=> "Dateformat of PHP. You can find the meaning of the letters here: <a href=\"http://php.net/manual/en/function.date.php\" target=\"_blank\">http://php.net/...date.php</a>",
	"gzip_info"										=> "G-zip helps to save bandwidth of your webserver, but the webserver needs more performance to create the g-zipped files.",
	"keywords_info"									=> "Keywords help search-engines to decide, which words are important for you can your site.",
	"description_info"								=> "A little description of your site.",
	"livecounter_info"								=> "The Realtime-Visitor-Counter let you know how many visitors are exactly in this moment on your website, but it needs more performance.",
	"register_enabled_info"							=> "Visitors can register on your site.",
	"register_require_email_info"					=> "Send out validation-emails to validate email-adresse.",
	"sitestatus_info"								=> "If site is under maintenance, only admins can view the site.",
	
	/* users and groups */
	"grouptype"										=> "group-type",
	
	/* global things */
	"add_data"										=> "Add entry..",
	"download"										=> "Download",
	"submit"										=> "submit",
	
	/* contact-form */
	"contact_introduction"							=> "Dear Site-Administrator, <br /><br />The following data was submitted via your contact-form:",
	"contact_greetings"								=> "Best Regards<br /><br />Goma-Auto-Messenger",
	// register e-mail
	"thanks_for_register"							=> "Very thanks, that you registered on out site.",
	"account_activate"								=> "to activate your account, please click on the following link:",
	"register_greetings"							=> "Best Regards<br /><br />The team",
	"register_ok_activate"							=> "The user was successfully created. Please visit your email-inbox to activate the account.",
	"register_ok"                                  	=> "The user was successfully activated. you can login now!",
	"register_not_found"							=> "There is no user with such information.",
	/* boxes */
	"login_myaccount"								=> "Login / My Account",
	/* notice */
	"notice"										=> "Notice",
	"notice_site_disabled"							=> "This page is disabled.",
	"delete_selected"								=> "Delete Selected",
	
	"menupoint_add"									=> "Show in menus",
	
	"database"										=> "Database",
	
	"login_required"								=> "Please login to view this page!",
	
	/* versions */
	"preview"										=> "preview",
	"publish"										=> "publish",
	"published_site"								=> "published page",
	"published"										=> "published",
	"draft"											=> "draft",
	"draft_save"									=> "save draft",
	"save_publish"									=> "save & publish",
	"draft_delete"									=> "delete draft",
	"current_state"									=> "Current State",
	"open_in_new_tab"								=> "Open in new window",
	"browse_versions"								=> "Browse all Versions",
	"revert_changes"								=> "Revert Changes",
	"revert_changes_confirm"						=> "Do you really want to revert changes and go back to the last published version?",
	"revert_changes_success"						=> "The last version was recovered successfully.",
	"unpublish"										=> "Unpublish",
	"unpublish_success"								=> "The site was successfully unpublished.",
	"state_publish"									=> "Published version",
	"state_state"									=> "Saved version",
	"state_autosave"								=> "Auto-saved version",
	"state_current"									=> "This version",
	"version_by"									=> "by",
	"version_at"									=> "at",
	"versions_javascript"							=> "Please enable JavaScript to use this page.",
	"done"											=> "Done",
	"restore"										=> "Restore",
	"no_versions"									=> "No version found",
	"versions_timeline"								=> "Timeline",
	"backups"										=> "Backups",
	
	"deleted_page"									=> "Deleted page",
	"edited_page"									=> "Edited page",
	
	/* goma welcome */
	"before_you_begin"								=> "Before you begin...",
	"create_user"									=> "Create your Account...",
	"set_settings"									=> "Define your settings...",
	"install_success"								=> "Your installation of Goma was successful. Please follow these steps to finalize the installation.",
	"next_step"										=> "Next step",
	"goma_let's_start"								=> "Goma - Let's start!",
	
	"file_perm_error"								=> "Couldn't open file caused of missing permission!",
	
	"info"											=> "information",
	"unknown"										=> "unknown",
	
	/* update algorythm */
	
	"update"										=> "Update",
	"updates"										=> "Updates",
	"update_install"								=> "Install update",
	"update_file"									=> "Update-Archive",
	"update_file_download"							=> "Download Update",
	"update_file_upload"							=> "Upload Update",
	"update_file_info"								=> "The update-archive is most of the time a .gfs-file.",
	"update_type"									=> "Kind of update",
	"update_framework"								=> "framework",
	"update_app"									=> "application",
	"update_success"								=> "The update was successful.",
	"updatable"										=> "Updatable",
	"installable"									=> "Installable",
	"install_destination"							=> "folder",
	"permission_error"								=> "Goma can't write all files of the Update.\n Please set the Permissions of the System-files to be writable for PHP.",
	"update_version_error"							=> "There is already a newer version installed.",
	"update_version_newer_required"					=> "You need to install first another version to update to this version. Please upgrade to the following version first:",
	"update_expansion"								=> "expansion",
	"update_no_available"							=> "There are no updates available!",
	"update_checking"								=> "Checking for updates",
	
	"updateSuccess"									=> "Goma was updated successfully.",
	"update_frameworkError"							=> "This update requires a newer version of the Goma-Base-system. Please update the base-system first.",
	
	"update_changelog"								=> "Changelog",
	"update_upload"									=> "Upload update-file",
	"update_connection_failed"						=> "The connection to the goma-server failed. Please activate Allow-URL-fopen in your PHP-Settings. Goma-cms.org could be offline, too.",
	
	// permissions
	"admins"										=> "administrators",
	"invert_groups"									=> "invert groups",
	"require_login"									=> "You have to login to view this page.",
	
	
	/* distro-building */
	"distro_build"									=> "Build a Version",
	"distro_changelog"								=> "What has changed?",
	
	"install_advanced_options"						=> "Advanced install-options",
	"install_option_preflight"						=> "preflight-PHP-script",
	"install_option_postflight"						=> "postflight-PHP-script",
	"install_option_getinfo"						=> "getinfo-PHP-script",
	
	"install_invalid_file"							=> "This is not an installable file.",
	"install_not_update"							=> "You can install this software, only.",
	
	"gfs_invalid"									=> "The GFS-archive is damaged or not openable.",
	
	"inherit_from_parent"							=> "inherit from parent",
	"hierarchy"										=> "hierarchy",
	
	"full_admin_permissions"						=> "Full admin permissions",
	"signed"										=> "signature",
	"signed_true"									=> "This package was validated by the Goma-Team and is safe to install!",
	"signed_false"									=> "This package wasn't validated by the Goma-Team! Installing is at own risk!",

	
	/* install */
	"install.folder"			=> "Install-Folder",
	"install.folder_info"		=> "Goma created a folder on your server, where the files will be stored. The name of that directory should only contain lowercase letters.",
	"install.db_user"			=> "Database-User",
	"install.db_host"			=> "Database-Server",
	"install.db_host_info"		=> "Mostly localhost",
	"install.db_name"			=> "Database-Name",
	"install.db_password"		=> "Database-Password",
	"install.table_prefix"		=> "Table-prefix",
	"install.folder_error"		=> "The folder already exists or is not valid.",
	"install.sql_error"			=> "The Database-Server denied the query.",
	"install.instal"			=> "install",
	
	/* date for ago */
	
	"ago.seconds"			=> "%d seconds ago",
	"ago.minute"			=> "about one minute ago",
	"ago.minutes"			=> "%d minutes ago",
	"ago.hour"				=> "about one hour ago",
	"ago.hours"				=> "%d hours ago",
	"ago.day"				=> "about one day ago",
	"ago.days"				=> "%d days ago",
	
	"domain"				=> "Domain",
	"restoreType"			=> "Restoremethod",
	"restore_currentapp"	=> "Current installation",
	"restore_newapp"		=> "new installation",
	"restore_sql_sure"		=> "Are you sure, you want to restore the database to the state of %s?",
	"restore_destination"	=> "Restore-Folder",
	
	"error_page_self"		=> "You are not allowed to arrange a page under itself.",
	
	"virtual_page"			=> "Clone-page",
	"requireEmailField"		=> "email is required",
	
	"author"				=> "author",
	
	"flush_log"				=> "Delete log-files",
	"flush_log_success"		=> "All log-files were deleted successfully."


);