<?php

namespace Magrathea2;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MagratheaMailSMTP extends MagratheaMail {

	private $mail;

	/**
	 * Sets SMTP info
	 * @param string 	$host		SMTP host
	 * @param string 	$port		SMTP port
	 * @param string 	$user		SMTP username
	 * @param string 	$pass		SMTP password
	 * @return 	MagratheaMailSMTP		itself
	 */
	public function SetSMTP($host, $port, $user, $pass): MagratheaMailSMTP {
		$this->smtpArr = [
			"smtp_host" => $host,
			"smtp_port" => $port,
			"smtp_username" => $user,
			"smtp_password" => $pass,
			"auth" => true,
		];
		return $this;
	}

	/**
	 * Loads SMTP inside PHPMailer
	 */
	public function LoadSMTP(): PHPMailer {
		$this->mail->SMTPAuth = true;
		$this->mail->Host = $this->smtpArr["smtp_host"];
		$this->mail->Port = $this->smtpArr["smtp_port"];
		$this->mail->Username = $this->smtpArr["smtp_username"];
		$this->mail->Password = $this->smtpArr["smtp_password"];
    $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; //Enable implicit TLS encryption
		return $this->mail;
	}

	/**
	 * Check if there's any visible errors in the e-mail preparation
	 * @return 		bool		
	 */
	public function Validate(): bool {
		if(is_null($this->htmlMessage)) {
			if(is_null($this->txtMessage)) {
				$this->error = "Message is null";
				return false;
			} else {
				$this->htmlMessage = $this->txtMessage;
			}
		}
		$smtpItems = ["smtp_host", "smtp_port", "smtp_username", "smtp_password"];
		foreach($smtpItems as $i) {
			if(empty($this->smtpArr[$i])) {
				$this->error = "SMTP configuration error: [".$i."] is empty";
				return false;
			}
		}
		return parent::Validate();
	}

	/**
	 * now we send it!
	 * @return 	bool 	true on e-mail sent, false if we have any error
	 */
	public function Send(): bool {
		if(!$this->Validate()) return false;

		$this->mail = new PHPMailer(true);
		try {
			$this->LoadSMTP();
			$this->mail->setFrom($this->from);
			$this->mail->addReplyTo($this->replyTo);
			$this->mail->addAddress($this->to);
			$this->mail->isHTML(true);
			$this->mail->Subject = $this->subject;
			$this->mail->Body = $this->htmlMessage;
			$this->mail->AltBody = $this->txtMessage;

			if($this->simulate) $successMail = true;
			else $successMail = $this->mail->send();

			if ($successMail) {
				return true;
			} else {
				$this->error = $this->mail->ErrorInfo;
				Debugger::Instance()->Add("Error sending email to ".$this->to.": ".$this->error);
				return false;	
			}
		} catch(\Exception $ex) {
			throw $ex;
		}
	}

}