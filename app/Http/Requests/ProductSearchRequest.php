<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class ProductSearchRequest extends FormRequest { public function authorize(): bool{return true;} public function rules(): array{return ['q'=>['nullable','string','max:100'],'category'=>['nullable','string','max:180'],'min_price'=>['nullable','numeric','min:0'],'max_price'=>['nullable','numeric','gte:min_price'],'sort'=>['nullable',Rule::in(['newest','price_low','price_high','popular','rated'])],'featured'=>['nullable','boolean']];} }