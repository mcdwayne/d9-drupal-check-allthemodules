<?php

		 define('SMTP_HOST', "server.brsequity.co.uk"); 
      define('SMTP_PORT', 465);
      define('SMTP_USERNAME', 'brad.notifications@bower.group');
      define('SMTP_PASSWORD', "~pATyfuB2viw");

		require_once('application/static/phpmailer/class.phpmailer.php');
        include_once("application/static/phpmailer/class.smtp.php");
        $mail = new \PHPMailer();
        $mail->IsSMTP();
        $mail->Host = SMTP_HOST; // SMTP server
        $mail->SMTPDebug = 0;  // enables SMTP debug information (for testing)
        // 1 = errors and messages
        // 2 = messages only
        $mail->SMTPAuth = true;    //	 enable SMTP authentication
        $mail->Port = SMTP_PORT;                    // set the SMTP port for the GMAIL server
        $mail->Username = SMTP_USERNAME; // SMTP account username
        $mail->Password = SMTP_PASSWORD;        // SMTP account password
        $mail->SMTPSecure = 'ssl';
		
		$Priority=3;
        $mail->Priority = $Priority;
        $from = SMTP_USERNAME;
        $mail->SetFrom($from, 'Bower Retirement Administration Database <brad.notifications@brad.bowerretirement.co.uk>');
		$subject = "test";
		$message = "test ";
        $mail->Subject = $subject;
        $mail->MsgHTML($message);
		$to = "devsttl2012@gmail.com";
        $address = $to;
        $mail->AddAddress($address);
        $email_data = $to . '<<<<->>>' . $subject . '<<<<->>>' . $message;
        if (!$mail->Send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
            $this->emailFailAlert($mail->ErrorInfo, $from, $priority, $email_data);
            //return false;
        } else {
            return true;
        }
		echo "<pre>";print_r($mail);
?>