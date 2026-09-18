<?php

require_once 'send_email.php';

$to = 'bopaliyaharsh334@gmail.com';

$subject = 'CampusConnect Test Email';

$message = "
<html>
<body>

<h2>CampusConnect Email Test</h2>

<p>Hello!</p>

<p>This is a test email from the CampusConnect notification system.</p>

<p>If you received this email, the email notification system is working correctly.</p>

<br>

<p><strong>CampusConnect</strong></p>

</body>
</html>
";

if (sendEmail($to, $subject, $message)) {

    echo "<h2>Email sent successfully! ✅</h2>";

} else {

    echo "<h2>Email could not be sent. ❌</h2>";
}
?>