<?php
namespace PHPMailer\PHPMailer;

class SMTP
{
    private $conn;
    private $timeout = 10; // Giảm timeout xuống 10 giây

    public function connect($host, $port, $use_ssl = false) {
        // Nếu dùng SSL (port 465), kết nối SSL ngay từ đầu
        if ($use_ssl) {
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
            $this->conn = @stream_socket_client(
                "ssl://$host:$port",
                $errno,
                $errstr,
                $this->timeout,
                STREAM_CLIENT_CONNECT,
                $context
            );
        } else {
            $this->conn = @fsockopen($host, $port, $errno, $errstr, $this->timeout);
        }
        
        if (!$this->conn) return false;
        
        // Set timeout cho stream để tránh block vô hạn
        stream_set_timeout($this->conn, $this->timeout);
        stream_set_blocking($this->conn, true);
        
        // Đọc reply từ server (cả SSL và non-SSL đều cần)
        $reply = $this->getReply();
        if (empty($reply)) {
            // Nếu không đọc được reply, có thể server chưa sẵn sàng, thử lại một lần
            usleep(100000); // Đợi 0.1 giây
            $reply = $this->getReply();
            if (empty($reply)) return false;
        }
        
        return true;
    }
    public function hello($host) { return $this->cmd('EHLO ' . $host, 250); }
    public function startTLS() { 
        if (!$this->cmd('STARTTLS', 220)) return false;
        return @stream_socket_enable_crypto($this->conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT); 
    }
    public function authenticate($u, $p) {
        if (!$this->cmd('AUTH LOGIN', 334)) return false;
        if (!$this->cmd(base64_encode($u), 334)) return false;
        if (!$this->cmd(base64_encode($p), 235)) return false;
        return true;
    }
    public function mailFrom($from)  { return $this->cmd("MAIL FROM:<$from>", 250); }
    public function recipient($to)   { return $this->cmd("RCPT TO:<$to>", [250,251]); }
    public function data($data)      { 
        if (!$this->cmd('DATA', 354)) return false;
        @fputs($this->conn, $data . "\r\n."); 
        return $this->cmd('.', 250); 
    }
    public function quit()           { 
        if ($this->conn) {
            @$this->cmd('QUIT', 221); 
            @fclose($this->conn);
            $this->conn = null;
        }
    }

    private function cmd($cmd, $expect) {
        if ($cmd !== '.') {
            @fputs($this->conn, $cmd . "\r\n");
        }
        $reply = $this->getReply();
        if (empty($reply)) return false;
        $code = substr($reply, 0, 3);
        if (!in_array($code, (array)$expect)) return false;
        return true;
    }
    private function getReply() {
        $data = '';
        $max_attempts = 100; // Giới hạn số lần đọc
        $attempt = 0;
        
        while ($attempt < $max_attempts) {
            // Kiểm tra timeout
            $info = stream_get_meta_data($this->conn);
            if ($info['timed_out']) {
                return ''; // Timeout
            }
            
            $str = @fgets($this->conn, 512);
            if ($str === false) {
                // Không đọc được dữ liệu
                break;
            }
            
            $data .= $str;
            // SMTP response kết thúc bằng dòng có ký tự thứ 4 là space
            if (strlen($str) >= 4 && substr($str, 3, 1) === ' ') {
                break;
            }
            
            $attempt++;
        }
        
        return $data;
    }
    
    public function __destruct() {
        if ($this->conn) {
            @fclose($this->conn);
        }
    }
}