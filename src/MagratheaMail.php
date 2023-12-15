<?php

namespace Magrathea2;

/**
 * MagratheaEmail:
 * 	function that manages e-mail sends, building headers and sending e-mails
 */
class MagratheaMail {
	
	public $to;
	public $from;
	public $replyTo;
	public $htmlMessage;
	public $txtMessage;
	public $subject;
	public $error;

	public bool $simulate = false;

	public $smtpArr;
	
	/**
	 * if an error happened, it's this way you're gonna get it!
	 * @return 		string 		error on mail sending or validation error...
	 */
	function GetError(): string {
		return $this->error;
	}

	/**
	 * just simulate the error
	 * @return 	MagratheaMail		itself
	 */
	public function Simulate(): MagratheaMail {
		$this->simulate = true;
		return $this;
	}

	/**
	 * Who's the guy(s) you have been contacting, huh?
	 * @param 		string 		$var 		destination e-mail
	 * @return 		MagratheaMail			itself
	 */
	function SetTo($var): MagratheaMail {
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
	function SetReplyTo($var): MagratheaMail {
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
	function SetFrom($from, $reply=null): MagratheaMail {
		$this->from = $from;
		if( empty($replyTo) ){
			$this->SetReplyTo($from);
		} else {
			$this->SetReplyTo($reply);
		}
		return $this;
	}
	/**
	 * What the fuck are we talking about?
	 * @param 	string 		$subject 	message subject
	 * @return 	MagratheaMail				itself
	 */
	function SetSubject($subject): MagratheaMail {
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
	function SetNewEmail($to, $from, $subject): MagratheaMail {
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
	function SetHTMLMessage($message): MagratheaMail {
		$this->htmlMessage = nl2br($message);
		return $this;
	}
	/**
	 * Set Message as TXT
	 * @param 	string 		$message 		TXT message
	 * @return 	MagratheaMail					itself
	 */
	function SetTXTMessage($message): MagratheaMail {
		$this->txtMessage = $message;
		return $this;
	}

	/**
	 * Check if there's any visible errors in the e-mail preparation
	 * @return 		bool
	 */
	public function Validate(): bool {
		if( empty($this->to) ){ $this->error="E-mail destination empty!"; return false; }
		if( empty($this->from) ){ $this->error="E-mail sender empty!"; return false; }
		if( empty($this->subject) ){ $this->subject=""; return false; }
		if( empty($this->replyTo) ){ $this->replyTo = $this->from; }
		return true;
	}

	/**
	 * now we send it!
	 * @return 	bool 	true on e-mail sent, false if we have any error
	 */
	function Send(): bool {
		if(!$this->Validate()) return false;

		$content_type = empty($this->htmlMessage) ? "text/plain" : "text/html";

		$headers = 'MIME-Version: 1.0'."\r\n";
		$headers .= 'Content-Type: '.$content_type.'; charset=utf-8'."\r\n";
		$headers .= 'From: '.$this->from."\r\n";
		$headers .= 'Reply-To: '.$this->replyTo."\r\n";

		$message = empty($this->htmlMessage) ? $this->txtMessage : $this->htmlMessage;		

		if($this->simulate) $successMail = true;
		else $successMail = mail($this->to,$this->subject,$message,$headers);
		if( $successMail ){
			return true;
		} else {
			Debugger::Instance()->Add("Error sending email to ".$this->to);
			$this->error = "Error sending e-mail!";
			return false;
		}
	}

	/**
	 * returns info about the e-mail
	 * @return array	info
	 */
	public function GetInfo(): array {
		return [
			"from" => $this->from,
			"to" => $this->to,
			"subject" => $this->subject,
			"message" => $this->htmlMessage ?? $this->txtMessage,
		];
	}

	public function __toString() {
		$info = $this->GetInfo();
		return \Magrathea2\arrToStr($info);
	}
}
