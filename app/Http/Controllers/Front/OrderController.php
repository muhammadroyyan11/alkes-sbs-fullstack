<?php
namespace App\Http\Controllers\Front;use App\Http\Controllers\Controller;use App\Models\Order;use Illuminate\Http\Request;
class OrderController extends Controller{public function index(Request $r){return view('front.orders.index',['orders'=>$r->user()->orders()->with('shipment')->latest()->paginate(10)]);}public function show(Request $r,Order $order){abort_unless($order->user_id===$r->user()->id,403);$order->load('items','shipment');return view('front.orders.show',compact('order'));}}
