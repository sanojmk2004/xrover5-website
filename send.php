<?php
// XRover 5 contact form handler. Creates a lead in the CRM (which emails a copy to the address below);
// if the CRM is unavailable, emails the form directly.
$TO   = 'info@xrover5.com';
$FROM = 'info@xrover5.com'; // must be a real mailbox on the domain this site is hosted on

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Invalid request.']);
  exit;
}

// Spam trap: real visitors never fill this hidden field
if (!empty($_POST['website'])) {
  echo json_encode(['ok' => true]);
  exit;
}

function clean($v, $max) {
  $v = trim((string)$v);
  $v = str_replace(["\r", "\0"], '', $v);
  return mb_substr($v, 0, $max);
}

$name    = clean($_POST['name'] ?? '', 100);
$mobile  = clean($_POST['mobile'] ?? '', 30);
$message = clean($_POST['message'] ?? '', 5000);
$name    = str_replace("\n", ' ', $name);
$mobile  = str_replace("\n", ' ', $mobile);

if ($name === '' || $message === '' || strlen(preg_replace('/\D/', '', $mobile)) < 10) {
  http_response_code(422);
  echo json_encode(['ok' => false, 'error' => 'Please fill in your name, mobile number and a message.']);
  exit;
}

// Keep a copy of every enquiry in a private file outside public_html, so no lead is lost
$leadFile = dirname(__DIR__) . '/xrover5-leads.csv';
$isNew = !file_exists($leadFile);
if ($fh = @fopen($leadFile, 'a')) {
  if ($isNew) { fputcsv($fh, ['Date', 'Name', 'Mobile', 'Message']); }
  fputcsv($fh, [date('Y-m-d H:i:s'), $name, $mobile, str_replace("\n", ' / ', $message)]);
  fclose($fh);
}

// 1) Send the enquiry into the CRM (crm.xrover5.com on this same hosting account).
//    The CRM assigns it to a salesperson and emails a copy to $TO through Private Email.
//    No password is needed here: the CRM code is loaded straight from its folder.
$crmFile = dirname(__DIR__) . '/crm.xrover5.com/inc/website_lead.php';
if (is_file($crmFile)) {
  try {
    require $crmFile;
    $r = crm_website_enquiry([
      'name' => $name, 'mobile' => $mobile, 'message' => $message, 'notify' => $TO,
      'ip' => $_SERVER['REMOTE_ADDR'] ?? '', 'page' => $_SERVER['HTTP_REFERER'] ?? '',
    ]);
    if (!empty($r['ok'])) {
      echo json_encode(['ok' => true]);
      exit;
    }
    if (!empty($r['error'])) {
      http_response_code(422);
      echo json_encode(['ok' => false, 'error' => $r['error']]);
      exit;
    }
  } catch (Throwable $e) {
    error_log('XRover 5 contact form: CRM not reachable, emailing instead: ' . $e->getMessage());
  }
}

// 2) Fallback if the CRM is unavailable: plain email from the hosting server.
$subject = 'XRover 5 website enquiry from ' . $name;
$body  = "New message from the XRover 5 website\n\n";
$body .= "Name:    $name\n";
$body .= "Mobile:  $mobile\n\n";
$body .= "Message:\n$message\n\n";
$body .= "Sent: " . date('d M Y, H:i') . " (server time)\n";

$headers  = "From: XRover 5 Website <$FROM>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
// Some hosts reject the -f option, so try with it first and then without it
$sent = @mail($TO, $encSubject, $body, $headers, '-f' . $FROM);
if (!$sent) {
  $sent = @mail($TO, $encSubject, $body, $headers);
}
if (!$sent) {
  error_log('XRover 5 contact form: mail() failed sending to ' . $TO);
}

if ($sent) {
  echo json_encode(['ok' => true]);
} else {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Your message could not be sent.']);
}
