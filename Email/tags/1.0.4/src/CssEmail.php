<?php
namespace CompleteSolar\Email;

class CssEmail{
	public $headers = "";
	private $to;
	private $subject;
	private $boundaryString;
	private $textBody;
	private $htmlBody;
	private $result = Array();
	private $attachments = Array();
	public $body;
	public function __construct($to, $subject){
		date_default_timezone_set(Constants::DEFAULT_TIMEZONE);
		$this->to = $to;
		$this->subject = $subject;
		$this->boundaryString = md5(date('r', time()));
		$this->addHeader("Content-Type: multipart/mixed; boundary=\"PHP-mixed-".$this->boundaryString."\"");
		$this->body = $this->getBoundary("mixed");
		$this->body .= "Content-Type: multipart/alternative; boundary=\"PHP-alt-$this->boundaryString\"\r\n\r\n";
	}
	
	public function setFrom($from){
		$this->addHeader("From: $from");
		$this->setReplyTo($from);
	}
	
	public function setReplyTo($replyTo){
		$this->addHeader("Reply-To: $replyTo");
	}
	
	public function setContentType($contentType){
		$this->addHeader("Content-Type: $type");
	}
	
	public function setBCC($to){
		$this->addHeader("BCC: $to");
	}
	
	public function setCC($to){
		$this->addHeader("CC: $to");
	}
	
	public function addHeader($header){
		$this->headers .= "$header\r\n";
	}
	
	public function addAttachmentFromFile($filename){
		$content = file_get_contents($filename);
		$this->addAttachment($content, $filename);	
	}
	
	public function addAttachment($content, $filename){
		$this->attachments[$filename] = chunk_split(base64_encode($content));
	}
	
	public function setTextBody($text){
		$this->textBody = $text;
	}
	
	public function setHtmlBody($html){
		$this->htmlBody = $html;
	}
	
	public function send(){
		if (isset($this->textBody)){
			$this->body .= $this->getBoundary("alt");
			$this->body .= $this->getContentTypeText("text/plain");
			$this->body .= "Content-Transfer-Encoding: 7bit\r\n";
			$this->body .= "\r\n$this->textBody\r\n";
		}
		if (isset($this->htmlBody)){
			$this->body .= $this->getBoundary("alt");
			$this->body .= $this->getContentTypeText("text/html");
			$this->body .= $this->getContentTransferEncodingText("7bit");
			$this->body .= "\r\n$this->htmlBody\r\n\r\n";
		}
		$this->body .= "--PHP-alt-$this->boundaryString--\r\n\r\n";
		foreach($this->attachments as $filename => $content){
			$this->body .= $this->getBoundary("mixed");
			$this->body .= "Content-Type: application/zip; name=\"$filename\"\r\n";
			$this->body .= $this->getContentTransferEncodingText("base64");
			$this->body .= "Content-Disposition: attachment\r\n";
			$this->body .= "\r\n$content\r\n";
			$this->body .= $this->getBoundary("mixed")."--\r\n";
		} 

		$success = @mail($this->to, $this->subject, $this->body, $this->headers);
		if ($success){
			$this->result["status"]="success";
		} else {
			$this->result["status"]="failure";
			$this->result["errors"]=Array();
			$this->result["errors"][] = "Unspecified Error";
		}
		return $success;
	}
	
	public function getResult(){
		return $this->result;
	}
	
	private function getBoundary($tag){
		return "--PHP-$tag-$this->boundaryString\r\n";
	}
	
	private function getContentTypeText($type){
		return "Content-Type: $type; charset=\"iso-8859-1\"\r\n"; 
	}
	
	private function getContentTransferEncodingText($encoding){
		return "Content-Transfer-Encoding: $encoding\r\n";
	}
}

?>