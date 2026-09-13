<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Address;use App\Models\Order;use App\Models\Product;use App\Models\Variant;use Illuminate\Support\Facades\DB;use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $cart = $request->session()->get('cart', []);
        if(empty($cart))return redirect()->route('cart.index')->with('error','Keranjang masih kosong.');
        $addresses=$request->user()->addresses()->orderByDesc('is_primary')->get();return view('front.checkout.index',compact('cart','addresses'));
    }

    public function store(Request $request): RedirectResponse
    {$data=$request->validate(['address_id'=>'required|exists:addresses,id','shipping_method'=>'required|in:regular,instant','payment_method'=>'required|in:bank_transfer,cod','notes'=>'nullable|string|max:500']);$address=Address::whereKey($data['address_id'])->where('user_id',$request->user()->id)->firstOrFail();$cart=$request->session()->get('cart',[]);if(empty($cart))return redirect()->route('cart.index');$order=DB::transaction(function()use($cart,$data,$address,$request){$subtotal=collect($cart)->sum(fn($i)=>$i['price']*$i['quantity']);$shipping=$data['shipping_method']==='instant'?35000:20000;$order=Order::create(['order_number'=>'SBS-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(),-6)),'user_id'=>$request->user()->id,'address_id'=>$address->id,'status'=>'pending','payment_status'=>'unpaid','payment_method'=>$data['payment_method'],'shipping_method'=>$data['shipping_method'],'subtotal'=>$subtotal,'shipping_cost'=>$shipping,'total'=>$subtotal+$shipping,'shipping_address'=>implode(', ',[$address->recipient_name,$address->phone,$address->address,$address->city,$address->province,$address->postal_code]),'notes'=>$data['notes']??null]);foreach($cart as $item){$model=$item['variant_id']?Variant::lockForUpdate()->find($item['variant_id']):Product::lockForUpdate()->find($item['product_id']);if(!$model||$model->stock<$item['quantity'])throw ValidationException::withMessages(['cart'=>'Stok '.$item['name'].' tidak mencukupi.']);$model->decrement('stock',$item['quantity']);$order->items()->create(['product_id'=>$item['product_id'],'variant_id'=>$item['variant_id'],'product_name'=>$item['name'].($item['variant_name']?' - '.$item['variant_name']:''),'sku'=>$item['sku'],'price'=>$item['price'],'quantity'=>$item['quantity'],'subtotal'=>$item['price']*$item['quantity']]);}$order->shipment()->create(['courier'=>$data['shipping_method']==='instant'?'GoSend':'RajaOngkir','service'=>$data['shipping_method'],'status'=>'waiting']);return $order;});$request->session()->forget('cart');return redirect()->route('orders.show',$order)->with('success','Pesanan berhasil dibuat.');}
}
