<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
class SettingController extends Controller
{
 /** Section definitions: key => [label, group, type(text|email|number|password|select), options?, hint?] */
 public const SECTIONS=[
  'general'=>['title'=>'General','icon'=>'tune','fields'=>[
   'marketplace.name'=>['label'=>'Site name','group'=>'general','type'=>'text'],
   'marketplace.tagline'=>['label'=>'Tagline','group'=>'general','type'=>'text'],
   'marketplace.support_email'=>['label'=>'Support email','group'=>'general','type'=>'email'],
   'marketplace.currency'=>['label'=>'Currency (ISO)','group'=>'general','type'=>'text'],
  ]],
  'seo'=>['title'=>'SEO','icon'=>'travel_explore','fields'=>[
   'seo.meta_title'=>['label'=>'Default meta title','group'=>'seo','type'=>'text'],
   'seo.meta_description'=>['label'=>'Default meta description','group'=>'seo','type'=>'text'],
   'seo.meta_keywords'=>['label'=>'Meta keywords','group'=>'seo','type'=>'text'],
  ]],
  'smtp'=>['title'=>'SMTP / Mail','icon'=>'mail','fields'=>[
   'mail.host'=>['label'=>'SMTP host','group'=>'smtp','type'=>'text'],
   'mail.port'=>['label'=>'SMTP port','group'=>'smtp','type'=>'number'],
   'mail.username'=>['label'=>'Username','group'=>'smtp','type'=>'text'],
   'mail.password'=>['label'=>'Password','group'=>'smtp','type'=>'password','encrypted'=>true],
   'mail.encryption'=>['label'=>'Encryption','group'=>'smtp','type'=>'select','options'=>['','tls','ssl']],
   'mail.from_address'=>['label'=>'From address','group'=>'smtp','type'=>'email'],
   'mail.from_name'=>['label'=>'From name','group'=>'smtp','type'=>'text'],
  ]],
  'payments'=>['title'=>'Stripe','icon'=>'credit_card','fields'=>[
   'payments.stripe.enabled'=>['label'=>'Offer Stripe at checkout','group'=>'payments','type'=>'toggle','default'=>'1'],
   'payments.stripe_publishable_key'=>['label'=>'Publishable key','group'=>'payments','type'=>'text'],
   'payments.stripe_secret_key'=>['label'=>'Secret key','group'=>'payments','type'=>'password','encrypted'=>true],
   'payments.stripe_webhook_secret'=>['label'=>'Webhook signing secret','group'=>'payments','type'=>'password','encrypted'=>true],
  ]],
  'payments_paypal'=>['title'=>'PayPal','icon'=>'account_balance_wallet','fields'=>[
   'payments.paypal.enabled'=>['label'=>'Offer PayPal at checkout','group'=>'payments','type'=>'toggle','default'=>'0'],
   'payments.paypal_client_id'=>['label'=>'Client ID','group'=>'payments','type'=>'text'],
   'payments.paypal_secret'=>['label'=>'Client secret','group'=>'payments','type'=>'password','encrypted'=>true],
   'payments.paypal_mode'=>['label'=>'Mode','group'=>'payments','type'=>'select','options'=>['sandbox','live']],
   'payments.paypal_webhook_id'=>['label'=>'Webhook ID','group'=>'payments','type'=>'text','hint'=>'From the PayPal app webhook you point at /payments/paypal/webhook.'],
  ]],
  'payments_razorpay'=>['title'=>'Razorpay','icon'=>'currency_rupee','fields'=>[
   'payments.razorpay.enabled'=>['label'=>'Offer Razorpay at checkout (INR)','group'=>'payments','type'=>'toggle','default'=>'0'],
   'payments.razorpay_key'=>['label'=>'Key ID','group'=>'payments','type'=>'text'],
   'payments.razorpay_secret'=>['label'=>'Key secret','group'=>'payments','type'=>'password','encrypted'=>true],
   'payments.razorpay_webhook_secret'=>['label'=>'Webhook secret','group'=>'payments','type'=>'password','encrypted'=>true],
  ]],
  'payments_paystack'=>['title'=>'Paystack','icon'=>'payments','fields'=>[
   'payments.paystack.enabled'=>['label'=>'Offer Paystack at checkout','group'=>'payments','type'=>'toggle','default'=>'0'],
   'payments.paystack_public_key'=>['label'=>'Public key','group'=>'payments','type'=>'text'],
   'payments.paystack_secret'=>['label'=>'Secret key','group'=>'payments','type'=>'password','encrypted'=>true,'hint'=>'Also verifies inbound webhook signatures.'],
  ]],
  'payouts'=>['title'=>'Payout Methods','icon'=>'account_balance_wallet','fields'=>[
   'payouts.stripe.enabled'=>['label'=>'Stripe Connect payouts','group'=>'payouts','type'=>'toggle','default'=>'1','hint'=>'Hides the Connect Stripe button and the Stripe option on withdrawals when disabled.'],
   'payouts.paypal.enabled'=>['label'=>'PayPal payouts','group'=>'payouts','type'=>'toggle','default'=>'1'],
   'payouts.bank.enabled'=>['label'=>'Bank transfer payouts','group'=>'payouts','type'=>'toggle','default'=>'1'],
  ]],
  'payments_bank'=>['title'=>'Bank Transfer','icon'=>'account_balance','fields'=>[
   'payments.bank_transfer.enabled'=>['label'=>'Offer bank transfer at checkout','group'=>'payments','type'=>'toggle','default'=>'0'],
   'payments.bank_transfer_instructions'=>['label'=>'Transfer instructions shown to buyers','group'=>'payments','type'=>'textarea','hint'=>'Account name, IBAN/SWIFT and reference guidance. Orders stay unpaid until an admin confirms receipt.'],
  ]],
  'commerce'=>['title'=>'Commerce & Finance','icon'=>'payments','fields'=>[
   'commerce.default_commission_rate'=>['label'=>'Default commission %','group'=>'commerce','type'=>'decimal','min'=>0,'max'=>100],
   'commerce.affiliate_commission_rate'=>['label'=>'Affiliate share of commission %','group'=>'commerce','type'=>'decimal','min'=>0,'max'=>100],
   'commerce.withdrawal_fee_rate'=>['label'=>'Withdrawal fee %','group'=>'commerce','type'=>'decimal','min'=>0,'max'=>100],
   'commerce.minimum_withdrawal'=>['label'=>'Minimum withdrawal amount','group'=>'commerce','type'=>'decimal','min'=>1,'max'=>100000],
   'commerce.earnings_clearance_days'=>['label'=>'Earnings clearance (days)','group'=>'commerce','type'=>'number','min'=>0,'max'=>90],
   'commerce.tax_rate'=>['label'=>'Tax rate % (0 = no tax)','group'=>'commerce','type'=>'decimal','min'=>0,'max'=>50],
   'commerce.tax_label'=>['label'=>'Tax label shown to buyers','group'=>'commerce','type'=>'text','hint'=>'e.g. VAT, GST, Sales tax'],
  ]],
  'auth'=>['title'=>'Social Login','icon'=>'passkey','fields'=>[
   'auth.google.enabled'=>['label'=>'Sign in with Google','group'=>'auth','type'=>'toggle','default'=>'0'],
   'auth.google_client_id'=>['label'=>'Google OAuth client ID','group'=>'auth','type'=>'text','hint'=>'Authorized redirect URI: /auth/google/callback'],
   'auth.google_client_secret'=>['label'=>'Google OAuth client secret','group'=>'auth','type'=>'password','encrypted'=>true],
  ]],
  'integrations'=>['title'=>'Integrations','icon'=>'extension','fields'=>[
   'integrations.tawk_property_id'=>['label'=>'Tawk.to property ID','group'=>'integrations','type'=>'text','hint'=>'From your tawk.to dashboard URL; leave blank to disable live chat.'],
   'integrations.tawk_widget_id'=>['label'=>'Tawk.to widget ID','group'=>'integrations','type'=>'text','hint'=>'Usually "default".'],
  ]],
  'features'=>['title'=>'Features','icon'=>'toggle_on','fields'=>[
   'features.registration'=>['label'=>'Customer registration','group'=>'features','type'=>'toggle'],
   'features.seller_applications'=>['label'=>'Seller applications','group'=>'features','type'=>'toggle'],
   'features.reviews'=>['label'=>'Product reviews','group'=>'features','type'=>'toggle'],
   'features.comments'=>['label'=>'Product comments','group'=>'features','type'=>'toggle'],
   'features.blog'=>['label'=>'Public blog','group'=>'features','type'=>'toggle'],
   'features.cookie_consent'=>['label'=>'Cookie consent banner','group'=>'features','type'=>'toggle','default'=>'1'],
  ]],
  'social'=>['title'=>'Social Links','icon'=>'share','fields'=>[
   'social.website'=>['label'=>'Website URL','group'=>'social','type'=>'url'],
   'social.twitter'=>['label'=>'X (Twitter) URL','group'=>'social','type'=>'url'],
   'social.community'=>['label'=>'Community / Discord URL','group'=>'social','type'=>'url'],
  ]],
 ];
 public function index(): View
 {
  $settings=Setting::query()->when(request('q'),fn($query,$term)=>$query->where(fn($query)=>$query->where('key','like','%'.$term.'%')->orWhere('group','like','%'.$term.'%')))->orderBy('group')->orderBy('key')->get()->groupBy('group');
  $sections=collect(self::SECTIONS)->map(function(array $section){
   $section['fields']=collect($section['fields'])->map(function(array $field,string $key){
    $field['value']=($field['encrypted']??false)?'':(string)(Setting::get($key)??$field['default']??'');
    $field['configured']=($field['encrypted']??false)&&Setting::get($key)!==null;
    return $field;
   })->all();
   return $section;
  })->all();
  $logo=Setting::get('branding.logo_path');
  return view('admin.settings.index',compact('settings','sections','logo'));
 }
 public function updateSection(string $section): RedirectResponse
 {
  abort_unless(array_key_exists($section,self::SECTIONS),404);
  $fields=self::SECTIONS[$section]['fields'];
  $data=request()->validate(collect($fields)->mapWithKeys(fn(array $field,string $key)=>[str_replace('.','__',$key)=>match($field['type']){'email'=>['nullable','email','max:255'],'url'=>['nullable','url:http,https','max:500'],'number'=>['nullable','integer','between:'.($field['min']??1).','.($field['max']??65535)],'decimal'=>['nullable','numeric','between:'.($field['min']??0).','.($field['max']??1000000)],'toggle'=>['nullable','in:1,0'],'select'=>['nullable','in:'.implode(',',$field['options'])],'textarea'=>['nullable','string','max:5000'],default=>['nullable','string','max:2000']}])->all());
  $changed=[];
  foreach($fields as $key=>$field){
   $input=$data[str_replace('.','__',$key)]??null;
   if(($field['encrypted']??false)&&($input===null||$input===''))continue;
   Setting::put($key,$input===null?null:(string)$input,$field['group'],$field['encrypted']??false);
   $changed[]=$key;
  }
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'settings.section_updated','entity_type'=>Setting::class,'entity_id'=>null,'old_values'=>null,'new_values'=>['section'=>$section,'keys'=>$changed],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return back()->with('status',self::SECTIONS[$section]['title'].' settings saved.');
 }
 public function updateBranding(): RedirectResponse
 {
  request()->validate(['logo'=>['required','image','mimes:png,jpg,jpeg,webp,svg','max:2048']]);
  $path=request()->file('logo')->store('branding','public');
  $old=Setting::get('branding.logo_path');
  Setting::put('branding.logo_path',$path,'branding');
  if($old&&$old!==$path)\Illuminate\Support\Facades\Storage::disk('public')->delete($old);
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'settings.logo_updated','entity_type'=>Setting::class,'entity_id'=>null,'old_values'=>['logo'=>$old],'new_values'=>['logo'=>$path],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return back()->with('status','Logo updated.');
 }
 public function update(): RedirectResponse
 {
  $data=request()->validate(['group'=>['required','string','max:64'],'key'=>['required','string','max:128'],'value'=>['nullable','string','max:5000']]);
  $setting=Setting::firstOrNew(['key'=>$data['key']]);
  $old=$setting->exists?$setting->value:null;
  $setting->fill(['group'=>$data['group'],'value'=>$data['value']??''])->save();
  \Illuminate\Support\Facades\Cache::forget('setting:'.$setting->key);
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'setting.updated','entity_type'=>Setting::class,'entity_id'=>$setting->id,'old_values'=>['value'=>$old],'new_values'=>['value'=>$setting->value],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return back()->with('status','Setting saved.');
 }
}
