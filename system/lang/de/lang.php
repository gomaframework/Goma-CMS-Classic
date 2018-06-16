<?php
/**
 * @package		Goma\Lang\de
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version 	10.02.2015
 */

defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

$lang = array(
	/*  OTHER THINGS*/
	"okay"											=> "OK",
	"confirm"										=> "Bestätigen",
	"prompt"										=> "Text eingeben",
	"mysql_error"                                 	=> "<div class=\"error\"><strong>Fehler</strong><br />Zugriff fehlgeschlagen!</div>",
	"mysql_connect_error"							=> "Keine Verbindung zur Datenbank",
	'mysql_error_small'								=> "Zugriff fehlgeschlagen!",
	"visitor_online_multi"                          => "Es sind %user% Besucher online!",
	"visitor_online_1"                             	=> "Es ist ein Besucher online!",
	"visitors"                                     	=> "Besucher",
	"statistics"									=> "Statistiken",
	"page_views"                                   	=> "Seitenaufrufe",
	"username"                                     	=> "Benutzername",
	"email_or_username"								=> "E-Mail oder Benutzername",
	"register_disabled"								=> "Registrierung leider nicht verfügbar.",
	"db_edit"                                      	=> "Datenbank",
	"more"                                         	=> "mehr",
	'content'										=> "Inhalt",
	'version'										=> "Version",
	"installed_version"								=> "Installierte Version",
	"password"                                    	=> "Passwort",
	'smilies'										=> "Smileys",
	"edit_password"									=> "Passwort ändern",
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
	"permission_administration"						=> "Zugriff auf die Administration",
	"manage_website"								=> "Webseite verwalten",
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
	"crop_image"									=> "Bildausschnitt wählen",
	"subject"                                      	=> "Betreff",
	"message"                                      	=> "Nachricht",
	"mandatory field"                              	=> "Pflichtfeld",
	"rate_site"                                    	=>"Seite bewerten",
	"php_not_run"                                  	=> "<div class=\"error\"><strong>Fehler</strong><br />Kann PHP code nicht ausf&uuml;hren!!</div>",
	"mail_not_sent"                                 => "Die E-Mail konnte nicht versandt werden!",
	"sent"                                         	=> "Die E-Mail wurde gesendet!",
	"error"                                        	=> "Fehler",
	"whole"                                        	=> "Insgesamt",      // like Insgesamt x Stimmen
	"votes"                                        	=> "Stimme(n)",
	"vote"                                         	=> "abstimmen!",
	"kontakt_email"                                	=> "Ihre E-Mail-Adresse",
	"back"                                         	=> "Zur&uuml;ck",
	"new_messages"                                 	=> "Neue Nachrichten",
	"you_are_here"                                 	=> "Sie befinden sich hier",
	"stats"                                        	=> "Statistiken",
	"hits"                                         	=> "Aufrufe",
	"edit"                                         	=> "bearbeiten",
	"yes"                                          	=> "Ja",
	"no"                                           	=> "Nein",
	"code"                                         	=> "Code",
	/* ACCOUNT START */
	"signatur"                                     	=> "Signatur",
	"passwords_not_match"                          	=> "Die Passwörter stimmen nicht überein!",
	"password_cannot_be_empty"                      => "Das Passwort darf nicht leer sein.",
	"password_wrong"                               	=> "Das Passwort ist falsch!",
	"new_password"                                 	=> "Neues Passwort",
	"old_password"                                 	=> "Altes Passwort",
	/* ACCOUNT END REGISTRATION START */
	"register_code_ok"                             	=> "Der Registrierungscode ist richtig!",
	"repeat"                                       	=> "Wiederholen",
	"REPEAT_PASSWORD"								=> "Passwort wiederholen",
	"reg_code"                                     	=> "Registrierungscode",
	"register_code_wrong"                          	=> "Der Registrierungscode ist falsch!",
	"register_username_bad"                        	=> "Der Benutzername ist bereits vergeben!",
	"register_username_free"                       	=> "Der Benutzername ist frei!",
	"user_creat_bad"                               	=> "Fehler bei anlegen des Benutzers!",
	/* ADMIN AREA  */
	/* boxes START  */
	"bgimage"										=> "Hintergrundbild",
	"fullwidth"										=> "Volle Breite",
	"color"											=> "Farbe",
	"bgcolor"										=> "Hintergrundfarbe",
	"cssclass"										=> "CSS-Klasse",
	"width"											=> "Breite",
	"NEW_BOX"                                      => "Box erstellen",
	"EDIT_BOX"                                     => "Box bearbeiten",
	"DEL_BOX"                                      => "Box l&ouml;schen",
	"ansicht_page"                                 => "Sie sehen die Seite als ",
	"box_edited_ok"                                => "Die Box wurde erfolgreich bearbeitet.",
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
	"update_ok"                                    	=> "Die Aktion wurde erfolgreich ausgef&uuml;hrt!",   // if updated
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
	"delete"                                       	=> "Löschen",
	"group"											=> "Gruppe",
	/* USERGROUPS END USERS START */
	"rank"                                         	=> "Rang",
	"status"                                       	=> "Status",
	"ACCESS"										=> "Zugang",
	"edit_rights"                                  	=> "Rechte &auml;ndern",
	"user_del_confirm"                            	=> "Wollen Sie diesen Benutzer wirklich l&ouml;schen?",
	"user"											=> "Benutzer",
	/* USERS END MAINBAR START */
	"mainbar"                                      	=> "Hauptmen&uuml;",
	"all"                                          	=> "Alle",
	"open_in"                                      	=> "&Ouml;ffnen in",
	"same_window"                                  	=> "Selbem Fenster",
	"new_window"                                   	=> "Neuem Fenster",
	"url"                                          	=> "URL",
	/* MAINBAR END SITES START */
	"rename"                                       	=> "umbenennen",
	'site'											=> "Seite",
	"text"                                         	=> "Text",
	"MENUPOINT_TITLE"								=> "Navigations-Titel",
	"MENUPOINT_TITLE_INFO"							=> "Titel der Seite im Menü.",
	"WINDOW_TITLE"									=> "Titel des Browser-Fensters.",
	"WINDOW_TITLE_INFO"								=> "Leer lassen, um Standard-Titel zu verwenden",
	"OVERVIEW"                                     	=> "&Uuml;bersicht",
	"own_css"                                      	=> "Eigenes CSS (F&uuml;r Profis)",
	"less_rights"                                  	=> "Sie haben nicht die Berechtigung diese Aktion auszuführen!",
	"wrong_login"                                  	=> "<strong>Fehler</strong><br />Falscher Benutzername oder falsches Passwort!",
	"edit_profile"                                 	=> "Profil ändern",
	"show_profile"                                 	=> "Profil betrachten",
	"profile"										=> "Profil",
	"save"                                         	=> "Speichern",
	"cancel"                                       	=> "Abbrechen",
	"site_exists"                                   => "Eine Seite mit diesem Pfadnamen existiert schon!",
	"userstatus_admin"                             	=> "Benutzerstatus: <strong>Adminstrator</strong>",
	'lang'                                         	=> "Sprache",
	'switchlang'									=> "Sprache wechseln",
	"select_lang"									=> "Wählen Sie Ihre Sprache",
	'filemanager'                                  	=> "Dateimanager",
	"cache"											=> "Temporäre Daten",
	"del_cache"                                    	=> "Cache leeren",
	"cache_deleted"									=> "Der Cache wurde erfolgreich geleert.",
	"cache_del_info"								=> "Der Cache ist ein Zwischenspeicher, um die Geschwindigkeit zu optimieren.",
	"login"                                        	=> "Anmeldung" ,
	/* infos */
	"email_info"									=> "Mehrere Adressen mit Komma trennen",
	"email_correct_info"							=> "Die E-Mail-Adresse muss richtig sein und Ihnen gehören.",
	'dragndrop_info'								=> "Ziehen Sie die Elemente, um sie zu sortieren.",
	"no_item_selected"								=> "Kein Element ausgewählt",
	"noscript"                                      => "Bitte aktivieren Sie JavaScript, um diese Funktion zu nutzen!",
	"checked"                                       => "Ausgew&auml;hlte",
	/* edit tue 3.11.09 11:10 */
	"user_locked"									=> "Der Benutzeraccount wurde gesperrt.",
	"user_lock_q"									=> "Wollen Sie den Benutzeraccount sperren?",
	"user_unlocked"									=> "Der Benutzeraccount wurde entsperrt.",
	"user_unlock_q"									=> "Wollen Sie den Benutzeraccount entsperren?",
	"user_unlock_mail_attention"					=> "Die E-Mail-Adresse wurde noch nicht verifiziert.",
	'login_locked'                                  => "Der Benutzeraccount wurde gesperrt.",
	"login_not_unlocked_by_mail"					=> "Der Benutzeraccount wurde noch nicht via E-Mail aktiviert.",
	"login_not_unlocked"							=> "Der Benutzeraccount wurde noch nicht aktiviert.",
	"not_unlocked"									=> "Noch nicht aktiviert",
	"SITE_CURRENT_MAINTENANCE"                      => "Diese Seite befindet sich gerade im Wartungsmodus! Bitte kommen Sie sp&auml;ter wieder!",
	"SITE_MAINTENANCE"                              => "Wartungsmodus aktiv",
	"SITE_STATUS"                                   => "Status der Internetseite",
	"SITE_ACTIVE"                                   => "Normal",
	"phone"                                         => "Telefon",
	"settings_normal"                               => "Allgemeine Einstellungen",
	"web_description"                               => "Website-Beschreibung",
	"site_description"								=> "Beschreibung dieser Seite",
	"every"                                         => "Alle",
	"captcha"                                       => "Sicherheitscode",
	"captcha_wrong"                                 => "Der Sicherheitscode war falsch.",
	"captcha_reload"                                => "Neu Laden",
	'path'                                          => "Pfad",
	"box_successful_saved"							=> "Die Box wurde erfolgreich gespeichert!",
	"successful_saved"                              => "Die Informationen wurden erfolgreich gespeichert!",
	"saved"											=> "Gespeichert",
	"successful_deleted"							=> "Der Eintrag wurden erfolgreich gelöscht",
	"deleted"										=> "Gelöscht",
	"successful_published"							=> "Der Eintrag wurden erfolgreich gespeichert und veröffentlicht!",
	"published"										=> "Veröffentlicht",
	'sites_edit'									=> "Seiten verwalten und anlegen",
	'admin_smilies'									=> "Smilies verwalten",
	'admin_boxes'									=> "Inhalt der Boxen verwalten",
	'save_error'									=> "Es ist ein Fehler aufgetreten!",
	// lost password lanuages (lp_)
	'lp_key_not_right'								=> "Ihr Sicherheitsschl&uuml;ssel ist falsch oder abgelaufen.",
	'lost_password'									=> "Passwort vergessen",
	"set_password"                                  => "Passwort festlegen",
	'lp_edit_pass'									=> "Passwort &auml;ndern",
	'lp_email_or_user'								=> "E-Mail-Adresse oder Benutzername",
	'lp_submit'										=> "Absenden",
	'lp_update_ok'									=> "Das Passwort wurde erfolgreich ge&auml;ndert!",
	'lp_not_found'									=> "Es wurde keine E-Mail-Adresse in der Datenbank gefunden, die auf diese Angaben passt.",
	'lp_sent'										=> "Sie haben schon eine Passwort-Vergessen-Anfrage abgesetzt.",
	'hello'											=> "Hallo",
	'lp_text'										=> "Falls Sie ihr Passwort vergessen haben, klicken Sie bitte auf folgenden Link:",
	'lp_at'											=> " auf ",
	'lp_mfg'										=> "Mit freundlichen Gr&uuml;&szlig;en<br />Das Team",
	'lp_mail_sent'									=> "Die E-Mail wurde erfolgreich versandt." ,
	"lp_know_password"								=> "Sie wissen Ihr Passwort, sonst w&auml;ren Sie nicht eingeloggt.",
	"lp_deny"										=> "Klicken Sie auf folgenden Link, wenn Sie diese E-Mail nicht angefordert haben:",
	"lp_deny_okay"									=> "Verfahren erfolgreich abgebrochen!",
	"lp_code"										=> "Sicherheitscode",
	"lp_wrong_code"									=> "Leider war der Sicherheitscode falsch.",

	"user_needs_activation"							=> "Es hat sich ein neuer Nutzer angemeldet, der freigegeben werden muss.",
	"user_activate"									=> "Benutzer freigeben",
	"user_activate_confirm"							=> "Wollen Sie den Benutzer freigeben?",
	"user_activated_subject"						=> "Benutzer freigegeben",
	"user_activated"								=> "Herzlichen Glückwunsch! Ihr Benutzer wurde von einem Administrator freigegeben.",
	/*admin nitices*/
	'maintenance'									=> "Der Wartungsmodus ist aktiv.",
	'pagetree'										=> "Seitenbaum",
	'meta'											=> "Suchmaschinen",
	'end'											=> "Endung",
	'parentpage'									=> "&Uuml;bergeordnete Seite",
	'no_parentpage'									=> "Übergeordnete Seite",
	"subpage"										=> "Untergeordnete Seite",
	'boxes_page'									=> "Seite mit Boxsystem",
	"boxes"											=> "Boxsystem",
	'view_site'										=> "Startseite aufrufen",
	"view_page"										=> "%s aufrufen",
	'show_in_search'								=> "In der Suche anzeigen",
	'redirect'										=> "Weiterleitung",
	'legend'										=> "Legende",
	'nomainbar'										=> "Kein Men&uuml;punkt",
	'just_content_page'								=> "Standard-Seite",
	'PAGETYPE'										=> "Seitentyp",
	"box_linktype"									=> "Link in der Kopfzeile",
	"no_link"										=> "Kein Link",
	"BOXTYPE"										=> "Box-Typ",
	"CREATE_BOX"									=> "Box erstellen",
	"SAVE_BOX"										=> "Box speichern",
	'filename'										=> "Dateiname",
	'search'										=> "Suchen...",
	'close'											=> "Schliessen",
	'welcome_content_area'							=> "<h3>Willkommen</h3><p>Hier finden Sie den Inhalt ihrer Website. Dr&uuml;cken Sie auf eine Seite, um diese zu bearbeiten.</p>",
	'db_update'										=> "Datenbanktabellen validieren",
	'upload'										=> "Hochladen",
	"drag_file_here"								=> "Datei hierher ziehen",
	"drop_file_here"								=> "Loslassen um hochzuladen",
	'db_update_info'								=> "Goma generiert dadurch die Datenbank neu, das bedeutete nicht installiere Module werden installiert.",
	'online'										=> "Online",
	'offline'										=> "Offline",
	'lock'											=> "sperren",
	'unlock'										=> "entsperren",
	'locked'										=> 'Gesperrt',
	'not_locked'									=> "Nicht gesperrt",
	'edit_rights_groups'							=> "Wer darf diesen Datensatz bearbeiten?",
	"disabled"										=> "Deaktiviert",
	"active"										=> "Aktiviert",
	'page_not_active'								=> "Diese Seite ist nicht Aktiv!",
	'unload_lang'									=> "Soll die Seite wirklich verlassen werden?\n\nVorsicht: Die Änderungen wurden noch nicht gespeichert.\n\nDrücken Sie OK, um fortzusetzen oder Abbrechen,um auf der Aktuellen Seite zu bleiben.",
	"unload_lang_start"								=> "Sollen die Seite wirklich verlassen werden?\n\n",
	"unload_lang_end"								=> "\n\nDrücken Sie OK, um fortzusetzen oder Abbrechen,um auf der Aktuellen Seite zu bleiben.",
	"unload_not_saved"								=> "Vorsicht: Die Änderungen wurden noch nicht gespeichert.",
	"date_format"									=> "Datumsformat",
	"time_format"									=> "Zeitformat",
	"timezone"										=> "Zeitzone",
	"errorpage"										=> "Fehlerseite",
	"errorcode"										=> "Fehlercode",
	"general"										=> "Allgemein",
	"delete_confirm"								=> "Wollen Sie dieses Objekt wirklich löschen?",
	"pages_delete"									=> "Seiten l&ouml;schen",
	"pages_edit"									=> "Seiten bearbeiten",
	"pages_add"										=> "Seiten erstellen",
	"PAGE_CREATE"									=> "Neue Seite erstellen",
	"SUBPAGE_CREATE"								=> "Unterseite erstellen",
	"pages_publish"									=> "Seiten veröffentlichen",
	// default DataObjects
	"data_manage" 									=> "Andere Daten verwalten",
	// right-mangement
	"following_groups"								=> "Folgende Gruppen",
	"editors"										=> "Recht zur Bearbeitung",
	"publisher"										=> "Recht zur Veröffentlichung",
	"viewer_types"									=> "Betrachter-Rechte",
	"everybody"										=> "Jeder",
	"login_groups"									=> "Jeder, der sich anmelden kann",
	"following_rights"								=> "Folgende Rechte",
	"version_available"								=> "Es ist eine neuere Version verfügbar.",
	"upgrade_to_next"								=> "N&auml;chste Version herunterladen",
	"version_current"								=> "Sie haben die aktuellste Version!",
	"CREATE"										=> "Erstellen",
	"no_result"										=> "Keine Elemente",
	"delete_okay"									=> "Der Eintrag wurden erfolgreich entfernt!",

	/*mobile*/
	"classic_version"								=> "Klassische Ansicht",
	"mobile_version"								=> "Mobile Ansicht",

	/*check*/
	"checkall"										=> "Alle auswählen",
	"uncheckall"									=> "Alle abwählen",
	"edit_record"									=> "Eintrag bearbeiten",
	"add_record"									=> "Eintrag hinzufügen",
	"del_record"									=> "Eintrag löschen",
	"loading"										=> "Laden...",
	"loadMore"										=> "Mehr laden",
	"waiting"										=> "Warten...",
	"view_website"									=> "Seite aufrufen",
	"preview_website"								=> "Vorschau aufrufen",
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
	"registercode_info"								=> "Gäste werden vor der Registrierung nach diesem Code gefragt. Sie können dieses Feld leer lassen, um die Abfrage zu deaktivieren.",
	"gzip_info"										=> "G-Zip hilft Webseiten-Bandbreite zu sparen und beschleunigt die Ladezeit, jedoch benötigt der Server dann mehr Leistung.",
	"description_info"								=> "Die Beschreibung wird von Suchmaschinen benutzt, um den Inhalt Ihrer Seite für Nutzer klarer zu machen.",
	"register_enabled_info"							=> "Ermöglicht es Gästen sich zu registrieren",
	"register_require_email_info"					=> "Es wird bei der Registrierung eine Bestätigungs-E-Mail gesendet, um die E-Mail-Adresse zu validieren.",
	"register_require_acitvation"					=> "Ihr Account wurde erfolgreich registriert. Ein Administrator muss ihn nun nur noch aktivieren.",
	"sitestatus_info"								=> "Wenn sich die Seite im Wartungsmodus befindet, kann Sie nur von Mitgliedern angezeigt werden, die auch die Administration aufrufen können. Sie können diese Funktion z.B. verwenden, wenn Sie Wartungsarbeiten durchführen.",

	/* users and groups */
	"grouptype"										=> "Gruppentyp",

	/* global things */
	"add_data"										=> "Eintrag hinzuf&uuml;gen...",
	"download"										=> "Herunterladen",
	"submit"										=> "Absenden",

	/* contact-form */
	"contact_introduction"							=> "Sehr geehrter Seiten-Betreiber, <br /><br /> Die Folgenden Daten wurden &uuml;ber das Goma-Kontakt-Formular abgesendet:",
	"contact_greetings"								=> "Beste Gr&uuml;&szlig;e<br /><br />Goma-Auto-Messenger",
	// register e-mail
	"thanks_for_register"							=> "Vielen Dank, dass Sie sich auf unserer Seite registriert haben.",
	"account_activate"								=> "Klicken Sie bitte auf folgenden Link, um Ihren Account zu aktivieren:",
	"register_greetings"							=> "Beste Grüße<br /><br />Das Team",
	"register_ok_activate"							=> "Der Benutzer wurde erfolgreich registriert. Bitte sehen Sie in Ihrem E-Mail-Postfach nach, um den Benutzer zu aktivieren.",
	"register_resend"								=> "Die Aktivierungs-E-mail wurde erfolgreich erneut gesendet.",
	"register_resend_title"							=> "Aktivierungs-E-Mail erneut senden",
	"register_ok"                                  	=> "Der Benutzer wurde erfolgreich aktiviert! Sie k&ouml;nnen sich jetzt anmelden!",
	"register_wait_for_activation"					=> "Der Benutzer wurde erfolgreich registriert. Bitte warten Sie, bis er von einem Administrator freigegeben wurde. Sie werden per E-Mail benachrichtigt.",
	"register_not_found"							=> "Es wurde kein Benutzer mit solchen Angaben gefunden.",
	/* boxes */
	"login_myaccount"								=> "Login / Mein Account",
	/* notice */
	"notice"										=> "Hinweis",
	"notice_site_disabled"							=> "Diese Seite ist deaktiviert.",
	"delete_selected"								=> "Ausgewählte löschen",

	"menupoint_add"									=> "Im Men&uuml; anzeigen",


	"database"										=> "Datenbank",

	"login_required"								=> "Sie müssen sich einloggen, um diese Seite zu sehen.",

	/**
	 * versions
	 */
	"preview"										=> "Vorschau",
	"publish"										=> "Veröffentlichen",
	"published_site"								=> "Veröffentlichte Seite",
	"draft"											=> "Entwurf",
	"draft_save"									=> "Entwurf speichern",
	"save_publish"									=> "Speichern & Veröffentlichen",
	"draft_delete"									=> "Entwurf verwerfen",
	"current_state"									=> "Aktueller Zustand",
	"browse_versions"								=> "Versionen durchsuchen",
	"open_in_new_tab"								=> "Auf neuer Seite öffnen",
	"revert_changes"								=> "Änderungen verwerfen",
	"revert_changes_confirm"						=> "Wollen Sie die Änderungen wirklich verwerfen und zur letzten Veröffentlichung zurückkehren?",
	"reverted"										=> "Verworfen",
	"revert_changes_success"						=> "Die letzte Version wurde erfolgreich wiederhergestellt.",
	"unpublish"										=> "Veröffentlichung zurücknehmen",
	"unpublished"									=> "Zurückgenommen",
	"unpublish_success"								=> "Die Seite ist nun nicht mehr veröffentlicht.",
	"state_publish"									=> "Veröffentlicht",
	"state_state"									=> "Speicherpunkt",
	"state_autosave"								=> "Auto-Speicher-Punkt",
	"state_current"									=> "Diese Version",
	"version_by"									=> "von",
	"version_at"									=> "am",
	"versions_javascript"							=> "Bitte aktivieren Sie JavaScript, um diese Funktion zu nutzen.",
	"done"											=> "Fertig",
	"undo"											=> "Rückgängig",
	"restore"										=> "Wiederherstellen",
	"restore_confirm"								=> "Wollen Sie wirklich zu dieser Version zurückkehren?",
	"compare"										=> "Vergleichen",
	"no_versions"									=> "Keine Version vorhanden",
	"versions_timeline"								=> "Zeitleiste",
	"backups"										=> "Sicherungen",

	"DELETED_PAGE"									=> "Gel&ouml;schte Seite",
	"EDITED_PAGE"									=> "Geänderte Seite",

	"CHANGED"										=> "Geändert",
	"NEW"											=> "Neu",


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
	"updates"										=> "Aktualisierungen",
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
	"full_admin_permissions_info"					=> "Alle Rechte werden automatisch gewährt, auch wenn eines deaktiviert wird.",
	"signed"										=> "Signatur",
	"signed_true"									=> "Dieses Paket wurde vom Goma-Team geprüft und ist freigegeben!",
	"signed_false"									=> "Dieses Paket wurde nicht vom Goma-Team geprüft. Die Installation erfolgt auf eigene Gefahr!",
	"signed_false_ssl"								=> "Das Paket konnte nicht vom Goma-Team geprüft werden. Open-SSL ist nicht auf Ihrem Server installiert. Bitte kontaktieren Sie den Server-Administrator oder holen Sie sich Support auf der <a href=\"http://goma-cms.org\" target=\"_blank\">Goma-Seite</a>.",

	/* date for ago */

	"ago.seconds"			=> "vor %d Sekunden",
	"ago.minute"			=> "vor etwa einer Minute",
	"ago.minutes"			=> "vor %d Minuten",
	"ago.hour"				=> "vor etwa einer Stunde",
	"ago.hours"				=> "vor %d Stunden",
	"ago.day"				=> "vor etwa einem Tag",
	"ago.weekday"			=> "%s um %s",

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

	"flush_log"				=> "Alte Log-Dateien löschen",
	"flush_log_success"		=> "Alte Log-Dateien wurde erfolgreich gelöscht.",
	"flush_log_recommended"	=> "Es sind zu viele Log-Dateien auf dem Server. Es wird Ihnen empfohlen alte Log-Dateien zu löschen.",

	"tablefield_out_of"		=> "von",

	"history"				=> "Änderungs-Geschichte",

	"h_user_update"			=> '$user bearbeitete den Benutzer <a href="$userUrl">$euser</a>',
	"h_profile_update"		=> '$user bearbeitete das eigene Profil.',
	"h_user_remove"			=> '$user löschte den Benutzer $euser',
	"h_user_create"			=> '$user erstellte den Benutzer <a href="$userUrl">$euser</a>',
	"h_user_login"			=> '<a href="$userUrl">$euser</a> hat sich eingeloggt',
	"h_user_logout"			=> '<a href="$userUrl">$euser</a> hat sich ausgeloggt',
	"h_user_locked"			=> '$user sperrte den Benutzer <a href="$userUrl">$euser</a>',
	"h_user_unlocked"		=> '$user entsperrte den Benutzer <a href="$userUrl">$euser</a>',

	"h_settings"			=> '$user aktualisierte die <a href="$url">Einstellungen</a>',

	"h_group_update"		=> '$user bearbeitete die Gruppe <a href="$groupUrl">$group</a>',
	"h_group_remove"		=> '$user löschte die Gruppe $group',
	"h_group_create"		=> '$user erstellte die Gruppe <a href="$groupUrl">$group</a>',

	"h_all_events"			=> "Alle Datenquellen",
	"h_relevant"			=> "Wichtige",
	"h_all"					=> "Alle Ereignisse",
    "h_multiplechanges"     => 'Es gab mehrere Änderungen:',

	"older"					=> "Ältere Einträge",
	"newer"					=> "Neuere Einträge",

	"notification"			=> "Mitteilung",
	"alert_big_image"		=> "Achtung:\n\nIhre hochgeladene Bilddatei ist zu groß für eine Webseite. Sie wird möglicherweise sehr lange laden.\n\nBitte reduzieren Sie die Bildgröße mit einem Bildbearbeitungsprogramm und laden Sie die Datei erneut hoch.",

	"wrapper_page"			=> "Liste mit Unterseiten",

	"toggle_navigation"		=> "Navigation umschalten",
	"toggle_sidebar"		=> "Seitenleiste umschalten",
	"time_not_in_range"		=> "Die Zeit muss zwischen \$start und \$end liegen.",
	"date_not_in_range"		=> "Das Datum muss zwischen dem \$start und dem \$end liegen.",
	"no_valid_time"			=> "Die eingegebene Zeit ist nicht im richtigen Format.",
	"no_valid_date"			=> "Das angegebene Datum ist nicht im richtigen Format.",
	"mail_successful_sent"	=> "Die E-Mail wurde erfolgreich gesendet.",

	"error_disk_space"		=> "Es ist nicht genügend freier Speicherplatz auf dem Webserver verfügbar.",

	"help"					=> "Hilfe",
	"video"					=> "Video",
	"help_article"			=> "Hilfe-Artikel",

	"useSSL"				=> "SSL benutzen",
	"useSSL_info"			=> "Sie sollten diese Option nur aktivieren, wenn Ihr Server SSL unterstützt und Sie ein SSL-Zertifikat besitzen.",
	"useSSL_unsupported"	=> "Um SSL zu aktivieren, rufen Sie diese Seite via SSL auf: <a href='\$link'>Einstellungen SSL</a>",

	"push"					    => "Push",
	"p_app_key"				    => "App-Key",
	"p_app_secret"			    => "App-Secret",
	"p_app_id"				    => "App-ID",
	"push_info"				    => 'API-Informationen <a target="_blank" href="http://pusher.com?utm_source=badge"><img src="http://pusher.com/images/badges/pusher_badge_light_1.png"></a>.',
	"google-site-verification"  => "Google-Webmaster-Key",

	"no_value"					=> "Kein Wert",
	"favicon"					=> "Lesezeichen-Symbol",

	"user_defaultgroup"			=> "Standard-Gruppe neuer Nutzer",

	"on"						=> "An",
	"off"						=> "Aus",

	"DAYLY"						=> "täglich",
	"DAY"						=> "Tag",
	"WEEKLY"					=> "wöchentlich",
	"WEEK"						=> "Woche",
	"MONTHLY"					=> "monatlich",
	"MONTH"						=> "Monat",
	"YEARLY"					=> "jährlich",
	"YEAR"						=> "Jahr",

	"NO_EDITOR"					=> "Kein grafischer Editor",
	"EDITOR"					=> "Editor",
	"EDITOR_TOGGLE"             => "Editor an/aus",
	"JS_DISABLE_EDITOR"        	=> "Sie m&uuml;ssen Javascript aktivieren um den Editor zu benutzen!",

	"HELP.SHOW-MENU"			=> "Klicken, um das Menü anzuzeigen.",
	"HELP.ADD-NEW-PAGE"			=> "Neue Seite erstellen",
	"HELP.HISTORY"				=> "In der Änderungshistorie sehen Sie, was in letzter Zeit auf der Seite geändert wurde.",
	"HELP.HIERARCHY_OPEN"		=> "Klicken, um Baum auszuklappen.",
	"HELP.PAGES_SORT"			=> "Ziehen, um Seiten umzusortieren.",
	"HELP.HELP"					=> "Hilfe ein/ausblenden",

	"security"					=> "Sicherheit",
	"safe_mode"					=> "Sicherer Dateisystemmodus",
	"safe_mode_info"			=> "Wenn der sichere Modus aktiviert ist, behält Goma die Rechte an allen Dateien. Sie können dann nicht über FTP auf die Dateien schreibend zugreifen. Dies wird für Produktivsysteme empfohlen.",


	"mail"						=> "E-Mail",
	"use_smtp"					=> "SMTP benutzen",
	"smtp_host"					=> "Hostname",
	"smtp_auth"					=> "Server benötigt Authentifizierung",
	"smtp_user"					=> "Benutzername",
	"smtp_pwd"					=> "Passwort",
	"smtp_secure"				=> "Verschlüsselung",
	"smtp_port"					=> "Port",
	"smtp_connect_failure"		=> "Die SMTP-Verbindung konnte nicht überprüft werden.",
	"smtp_from"					=> "Absender-E-Mail (optional)",
	"optional"					=> "Optional",
	"validating_smtp"			=> "Validiere SMTP-Einstellungen...",
	"validating_submit"			=> "Es wird funktionieren, jetzt sichern.",
	"smtp_status"				=> "SMTP-Verbindung",

	"useCaptcha"				=> "Sicherheitscode verwenden",

	"groupAdmin"				=> "Gruppen-Admin",
	"group_notificationmail"	=> "E-Mail-Benachrichtigung bei Aktualisierung",
	"group_notificationmail_info"	=> "Sie werden benachrichtgt, wenn sich Nutzer in der Gruppe verändern. Mehrere E-Mail-Adressen durch Komma trennen.",
	"group_user_changed"		=> "Ein Nutzer in Ihrer Gruppe wurde verändert.",

	"export_to_csv"				=> "CSV herunterladen",

	"fromLabel"   => "Von",
	"toLabel"     => "Bis",
	"customLabel" => "Eigenes",

	"block_deleted"		=> "Block wurde gelöscht",

	"ADD_CONTENT"		=> "Inhalt hinzufügen",

	"this_version"		=> "Diese Version",
	"versions"			=> "Versionen",

	"move"				=> "Verschieben",
    "moveContentBlock"  => "Inhaltsblock verschieben",
    "removeContentBlock"=> "Inhaltsblock löschen",

	"imageTooBig"		=> "Die hochgeladene Datei ist zu groß.",
	"usercount"			=> "Benutzer",
	"leave_page_upload_confirm"	=> "Ein Upload läuft, soll dieser abgebrochen werden?",
	"apply"			=> "Anwenden",

	"uploads_manage"	=> "Dateien verwalten",

    "your_new_user_account" => "Es wurde für Sie ein Benutzer angelegt",
    "welcome_new_user" => "Herzlich Willkommen. Bitte legen Sie ihr Passwort fest, um fortzufahren.",


    "welcome_mail_subject" => "Sie wurden eingeladen, Ihren Account für \$serverName zu aktivieren."
);
