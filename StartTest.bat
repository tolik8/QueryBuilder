path %PATH%;c:\ospanel\modules\php\PHP_7.1-x64;
path %PATH%;c:\ospanel\modules\php\PHP_7.1-x64\ext;
path %PATH%;c:\ospanel\modules\php\PHP_7.1-x64\pear;
path %PATH%;c:\ospanel\modules\php\PHP_7.1-x64\pear\bin;

cd /d d:\www\qb.loc

cls

call vendor\bin\PHPUnit tests

pause