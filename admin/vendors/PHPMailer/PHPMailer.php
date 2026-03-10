<?php
namespace PHPMailer\PHPMailer;

class PHPMailer
{
    const ENCRYPTION_STARTTLS = 'tls';

    // Các thuộc tính cần thiết (để VSCode không báo lỗi)
    public $CharSet       = 'UTF-8';
    public $From          = 'no-reply@dentacare.vn';
    public $FromName      = 'DentaCare';
    public $Subject       = '';
    public $Body          = '';
    public $isHTML        = true;
    public $Host          = 'smtp-relay.brevo.com';
    public $Port          = 587;
    public $SMTPSecure    = self::ENCRYPTION_STARTTLS;
    public $SMTPAuth      = true;
    public $Username      = 'nguyenkieuphong05@gmail.com';
    public $Password      = 'xsmtpsib-9b8f08c8f1202d8d8d8d8d8d8d8d8d8d8d8d8d8d8d8d8d8d8d8d8d8d8d8d8d8d8d8d8d8d8d8d'; // KEY BREVO
    public $Timeout       = 30;
    public $ErrorInfo     = '';

    protected $to  = [];
    protected $cc  = [];
    protected $bcc = [];

    // Các method cần thiết
    public function isHTML($bool = true) { $this->isHTML = $bool; }
    public function addAddress($address, $name = '') { $this->to[] = [$address, $name]; }
    public function addCC($address, $name = '') { $this->cc[] = [$address, $name]; }
    public function setFrom($address, $name = '') { $this->From = $address; $this->FromName = $name; }

    public function send()
    {
        $all_recipients = array_merge($this->to, $this->cc, $this->bcc);
        if (empty($all_recipients)) { $this->ErrorInfo = 'No recipient'; return false; }

        $smtp = new SMTP();
        // Nếu dùng SSL (port 465), kết nối SSL ngay từ đầu
        $use_ssl = ($this->SMTPSecure === 'ssl');
        if (!$smtp->connect($this->Host, $this->Port, $use_ssl)) { $this->ErrorInfo = 'Connect failed'; return false; }

        $smtp->hello('localhost');
        // Chỉ dùng STARTTLS nếu không phải SSL (port 587 với TLS)
        if ($this->SMTPSecure === self::ENCRYPTION_STARTTLS || $this->SMTPSecure === 'tls') {
            $smtp->startTLS();
        }
        if ($this->SMTPAuth) $smtp->authenticate($this->Username, $this->Password);

        $smtp->mailFrom($this->From);
        foreach ($all_recipients as $r) $smtp->recipient($r[0]);

        $header  = "From: {$this->FromName} <{$this->From}>\r\n";
        $header .= "Subject: {$this->Subject}\r\n";
        $header .= "MIME-Version: 1.0\r\n";
        $header .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";

        $smtp->data($header . $this->Body);
        $smtp->quit();
        return true;
    }
}