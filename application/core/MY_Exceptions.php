<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Exceptions extends CI_Exceptions {

	  public function show_error($heading, $message, $template = 'error_general', $status_code = 500)
	{
		$url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'unknown';

		$formattedMessage = "[$status_code] $heading at URL: $url\n" .
							(is_array($message) ? implode("\n", $message) : $message);

		$this->send_discord_error($formattedMessage);

		return parent::show_error($heading, $message, $template, $status_code);
	}


    public function show_exception($exception)
    {
        $this->send_discord_error("Uncaught Exception:\n" .
            $exception->getMessage() . "\nFile: " . $exception->getFile() . "\nLine: " . $exception->getLine());
        return parent::show_exception($exception);
    }

    public function show_php_error($severity, $message, $filepath, $line)
    {
        $this->send_discord_error("PHP Error:\nSeverity: $severity\nMessage: $message\nFile: $filepath\nLine: $line");
        return parent::show_php_error($severity, $message, $filepath, $line);
    }

   private function send_discord_error($text)
{
    $webhookUrl = 'https://discord.com/api/webhooks/1371835572557058079/x70tdoBoh57UOVj7MS9zDkiffXmI4l_U4C8iJoFHBGfakSrkMwKvmoGXwB114NRDJt5q';

    // Get client IP address
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Add IP address to the beginning of the message
    $fullMessage = "IP: $ip\n" . $text;

    $message = ['content' => "```" . substr($fullMessage, 0, 1900) . "```"]; // Discord limit: 2000 chars

    $headers = [
        'Content-Type: application/json',
        'User-Agent: MyCodeIgniterApp/1.0 (+https://yourdomain.com)'
    ];

    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 204) {
        log_message('error', 'Discord Webhook Failed: ' . $response);
    }
}

}
