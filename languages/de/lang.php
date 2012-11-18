<?php
/**
  *@package goma framework
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2012 Goma-Team
  * last modified: 18.11.2012
*/

defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

$lang = array(
	/*  OTHER THINGS*/
	"okay"											=> "OK",
	"confirm"										=> "Bestätigen...",
	"prompt"										=> "Text eingeben...",
	"mysql_error"                                 	=> "<div class=\"error\"><strong>Fehler</strong><br />Zugriff fehlgeschlagen!</div>",
	"mysql_connect_error"							=> "Keine Verbindung zur Datenbank",
	'mysql_error_small'								=> "Zugriff fehlgeschlagen!",
	"visitor_online_multi"                          => "Es sind %user% Besucher online!",
	"visitor_online_1"                             	=> "Es ist ein Besucher online!",
	"livecounter"									=> "Online-Besucher-Z&auml;hler",
	"visitors"                                     	=> "Besucher",
	"page_views"                                   	=> "Seitenaufrufe",
	"username"                                     	=> "Benutzername",
	"email_or_username"								=> "E-Mail oder Benutzername",
	"register_disabled"								=> "Die Registrierung ist auf dieser Seite leider nicht verfügbar.",
	"db_edit"                                      	=> "Datenbank",
	"more"                                         	=> "mehr",
	'content'										=> "Inhalt",
	'version'										=> "Version",
	"installed_version"								=> "Installierte Version",
	"password"                                    	=> "Passwort",
	'smilies'										=> "Smileys",
	"edit_password_ok"                             	=> "Das Passwort wurde ge&auml;ndert!",
	"homepage"                                     	=> "Startseite",
	"page"											=> "Seite",
	"switch_view_edit_off"							=> "Bearbeitungsmodus deaktivieren",
	"switch_view_edit_on"							=> "Bearbeitungsmodus aktivieren",
	"switch_ansicht"                               	=> "Ansicht wechseln",
	'support'										=> "Support",
	"pic"                                          	=> "Bild",
	'description'                                  	=> "Beschreibung",
	'expertmode'									=> "Expertenmodus",
	"smiliecode"                                   	=> "Smiliecode",
	"smilie_add"                                   	=> "Smilie hinzuf&uuml;gen",
	"smilie_title"									=> "Name des Smilies",
	"register"                                     	=> "Registrieren",
	"administration"                               	=> "Administration",
	"manage_website"								=> "Webseite verwalten",
	'default_admin'									=> "Normale Administration",
	"my_account"                                   	=> "Mein Account" ,
	"really welcome"                               	=> "Herzlich Willkommen",
	"perform_login"                                	=> "Einloggen",
	"edit_page"                                    	=> "Seite bearbeiten",
	"edit_this_page"								=> "Aktuelle Seite bearbeiten",
	"logout"                                       	=> "Ausloggen",
	"email"                                        	=> "E-Mail",
	"name"                                         	=> "Name",
	"str"                                          	=> "Stra&szlig;e",
	"ort"                                          	=> "Ort",
	"imprint"                                      	=> "Impressum",
	"contact"                                      	=> "Kontakt",
	"user_groups"									=> "Benutzer & Gruppen",
	"users"											=> "Benutzer",
	"groups"										=> "Gruppen",
	"upload file"                                  	=> "Datei hochladen",
	"subject"                                      	=> "Betreff",
	"message"                                      	=> "Nachricht",
	"mandatory field"                              	=> "Pflichtfeld",
	"rate_site"                                    	=>"Seite bewerten",
	"php_not_run"                                  	=> "<div class=\"error\"><strong>Fehler</strong><br />Kann PHP code nicht ausf&uuml;hren!!</div>",
	"mail_not_sent"                                 => "Die E-Mail konnte nicht versendet werden!",
	"sent"                                         	=> "Die E-Mail wurde gesendet!",
	"error"                                        	=> "Fehler",
	"whole"                                        	=> "Insgesamt",      // like Insgesamt x Stimmen
	"votes"                                        	=> "Stimme(n)",
	"vote"                                         	=> "abstimmen!",
	"kontakt_email"                                	=> "Ihre E-Mail-Adresse",
	"back"                                         	=> "Zur&uuml;ck",
	"new_messages"                                 	=> "Neue Nachrichten",
	"user_online"                                  	=> "Benutzer online:",
	"you_are_here"                                 	=> "Sie befinden sich hier",
	"stats"                                        	=> "Statistiken",
	"hits"                                         	=> "Aufrufe",
	"edit"                                         	=> "bearbeiten",
	"yes"                                          	=> "Ja",
	"no"                                           	=> "Nein",
	"code"                                         	=> "Code",
	/* ACCOUNT START */
	"signatur"                                     	=> "Signatur",
	"passwords_not_match"                          	=> "Die Passw&ouml;rter stimmen nicht &uuml;berein!",
	"password_wrong"                               	=> "Das Passwort ist falsch!",
	"new_password"                                 	=> "Neues Passwort",
	"old_password"                                 	=> "Altes Passwort",
	/* ACCOUNT END REGISTRATION START */
	"register_code_ok"                             	=> "Der Registrierungscode ist richtig!",
	"repeat"                                       	=> "Wiederholen",
	"reg_code"                                     	=> "Registrierungscode",
	"register_code_wrong"                          	=> "Der Registrierungscode ist falsch!",
	"register_username_bad"                        	=> "Der Benutzername ist bereits vergeben!",
	"register_username_free"                       	=> "Der Benutzername ist frei!",
	"user_creat_bad"                               	=> "Fehler bei anlegen des Benutzers!",
	/* ADMIN AREA  */
	/* boxes START  */
	"new_box"                                      => "Neue Box erstellen",
	"edit_box"                                     => "Diese Box bearbeiten",
	"del_box"                                      => "Die Box l&ouml;schen",
	"ansicht_page"                                 => "Sie sehen die Seite als ",
	"box_edited_ok"                                => "Die Box wurde erfolgreich bearbeitet",
	"close_window"                                 => "Fenster schliessen",
	"box_title"                                    => "Titel der Box (leer lassen um Titel auszublenden):",
	"confirm_delbox"                               => "Box wirklich l&ouml;schen",
	/* umfrage START */
	"question"                                     	=> "Frage:",
	"answers"                                      	=> "Antworten (jede Zeile eine Antwort)",
	"reset"                                        	=> "Zur&uuml;cksetzen",
	'result'										=> "Ergebnis",
	/* umfrage END */
	"border"                                       	=> "Rahmen",
	/* BOX START */
	"add_box"                                      => "Box erstellen",
	"textarea"                                     => "Textbereich",
	"umfrage"                                      => "Umfrage",
	"php_script"                                   => "PHP Skript",
	"min_rights"                                   => "Mindestrechte",
	/* ADDBOX END */
	/* BOXES END */
	"time"                                         	=> "Zeit",
	"update_ok"                                    	=> "Die Operation wurde erfolgreich ausgef&uuml;hrt!",   // if updated
	/* SETTINGS START */
	"settings"                                     	=> "Einstellungen",       // setting for admin
	"edit_settings"                                	=> "Einstellungen bearbeiten",
	"registercode"                                 	=> "Registrierungscode",
	"register_code"                                	=> "Registrierungscode",
	"title"                                        	=> "Titel",
	"title_page"									=> "Titel der Seite",
	/* SETTINGS END TPL START */
	"available_styles"                             	=> "Verf&uuml;gbare Designs",
	"available_adminstyles"                        	=> "Verf&uuml;gbare Admin-Designs",
	/* TPL END USERGROUPS START */
	"rights"                                       	=> "Rechte",
	"rights_manage"									=> "Rechte verwalten",
	"delete"                                       	=> "l&ouml;schen",
	"group"											=> "Gruppe",
	/* USERGROUPS END USERS START */
	"rank"                                         	=> "Rang",
	"status"                                       	=> "Status",
	"edit_rights"                                  	=> "Rechte &auml;ndern",
	"user_del_confirm"                            	=> "Wollen Sie diesen Benutzer wirklich l&ouml;schen?",
	"user"											=> "Benutzer",
	/* USERS END MAINBAR START */
	"mainbar"                                      	=> "Hauptmen&uuml;",
	"all"                                          	=> "Alle",
	"open_in"                                      	=> "&Ouml;ffnen in",
	"same_window"                                  	=> "Selbem Fenster",
	"new_window"                                   	=> "Neuem Fenster",
	"url"                                          	=> "Adresse",
	/* MAINBAR END SITES START */
	"rename"                                       	=> "umbenennen",
	'site'											=> "Seite",
	"text"                                         	=> "Text",
	"menupoint_title"								=> "Navigations-Titel",
	"menupoint_title_info"							=> "Titel der Seite, der in der Navigation angezeigt wird.",
	"js_disable_editor"                            	=> "Sie m&uuml;ssen Javascript aktivieren um den Editor zu benutzen!",
	"overview"                                     	=> "&Uuml;bersicht",
	"own_css"                                      	=> "Eigenes CSS (F&uuml;r Profis)",
	"less_rights"                                  	=> "Sie haben hier leider keinen Zugriff!",
	"wrong_login"                                  	=> "<strong>Fehler</strong><br />Falscher Benutzername oder falsches Passwort!",
	"edit_profile"                                 	=> "Profil ändern",
	"show_profile"                                 	=> "Profil betrachten",
	"profile"										=> "Profil",
	"save"                                         	=> "Speichern",
	"cancel"                                       	=> "Abbrechen",
	"site_exists"                                   => "Eine Seite mit diesem Dateinamen existiert schon!",
	"userstatus_admin"                             	=> "Benutzerstatus: <strong>Adminstrator</strong>",
	'lang'                                         	=> "Sprache",
	'switchlang'									=> "Sprache wechseln",
	"select_lang"									=> "Wählen Sie Ihre Sprache",
	'filemanager'                                  	=> "Dateimanager",
	"del_cache"                                    	=> "Cache leeren",
	"cache_deleted"									=> "Der Cache wurde erfolgreich geleert.",
	"cache_del_info"								=> "Der Cache ist ein Zwischenspeicher für einige Temporäre angelegte Dateien, um die Geschwindigkeit zu optimieren. Wenn Sie den Cache leeren, generiert Goma diese Dateien neu.",
	"login"                                        	=> "Anmeldung" ,
	/* infos */
	"usergroups_info"								=> "<h3>Willkommen</h3>Hier k&ouml;nnen Sie Benutzer und Gruppen verwalten. W&auml;hlen Sie im Baum, was Sie bearbeiten möchten.",
	"email_info"									=> "Sie k&ouml;nenn mehrere E-Mail-Adressen mit Komma trennen.",
	"email_correct_info"							=> "Diese E-Mail-Adresse sollte richtig sein und Ihnen gehören.",
	'dragndrop_info'								=> "Ziehen Sie die Elemente, um sie zu sortieren.",
	"noscript"                                      => "Bitte aktivieren Sie JavaScript, um diese Funktion zu nutzen!",
	"checked"                                       => "Ausgew&auml;hlte",
	/* edit tue 3.11.09 11:10 */
	'login_locked'                                  => "Der Benutzeraccount wurde gesperrt!",
	"login_not_unlocked"							=> "Der Benutzeraccount wurde noch nicht aktiviert!",
	"not_unlocked"									=> "Noch nicht aktiviert",
	"page_wartung"                                  => "Diese Seite befindet sich gerade im Wartungsmodus! Bitte kommen Sie sp&auml;ter wieder!",
	"wartung"                                       => "Wartungsmodus aktiv",
	"site_status"                                   => "Status der Website",
	"normal"                                        => "Normal",
	"phone"                                         => "Telefon",
	"settings_normal"                               => "Allgemeine Einstellungen",
	"editor_toggle"                                 => "Editor an/aus",
	"keywords"                                      => "Website-Schlagwörter",
	"web_description"                               => "Website-Beschreibung",
	"site_keywords"									=> "Schlagwörter dieser Seite",
	"site_description"								=> "Beschreibung dieser Seite",
	"every"                                         => "Alle",
	"captcha"                                       => "Sicherheitscode",
	"captcha_wrong"                                 => "Der Sicherheitscode war falsch.",
	"captcha_reload"                                => "Neu Laden",
	'path'                                          => "Pfad",
	"successful_saved"                              => "Die Daten wurden erfolgreich gespeichert!",
	"successful_published"							=> "Die Daten wurden erfolgreich ver&ouml;ffentlicht!",
	'edit_settings'									=> "Einstellungen bearbeiten",
	'sites_edit'									=> "Seiten verwalten und anlegen",
	'admin_smilies'									=> "Smilies verwalten",
	'admin_boxes'									=> "Inhalt der Boxen verwalten",
	'save_error'									=> "Es ist ein Fehler aufgetreten!",
	// lost password lanuages (lp_)
	'lp_key_not_right'								=> "Ihr Sicherheitsschl&uuml;ssel ist falsch oder abgelaufen.",
	'lost_password'									=> "Passwort vergessen",
	'lp_edit_pass'									=> "Passwort &auml;ndern",
	'lp_email_or_user'								=> "E-Mail-Adresse oder Benutzername",
	'lp_submit'										=> "Absenden",
	'lp_update_ok'									=> "Das Passwort wurde erfolgreich ge&auml;ndert!",
	'lp_not_found'									=> "Es wurde keine E-Mail-Adresse in der Datenbank gefunden, die auf diese Angaben passt.",
	'lp_sent'										=> "Sie haben schon eine Passwort-Vergessen-Anfrage abgesetzt.",
	'hello'											=> "Hallo",
	'lp_text'										=> "Sie haben die Passwort-Vergessen-Funktion unserer Seite benutzt.<br /> Falls Sie ihr Passwort vergessen haben, kopieren Sie bitte den unteren Link in ihre Adresszeile.<br /><strong>Klicken Sie nicht auf den Link, da die Datei&uuml;bertragung im Internet unsicher ist.</strong>",
	'lp_at'											=> " auf ",
	'lp_mfg'										=> "Mit freundlichen Gr&uuml;&szlig;en<br />Das Team",
	'lp_mail_sent'									=> "Die E-Mail wurde versendet." ,
	"lp_know_password"								=> "Sie wissen Ihr Passwort, sonst w&auml;ren Sie nicht eingeloggt.",
	"lp_deny"										=> "Rufen Sie folgende Adresse auf, wenn Sie dieses Verfahren nicht iniziert haben.",
	"lp_deny_okay"									=> "Verfahren erfolgreich abgebrochen!",
	/*admin nitices*/
	'maintenance'									=> "Der Wartungsmodus ist aktiv.",
	'pagetree'										=> "Seitenbaum",
	'meta'											=> "Suchmaschinen",
	'end'											=> "Endung",
	'parentpage'									=> "&Uuml;bergeordnete Seite",
	'no_parentpage'									=> "Übergeordnete Seite",
	"subpage"										=> "Untergeordnete Seite",
	'boxes_page'									=> "Seite mit Boxsystem",
	'view_site'										=> "Seite aufrufen",
	'show_in_search'								=> "In der Suche anzeigen",
	'redirect'										=> "Weiterleitung",
	'legend'										=> "Legende",
	'nomainbar'										=> "Kein Men&uuml;punkt",
	'mainbar'										=> "Mit Men&uuml;punkt",
	'just_content_page'								=> "Standard-Seite",
	'pagetype'										=> "Seitentyp",
	"boxtype"										=> "Box-Typ",
	'confirm_change_url'							=> "Wollen Sie den Dateinamen der Seite zu folgendem ändern:",
	'filename'										=> "Dateiname",
	'search'										=> "Suchen...",
	'close'											=> "Schliessen",
	'welcome_content_area'							=> "<h3>Willkommen</h3><p>Hier finden Sie den Inhalt ihrer Website. Dr&uuml;cken Sie auf eine Seite, um diese zu bearbeiten.</p>",
	'db_update'										=> "Datenbanktabellen validieren",
	'upload'										=> "Hochladen",
	'db_update_info'								=> "Goma generiert dadurch die Datenbank neu, das bedeutete nicht installiere Module werden installiert.",
	'online'										=> "Online",
	'offline'										=> "Offline",
	'locked'										=> 'Gesperrt',
	'not_locked'									=> "Nicht gesperrt",
	'edit_rights_groups'							=> "Wer darf diesen Datensatz bearbeiten?",
	"disabled"										=> "Deaktiviert",
	"active"										=> "Aktiviert",
	'page_not_active'								=> "Diese Seite ist nicht Aktiv!",
	'unload_lang'									=> "Soll die Seite wirklich verlassen werden?\n\nVorsicht: Die Änderungen wurden noch nicht gespeichert.\n\nDrücken Sie OK, um fortzusetzen oder Abbrechen,um auf der Aktuellen Seite zu bleiben.",
	"unload_not_saved"								=> "Vorsicht: Die Änderungen wurden noch nicht gespeichert.",
	"date_format"									=> "Datumsformat",
	"timezone"										=> "Zeitzone",
	"errorpage"										=> "Fehlerseite",
	"errorcode"										=> "Fehlercode",
	"general"										=> "Allgemein",
	"delete_confirm"								=> "Wollen Sie dieses Objekt wirklich löschen?",
	"pages_delete"									=> "Seiten l&ouml;schen",
	"pages_edit"									=> "Seiten bearbeiten",
	"pages_add"										=> "Seiten erstellen",
	// default DataObjects
	"dataobject_all"								=> "Vollzugriff auf andere Datenobjekte",
	"dataobject_edit"								=> "Daten von anderen Datenobjekten bearbeiten",
	"dataobject_add"								=> "Daten zu anderen Datenobjekten hinzufügen",
	"dataobject_delete"								=> "Daten von anderen Datenobjekten löschen",

	// right-mangement
	"following_groups"								=> "Folgende Gruppen",
	"editor_groups"									=> "Bearbeitungs-Gruppen",
	"viewer_groups"									=> "Betrachungsgruppen",
	"content_edit_users"							=> "Alle Bearbeitungs-Gruppen",
	"editors"										=> "Recht zur Bearbeitung",
	"viewer_types"									=> "Betrachter-Rechte",
	"everybody"										=> "Jeder",
	"login_groups"									=> "Jeder, der sich anmelden kann",
	"following_rights"								=> "Folgende Rechte",
	"version_available"								=> "Es ist eine neuere Version verfügbar.",
	"upgrade_to_next"								=> "N&auml;chste Version herunterladen",
	"version_current"								=> "Sie haben die aktuellste Version!",
	"Create"										=> "Erstellen",
	"no_result"										=> "Es wurden keine Daten gefunden",
	"delete_okay"									=> "Die Daten wurden erfolgreich gelöscht!",
	
	/*mobile*/
	"classic_version"								=> "Klassische Ansicht",
	"mobile_version"								=> "Mobile Ansicht",
	
	/* new groups*/
	"groups"										=> "Gruppen",
	
	/*check*/
	"checkall"										=> "Alle auswählen",
	"uncheckall"									=> "Alle abwählen",
	"checked"										=> "Ausgewählte",
	"edit_record"									=> "Eintrag bearbeiten",
	"add_record"									=> "Eintrag hinzufügen",
	"del_record"									=> "Eintrag löschen",
	"loading"										=> "Laden...",
	"waiting"										=> "Warten...",
	"view_website"									=> "Seite aufrufen",
	"dashboard"										=> "Übersicht",
	"visitors_by_day"								=> "Pro Tag",
	"visitors_by_month"								=> "Pro Monat",
	"tree"											=> "Baum",
	/* settings */
	"register_enabled"								=> "Registrierung",
	"register_require_email"						=> "Aktivierungs-E-Mail",
	"gzip"											=> "G-Zip",
	"style"											=> "Design",
	/* infos for settings */
	"registercode_info"								=> "Gäste werden vor der Registrierung nach diesem Code gefragt. Sie können dieses Feld leer lassen um die Abfrage zu deaktivieren.",
	"date_format_info"								=> "Datumsformat in der von PHP vorgeschriebenen Syntax. Die Bedeutung der Buchstaben finden Sie hier: <a href=\"http://php.net/manual/en/function.date.php\" target=\"_blank\">http://php.net/...date.php</a>",
	"gzip_info"										=> "G-Zip hilft Webseiten-Bandbreite zu sparen und beschleunigt die Ladezeit, jedoch benötigt der Server dann mehr Leistung.",
	"keywords_info"									=> "Schlagwörter helfen Suchmaschienen zu entscheiden, unter welchen Wörtern Sie gefunden werden wollen.",
	"description_info"								=> "Eine kleine Beschreibung Ihrer Seite.",
	"livecounter_info"								=> "Der Echtzeit-Besucherzähler zeigt Ihnen, wie viele Besucher aktuell auf der Seite sind. Die Webseite lädt jedoch etwas langsamer, da der Zähler mehr Leistung benötigt.",
	"register_enabled_info"							=> "Ermöglicht es Gästen sich zu registrieren",
	"register_require_email_info"					=> "Es wird bei der Registrierung eine Bestätigungs-E-Mail gesendet, um die E-Mail-Adresse zu validieren.",
	"sitestatus_info"								=> "Wenn sich die Seite im Wartungsmodus befindet, kann Sie nur von Mitgliedern angezeigt werden, die auch die Administration aufrufen können. Sie können diese Funktion z.B. verwenden, wenn Sie Wartungsarbeiten durchführen.",
	
	/* users and groups */
	"grouptype"										=> "Gruppentyp",
	
	/* global things */
	"add_data"										=> "Eintrag hinzuf&uuml;gen...",
	"download"										=> "Herunterladen",
	"submit"										=> "Absenden",
	
	/* contact-form */
	"contact_introduction"							=> "Sehr geehrter Seiten-Betreiber, <br /><br /> Die Folgenden Daten wurden &uuml;ber das Goma-Kontakt-Formulat abgesendet:",
	"contact_greetings"								=> "Beste Gr&uuml;&szlig;e<br /><br />Goma-Auto-Messenger",
	// register e-mail
	"thanks_for_register"							=> "Vielen Dank, dass Sie sich auf unserer Seite registriert haben.",
	"account_activate"								=> "Um Ihren Account zu aktivieren, kopiren Sie bitte auf folgenden Link in die Adress-Zeile Ihres Browsers:",
	"register_greetings"							=> "Beste Grüße<br /><br />Das Team",
	"register_ok_activate"							=> "Der Benutzer wurde erfolgreich registriert. Bitte sehen Sie in Ihrem E-Mail-Postfach nach, um den Benutzer zu aktivieren.",
	"register_ok"                                  	=> "Der Benutzer wurde erfolgreich aktiviert! Sie k&ouml;nnen sich jetzt anmelden!",
	"register_not_found"							=> "Es wurde kein Benutzer mit solchen Angaben gefunden.",
	/* boxes */
	"login_myaccount"								=> "Login / Mein Account",
	/* notice */
	"notice"										=> "Hinweis",
	"notice_site_disabled"							=> "Diese Seite ist deaktiviert.",
	"delete_selected"								=> "Ausgewählte löschen",
	
	"menupoint_add"									=> "Im Men&uuml; anzeigen",
	
	
	"database"										=> "Datenbank",
	
	"login_required"								=> "Sie müssen sich einloggen, um diese Seite zu sehen!",
	
	/**
	 * versions
	*/
	"preview"										=> "Vorschau",
	"publish"										=> "Ver&ouml;ffentlichen",
	"published_site"								=> "Veröffentlichte Seite",
	"published"										=> "Veröffentlicht",
	"draft"											=> "Entwurf",
	"draft_save"									=> "Entwurf speichern",
	"save_publish"									=> "Speichern & Veröffentlichen",
	"draft_delete"									=> "Entwurf verwerfen",
	"current_state"									=> "Aktueller Zustand",
	"browse_versions"								=> "Versionen durchsuchen",
	"open_in_new_tab"								=> "Auf neuer Seite öffnen",
	"revert_changes"								=> "Änderungen zurücksetzen",
	"revert_changes_confirm"						=> "Wollen Sie die Änderungen wirklich verwerfen und zur letzten Veröffentlichung zurückkehren?",
	"revert_changes_success"						=> "Die letzte Version wurde erfolgreich wiederhergestellt.",
	"unpublish"										=> "Veröffentlichung zurücknehmen",
	"unpublish_success"								=> "Die Seite ist nun nicht mehr veröffentlicht.",
	"state_publish"									=> "Veröffentlichung",
	"state_state"									=> "Speicherpunkt",
	"state_autosave"								=> "Auto-Speicher-Punkt",
	"state_current"									=> "Diese Version",
	"version_by"									=> "von",
	"version_at"									=> "am",
	"versions_javascript"							=> "Bitte aktivieren Sie JavaScript, um diese Funktion zu nutzen.",
	"done"											=> "Fertig",
	"restore"										=> "Wiederherstellen",
	"no_versions"									=> "Keine Version vorhanden",
	"versions_timeline"								=> "Zeitleiste",
	"backups"										=> "Sicherungen",
	
	"deleted_page"									=> "Gel&ouml;schte Seite",
	"edited_page"									=> "Geänderte Seite",
	
	/* goma welcome */
	"before_you_begin"								=> "Bevor Sie beginnen",
	"create_user"									=> "Benutzer erstellen...",
	"set_settings"									=> "Grundeinstellungen setzen...",
	"install_success"								=> "Ihre Goma Installation war erfolgreich. Bitte nehmen Sie sich noch ein wenig Zeit, um Ihre Grundeinstellungen zu setzen.",
	"next_step"										=> "Weiter zum nächsten Schritt",
	"goma_let's_start"								=> "Goma - Los Gehts!",
	
	"file_perm_error"								=> "Konnte Datei aufgrund fehlender Berechtigungen nicht öffnen!",
	
	"info"											=> "Informationen",
	"unknown"										=> "Unbekannt",
	
	/* update algorythm */
	
	"update"										=> "Aktualisierung",
	"updates"										=> "Aktualiserungen",
	"update_install"								=> "Aktualisieren",
	"update_file"									=> "Aktualisierungsdatei",
	"update_file_download"							=> "Aktualisierungsdatei herunterladen",
	"update_file_upload"							=> "Aktualisierungsdatei hochladen",
	"update_file_info"								=> "Die Aktualisierungsdatei hat meist die Endung GFS.",
	"update_type"									=> "Typ der Aktualisierung",
	"update_framework"								=> "Basissystem",
	"update_app"									=> "Applikation",
	"update_success"								=> "Die Aktualisierung wurde erfolgreich durchgef&uuml;hrt",
	"updatable"										=> "Aktualisierbar",
	"installable"									=> "Installierbar",
	"install_destination"							=> "Zielverzeichnis",
	"permission_error"								=> "Die Dateiberechtigungen reichen nicht aus, um das Update zu installieren.\n Bitte setzen Sie die Dateiberechtigungen so, dass PHP die Systemdateien überschreiben kann.",
	"update_version_error"							=> "Es ist bereits eine neuere Version installiert.",
	"update_version_newer_required"					=> "Es wird eine neuere Version dieser Software erwartet, um zu aktualisieren. Aktualisieren Sie erst auf folgende Version:",
	"updateSuccess"									=> "Die Aktualisierung war erfolgreich!",
	"update_frameworkError"							=> "Die Aktualisierung erwartet eine neuere Version des Goma-Basissystems. Bitte aktualisieren Sie erst das Goma-Basis-System.",
	"update_expansion"								=> "Add-On",
	"update_changelog"								=> "Änderungen",
	"update_no_available"							=> "Es sind keine Aktualisierungen verfügbar!",
	"update_checking"								=> "Prüfe auf Aktualisierung",
	"update_upload"									=> "Aktualisierungsdatei hochladen",
	"update_connection_failed"						=> "Die Verbindung zum Goma-Server ist fehlgeschlagen. Bitte aktivieren Sie Allow-URL-fopen in Ihren PHP-Einstellungen. goma-cms.org könnte auch offline sein.",
	
	// permissions
	"admins"										=> "Administratoren",
	"invert_groups"									=> "Gruppen ausschliessen",
	"require_login"									=> "Sie müssen sich anmelden, um die Seite anzuzeigen.",
	
	/* distro-building */
	"distro_build"									=> "Version exportieren",
	"distro_changelog"								=> "Was haben Sie zur vorherigen Version geändert?",
	
	"install_advanced_options"						=> "Erweiterte Installationsoptionen",
	"install_option_preflight"						=> "postflight-PHP-Skript",
	"install_option_postflight"						=> "preflight-PHP-Skript",
	"install_option_getinfo"						=> "getinfo-PHP-Skript",
	
	"install_invalid_file"							=> "Sie können diese Datei nicht installieren.",
	"install_not_update"							=> "Sie können diese Datei nur installieren, da diese Software noch nicht installiert ist.",
	
	
	"gfs_invalid"									=> "Das GFS-Archiv ist beschädigt oder kann nicht geöffnet werden.",
	
	"inherit_from_parent"							=> "Von übergeordnetem Objekt übernehmen",
	"hierarchy"										=> "Hierarchie",
	
	"full_admin_permissions"						=> "Volle Admin-Rechte",
	"signed"										=> "Signatur",
	"signed_true"									=> "Dieses Paket wurde vom Goma-Team geprüft und ist freigegeben!",
	"signed_false"									=> "Dieses Paket wurde nicht vom Goma-Team geprüft. Die Installation erfolgt auf eigene Gefahr!",
	
	/* install */
	"install.folder"		=> "Installationsverzeichnis",
	"install.folder_info"	=> "Goma legt auf der Festplatte ihres Servers ein Verzeichnis mit diesem Namen an, um die Daten in dieses Verzeichnis zu schreiben",
	"install.db_user"		=> "Datenbankbenutzer",
	"install.db_host"		=> "Datenbankserver",
	"install.db_host_info"	=> "Meist localhost",
	"install.db_name"		=> "Datenbankname",
	"install.db_password"	=> "Datenbankpasswort",
	"install.table_prefix"	=> "Tabellen-Pr&auml;fix",
	"install.folder_error"	=> "Der Ordner existiert bereits oder ist auf Liste der nicht erlaubten Ordnernamen.",
	"install.sql_error"		=> "Die Datenbankeinstellungen scheinen nicht korrekt zu sein.",
	
	"install.install"		=> "Installieren",
	
	/* date for ago */
	
	"ago.seconds"			=> "Vor %d Sekunden",
	"ago.minute"			=> "Vor etwa einer Minute",
	"ago.minutes"			=> "Vor %d Minuten",
	"ago.hour"				=> "Vor etwa einer Stunde",
	"ago.hours"				=> "Vor %d Stunden",
	"ago.day"				=> "Vor etwa einem Tag",
	"ago.days"				=> "Vor %d Tagen",
	
	"domain"				=> "Domain",
	"restoreType"			=> "Wiederherstellungsmethode",
	"restore_currentapp"	=> "Aktuelle Installation",
	"restore_newapp"		=> "Neue Installation",
	"restore_sql_sure"		=> "Sind Sie sicher, dass Sie die Datenbank auf den Stand vom %s zurücksetzen wollen?",
	"restore_destination"	=> "Wiederherstellungsverzeichnis",
	
	"error_page_self"		=> "Sie können eine Seite nicht unter sich selbst anordnen.",
	
	"virtual_page"			=> "Klon-Seite",
	"requireEmailField"		=> "E-Mail ist erforderlich",
	
	"author"				=> "Autor",
	
	"flush_log"				=> "Log-Dateien löschen",
	"flush_log_success"		=> "Alle Log-Dateien wurde erfolgreich gelöscht.",

	"tablefield_out_of"		=> "von",

	"history"				=> "Änderungs-Geschichte",
	
	// history
	"h_pages_update"		=> '$user bearbeitete die Seite <a href="$pageUrl">$page</a> am $date.',
	"h_pages_publish"		=> '$user veröffentlichte die Seite <a href="$pageUrl">$page</a> am $date.',
	"h_pages_remove"		=> '$user löschte die Seite <a href="$pageUrl">$page</a> am $date.',
	"h_pages_create"		=> '$user erstellte die Seite <a href="$pageUrl">$page</a> am $date.',
	
	"h_settings"			=> '$user aktualisierte die <a href="$url">Einstellungen</a> dieser Seite am $date.'
);