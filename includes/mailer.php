<?php
// SaaqiFolio - Email Service (PHPMailer SMTP)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

/**
 * Configure and return a ready PHPMailer instance.
 */
function get_mailer_instance(): PHPMailer {
    $mail = new PHPMailer(true);

    // Hostinger SMTP Configuration
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'info@saaqitech.com';
    $mail->Password   = 'Saydev@1234';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';

    // Sender details
    $mail->setFrom('info@saaqitech.com', 'SaaqiFolio');
    $mail->addReplyTo('info@saaqitech.com', 'SaaqiFolio Support');

    return $mail;
}

/**
 * Generate luxury dark HTML template for OTP verification.
 * Matches SaaqiFolio reference design.
 */
function get_otp_email_html(string $otp, string $userName = 'Creator'): string {
    // Extract individual 4 digits
    $digits = str_split(str_pad(substr($otp, 0, 4), 4, '0'));
    $d1 = htmlspecialchars($digits[0]);
    $d2 = htmlspecialchars($digits[1]);
    $d3 = htmlspecialchars($digits[2]);
    $d4 = htmlspecialchars($digits[3]);

    return '<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Your Password - SaaqiFolio</title>
  <!--[if mso]>
  <style type="text/css">
    body, table, td {font-family: Arial, sans-serif !important;}
  </style>
  <![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#080512;font-family:\'Space Grotesk\',\'Inter\',-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;-webkit-text-size-adjust:100%;color:#ffffff;">
  
  <!-- Outer Wrapper Table -->
  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#080512;background-image:radial-gradient(circle at 85% 15%, rgba(139,92,246,0.18) 0%, transparent 45%), radial-gradient(circle at 15% 85%, rgba(236,72,153,0.16) 0%, transparent 45%);margin:0;padding:40px 16px 50px 16px;">
    <tr>
      <td align="center" valign="top">
        
        <!-- Main Card Container -->
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:480px;background:#100b24;background:linear-gradient(180deg, #140d2d 0%, #0c081d 100%);border:1px solid rgba(139,92,246,0.28);border-radius:24px;box-shadow:0 24px 60px rgba(0,0,0,0.65), 0 0 45px rgba(139,92,246,0.15);overflow:hidden;">
          
          <!-- Card Body Padding -->
          <tr>
            <td style="padding:44px 36px 36px 36px;text-align:center;">
              
              <!-- Brand Header (Centered Icon + Title) -->
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" align="center" style="margin:0 auto 36px auto;">
                <tr>
                  <td valign="middle" style="padding-right:12px;">
                    <!-- Squircle Logo Icon -->
                    <div style="width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg, #a855f7 0%, #ec4899 50%, #f97316 100%);display:inline-block;text-align:center;line-height:46px;box-shadow:0 8px 20px rgba(168,85,247,0.4);border:1px solid rgba(255,255,255,0.22);">
                      <img src="https://saaqifolio.com/assets/favicon.png" width="28" height="28" alt="SaaqiFolio" style="display:inline-block;vertical-align:middle;border:0;outline:none;" />
                    </div>
                  </td>
                  <td valign="middle" align="left">
                    <div style="font-family:\'Space Grotesk\',-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:22px;font-weight:700;color:#ffffff;line-height:1.2;letter-spacing:-0.4px;">SaaqiFolio</div>
                    <div style="font-family:\'Inter\',-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12px;font-weight:500;color:#a78bfa;letter-spacing:0.2px;">Creator Portfolio</div>
                  </td>
                </tr>
              </table>

              <!-- Glowing Lock Badge Orb -->
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" align="center" style="margin:0 auto 28px auto;">
                <tr>
                  <td align="center">
                    <div style="width:88px;height:88px;border-radius:50%;background:radial-gradient(circle at 35% 30%, rgba(168,85,247,0.4) 0%, rgba(236,72,153,0.2) 60%, rgba(15,10,30,0.9) 100%);border:2px solid rgba(168,85,247,0.55);box-shadow:0 0 32px rgba(168,85,247,0.45), inset 0 0 20px rgba(147,51,234,0.3);text-align:center;line-height:84px;margin:0 auto;">
                      <!-- Lock Vector -->
                      <span style="font-size:38px;line-height:86px;display:inline-block;color:#ffffff;">&#128274;</span>
                    </div>
                  </td>
                </tr>
              </table>

              <!-- Heading -->
              <h1 style="margin:0 0 14px 0;font-family:\'Space Grotesk\',-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:26px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;line-height:1.25;">
                Reset Your Password
              </h1>

              <!-- Description -->
              <p style="margin:0 auto 28px auto;max-width:390px;font-family:\'Inter\',-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:14px;line-height:1.6;color:#b4b0cd;">
                We received a request to reset your password for your SaaqiFolio account. Please use the 4-digit code below to verify your identity and continue.
              </p>

              <!-- 4-Digit OTP Container -->
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" align="center" style="margin:0 auto 16px auto;background:#150e33;background:rgba(21,14,51,0.85);border:1px solid rgba(139,92,246,0.32);border-radius:18px;box-shadow:0 10px 26px rgba(0,0,0,0.4), inset 0 0 16px rgba(139,92,246,0.08);">
                <tr>
                  <td style="padding:16px 20px;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" align="center">
                      <tr>
                        <!-- Digit 1 -->
                        <td width="52" height="58" align="center" valign="middle" style="width:52px;height:58px;background:#1e1442;border:1.5px solid rgba(147,51,234,0.48);border-radius:12px;font-family:\'Space Grotesk\',\'JetBrains Mono\',monospace,sans-serif;font-size:30px;font-weight:700;color:#ffffff;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,0.35);">
                          ' . $d1 . '
                        </td>
                        <td width="10" style="width:10px;"></td>
                        <!-- Digit 2 -->
                        <td width="52" height="58" align="center" valign="middle" style="width:52px;height:58px;background:#1e1442;border:1.5px solid rgba(147,51,234,0.48);border-radius:12px;font-family:\'Space Grotesk\',\'JetBrains Mono\',monospace,sans-serif;font-size:30px;font-weight:700;color:#ffffff;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,0.35);">
                          ' . $d2 . '
                        </td>
                        <td width="10" style="width:10px;"></td>
                        <!-- Digit 3 -->
                        <td width="52" height="58" align="center" valign="middle" style="width:52px;height:58px;background:#1e1442;border:1.5px solid rgba(147,51,234,0.48);border-radius:12px;font-family:\'Space Grotesk\',\'JetBrains Mono\',monospace,sans-serif;font-size:30px;font-weight:700;color:#ffffff;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,0.35);">
                          ' . $d3 . '
                        </td>
                        <td width="10" style="width:10px;"></td>
                        <!-- Digit 4 -->
                        <td width="52" height="58" align="center" valign="middle" style="width:52px;height:58px;background:#1e1442;border:1.5px solid rgba(147,51,234,0.48);border-radius:12px;font-family:\'Space Grotesk\',\'JetBrains Mono\',monospace,sans-serif;font-size:30px;font-weight:700;color:#ffffff;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,0.35);">
                          ' . $d4 . '
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- Expiry Note -->
              <p style="margin:0 0 28px 0;font-family:\'Inter\',-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12.5px;color:#9d99b9;letter-spacing:0.1px;">
                This code expires in 5 minutes.
              </p>

              <!-- Divider Line -->
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 22px 0;">
                <tr>
                  <td style="border-top:1px solid rgba(139,92,246,0.22);font-size:0;line-height:0;height:1px;">&nbsp;</td>
                </tr>
              </table>

              <!-- Security Notice -->
              <p style="margin:0 0 26px 0;font-family:\'Inter\',-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12px;line-height:1.5;color:#7a7696;">
                If you didn\'t request this, you can safely ignore this email.
              </p>

              <!-- Footer Logo + Website Link -->
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" align="center" style="margin:0 auto 8px auto;">
                <tr>
                  <td align="center">
                    <div style="width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg, #a855f7 0%, #ec4899 50%, #f97316 100%);display:inline-block;text-align:center;line-height:30px;margin-bottom:8px;border:1px solid rgba(255,255,255,0.18);">
                      <img src="https://saaqifolio.com/assets/favicon.png" width="18" height="18" alt="SaaqiFolio" style="display:inline-block;vertical-align:middle;border:0;outline:none;" />
                    </div>
                  </td>
                </tr>
                <tr>
                  <td align="center">
                    <a href="https://saaqifolio.com" target="_blank" style="font-family:\'Inter\',-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12.5px;color:#a78bfa;text-decoration:none;font-weight:500;">
                      www.saaqifolio.com
                    </a>
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding-top:6px;">
                    <span style="font-family:\'Inter\',-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:11px;color:#5a5675;">
                      &copy; ' . date('Y') . ' SaaqiFolio. All rights reserved.
                    </span>
                  </td>
                </tr>
              </table>

            </td>
          </tr>
        </table>
        <!-- End Main Card Container -->

      </td>
    </tr>
  </table>

</body>
</html>';
}

/**
 * Send the 4-digit OTP email using PHPMailer SMTP.
 *
 * @param string $toEmail Recipient email address
 * @param string $otp 4-digit OTP code
 * @param string $userName Optional recipient name
 * @return array ['success' => bool, 'error' => string]
 */
function send_password_reset_otp(string $toEmail, string $otp, string $userName = 'Creator'): array {
    try {
        $mail = get_mailer_instance();
        $mail->addAddress($toEmail, $userName);
        $mail->Subject = 'Reset Your Password - SaaqiFolio (' . $otp . ')';

        $mail->isHTML(true);
        $mail->Body    = get_otp_email_html($otp, $userName);
        $mail->AltBody = "Reset Your Password - SaaqiFolio\n\n"
                       . "We received a request to reset your password for your SaaqiFolio account.\n"
                       . "Your 4-digit verification code is: {$otp}\n\n"
                       . "This code expires in 5 minutes.\n"
                       . "If you didn't request this, you can safely ignore this email.\n\n"
                       . "www.saaqifolio.com\n(c) " . date('Y') . " SaaqiFolio";

        $mail->send();
        return ['success' => true, 'error' => ''];
    } catch (Exception $e) {
        error_log("PHPMailer error sending to {$toEmail}: " . $mail->ErrorInfo);
        return ['success' => false, 'error' => $mail->ErrorInfo ?: $e->getMessage()];
    }
}
