<?php

namespace Magrathea2;

/**
 * MagratheaEmail:
 * 	function that manages e-mail sends, building headers and sending e-mails
 */
class MagratheaMail{
	
	private $to;
	private $from;
	private $replyTo;
	private $htmlMessage;
	private $txtMessage;
	private $subject;
	private $error;

	private $smtpArr;
	
	function Email(){}
	
	/**
	 * if an error happened, it's this way you're gonna get it!
	 * @return 		array|null|object 		error on mail sending...
	 */
	function getError(){
		return $this->error;
	}

	/**
	 * if we want to use a different SMTP, it should be set here.
	 * The format of the SMTP is: 
	 * 		array(["smtp_host"] => "", ["smtp_port"] => "", ["smtp_username"] => "", ["smtp_password"] => "")
	 * @param 	array 		$smtp  	SMTP for use, in the format above
	 * @return 	MagratheaMail       itself
	 * @deprecated for smtp, you should use PEAR library
	 */
	function startSMTP($smtp): MagratheaMail{
		$this->smtpArr = $smtp;
		$this->smtpArr["auth"] = true;
		return $this;
	}

	/**
	 * Who's the guy(s) you have been contacting, huh?
	 * @param 		string 		$var 		destination e-mail
	 * @return 		MagratheaMail			itself
	 */
	function setTo($var): MagratheaMail {
		if( is_array($var) ){
			implode(", ", $var);
		}
		$this->to = $var;
		return $this;
	}
	/**
	 * Who should be replied?
	 * @param 	string 		$var 		e-mail 'reply-to'
	 * @return 	MagratheaMail			itself
	 */
	function setReplyTo($var): MagratheaMail {
		if( is_array($var) ){
			implode(", ", $var);
		}
		$this->replyTo = $var;
		return $this;
	}
	/**
	 * Who are you pretending to be?
	 * @param string $from  e-mail 'from'
	 * @param string $reply e-mail 'reply-to' (same as `setReplyTo`) *optional*
	 * @return 	MagratheaMail			itself
	 */	
	function setFrom($from, $reply=""): MagratheaMail {
		$this->from = $from;
		if( empty($replyTo) ){
			$this->replyTo = $from;
		} else {
			$this->replyTo = $reply;
		}
		return $this;
	}
	/**
	 * What the fuck are we talking about?
	 * @param 	string 		$subject 	message subject
	 * @return 	MagratheaMail				itself
	 */
	function setSubject($subject): MagratheaMail {
		$this->subject = $subject;
		return $this;
	}
	/**
	 * Ok, I'm in a hurry and don't want to set everything... 
	 * can you give me all of this in a single function?
	 * 	YES, I CAN!
	 * @param 	string 		$to      		destination e-mail
	 * @param 	string 		$from    		origin e-mail
	 * @param 	string 		$subject 		subject
	 * @return 	MagratheaMail					itself
	 */
	function setNewEmail($to, $from, $subject): MagratheaMail {
		$this->to = $to;
		$this->from = $from;
		$this->subject = $subject;
		return $this;
	}
	/**
	 * Set Message as HTML
	 * @param 	string 		$message 		HTML message
	 * @return 	MagratheaMail					itself
	 */
	function setHTMLMessage($message): MagratheaMail {
		$this->htmlMessage = nl2br($message);
		return $this;
	}
	/**
	 * Set Message as TXT
	 * @param 	string 		$message 		TXT message
	 * @return 	MagratheaMail					itself
	 */
	function setTXTMessage($message): MagratheaMail {
		$this->txtMessage = $message;
		return $this;
	}
	/**
	 * now we send it!
	 * @return 	bool 	true on e-mail sent, false if we have any error
	 */
	function send(): bool {

		if( empty($this->to) ){ $this->error="E-mail destination empty!"; return false; }
		if( empty($this->from) ){ $this->error="E-mail sender empty!"; return false; }
		if( empty($this->replyTo) ){ $this->replyTo = $this->from; }
		if( empty($this->subject) ){ $this->subject=""; }

		$content_type = empty($this->htmlMessage) ? "text/plain" : "text/html";

		$headers = 'MIME-Version: 1.0'."\r\n";
		$headers .= 'Content-Type: '.$content_type.'; charset=utf-8'."\r\n";
		$headers .= 'From: '.$this->from."\r\n";
		$headers .= 'Reply-To: '.$this->replyTo."\r\n";

		$message = empty($this->htmlMessage) ? $this->txtMessage : $this->htmlMessage;		

		if( mail($this->to,$this->subject,$message,$headers) ){
			return true;
		} else {
			Debugger::Instance()->Add("Error sending email to ".$this->to);
			$this->error = "Error sending e-mail!";
			return false;
		}
	}
}
