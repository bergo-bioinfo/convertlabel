# RegExpValidate

This is a REDcap module for enabling regular expression validation on Textbox field.

It create two action tags:
* @REGEX 
* @REGEX_MSG

## Usage

You must a least use @REGEX tag, without double quotes in it. **Space must be replaced by \u0020**: 

If @REGEX_MSG isn't used, a default message will be displayed

Legal usage :
```bash
@REGEX=^[0-9]+
@REGEX="^[0-9]+"
@REGEX=^[0-9]\u0020+

@REGEX_MSG="ERROR"
@REGEX_MSG=ERROR
@REGEX_MSG=ERROR\u0020MESSAGE
```

Illegal usage :
```bash
@REGEX=^[0-9] +
@REGEX=^[0-9]" "+
@REGEX_MSG=ERROR MESSAGE
```


## Installation 

* Clone this repo in $ROOTREDCAP/modules/regexvalidate_vX.Y
* Enable it in Control Center
* Should be enabled in all project, or not. You'r choice.


Only tested on v9.3.1 of REDCap

