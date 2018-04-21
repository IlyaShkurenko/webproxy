# Easy Auth

Hey there, ever wonder how to sign in by credintials in WHMCS or just check if user is authorized or not?

Ok, it's easily possible with this tiny plugin. It has two methods (scripts):
* do.php
* check.php

## do.php

Request schema is :
```php
[
   'username' => '',
   'password' => '',
   'url' => [
       'success' => '', // to be redirected on successful sign in
       'fail' => '' // to be redirected on sign in failure
   ],
   'text' => [
       'pending' => 'Signing In...', // user will see on signing in
       'error' => 'Credentials are incorrect!' // will be shown only on failure and fail url is not defined
   ]
]
```

## check.php

```
[
   'callbackUrl' => '' // what url it should redirect user request back
]
```

The callback response parameters:
```
[
  'id' => 1 // user id if authorized
]
```

## Hooks

* ClientAuthOnCheck - on auth check
* ClientAuthOnSignIn - on sign in