<?php
namespace Utils;

use \PHPMailer\PHPMailer\PHPMailer;
// require_once("qdmail.php");
// require_once("qdsmtp.php");

/**
 * メール送信
 */
class Mail
{
    public static function sendMail($from, $to, $subject, $body, &$files, $option)
    {
        mb_language('ja');
        mb_internal_encoding('ISO-2022-JP');

        $boundary = "__Boundary__" . uniqid(rand(), true) . "__";
        $mime = "application/octet-stream";

        $header = "";
        $header .= "From: $from\n";
        $header .= "MIME-Version: 1.0\n";
        $header .= "Content-Type: Multipart/Mixed; boundary=\"$boundary\"\n";
        $header .= "Content-Transfer-Encoding: 7bit";

        $mbody = "--$boundary\n";
        $mbody .= "Content-Type: text/plain; charset=ISO-2022-JP\n";
        $mbody .= "Content-Transfer-Encoding: 7bit\n";
        $mbody .= "\n";
        $mbody .= mb_convert_encoding($body, 'ISO-2022-JP', 'auto');
        $mbody .= "\n";

        for ($i = 0; $i < count($files); $i++) {

            $filename = mb_encode_mimeheader(mb_convert_encoding(basename($files[$i]), "ISO-2022-JP", 'auto'));

            $mbody .= "--$boundary\n";
            $mbody .= "Content-Type: $mime; name=\"$filename\"\n";
            $mbody .= "Content-Transfer-Encoding: base64\n";
            $mbody .= "Content-Disposition: attachment; filename=\"$filename\"\n";
            $mbody .= "\n";
            $mbody .= chunk_split(base64_encode(file_get_contents($files[$i])), 76, "\n");
            $mbody .= "\n";
        }

        $mbody .= "--$boundary--\n";

        return mail($to, mb_encode_mimeheader(mb_convert_encoding($subject, "ISO-2022-JP", 'auto')), $mbody, $header, $option);
    }

    /*
     * gmailからメールを送信する.
     * @param string $from      Gmailユーザ名
     * @param string password   Gmailパスワード
     * @param string $to        送信先(複数の際は配列)
     * @oaram string $subject   件名
     * @param string $message   本文
     * @param string $file_path 添付ファイルのパス
     * @param string $from_name 送信元名
     * @param string $to_name   送信先名($to が配列の場合はこちらも配列に)
     * @return bool true:成功   送信フラグ
     */
    public static function sendGmail($from, $password, $to, $subject, $message, $file_path = "", $from_name = "", $to_name = "")
    {
        $from_name = ($from_name == "") ? $from : $from_name;
        $to_name = ($to_name == "") ? $to : $to_name;
        

        // $param = array(
        //         'host' => 'ssl://smtp.gmail.com',
        //         'port' => '465',
        //         'from' => $from,
        //         'protocol' => 'SMTP_AUTH',
        //         'user' => $from,
        //         'pass' => $password,
        //         );
        
        // $mail = new \Qdmail();
        // $mail->smtp(true);
        // $mail->smtpServer($param);
        // $mail->errorDisplay(true);
        // $mail->smtpObject()->error_display = true;
        // $mail->from($from, $from_name);
        // $mail->to($to, $to_name);
        // $mail->subject($subject);
        // $mail->html($message);
        // if ($file_path != "") {
        //     $mail->attach($file_path);
        // }
        // $result = $mail->send();
        // echo $result . "\n";

        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->SMTPAuth = true;
        $mail->CharSet = 'utf-8';
        $mail->SMTPSecure = 'tls';
        $mail->Host = "smtp.gmail.com";
        $mail->Port = 587;
        $mail->IsHTML(true);
        $mail->Username = $from;
        $mail->Password = $password; 
        $mail->SetFrom($from);
        $mail->From     = $from;
        $mail->Subject = $subject;
        $mail->Body = $message;
        if (is_array($to)) {
            foreach ($to as $to_member) {
                $mail->AddAddress($to_member);
            }
        } else {
            $mail->AddAddress($to);
        }
        if ($file_path != "") {
            $mail->addAttachment($file_path);
        }
        return $mail->Send();
    }
    
}
