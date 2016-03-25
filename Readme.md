# CSS JS Minifier & Combiner
Simple & Fast CSS & Javascript minifier, built only one files and less than 50 kb (with this comment also :D)

The script is for combine and getting aso minifying assets Javascript and CSS

The script handle and minify css and javascript and combining multiple files

### URL & Path helper - just call the call and do it! tested on:

- Code Igniter 2 & 3

- Phalcon

- Slim 2 & 3

- FatFree PHP Framework

- Yii 2

and many more by our project need

### Tribute

inspire and use some code from :

> JShrink by - Robert Hafner

> @see  {[https://github.com/tedious/JShrink](https://github.com/tedious/JShrink)}

* Successfully about re-minifying of jquery minified version without issues

* High compression minify

* Fix for url() css rule ( with custom path or custom url)

* support allowing conditional comment that start /*! on javascript or you want it remove

* allow to show first comment to know what the css start for information


`Donation via @paypal are wellcome -> nawa@yahoo.com`

### Requirements

* PHP 5.3.2x or later

###NOTE
Not all javascript properly parsed !eg `bootstrap.js` that use non standar coding.
this combiner & minifer working properly when javascript write with coding standard eg:

```js
/**
 * condition
 */
if (conditions) { //brackets
 // statement
} // enclosing brackets

/**
 * Variables
 */
var variable = the_values; // endof semicolon
var variable = the_values,
    next_variable = the_values,
    next_variables; // end with semicolon
/**
 * Loop
 */
while(condition) { // brackets
  // statements
} // enclosing brackets

for (var count; count > lengthof_values; count++) {
 // statements
}

```

and maybe not working properly if you use multiple statements mixed ( variable, conditional etc. without standards of scripts)
eg :
```js
// 1 line conditional
if (condition) var variable=values

// 1 line loop
while(condition) // do then

// non bracket new line statements
if (condition)
    // do it here with 1 tabs / 4 spaces (as a conditional logic browser parsed)

// or multiple variables without semicolon
var variable=thevalue
var variable2=thevalue
var variable3
// and execute code in here

```
