# MSG91 Real-Time OTP Login System (PHP + MySQL)

A simple **passwordless login/signup system** built with PHP, MySQL,
sessions, and the MSG91 OTP API.

Users enter an Indian mobile number, receive an OTP through MSG91,
verify it, and are automatically created or authenticated in the local
`users` table. Authenticated users are redirected to a protected
dashboard.

> **Security warning:** Never commit real MSG91 credentials, database
> passwords, API keys, or other secrets to GitHub. Configure credentials
> through environment variables in production and rotate any credential
> that has already been exposed.

------------------------------------------------------------------------

## Features

-   Passwordless login/signup using mobile OTP
-   Indian mobile number validation and normalization (`+91`)
-   Real-time OTP sending through MSG91
-   OTP verification through MSG91
-   OTP resend/retry support
-   30-second resend/request cooldown
-   Maximum 5 OTP verification attempts per session
-   Automatic user registration after successful OTP verification
-   Existing-user login using the verified phone number
-   PDO prepared statements for database queries
-   Session ID regeneration after successful authentication
-   Protected dashboard
-   Secure logout with session and session-cookie cleanup
-   HTTPS-aware secure session cookie configuration
-   MySQL database using `utf8mb4`
-   cURL timeout and SSL verification for MSG91 requests

------------------------------------------------------------------------

## Tech Stack

  Technology      Purpose
  --------------- ---------------------------------------
  PHP             Backend application logic
  MySQL           User storage
  PDO             Secure database access
  PHP Sessions    Authentication/session state
  PHP cURL        MSG91 API communication
  MSG91 OTP API   OTP send, verify, and resend
  HTML/CSS        Login, verification, and dashboard UI

------------------------------------------------------------------------

## Application Flow

``` text
User
  |
  v
index.php
  |
  | Enter mobile number
  v
send_otp.php
  |
  | Validate + normalize number
  | Call MSG91 Send OTP API
  v
verify.php
  |
  | Enter OTP
  | Call MSG91 Verify OTP API
  v
users table
  |
  | Create user if new
  | Mark user verified
  | Create authenticated session
  v
dashboard.php
  |
  v
logout.php
```

OTP resend flow:

``` text
verify.php
   |
   v
resend_otp.php
   |
   v
MSG91 Retry/Resend OTP API
   |
   v
verify.php
```

------------------------------------------------------------------------

## Project Structure

``` text
project/
├── config.php
├── db.php
├── functions.php
├── index.php
├── send_otp.php
├── verify.php
├── resend_otp.php
├── dashboard.php
├── logout.php
├── users.sql
├── README.md
└── assets/
    └── style.css
```

### File Responsibilities

  -----------------------------------------------------------------------
  File                                Responsibility
  ----------------------------------- -----------------------------------
  `config.php`                        Database, MSG91, OTP, and session
                                      configuration

  `db.php`                            Creates the PDO MySQL connection

  `functions.php`                     Phone validation and MSG91
                                      send/verify/resend helpers

  `index.php`                         Login/signup form

  `send_otp.php`                      Validates the phone number and
                                      requests an OTP

  `verify.php`                        Verifies OTP, creates/finds the
                                      user, and starts login session

  `resend_otp.php`                    Requests another OTP with a
                                      cooldown

  `dashboard.php`                     Protected page for authenticated
                                      users

  `logout.php`                        Clears and destroys the
                                      authenticated session

  `users.sql`                         Creates the database and `users`
                                      table

  `assets/style.css`                  UI stylesheet referenced by the PHP
                                      pages
  -----------------------------------------------------------------------

> The PHP pages reference `assets/style.css`. Make sure this file exists
> in the repository when publishing the complete project.

------------------------------------------------------------------------

## Requirements

Before running the project, install/configure:

-   PHP 7.4+ recommended (PHP 8.x preferred)
-   MySQL 5.7+ or MySQL 8.x
-   Apache/Nginx or XAMPP/WAMP/LAMP
-   PHP PDO MySQL extension
-   PHP cURL extension
-   An active MSG91 account
-   An approved/configured MSG91 OTP template
-   Required sender/DLT configuration for your deployment
-   SMS/OTP credits where applicable

You can check whether cURL is enabled with:

``` bash
php -m | grep curl
```

On Windows:

``` bat
php -m | findstr curl
```

------------------------------------------------------------------------

## Installation

### 1. Clone the repository

``` bash
git clone https://github.com/YOUR_USERNAME/YOUR_REPOSITORY.git
cd YOUR_REPOSITORY
```

Or copy the project into your local XAMPP directory, for example:

``` text
C:\xampp\htdocs\msg91-otp-login\
```

