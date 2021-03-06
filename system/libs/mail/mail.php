<?php defined("IN_GOMA") OR die();

/**
 * Basic Mail-Class for Goma.
 *
 * @package        Goma\Mail
 *
 * @author        Goma-Team
 * @license        GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class Mail
{
    /**
     * the sender.
     * @var    string
     */
    public $senderemail;

    /**
     * the name of the sender
     */
    public $sendername;

    /**
     * This var defines if the message is html
     * @var    bool
     **/
    public $html;

    /**
     * This var defines if the message schould be replied
     * @var    bool
     **/
    public $reply;

    /**
     * subject.
     */
    public $subject;

    /**
     * receiver-list.
     */
    public $address;

    /**
     * body.
     */
    public $body;

    /**
     * @var array
     */
    public $bcc = array();

    /**
     * @var array
     */
    public $cc = array();

    /**
     * @var null|array
     *
     * array of type filename => path
     */
    public $attachements = null;

    /**
     * sets $sender, $html, $reply
     *
     * @param string|null $from
     * @param bool $html
     * @param string|null $reply
     * @param string|null $sendername
     * @access    public
     */
    public function __construct($from = null, $html = true, $reply = null, $sendername = null)
    {
        if ($from === null) {
            $server = isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : "localhost";
            $from = "noreply@" . $server;
        }

        if (!empty($from)) {
            $this->senderemail = $from;
            if ($sendername === null) {
                $this->sendername = $from;
            } else {
                $this->sendername = $from;
            }
        }
        $this->html = $html;
        $this->reply = $reply;
        return true;
    }

    /**
     * sends a mail
     *
     * @param    string - adresse
     * @param    string - subject
     * @param    string - text
     * @return    bool
     *
     * @throws phpmailerException
     */
    public function send($address, $subject, $message)
    {
        $this->address = $address;
        $this->subject = $subject;
        $this->body = $message;

        $mail = $this->prepareMail();

        try {
            GlobalSessionManager::globalSession()->stopSession();
            $r = $mail->send();
        } finally {
            GlobalSessionManager::globalSession()->init();
        }

        return $r;
    }

    /**
     * builds PHP-Mailer-Object and calls two hooks.
     *
     * @hook    mail_prepareMailer($this, $mailer)    called before data like sender or receiver is set
     * @hook    mail_prepareSend($this, $mailer)    called immediatly before preperation is done.
     * @return PHPMailer
     * @throws phpmailerException
     */
    public function prepareMail()
    {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'utf-8';

        Core::CallHook("mail_prepareMailer", $this, $mail);

        $this->setFrom($this->senderemail, $this->reply, $mail);

        $mail->isHTML(!!$this->html);

        $mail->Subject = $this->subject;
        $mail->Body = $this->body;

        $this->addAddresses($this->address, $mail);
        $this->addBCC($mail);

        if($this->attachements) {
            foreach ($this->attachements as $filename => $path) {
                if(!file_exists($path)) {
                    throw new InvalidArgumentException("Attachements need format filename => path");
                }

                $mail->addAttachment($path, $filename ? $filename : "");
            }
        }

        Core::callHook("mail_prepareSend", $this, $mail);

        return $mail;
    }

    /**
     * sets from.
     *
     * @param string $from
     * @param PHPMailer $mailer
     * @throws phpmailerException
     */
    protected function setFrom($from, $reply, $mailer) {
        if (!empty($from)) {

            $addressInfo = self::parseSingleAddress($from);
            $mailer->setFrom($addressInfo[0], $addressInfo[1]);

            if (is_string($reply)) {
                $replyTo = self::parseSingleAddress($reply);
                $mailer->addReplyTo($replyTo[0], $replyTo[1]);
            } else if($reply) {
                $mailer->addReplyTo($addressInfo[0], $addressInfo[1]);
            }
        } else {
            $mailer->setFrom("noreply@" . $_SERVER["SERVER_NAME"], "noreply@" . $_SERVER["SERVER_NAME"]);
        }
    }

    /**
     * adds addresses to mail.
     *
     * @param string $address addresses
     * @param PHPMailer $mailer
     */
    protected function addAddresses($address, $mailer) {
        foreach (self::parseAddress($address) as $addAddr) {
            if (is_array($addAddr)) {
                $mailer->addAddress($addAddr[0], $addAddr[1]);
            }
        }
    }

    /**
     * adds bccs to mail.
     *
     * @param PHPMailer $mailer
     */
    protected function addBCC($mailer) {
        if($this->bcc) {
            foreach (self::parseAddress($this->bcc) as $addAddr) {
                if (is_array($addAddr)) {
                    $mailer->addBCC($addAddr[0], $addAddr[1]);
                }
            }
        }
    }


    /**
     * adds ccs to mail.
     *
     * @param PHPMailer $mailer
     */
    protected function addCC($mailer) {
        if($this->cc) {
            foreach (self::parseAddress($this->cc) as $addAddr) {
                if (is_array($addAddr)) {
                    $mailer->addCC($addAddr[0], $addAddr[1]);
                }
            }
        }
    }

    /**
     * parses address for PHP-Mailer.
     * @param array|string $address
     * @return array
     */
    public static function parseAddress($address)
    {
        if(is_array($address)) {
            if(ArrayLib::isAssocArray($address)) {
                return self::parseAddressArray($address);
            } else {
                $address = implode(",", $address);
            }
        }

        $parts = explode(",", $address);
        $mails = array();

        foreach ($parts as $part) {
            $mails[] = self::parseSingleAddress($part);
        }

        return $mails;
    }

    /**
     * parses an address array in form of address => title.
     *
     * @param array $addresses
     * @return array
     */
    protected static function parseAddressArray($addresses) {
        $mails = array();

        foreach ($addresses as $currentAddress => $name) {
            $mails[] = self::parseSingleAddress($currentAddress . "<".$name.">");
        }

        return $mails;
    }

    /**
     * parses name for email-address.
     */
    public static function parseSingleAddress($address)
    {
        if (strpos($address, "@") === false) {
            throw new InvalidArgumentException("Address $address is not valid.", ExceptionManager::EMAIL_INVALID);
        }

        if (preg_match('/^(.+)<(.*)\>$/', $address, $matches)) {
            return array(trim($matches[1]), trim($matches[2]));
        } else {
            return array($address, $address);
        }
    }

    /**
     * sends HTML with predefined template
     *
     * @param    string    addresse
     * @param    string    subject
     * @param    string    message
     * @return bool
     * @throws phpmailerException
     */
    public function sendHTML($adresse, $subject, $message)
    {
        $this->html = true;
        $template = new Template();
        $template->assign("subject", $subject);
        $template->assign("message", $message);
        $text = $template->display('mail.html');

        return $this->send($adresse, $subject, $text);
    }
}
