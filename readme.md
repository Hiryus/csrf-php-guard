# CSRF PHP Guard

CSRF PHP Guard is a library to implement protection against CSRF ([Cross Site Request Forgery](https://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF))) attacks.  
Multiple CSRF tokens are allowed.

## Warning

This library has **not been fully tested** and is more a proof of concept than production-proof.

Use at your own risk or make sure to do a detailed code review first.

## How to use it

* When generating a form in an HTML page, call `CsrfGuard::generate()`.
  This function can take the name of the form as optional parameter and returns a security token.
* Add this token in your form as an hidden parameter: `<input type="hidden" name="CSRF" value="<?= token ?>">`.
  NB: your can change the parameter name if you want, the library won't complain.
* When the user validate the form, and before any othen action (esp. database modification), read the submited token and check its validity by calling `CsrfGuard::check()`.
  This function takes the value of the submited token as first parameter, plus two optional parameters:
    * the form name (used to generate the token in step 1),
    * the validity timespan of the token (in milliseconds) - if ommited, tokens never timeout.  
* If the token is invalid, an exception will be thrown.

## Example

Generate the form:
```php
<?php
    const $token = CsrfGuard::generate("subscription_form");
?>

<form action="validate.php" method="post">
    <!-- label, inputs, buttons, etc... -->
    <input type="hidden" name="CSRF" value="<?= token ?>">
    <input type="submit" value="Submit">
</form>
```

Validate the form upon submition:
```php
<?php
    // Before any business action
    const $submitedToken = $_POST['CSRF'];
    try {
        CsrfGuard::check($submitedToken, "subscription_form");
    } catch (err) {
        // log error and show an error to the user
    }
    
    // Business logic
    // ...
?>
```

## How it works under the hood

When you generate a token, the library also sets a cookie containing:
* the token value - 32 random bytes (generated with `random_bytes()` PHP primitive),
* the form name (if any),
* the generation time (using `time()` PHP primitive).

This cookie is signed using sha256 HMAC (with `hash_hmac()` PHP primitive) to provent modification.
* The key is generated the same way as the token. It is stored in memory and shared between all users
* If you want, you can manage this key yourself by using `CsrfGuard::setKey()` before any token generation.
  Just make sure that this key remains **confidential**.

To verify a token, the library:
* reads the cookie and checks its signature,
* ensure that the form name and token correspond to the given values
* if needed, it also check for token timeout by using a rather simple equation: is cookie.time + timespawn < time() ?

NB: actually, the cookie may contain several structures (token + form name + time) encoded in JSON to allow several tokens to coexist at the same time.

## Why ?

When I looked for a CSRF protection library (for an internal project), I didn't find any which was both:
* Robust (I still find it astonishing how many flaws I found just by looking the code of several implementations...),
* Simple and user friendly.

That's why I decided to code this little helper library on my free time.

However, the project was never finished (business needs diappeared) and I never took the time to add proper tests and packages... *sad story* :(.