### 2. Create the database

Import `users.sql` using phpMyAdmin or MySQL CLI.

The SQL creates:

``` text
Database: otp_login
Table:    users
```

CLI example:

``` bash
mysql -u root -p < users.sql
```

The `users` table contains:

  Column          Description
  --------------- --------------------------------------
  `id`            Auto-increment user ID
  `phone`         Unique normalized phone number
  `is_verified`   Whether the number has been verified
  `created_at`    User creation timestamp
  `updated_at`    Last update timestamp

### 3. Configure the database

The current development defaults are:

``` php
define('DB_HOST', 'localhost');
define('DB_NAME', 'otp_login');
define('DB_USER', 'root');
define('DB_PASS', '');
```

These are suitable only when they match your local setup.

For production, do not keep database credentials directly in source
code. Prefer environment variables or hosting-level secret
configuration.

### 4. Configure MSG91 securely

The application expects these settings:

``` text
MSG91_AUTH_KEY
MSG91_TEMPLATE_ID
```

Set them as environment variables on the server rather than committing
credentials to Git.

Example concept:

``` text
MSG91_AUTH_KEY=your_new_auth_key
MSG91_TEMPLATE_ID=your_template_id
```

The application is configured for:

``` text
OTP length:  6 digits
OTP expiry:  5 minutes
```

### 5. Confirm MSG91 setup

Before testing, verify in MSG91 that:

-   the OTP service is active;
-   the template ID is correct;
-   the relevant template/sender setup is approved;
-   destination requirements are satisfied;
-   DLT requirements are complete where applicable;
-   your account has the required balance/credits;
-   any account/IP restrictions are correctly configured.

### 6. Start the application

With XAMPP:

1.  Start **Apache**.
2.  Start **MySQL**.
3.  Open the project in your browser.

Example:

``` text
http://localhost/msg91-otp-login/
```

------------------------------------------------------------------------

## How Authentication Works

### Sending OTP

`index.php` submits the mobile number to `send_otp.php`.

The backend:

1.  accepts a 10-digit Indian mobile number;
2.  removes non-digit characters;
3.  validates that the local number begins with `6`, `7`, `8`, or `9`;
4.  converts it to MSG91 format by prefixing country code `91`;
5.  applies a 30-second request cooldown;
6.  calls the MSG91 OTP endpoint;
7.  stores temporary OTP flow information in the PHP session;
8.  redirects the user to `verify.php`.

### Verifying OTP

`verify.php`:

1.  validates the OTP format;
2.  limits verification to 5 attempts in the current flow;
3.  sends the OTP and mobile number to MSG91 for verification;
4.  looks up the verified phone number in MySQL;
5.  inserts a new user if the phone number does not exist;
6.  marks an existing user as verified when applicable;
7.  regenerates the PHP session ID;
8.  stores the authenticated user ID and phone number in the session;
9.  redirects to `dashboard.php`.

### Resending OTP

`resend_otp.php` uses the MSG91 retry/resend endpoint.

A user must wait at least **30 seconds** before requesting another OTP.
After a successful resend, the local verification-attempt counter is
reset.

### Logout

`logout.php`:

-   clears the session array;
-   expires the session cookie when cookies are enabled;
-   destroys the PHP session;
-   redirects to `index.php`.

------------------------------------------------------------------------

## MSG91 API Integration

The application uses the MSG91 v5 OTP endpoints for:

``` text
Send OTP
Verify OTP
Retry/Resend OTP
```

The API helper uses PHP cURL with:

-   JSON response handling
-   connection timeout
-   request timeout
-   SSL peer verification
-   SSL host verification
-   HTTP status checks
-   MSG91 response-type validation

SMS delivery is not controlled entirely by the PHP application. A
successful HTTP/API request does not guarantee handset delivery if the
MSG91 account, template, sender, DLT setup, destination rules, or
credits are not correctly configured.

------------------------------------------------------------------------

## Security Already Implemented

The current code includes several useful protections:

-   PDO prepared statements
-   output escaping with `htmlspecialchars()`
-   strict PHP session mode
-   HTTP-only session cookies
-   secure cookies automatically enabled when running over HTTPS
-   `session_regenerate_id(true)` after successful OTP authentication
-   OTP attempt limiting
-   OTP request/resend cooldown
-   server-side phone validation
-   cURL SSL certificate verification
-   generic database connection error shown to users

------------------------------------------------------------------------

## Recommended Changes Before Production

### Critical

**1. Remove credentials from the repository**

Do not keep a real MSG91 Auth Key as a fallback value in `config.php`.

Use:

