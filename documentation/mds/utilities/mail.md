# MagratheaMail — Email Functionality

**Files:** `src/MagratheaMail.php`, `src/MagratheaMailSMTP.php`
**Namespace:** `Magrathea2`

Email sending with support for both PHP's native `mail()` and SMTP via PHPMailer. Supports HTML and plain-text messages, multiple recipients, simulation mode, and fluent interface.

---

## MagratheaMail (Native mail())

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `$to` | `string` | Recipient(s) |
| `$from` | `string` | Sender address |
| `$replyTo` | `string` | Reply-to address |
| `$htmlMessage` | `string` | HTML email body |
| `$txtMessage` | `string` | Plain-text email body |
| `$subject` | `string` | Email subject |
| `$error` | `string` | Last error message |
| `$simulate` | `bool` | If true, don't actually send |

---

### Methods

#### `SetTo(string|array $var): MagratheaMail`
Set the recipient(s). Pass a string address or an array of addresses.

```php
$mail->SetTo("john@example.com");
$mail->SetTo(["a@example.com", "b@example.com"]);
```

#### `SetFrom(string $from, ?string $reply = null): MagratheaMail`
Set the sender address and optional reply-to.

```php
$mail->SetFrom("noreply@myapp.com", "support@myapp.com");
```

#### `SetReplyTo(string|array $var): MagratheaMail`
Set only the reply-to address.

```php
$mail->SetReplyTo("support@myapp.com");
```

#### `SetSubject(string $subject): MagratheaMail`

```php
$mail->SetSubject("Welcome to MyApp!");
```

#### `SetHTMLMessage(string $message): MagratheaMail`
Set the HTML version of the email body.

```php
$mail->SetHTMLMessage("<h1>Hello!</h1><p>Welcome aboard.</p>");
```

#### `SetTXTMessage(string $message): MagratheaMail`
Set the plain-text fallback version.

```php
$mail->SetTXTMessage("Hello! Welcome aboard.");
```

#### `SetNewEmail(string $to, string $from, string $subject): MagratheaMail`
Convenience method to set recipient, sender, and subject in one call.

```php
$mail->SetNewEmail("user@example.com", "noreply@myapp.com", "Your account");
```

#### `Simulate(): MagratheaMail`
Enable simulation mode — all other methods work normally but `Send()` does not actually deliver the email.

```php
$mail->Simulate(); // safe for dev/testing
```

#### `Validate(): bool`
Validates that required fields (to, from, subject, message) are set.

```php
if (!$mail->Validate()) {
    echo $mail->GetError();
}
```

#### `Send(): bool`
Send the email. Returns `true` on success, `false` on failure.

```php
$result = $mail->Send();
if (!$result) {
    Logger::Instance()->Log("Email failed: " . $mail->GetError());
}
```

#### `GetError(): string`
Returns the last error message from a failed send.

#### `GetInfo(): array`
Returns the current email data as an array (to, from, subject, etc.).

#### `__toString(): string`
Returns a readable summary of the email object.

---

### Basic Email Example

```php
use Magrathea2\MagratheaMail;

$mail = new MagratheaMail();
$mail->SetTo("customer@example.com")
     ->SetFrom("noreply@myshop.com")
     ->SetSubject("Order Confirmation #12345")
     ->SetHTMLMessage("
         <h2>Thank you for your order!</h2>
         <p>Your order #12345 has been received.</p>
     ")
     ->SetTXTMessage("Thank you for your order! Order #12345 has been received.")
     ->Send();
```

---

## MagratheaMailSMTP (SMTP via PHPMailer)

For production use, SMTP is more reliable than PHP's native `mail()`.

### Configuration (magrathea.conf)

```ini
[mail]
host     = smtp.gmail.com
port     = 587
username = myapp@gmail.com
password = $=SMTP_PASSWORD
from     = myapp@gmail.com
```

### Usage

```php
use Magrathea2\MagratheaMailSMTP;

$mail = new MagratheaMailSMTP();
$mail->SetTo("customer@example.com")
     ->SetSubject("Password Reset")
     ->SetHTMLMessage("<p>Click <a href='...'>here</a> to reset your password.</p>")
     ->Send();
```

`MagratheaMailSMTP` shares the same fluent interface as `MagratheaMail` but routes delivery through the configured SMTP server using PHPMailer.

---

### SMTP Array Configuration

You can also pass SMTP settings directly without relying on config:

```php
$mail = new MagratheaMailSMTP();
$mail->smtpArr = [
    "host"     => "smtp.sendgrid.net",
    "port"     => 587,
    "username" => "apikey",
    "password" => $_ENV["SENDGRID_KEY"],
    "from"     => "noreply@myapp.com",
];
$mail->SetTo("user@example.com")
     ->SetSubject("Hello!")
     ->SetHTMLMessage("<p>Hello from SMTP!</p>")
     ->Send();
```

---

## Development Pattern: Simulate in Non-Production

```php
use Magrathea2\MagratheaMail;
use Magrathea2\Config;

$mail = new MagratheaMail();

// Only actually send in production
if (Config::Instance()->GetEnvironment() !== "production") {
    $mail->Simulate();
}

$mail->SetTo($user->email)
     ->SetFrom("noreply@myapp.com")
     ->SetSubject("Welcome!")
     ->SetHTMLMessage(renderTemplate("welcome.html", ["user" => $user]))
     ->Send();
```

---

## Notes

- Both classes implement the same fluent interface, making them interchangeable.
- `Simulate()` is ideal for development — it logs the email data without actually sending.
- SMTP is recommended for production to avoid relying on server-level `sendmail` configuration.
- PHPMailer is bundled as a Composer dependency (`phpmailer/phpmailer ^6.9.1`).
