<?php
// Locales offered in the switcher. `dir` drives the <html dir> attribute and RTL styling.
// Add a locale here and drop a matching lang/<code>/ directory to enable it — no code change.
// The marketplace runs English-only for now; with one locale the switcher is hidden.
// Translations for ar, es and fr remain in lang/ and can be re-enabled by listing them again.
return [
 'default'=>env('APP_LOCALE','en'),
 'available'=>[
  'en'=>['name'=>'English','native'=>'English','dir'=>'ltr'],
 ],
];