``` php
define('MSG91_AUTH_KEY', getenv('MSG91_AUTH_KEY') ?: '');
define('MSG91_TEMPLATE_ID', getenv('MSG91_TEMPLATE_ID') ?: '');
```

If a real key has already been committed or shared, **rotate/revoke it
in MSG91 and create a new key**.

**2. Move database credentials to environment variables**

Example:

``` php
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'otp_login');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');
```

**3. Force HTTPS in production**

OTP authentication and session cookies should be served only over HTTPS.

### Strongly Recommended

**4. Add CSRF protection**

Add CSRF tokens to:

-   Send OTP
-   Verify OTP
-   Resend OTP
-   Logout, preferably as POST

**5. Add server-side/IP rate limiting**

The current 30-second cooldown is session-based. A user can potentially
bypass it with a fresh session/browser.

Add persistent limits by:

-   IP address
-   phone number
-   IP + phone combination
-   rolling time window

This also helps protect your MSG91 credits from abuse.

**6. Avoid exposing raw provider errors to users**

Detailed MSG91 HTTP/provider errors are useful during development but
can reveal implementation information.

In production:

-   log detailed errors server-side;
-   show a generic message to the user.

**7. Add application logging**

Log important events without logging OTP values:

``` text
OTP request success/failure
OTP verification success/failure
rate-limit events
MSG91 HTTP failures
database failures
authentication events
```

Never store plain OTP codes in logs.

**8. Add security headers**

Recommended headers include:

``` text
Content-Security-Policy
X-Content-Type-Options: nosniff
Referrer-Policy
Permissions-Policy
```

Also consider clickjacking protection through CSP `frame-ancestors`.

**9. Harden session cookie settings**

Consider explicitly setting:

``` text
SameSite=Lax
Secure=true on production
HttpOnly=true
```

**10. Validate configuration at startup**

Fail safely when required production environment variables are missing
rather than silently using insecure defaults.

### Code/Architecture Improvements

**11. Separate configuration from application logic**

A production structure could use:

``` text
app/
config/
public/
database/
storage/logs/
```

Only the `public/` directory should be web-accessible.

**12. Use a centralized redirect/flash-message helper**

The current session flash-message logic works, but helper functions
would reduce duplication.

**13. Add automated tests**

Useful tests include:

-   valid/invalid Indian mobile numbers;
-   normalization from 10 to 12 digits;
-   resend cooldown;
-   OTP attempt limit;
-   authenticated route protection;
-   user creation after verification;
-   existing-user login.

**14. Add a `.gitignore`**

Recommended starting point:

``` gitignore
.env
.env.*
!.env.example

/vendor/
/.idea/
/.vscode/

*.log
storage/logs/*

.DS_Store
Thumbs.db
```

**15. Add `.env.example`**

Commit placeholders, never real values:

``` dotenv
DB_HOST=localhost
DB_NAME=otp_login
DB_USER=root
DB_PASS=

MSG91_AUTH_KEY=
MSG91_TEMPLATE_ID=
```

If you use `.env` files directly, add a library such as
`vlucas/phpdotenv` or configure equivalent environment variables through
Apache/Nginx/your hosting panel.

------------------------------------------------------------------------


## 💳 Real-Time OTP Pricing & Cost Notice

> **Important:** Real-time OTP delivery to an actual mobile number is generally a **paid service**. The PHP code and API integration itself can be used without a software fee, but the SMS/WhatsApp/Voice channel used to deliver the OTP is charged by the OTP/messaging provider. Free/demo/test modes should not be treated as free production OTP delivery.

### MSG91 SMS OTP Pricing — India to India

The following pricing was checked from MSG91's official India OTP pricing page in **September 2026**. Prices can change, so always verify the current rate on MSG91 before purchasing credits.

| OTP Volume | Approx. Rate per OTP | Base Cost | Approx. Cost incl. 18% GST |
| ---: | ---: | ---: | ---: |
| 5,000 | ₹0.25 | ₹1,250 | ₹1,475 |
| 15,000 | ₹0.22 | ₹3,300 | ₹3,894 |
| 27,000 | ₹0.20 | ₹5,400 | ₹6,372 |
| 53,685 | ₹0.19 | ₹10,200.15 | ₹12,036.18 |
| 1,05,264 | ₹0.19 | ₹20,000.16 | ₹23,600.19 |
| 4,02,632 | ₹0.19 | ₹76,500.08 | ₹90,270.09 |
| 8,55,556 | ₹0.18 | ₹1,54,000.08 | ₹1,81,720.09 |

MSG91 states that these listed prices are exclusive of **18% GST**. Higher-volume/custom business pricing may also be available from its sales team.

