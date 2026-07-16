<?php
// Locales offered in the switcher. `dir` drives the <html dir> attribute and RTL styling.
// Add a locale here and drop a matching lang/<code>/ directory to enable it — no code change.
return [
 'default'=>env('APP_LOCALE','en'),
 'available'=>[
  'en'=>['name'=>'English','native'=>'English','dir'=>'ltr'],
  'ar'=>['name'=>'Arabic','native'=>'العربية','dir'=>'rtl'],
  'es'=>['name'=>'Spanish','native'=>'Español','dir'=>'ltr'],
  'fr'=>['name'=>'French','native'=>'Français','dir'=>'ltr'],
 ],
];
