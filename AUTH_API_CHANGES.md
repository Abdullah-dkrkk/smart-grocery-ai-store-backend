# Backend API Changes Required

## 1. Forgot Password — Send Actual Email

**File:** `app/Http/Controllers/AuthController.php` — `forgotPassword()` method

The current implementation only validates the email exists and returns a success message. It must be updated to:

- Generate a password reset token (store in `password_reset_tokens` table)
- Send an email to the user with a reset link pointing to the frontend:
  `http://localhost:3000/reset-password?token=RESET_TOKEN&email=USER_EMAIL`
- Use Laravel's built-in `Password::broker()->sendResetLink()` or a custom notification

### Suggested Implementation

```php
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Notifications\ResetPasswordNotification;

public function forgotPassword(Request $request)
{
    $request->validate(['email' => 'required|email|exists:users,email']);

    $user = User::where('email', $request->email)->first();
    $token = Str::random(60);

    // Store token in password_reset_tokens table
    DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $user->email],
        ['email' => $user->email, 'token' => Hash::make($token), 'created_at' => now()]
    );

    $user->notify(new ResetPasswordNotification($token, $user->email));

    return response()->json([
        'success' => true,
        'message' => 'Password reset link sent to your email.',
    ]);
}
```

### Create Notification

Create `app/Notifications/ResetPasswordNotification.php`:

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public string $token;
    public string $email;

    public function __construct(string $token, string $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('http://localhost:3000/reset-password?token=' . $this->token . '&email=' . urlencode($this->email));

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $url)
            ->line('This password reset link will expire in 60 minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}
```

---

## 2. Reset Password — Actually Update Password

**File:** `app/Http/Controllers/AuthController.php` — `resetPassword()` method

The current implementation validates inputs and returns success but does NOT update the password. It must be updated to:

- Verify the reset token exists in `password_reset_tokens` table
- Check that the token matches (using `Hash::check`)
- Check that the token hasn't expired (tokens older than 60 minutes)
- Update the user's password
- Delete the used token from `password_reset_tokens`

### Suggested Implementation

```php
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

public function resetPassword(Request $request)
{
    $request->validate([
        'token' => 'required|string',
        'email' => 'required|email|exists:users,email',
        'password' => 'required|string|min:8|confirmed',
    ]);

    $record = DB::table('password_reset_tokens')
        ->where('email', $request->email)
        ->first();

    if (!$record || !Hash::check($request->token, $record->token)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired reset token.',
        ], 400);
    }

    // Check token expiration (60 minutes)
    if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        return response()->json([
            'success' => false,
            'message' => 'Reset token has expired.',
        ], 400);
    }

    $user = User::where('email', $request->email)->first();
    $user->update(['password' => Hash::make($request->password)]);

    // Delete all tokens for this email
    DB::table('password_reset_tokens')->where('email', $request->email)->delete();

    return response()->json([
        'success' => true,
        'message' => 'Password has been reset successfully.',
    ]);
}
```

---

## 3. Enable Mail Configuration

**File:** `.env` — Configure mail driver

Add SMTP credentials (e.g., Mailtrap for development):

```
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@smartgrocery.com"
MAIL_FROM_NAME="Smart Grocery AI"
```

---

## 4. Ensure Route Names Match Frontend Expectations

**File:** `routes/api.php`

Confirm the following routes exist:

| Method | Endpoint | Frontend Call |
|--------|----------|---------------|
| POST | `/api/forgot-password` | `POST /forgot-password` with `{ email }` |
| POST | `/api/reset-password` | `POST /reset-password` with `{ email, token, password, password_confirmation }` |

The `NEXT_PUBLIC_API_URL` env var is set to `http://localhost:8000/api`, so the full URL for forgot-password requests becomes `http://localhost:8000/api/forgot-password`.

---

## 5. Register Response — Include `role` Field

**Already correct** — the `login` endpoint returns the `role` field in the user object. Ensure `register` also returns it (checked — it does).

The frontend `auth.ts` JWT callback stores `role` from the login response. No frontend changes needed for role support.