### What is free and what is paid?

| Item | Cost |
| --- | --- |
| This PHP OTP integration/code | Free to run yourself |
| MSG91 OTP Widget/SDK | MSG91 currently lists widget/SDK usage as free |
| Real SMS OTP delivery | **Paid per OTP/message** |
| WhatsApp OTP delivery | **Paid according to channel/rate card** |
| Voice OTP delivery | **Paid according to channel/rate card** |
| Email OTP | May have separate provider/channel pricing |
| Development/test mechanisms | Provider-specific; do not assume production delivery is free |

### Example Monthly SMS OTP Budget

At a hypothetical rate of **₹0.25 per OTP**, 1,000 OTP messages would cost approximately **₹250 before GST**, or **₹295 including 18% GST**. At 5,000 OTPs, the currently listed MSG91 tier is ₹1,250 before GST, approximately ₹1,475 including GST. Actual billing depends on your purchased volume, channel, destination, provider pricing, taxes, and account configuration.

### Important for Developers and Clients

When deploying this project for a real website or application, budget separately for OTP delivery. **Hosting and development charges do not include SMS OTP charges unless explicitly included in your agreement.** The MSG91 account should maintain sufficient credits/balance; otherwise OTP delivery can stop even when the PHP integration is working correctly.

For the latest pricing, use the official MSG91 pricing page: https://msg91.com/in/pricing/otp

---

## Troubleshooting

### OTP API says success but SMS is not received

Check:

-   MSG91 OTP logs;
-   mobile number format (`91XXXXXXXXXX`);
-   template ID;
-   sender/template approval;
-   DLT configuration;
-   destination restrictions;
-   account credits/balance;
-   account/IP restrictions.

The PHP application can successfully contact MSG91 while final SMS
delivery can still fail because of provider/account/operator
configuration.

### `PHP cURL extension is not enabled`

Enable cURL in your active `php.ini`, then restart Apache/PHP.

On XAMPP, make sure the cURL extension is enabled in the PHP
configuration used by Apache.

### Database connection failed

Verify:

-   MySQL is running;
-   `otp_login` exists;
-   `users.sql` was imported;
-   DB host/user/password are correct;
-   PDO MySQL is enabled.

### OTP verification keeps failing

Check:

-   the OTP has not expired;
-   the correct phone number/session is being used;
-   the user has not exceeded the local attempt limit;
-   MSG91 verification logs;
-   system/server time is correct.

### Resend does not work immediately

This is expected. The project enforces a **30-second cooldown** between
OTP requests.

------------------------------------------------------------------------

## Development Notes

The supplied PHP files have been syntax-checked with PHP's parser and
reported no syntax errors.

This does **not** guarantee that external services, credentials,
database permissions, SMS delivery, or hosting configuration are
correct. Test the complete flow in the intended environment.

------------------------------------------------------------------------

## Suggested GitHub Repository Description

> Passwordless PHP login/signup system using MSG91 real-time OTP, MySQL,
> PDO, secure sessions, OTP verification, resend support, and protected
> dashboard.

### Suggested GitHub Topics

``` text
php
mysql
msg91
otp
otp-authentication
passwordless-authentication
php-authentication
pdo
sms-otp
login-system
```

------------------------------------------------------------------------

## Production Checklist

-   [ ] Rotate any MSG91 key that was previously exposed
-   [ ] Remove all secrets from source code
-   [ ] Configure environment variables
-   [ ] Use a non-root production database user
-   [ ] Enable HTTPS
-   [ ] Set secure session cookies
-   [ ] Add CSRF protection
-   [ ] Add persistent rate limiting
-   [ ] Add safe application logging
-   [ ] Add security headers
-   [ ] Confirm MSG91 template/sender/DLT configuration
-   [ ] Confirm production SMS credits/balance
-   [ ] Test new-user registration
-   [ ] Test existing-user login
-   [ ] Test invalid OTP handling
-   [ ] Test OTP expiry
-   [ ] Test resend cooldown
-   [ ] Test logout/session destruction
-   [ ] Add `.gitignore`
-   [ ] Add `.env.example`
-   [ ] Add a LICENSE if you intend to open-source the project

------------------------------------------------------------------------

## License

No license was included with the supplied project files.

If this repository will be public/open source, add an appropriate
`LICENSE` file (for example, MIT) only if that license matches how you
want others to use the code.

------------------------------------------------------------------------

## Disclaimer

This project is a starter implementation for OTP-based authentication.
Before using it in production, perform a security review and configure
MSG91, PHP, MySQL, HTTPS, sessions, rate limiting, logging, and secret
management for your hosting environment.
